<?php

namespace App\Http\Controllers\Api\Seeker;

use App\Http\Controllers\Controller;
use App\Models\AlertDispatch;
use App\Models\Subscription\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Which WhatsApp sends has this user received?
        $query = AlertDispatch::where('user_id', $user->id)
            ->where('channel', 'whatsapp')
            ->orderByDesc('id');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($kind = $request->get('kind')) {
            $query->where('kind', $kind);
        }

        if ($from = $request->get('from')) {
            $query->where('created_at', '>=', $from . ' 00:00:00');
        }
        if ($to = $request->get('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $dispatches = $query->paginate($perPage);

        // Is the user currently subscribed to WhatsApp alerts?
        $subscription = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->where('current_period_end', '>', now())
            ->orderByDesc('id')
            ->first();

        $whatsappFeatureActive = $subscription && $subscription->hasFeature('whatsapp_alerts');

        // Counts
        $counts = [
            'all'    => AlertDispatch::where('user_id', $user->id)->where('channel', 'whatsapp')->count(),
            'sent'   => AlertDispatch::where('user_id', $user->id)->where('channel', 'whatsapp')->where('status', 'sent')->count(),
            'failed' => AlertDispatch::where('user_id', $user->id)->where('channel', 'whatsapp')->where('status', 'failed')->count(),
        ];

        return response()->json([
            'success'                  => true,
            'feature_active'           => $whatsappFeatureActive,
            'has_any_subscription'     => (bool) $subscription,
            'data'                     => $dispatches->through(fn($d) => [
                'id'         => $d->id,
                'kind'       => $d->kind,
                'kind_label' => $this->kindLabel($d->kind),
                'jobs_sent'  => $d->jobs_sent,
                'status'     => $d->status,
                'error'      => $d->error_message,
                'sent_at'    => $d->sent_at?->toISOString(),
                'created_at' => $d->created_at?->toISOString(),
            ]),
            'counts' => $counts,
        ]);
    }

    private function kindLabel(string $kind): string
    {
        return match ($kind) {
            'jobs'               => 'Job Alert',
            'preferences_prompt' => 'Set Preferences Prompt',
            'profile_prompt'     => 'Complete Profile Prompt',
            'cv_prompt'          => 'Upload CV Prompt',
            default              => ucfirst(str_replace('_', ' ', $kind)),
        };
    }
}