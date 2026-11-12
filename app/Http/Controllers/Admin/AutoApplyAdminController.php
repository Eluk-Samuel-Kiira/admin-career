<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutoApplyRun;
use App\Models\AutoApplyRelay;
use App\Models\AutoApplyExternalDraft;
use App\Models\Job\JobPost;
use App\Models\SeekerProfile;
use App\Services\AutoApply\Channels\EmailChannel;
use App\Services\AutoApply\Channels\WhatsAppChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class AutoApplyAdminController extends Controller
{
    public function index(Request $request)
    {
        // Stats
        $stats = [
            'drafts_ready' => AutoApplyRelay::where('status', 'queued')->count() + AutoApplyExternalDraft::where('status', 'drafted')->count(),
            'sent_today'   => AutoApplyRelay::where('status', 'sent')->whereDate('sent_at', today())->count(),
            'failed_today' => AutoApplyRun::whereDate('created_at', today())->sum('failed_count'),
        ];

        // Load External Drafts
        $externalDrafts = AutoApplyExternalDraft::with(['user:id,name,email', 'jobPost:id,job_title,slug'])
            ->where('status', 'drafted')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'ext_page');

        // Load Email/WA Relays
        $relays = AutoApplyRelay::with(['user:id,name,email', 'jobPost:id,job_title,slug'])
            ->where('status', 'queued')
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'relay_page');

        return view('admin.auto-apply.index', compact('stats', 'externalDrafts', 'relays'));
    }

    // Download PDF for Admin
    public function downloadDraft(Request $request, $id)
    {
        $type = $request->query('type'); // 'cv' or 'cover'
        
        if ($request->is_external) {
            $draft = AutoApplyExternalDraft::findOrFail($id);
            $html = $type === 'cv' ? $draft->tailored_cv_html : $draft->cover_letter_html;
            $name = "Draft_{$type}_" . substr($draft->id, 0, 5);
        } else {
            $draft = AutoApplyRelay::findOrFail($id);
            $html = $type === 'cv' ? $draft->tailored_cv_html : $draft->cover_letter_html;
            $name = "Draft_{$type}_" . substr($draft->id, 0, 5);
        }

        if (!$html) abort(404, 'Document not generated.');

        $pdf = Pdf::loadHTML($html)->setPaper('a4');
        return $pdf->download("{$name}.pdf");
    }

    // Send Email/WA
    public function sendRelay(Request $request, $id)
    {
        $relay = AutoApplyRelay::with(['user', 'jobPost'])->findOrFail($id);
        
        // Call the actual channel (You must have these classes built already)
        try {
            $seeker = SeekerProfile::where('user_id', $relay->user_id)->first();
            $cv = ['html' => $relay->tailored_cv_html, 'path' => null]; // Path handles internally if needed
            
            if ($relay->channels_attempted[0] === 'email') {
                app(\App\Services\AutoApply\Channels\EmailChannel::class)->submit($seeker, $relay->jobPost, $cv, $relay->cover_letter_html, []);
            } elseif ($relay->channels_attempted[0] === 'whatsapp') {
                app(\App\Services\AutoApply\Channels\WhatsAppChannel::class)->submit($seeker, $relay->jobPost, $cv, $relay->cover_letter_html, []);
            }

            $relay->update(['status' => 'sent', 'sent_at' => now()]);
            return back()->with('success', 'Application sent successfully!');

        } catch (\Throwable $e) {
            Log::error("Admin Send Relay Failed: " . $e->getMessage());
            return back()->with('error', 'Failed to send: ' . $e->getMessage());
        }
    }

    // Mark External as Applied
    public function markExternalApplied($id)
    {
        $draft = AutoApplyExternalDraft::findOrFail($id);
        $draft->update(['status' => 'applied']);

        // Log into JobSeekerJob so the seeker sees it
        \App\Models\Job\JobSeekerJob::updateOrCreate(
            ['seeker_profile_id' => SeekerProfile::where('user_id', $draft->user_id)->value('id'), 'job_post_id' => $draft->job_post_id],
            ['is_applied' => true, 'applied_at' => now(), 'notes' => 'Auto-applied via external link']
        );

        return back()->with('success', 'Marked as applied. Seeker will see this in their dashboard.');
    }
}