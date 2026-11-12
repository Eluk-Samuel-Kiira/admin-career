<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendManualEmailJob;
use App\Mail\ManualEmail;
use App\Models\Deposit;
use App\Models\ManualEmailDispatch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\ApplicationEmail;
use App\Models\Job\Country;


class ManualEmailController extends Controller
{
    public function index()
    {
        if (!auth()->user()->can('send manual emails')) {
            abort(403);
        }

        return view('admin.manual-email.index');
    }

    // ═════════════════════════════════════════════════════════════
    // RECIPIENT SEARCH (bulk mode)
    // ═════════════════════════════════════════════════════════════

    public function searchUsers(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $audience = $request->get('audience', 'seekers');
        $q        = trim((string) $request->get('q', ''));
        $country  = $request->get('country');

        $query = User::query();

        if ($audience === 'seekers') {
            $query->whereHas('seekerProfile');
        } elseif ($audience === 'employers') {
            $query->whereHas('employerProfile');
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if ($country) {
            $query->where('country_code', strtoupper($country));
        }

        $users = $query->orderBy('name')->limit(50)->get(['id', 'name', 'email', 'country_code']);

        return response()->json(['success' => true, 'users' => $users]);
    }

    public function bulkRecipients(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $audience = $request->get('audience', 'seekers');
        $q        = trim((string) $request->get('q', ''));
        $country  = $request->get('country');

        $query = User::query()
            ->whereNotNull('email')
            ->where('is_active', true);

        if ($audience === 'seekers') {
            $query->whereHas('seekerProfile');
        } elseif ($audience === 'employers') {
            $query->whereHas('employerProfile');
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if ($country) {
            $query->where('country_code', strtoupper($country));
        }

        $users = $query->orderBy('name')->get(['id', 'name', 'email']);

        return response()->json([
            'success'    => true,
            'count'      => $users->count(),
            'recipients' => $users->map(fn ($u) => [
                'email'   => $u->email,
                'name'    => $u->name,
                'user_id' => $u->id,
            ]),
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // ATTACHMENT UPLOAD (bulk mode)
    // ═════════════════════════════════════════════════════════════

    public function upload(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $request->validate([
            'files.*' => 'file|max:10240',
        ]);

        $uploaded = [];

        foreach ($request->file('files', []) as $file) {
            $path = $file->store('manual-email/' . date('Y/m'), 'public');
            $uploaded[] = [
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'size'          => $file->getSize(),
                'mime_type'     => $file->getMimeType(),
            ];
        }

        return response()->json(['success' => true, 'files' => $uploaded]);
    }

    // ═════════════════════════════════════════════════════════════
    // SEND — bulk mode
    // ═════════════════════════════════════════════════════════════

    public function send(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'purpose'     => 'required|in:product_advert,job_application_help,hiring_appointment,manual_outreach,newsletter',
            'audience'    => 'required|in:seekers,employers,custom',
            'subject'     => 'required|string|max:255',
            'body'        => 'required|string',
            'recipients'  => 'required|array|min:1',
            'recipients.*.email'   => 'required|email',
            'recipients.*.name'    => 'nullable|string',
            'recipients.*.user_id' => 'nullable|integer',
            'attachments'   => 'nullable|array',
            'attachments.*' => 'string',
            'cc'          => 'nullable|string',
            'bcc'         => 'nullable|string',
            'deposit_amount_cents' => 'nullable|integer|min:0',
            'deposit_method'       => 'nullable|in:cash,bank,mobile_money,card',
            'deposit_notes'        => 'nullable|string|max:500',
        ]);

        $depositId = null;
        if (!empty($validated['deposit_amount_cents'])) {
            $deposit = Deposit::create([
                'deposit_ref'         => (string) Str::uuid(),
                'currency_id'         => \App\Models\Currency::getByCode('UGX')?->id,
                'amount'              => (int) $validated['deposit_amount_cents'],
                'fee'                 => 0,
                'net_amount'          => (int) $validated['deposit_amount_cents'],
                'deposit_method'      => $validated['deposit_method'] ?? 'cash',
                'purpose_type'        => 'manual_email_' . $validated['purpose'],
                'purpose_description' => $validated['subject'],
                'status'              => 'completed',
                'deposit_date'        => now(),
                'cleared_date'        => now(),
                'depositor_id'        => auth()->id(),
                'paid_by_user_id'     => auth()->id(),
                'depositor_name'      => auth()->user()->name,
                'depositor_email'     => auth()->user()->email,
                'country_code'        => auth()->user()->country_code ?? 'UG',
                'created_by'          => auth()->id(),
                'notes'               => $validated['deposit_notes'] ?? null,
            ]);
            $depositId = $deposit->id;
        }

        $dispatch = ManualEmailDispatch::create([
            'sent_by'         => auth()->id(),
            'purpose'         => $validated['purpose'],
            'audience'        => $validated['audience'],
            'subject'         => $validated['subject'],
            'body'            => $validated['body'],
            'attachments'     => $validated['attachments'] ?? [],
            'cc'              => $validated['cc'] ?? null,
            'bcc'             => $validated['bcc'] ?? null,
            'recipients'      => $validated['recipients'],
            'recipient_count' => count($validated['recipients']),
            'status'          => 'queued',
            'deposit_id'      => $depositId,
        ]);

        SendManualEmailJob::dispatch($dispatch->id);

        return response()->json([
            'success'     => true,
            'message'     => 'Email queued for ' . $dispatch->recipient_count . ' recipient(s).',
            'dispatch_id' => $dispatch->id,
        ]);
    }

    // ═════════════════════════════════════════════════════════════
    // SEND — SINGLE APPLICATION (job_application_help)
    // ═════════════════════════════════════════════════════════════


    /**
     * POST /admin/manual-email/send-application
     *
     * Gmail-style send on behalf of a seeker:
     *   to, cc, bcc, subject, body (HTML), attachments[] (already uploaded via /upload)
     * The applicant is always CC'd and set as Reply-To.
     */
    public function sendApplication(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'to'                      => 'required|string',
            'cc'                      => 'nullable|string',
            'bcc'                     => 'nullable|string',
            'subject'                 => 'required|string|max:255',
            'body'                    => 'required|string',
            'seeker_name'             => 'required|string|max:255',
            'seeker_email'            => 'nullable|email|max:255',
            'job_title'               => 'nullable|string|max:255',
            'company_name'            => 'nullable|string|max:255',
            'employer_name'           => 'nullable|string|max:255',
            'country_code'            => 'nullable|string|size:2',
            'attachments'             => 'required|array|min:1|max:10',
            'attachments.*.path'          => 'required|string',
            'attachments.*.original_name' => 'required|string|max:255',
            'deposit_amount_cents'    => 'nullable|integer|min:0',
            'deposit_method'          => 'nullable|in:cash,mobile_money,bank,card',
            'deposit_notes'           => 'nullable|string|max:500',
        ]);

        // ── Parse + validate address lists ──
        $to  = $this->parseEmails($validated['to'], $badTo);
        $cc  = $this->parseEmails($validated['cc']  ?? '', $badCc);
        $bcc = $this->parseEmails($validated['bcc'] ?? '', $badBcc);

        $bad = array_merge($badTo, $badCc, $badBcc);
        if ($bad) {
            return response()->json(['success' => false, 'message' => 'Invalid email address: ' . implode(', ', $bad)], 422);
        }
        if (!$to) {
            return response()->json(['success' => false, 'message' => 'Add at least one employer email in "To".'], 422);
        }

        // Applicant is always copied (and never duplicated)
        if (!empty($validated['seeker_email'])) {
            $cc[] = strtolower($validated['seeker_email']);
        }
        $cc  = array_values(array_diff(array_unique($cc), $to));
        $bcc = array_values(array_diff(array_unique($bcc), $to, $cc));

        // ── Verify attachments: only files we uploaded, must exist, total ≤ 20 MB ──
        $files = [];
        $total = 0;
        foreach ($validated['attachments'] as $a) {
            $path = $a['path'];
            if (!str_starts_with($path, 'manual-email/') || str_contains($path, '..')
                || !Storage::disk('public')->exists($path)) {
                return response()->json(['success' => false, 'message' => "Attachment not found: {$a['original_name']}. Please re-upload it."], 422);
            }
            $size   = Storage::disk('public')->size($path);
            $total += $size;
            $files[] = ['path' => $path, 'original_name' => $a['original_name'], 'size' => $size];
        }
        if ($total > 20 * 1024 * 1024) {
            return response()->json(['success' => false, 'message' => 'Attachments exceed 20 MB in total. Remove or compress some files.'], 422);
        }

        $bodyHtml    = $this->cleanHtml($validated['body']);
        $countryCode = strtoupper($validated['country_code'] ?? auth()->user()->country_code ?? 'UG');
        $countryName = Country::where('code', $countryCode)->value('name') ?? '';

        $depositId = $this->recordDeposit($validated, 'manual_job_application',
            'Application on behalf of: ' . $validated['seeker_name'] . ' → ' . ($validated['job_title'] ?? $validated['subject']));

        $dispatch = ManualEmailDispatch::create([
            'sent_by'         => auth()->id(),
            'purpose'         => 'job_application_help',
            'audience'        => 'custom',
            'employer_email'  => implode(', ', $to),
            'employer_name'   => $validated['employer_name'] ?? null,
            'job_title'       => $validated['job_title'] ?? null,
            'company_name'    => $validated['company_name'] ?? null,
            'seeker_name'     => $validated['seeker_name'],
            'seeker_email'    => $validated['seeker_email'] ?? null,
            'subject'         => $validated['subject'],
            'body'            => $bodyHtml,
            'attachments'     => $files,
            'cc'              => $cc ? implode(', ', $cc) : null,
            'bcc'             => $bcc ? implode(', ', $bcc) : null,
            'recipients'      => array_map(fn ($e) => ['email' => $e, 'name' => $validated['employer_name'] ?? 'Employer', 'role' => 'employer'], $to),
            'recipient_count' => count($to),
            'status'          => 'sending',
            'deposit_id'      => $depositId,
        ]);

        try {
            $pending = Mail::to($to);
            if ($cc)  $pending->cc($cc);
            if ($bcc) $pending->bcc($bcc);

            $pending->send(new ApplicationEmail(
                emailSubject: $validated['subject'],
                bodyHtml:     $bodyHtml,
                files:        $files,
                seekerName:   $validated['seeker_name'],
                seekerEmail:  $validated['seeker_email'] ?? null,
                jobTitle:     $validated['job_title'] ?? null,
                companyName:  $validated['company_name'] ?? null,
                countryName:  $countryName,
            ));

            $dispatch->update(['status' => 'sent', 'sent_count' => count($to), 'sent_at' => now()]);

            return response()->json([
                'success'     => true,
                'message'     => 'Application sent to ' . implode(', ', $to) . '.',
                'dispatch_id' => $dispatch->id,
            ]);
        } catch (\Throwable $e) {
            $dispatch->update(['status' => 'failed', 'failed_count' => count($to), 'error_message' => $e->getMessage()]);
            \Log::error('ManualEmail: application send failed', ['dispatch_id' => $dispatch->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Send failed: ' . $e->getMessage()], 500);
        }
    }

    /** "a@x.com, b@y.com; c@z.com" → ['a@x.com', ...]; invalid entries go into $invalid. */
    private function parseEmails(string $raw, &$invalid = []): array
    {
        $invalid = [];
        $out = [];
        foreach (preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) as $e) {
            $e = strtolower(trim($e, "<>\"'"));
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) $out[] = $e; else $invalid[] = $e;
        }
        return array_values(array_unique($out));
    }

    /** Allow only simple formatting; strip scripts, event handlers and javascript: links. */
    private function cleanHtml(string $html): string
    {
        $html = strip_tags($html, '<p><br><strong><b><em><i><u><ul><ol><li><a><h3><h4><blockquote><span>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/href\s*=\s*(["\'])\s*javascript:[^"\']*\1/i', 'href="#"', $html);
        // Give paragraphs email-safe spacing
        $html = preg_replace('/<p(\s[^>]*)?>/i', '<p style="margin:0 0 14px;">', $html);
        return trim($html);
    }

    private function recordDeposit(array $validated, string $purposeType, string $description): ?int
    {
        if (empty($validated['deposit_amount_cents'])) return null;

        return Deposit::create([
            'deposit_ref'         => (string) Str::uuid(),
            'currency_id'         => \App\Models\Currency::getByCode('UGX')?->id,
            'amount'              => (int) $validated['deposit_amount_cents'],
            'fee'                 => 0,
            'net_amount'          => (int) $validated['deposit_amount_cents'],
            'deposit_method'      => $validated['deposit_method'] ?? 'cash',
            'purpose_type'        => $purposeType,
            'purpose_description' => $description,
            'status'              => 'completed',
            'deposit_date'        => now(),
            'cleared_date'        => now(),
            'depositor_id'        => auth()->id(),
            'paid_by_user_id'     => auth()->id(),
            'depositor_name'      => auth()->user()->name,
            'depositor_email'     => auth()->user()->email,
            'country_code'        => auth()->user()->country_code ?? 'UG',
            'created_by'          => auth()->id(),
            'notes'               => $validated['deposit_notes'] ?? null,
        ])->id;
    }

    // ═════════════════════════════════════════════════════════════
    // DETAIL / DELETE
    // ═════════════════════════════════════════════════════════════

    public function show(int $id): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $d = ManualEmailDispatch::with(['deposit.currency'])->findOrFail($id);

        $attachmentsDetail = collect($d->attachments ?? [])->map(function ($item) {
            $path = is_array($item) ? ($item['path'] ?? null) : $item;
            $name = is_array($item) ? ($item['original_name'] ?? basename((string) $path)) : basename((string) $path);
            $size = is_array($item) ? ($item['size'] ?? null) : null;
            if (!$path) return null;
            return [
                'path'          => $path,
                'original_name' => $name,
                'size'          => $size,
                'url'           => Storage::disk('public')->url($path),
                'exists'        => Storage::disk('public')->exists($path),
            ];
        })->filter()->values()->all();

        $recipients = collect($d->recipients ?? [])->map(function ($r) use ($d) {
            return [
                'email'   => $r['email'] ?? '',
                'name'    => $r['name'] ?? '',
                'user_id' => $r['user_id'] ?? null,
                'status'  => $d->status === 'sent' ? 'sent' : 'unknown',
                'error'   => null,
            ];
        })->values()->all();

        $deposit = null;
        if ($d->deposit) {
            $deposit = [
                'amount_formatted' => $d->deposit->formatted_amount,
                'method'           => $d->deposit->deposit_method,
                'notes'            => $d->deposit->notes,
            ];
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'id'                 => $d->id,
                'purpose'            => $d->purpose,
                'purpose_label'      => $d->purpose_label,
                'audience'           => $d->audience,
                'subject'            => $d->subject,
                'body'               => $d->body,
                'sender'             => $d->sender?->name,
                'status'             => $d->status,
                'status_badge'       => $d->status_badge,
                'recipient_count'    => $d->recipient_count,
                'sent_count'         => $d->sent_count,
                'failed_count'       => $d->failed_count,
                'recipients'         => $recipients,
                'attachments_detail' => $attachmentsDetail,
                'deposit'            => $deposit,
                'employer_email'     => $d->employer_email,
                'employer_name'      => $d->employer_name,
                'job_title'          => $d->job_title,
                'company_name'       => $d->company_name,
                'seeker_name'        => $d->seeker_name,
                'seeker_email'       => $d->seeker_email,
                'cc'                 => $d->cc,
                'bcc'                => $d->bcc,
                'error_message'      => $d->error_message,
                'sender_email'       => $d->sender?->email,
                'sent_at'            => $d->sent_at?->toISOString(),
                'created_at'         => $d->created_at->toISOString(),
            ],
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $d = ManualEmailDispatch::findOrFail($id);
        $this->deleteAttachments($d);
        $d->delete();

        return response()->json(['success' => true, 'message' => 'Dispatch deleted.']);
    }

    public function bulkDelete(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $validated = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:manual_email_dispatches,id',
        ]);

        $count = 0;
        ManualEmailDispatch::whereIn('id', $validated['ids'])
            ->get()
            ->each(function ($d) use (&$count) {
                $this->deleteAttachments($d);
                $d->delete();
                $count++;
            });

        return response()->json([
            'success' => true,
            'message' => "Deleted {$count} dispatch record(s) and their files.",
        ]);
    }

    private function deleteAttachments(ManualEmailDispatch $dispatch): void
    {
        foreach ($dispatch->attachments ?? [] as $item) {
            $path = is_array($item) ? ($item['path'] ?? null) : $item;
            if (!$path) continue;
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable $e) {
                \Log::warning('ManualEmail: failed to delete attachment', [
                    'dispatch_id' => $dispatch->id,
                    'path'        => $path,
                    'error'       => $e->getMessage(),
                ]);
            }
        }
    }

    // ═════════════════════════════════════════════════════════════
    // LOG (paginated)
    // ═════════════════════════════════════════════════════════════

    public function log(Request $request): JsonResponse
    {
        if (!auth()->user()->can('send manual emails')) {
            return response()->json(['success' => false], 403);
        }

        $perPage = in_array((int) $request->get('per_page', 25), [10, 25, 50, 100], true)
            ? (int) $request->get('per_page', 25)
            : 25;

        $rows = ManualEmailDispatch::with('sender:id,name,email')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $rows->through(fn ($d) => [
                'id'              => $d->id,
                'purpose'         => $d->purpose,
                'purpose_label'   => $d->purpose_label,
                'audience'        => $d->audience,
                'subject'         => $d->subject,
                'recipient_count' => $d->recipient_count,
                'sent_count'      => $d->sent_count,
                'failed_count'    => $d->failed_count,
                'status'          => $d->status,
                'status_badge'    => $d->status_badge,
                'sender'          => $d->sender?->name ?? '—',
                'sender_email'    => $d->sender?->email,
                'sent_at'         => $d->sent_at?->toISOString(),
                'created_at'      => $d->created_at->toISOString(),
            ]),
            'pagination' => [
                'current_page' => $rows->currentPage(),
                'last_page'    => $rows->lastPage(),
                'total'        => $rows->total(),
            ],
        ]);
    }
}