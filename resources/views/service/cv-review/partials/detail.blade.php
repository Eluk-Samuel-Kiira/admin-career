@php
    $effective = $request->effective_review;
    $hasReview = $request->has_review;
    $wasEdited = !empty($request->admin_edited_review);
@endphp

<div class="d-flex flex-column gap-6">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <div class="fs-4 fw-bold">{{ $request->user->name ?? '—' }}</div>
            <div class="text-muted">{{ $request->user->email ?? '—' }}</div>
            @if($request->user->phone ?? null)
                <div class="text-muted fs-7">📱 {{ $request->user->phone }}</div>
            @endif
            <div class="text-muted fs-7">#{{ substr($request->uuid, 0, 8) }}</div>
        </div>
        <div class="text-end">
            <div>{!! $request->status_badge !!}</div>
            <div class="mt-1">{!! $request->payment_badge !!}</div>
            @if($request->review_delivered)
                <div class="mt-1">
                    <span class="badge badge-light-success">
                        📤 Delivered {{ $request->review_delivered_at->diffForHumans() }}
                    </span>
                </div>
            @endif
        </div>
    </div>

    {{-- Service & price --}}
    <div class="card bg-light-primary">
        <div class="card-body py-4">
            <div class="d-flex justify-content-between">
                <div>
                    <div class="fw-bold">{{ $request->service->name ?? '—' }}</div>
                    <div class="text-muted fs-7">{{ $request->service->description ?? '' }}</div>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-bold text-primary">
                        {{ $request->currency?->formatAmount($request->amount_cents) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Target job --}}
    @if($request->target_job_title || $request->target_job_description)
    <div>
        <h5 class="fw-bold mb-3">Target Job</h5>
        @if($request->target_job_title)
            <div class="fw-semibold">🎯 {{ $request->target_job_title }}</div>
        @endif
        @if($request->target_job_description)
            <div class="text-muted mt-2" style="max-height:200px;overflow:auto;">
                {!! nl2br(e($request->target_job_description)) !!}
            </div>
        @endif
    </div>
    @endif

    {{-- Original CV --}}
    <div>
        <h5 class="fw-bold mb-3">Original CV</h5>

        @if($request->cv_download_url)
            <a href="{{ $request->cv_download_url }}" target="_blank" class="btn btn-light-primary btn-sm">
                <i class="ki-duotone ki-file-down fs-4 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                {{ $request->cv_display_name }}
            </a>
        @else
            <div class="alert alert-light-warning d-flex align-items-center py-3">
                <i class="ki-duotone ki-information-5 fs-3 me-2 text-warning">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <div>
                    <div class="fw-semibold">CV file not available</div>
                    <div class="text-muted fs-7">
                        Path: <code>{{ $request->original_cv_path ?: $request->seeker_cv_path ?: '—' }}</code>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- AI REVIEW SECTION                                         --}}
    {{-- ========================================================= --}}
    <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold mb-0">AI Gap Review</h5>
            <div class="d-flex gap-2">
                @if(!$hasReview)
                    <button type="button"
                            class="btn btn-sm btn-primary"
                            data-action="run-ai-review"
                            data-id="{{ $request->id }}">
                        <i class="ki-duotone ki-robot fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Run AI Review
                    </button>
                @else
                    <button type="button"
                            class="btn btn-sm btn-light-primary"
                            data-action="run-ai-review"
                            data-id="{{ $request->id }}">
                        <i class="ki-duotone ki-arrows-circle fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Re-run AI
                    </button>
                    <button type="button"
                            class="btn btn-sm btn-light"
                            data-action="toggle-edit-review">
                        <i class="ki-duotone ki-pencil fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Edit
                    </button>
                @endif
            </div>
        </div>

        @if(!$hasReview)
            <div class="alert alert-light-info text-center py-6">
                <i class="ki-duotone ki-information-5 fs-3x text-info mb-3 d-block"></i>
                <div class="fw-bold mb-1">No review yet</div>
                <div class="text-muted fs-7">Click "Run AI Review" to generate the gap analysis.</div>
            </div>
        @else
            @if($wasEdited)
                <div class="alert alert-light-warning d-flex align-items-center mb-3 py-3">
                    <i class="ki-duotone ki-information-5 fs-3 me-2">
                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                    </i>
                    <div class="fs-7">This review has been edited by an admin. The seeker sees the edited version.</div>
                </div>
            @endif

            {{-- View mode --}}
            <div id="reviewView">
                <div class="card bg-light-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <div class="fw-bold">Overall Score</div>
                            <div class="fs-3 fw-bold text-info">
                                {{ $effective['overall_score'] ?? '—' }}/100
                            </div>
                        </div>

                        @if(!empty($effective['summary']))
                            <p class="text-muted mb-3">{{ $effective['summary'] }}</p>
                        @endif

                        @if(!empty($effective['sections']))
                            <table class="table table-sm">
                                <thead>
                                    <tr><th>Section</th><th>Status</th><th>Note</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($effective['sections'] as $sec)
                                        <tr>
                                            <td class="fw-semibold">{{ $sec['section'] ?? '' }}</td>
                                            <td>
                                                @php $s = $sec['status'] ?? ''; @endphp
                                                @if($s === 'ok')       <span class="badge badge-light-success">OK</span>
                                                @elseif($s === 'weak') <span class="badge badge-light-warning">Weak</span>
                                                @else                  <span class="badge badge-light-danger">Missing</span>
                                                @endif
                                            </td>
                                            <td class="text-muted fs-7">{{ $sec['note'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif

                        @if(!empty($effective['missing_fields']))
                            <div class="mt-3">
                                <div class="fw-bold mb-2 fs-7">Missing Information</div>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($effective['missing_fields'] as $mf)
                                        <span class="badge badge-light-danger">{{ $mf }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Edit mode --}}
            <div id="reviewEdit" class="d-none">
                <div class="card border border-primary">
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="fw-semibold fs-7 mb-2">Overall Score (0–100)</label>
                                <input type="number" min="0" max="100" class="form-control form-control-sm"
                                       id="edit_score" value="{{ $effective['overall_score'] ?? 0 }}">
                            </div>
                            <div class="col-md-8">
                                <label class="fw-semibold fs-7 mb-2">Summary</label>
                                <input type="text" class="form-control form-control-sm"
                                       id="edit_summary" value="{{ $effective['summary'] ?? '' }}">
                            </div>
                        </div>

                        <label class="fw-semibold fs-7 mb-2 d-block">Sections</label>
                        <div id="edit_sections_list">
                            @foreach(($effective['sections'] ?? []) as $sec)
                                <div class="row g-2 mb-2 section-row">
                                    <div class="col-md-4">
                                        <input type="text" class="form-control form-control-sm"
                                               data-field="section"
                                               value="{{ $sec['section'] ?? '' }}" placeholder="Section">
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select form-select-sm" data-field="status">
                                            <option value="ok"      {{ ($sec['status'] ?? '') === 'ok' ? 'selected' : '' }}>OK</option>
                                            <option value="weak"    {{ ($sec['status'] ?? '') === 'weak' ? 'selected' : '' }}>Weak</option>
                                            <option value="missing" {{ ($sec['status'] ?? '') === 'missing' ? 'selected' : '' }}>Missing</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control form-control-sm"
                                               data-field="note"
                                               value="{{ $sec['note'] ?? '' }}" placeholder="Note">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button"
                                                class="btn btn-sm btn-light-danger w-100"
                                                data-action="remove-section">
                                            <i class="ki-duotone ki-trash fs-5"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button type="button"
                                class="btn btn-sm btn-light-primary mt-2"
                                data-action="add-section">
                            <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Add Section
                        </button>

                        <label class="fw-semibold fs-7 mb-2 mt-4 d-block">Missing Fields (comma separated)</label>
                        <input type="text" class="form-control form-control-sm"
                               id="edit_missing"
                               value="{{ implode(', ', $effective['missing_fields'] ?? []) }}">

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-sm btn-light" data-action="cancel-edit-review">Cancel</button>
                            <button type="button"
                                    class="btn btn-sm btn-primary"
                                    data-action="save-edited-review"
                                    data-id="{{ $request->id }}">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ========================================================= --}}
    {{-- DELIVERY SECTION                                          --}}
    {{-- ========================================================= --}}
    @if($hasReview)
    <div>
        <h5 class="fw-bold mb-3">Deliver to Seeker</h5>
        <div class="card bg-light-secondary">
            <div class="card-body">
                @if($request->review_delivered)
                    <div class="alert alert-light-success py-3 mb-3">
                        <div class="fw-bold fs-7">📤 Last delivered {{ $request->review_delivered_at->diffForHumans() }}</div>
                        <div class="text-muted fs-8">Via: {{ $request->review_delivery_channel }}</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="fw-semibold fs-7 mb-2 d-block">Channels</label>
                    <div class="d-flex gap-3">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="channel_email" checked>
                            <span class="form-check-label fw-semibold">
                                ✉️ Email
                                @if($request->user->email ?? null)
                                    <span class="text-muted fs-7">({{ $request->user->email }})</span>
                                @else
                                    <span class="text-danger fs-7">(no email)</span>
                                @endif
                            </span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="channel_whatsapp">
                            <span class="form-check-label fw-semibold">
                                📱 WhatsApp
                                @if($request->user->phone ?? null)
                                    <span class="text-muted fs-7">({{ $request->user->phone }})</span>
                                @else
                                    <span class="text-danger fs-7">(no phone)</span>
                                @endif
                            </span>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-semibold fs-7 mb-2">Custom Message (optional)</label>
                    <textarea class="form-control form-control-sm" rows="2"
                              id="delivery_message"
                              placeholder="Hi {{ $request->user->name ?? 'there' }}, we've reviewed your CV..."></textarea>
                </div>

                <button type="button"
                        class="btn btn-success btn-sm"
                        data-action="open-deliver-modal"
                        data-id="{{ $request->id }}"
                        {{ (!$request->user->email && !$request->user->phone) ? 'disabled' : '' }}>
                    <i class="ki-duotone ki-send fs-4 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Send Review
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Seeker's answers --}}
    @if($request->seeker_gap_answers)
    <div>
        <h5 class="fw-bold mb-3">Seeker's Answers</h5>
        <div class="card bg-light-success">
            <div class="card-body">
                @foreach($request->seeker_gap_answers as $q => $a)
                    <div class="mb-2 pb-2 border-bottom border-light">
                        <div class="fw-semibold fs-7 text-muted">{{ ucwords(str_replace('_', ' ', $q)) }}</div>
                        <div>{{ is_array($a) ? implode(', ', $a) : $a }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Admin assignment --}}
    <div>
        <h5 class="fw-bold mb-3">Administration</h5>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="fw-semibold fs-7 text-muted">Assigned Admin</label>
                <div>{{ $request->assignedAdmin->name ?? 'Unassigned' }}</div>
            </div>
            <div class="col-md-6">
                <label class="fw-semibold fs-7 text-muted">SLA Due</label>
                <div>{{ $request->sla_due_at?->format('M d, Y H:i') ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="fw-semibold fs-7 text-muted">Payment Reference</label>
                <div>{{ $request->payment_reference ?? '—' }}</div>
            </div>
            <div class="col-md-6">
                <label class="fw-semibold fs-7 text-muted">Delivered At</label>
                <div>{{ $request->delivered_at?->format('M d, Y H:i') ?? '—' }}</div>
            </div>
        </div>
    </div>

    {{-- Admin notes --}}
    @if($request->admin_notes)
    <div>
        <h5 class="fw-bold mb-3">Internal Notes</h5>
        <div class="alert alert-secondary">{{ $request->admin_notes }}</div>
    </div>
    @endif

    {{-- ========================================================== --}}
    {{-- STATUS CONTROL — admin can jump to any status             --}}
    {{-- ========================================================== --}}
    <div>
        <h5 class="fw-bold mb-3">Change Status</h5>
        <div class="card bg-light-warning">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="text-muted fs-7">Current</div>
                        <div>{!! $request->status_badge !!}</div>
                    </div>
                    <div>
                        <div class="text-muted fs-7">Changed</div>
                        <div class="fs-7">{{ $request->updated_at->diffForHumans() }}</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-semibold fs-7 mb-2">New Status</label>
                    <select class="form-select form-select-sm" id="status_change_select">
                        @foreach(\App\Models\Service\CvReviewRequest::statuses() as $key => $label)
                            <option value="{{ $key }}" {{ $request->status === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="text-muted fs-8 mt-1">
                        Admin can jump to any status. Use this to record off-platform payments,
                        fix mistakes, or undo an accidental cancel.
                    </div>
                </div>

                <div class="mb-3">
                    <label class="fw-semibold fs-7 mb-2">Note (optional, for the audit trail)</label>
                    <input type="text" class="form-control form-control-sm"
                        id="status_change_note"
                        placeholder="e.g. Payment received via MTN MoMo ref 12345" />
                </div>

                <button type="button"
                        class="btn btn-warning btn-sm"
                        data-action="change-status"
                        data-id="{{ $request->id }}">
                    <i class="ki-duotone ki-arrows-circle fs-4 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    Apply Status Change
                </button>

                {{-- Show last 3 status changes --}}
                @php
                    $statusLogs = collect($request->delivery_log ?? [])
                        ->where('type', 'status_change')
                        ->sortByDesc('at')
                        ->take(3);
                @endphp
                @if($statusLogs->isNotEmpty())
                    <div class="separator separator-dashed my-4"></div>
                    <div class="fw-bold fs-7 mb-2">Recent Changes</div>
                    @foreach($statusLogs as $log)
                        <div class="d-flex justify-content-between align-items-start py-1 border-bottom border-light fs-8">
                            <div>
                                <span class="text-muted">{{ $log['from'] ?? '?' }}</span>
                                <i class="ki-duotone ki-arrow-right fs-6 mx-1"><span class="path1"></span><span class="path2"></span></i>
                                <span class="fw-semibold">{{ $log['to'] ?? '?' }}</span>
                                @if(!empty($log['note']))
                                    <div class="text-muted">{{ $log['note'] }}</div>
                                @endif
                            </div>
                            <div class="text-muted text-end" style="min-width:120px;">
                                {{ \Carbon\Carbon::parse($log['at'])->diffForHumans() }}
                                @if(!empty($log['by_name']))
                                    <div>{{ $log['by_name'] }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif

            </div>
        </div>
    </div>

</div>

