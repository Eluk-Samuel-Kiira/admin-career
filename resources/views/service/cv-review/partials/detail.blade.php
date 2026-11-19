@php
    /* ── Defensive guards ───────────────────────────────────── */
    $effective       = $request->effective_review ?? [];
    $hasReview       = !empty($effective) && is_array($effective);
    $wasEdited       = !empty($request->admin_edited_review);
    $aiReview        = is_array($request->ai_gap_review ?? null) ? $request->ai_gap_review : [];
    $missingFields   = is_array($aiReview['missing_fields'] ?? null) ? $aiReview['missing_fields'] : [];
    $existingAnswers = is_array($request->seeker_gap_answers ?? null) ? $request->seeker_gap_answers : [];
    $hasAnswers      = !empty($existingAnswers);
    $status          = (string) $request->status;
    $mode            = $request->rewrite_mode;
    $isAi            = $mode === 'ai';
    $isHuman         = $mode === 'human';

    $user            = $request->user;
    $userName        = $user->name ?? '—';
    $userEmail       = $user->email ?? null;
    $userPhone       = $user->phone ?? null;

    $canSubmitAnswers = in_array($status, ['submitted', 'ai_reviewed'], true) && !$hasAnswers && !empty($missingFields);

    $canEditAnswers = $hasAnswers && !in_array($status, ['cancelled', 'completed'], true);
    $answerFields   = array_values(array_diff(
        array_unique(array_merge($missingFields, array_keys($existingAnswers))),
        ['_skipped']
    ));
    $editAnswers    = array_diff_key($existingAnswers, ['_skipped' => 1]);

    // Cover letter
    $coverLetter = $request->coverLetter ?? null;
    $hasCover    = !empty($request->cover_letter_uuid) && $coverLetter;

    // CV content
    $rewriteHtml  = is_array($request->rewrite_content ?? null) ? ($request->rewrite_content['html'] ?? null) : null;
    $originalHtml = is_array($request->rewrite_content_original ?? null) ? ($request->rewrite_content_original['html'] ?? null) : null;
    $hasRewrite   = $isAi && !empty($rewriteHtml);

    $isSubscriber = (bool) ($request->covered_by_subscription ?? false);
    $canDeliver   = $hasReview && ($userEmail || $userPhone);

    /* ── Generation state (CV + cover letter) ───────────────── */
    $cvMissing    = empty($rewriteHtml);
    $coverMissing = empty($request->cover_letter_uuid);
    $genBusy      = in_array($status, ['generating'], true);
    $genClosed    = in_array($status, ['cancelled', 'completed'], true);
    
    // Answers are only required when the AI review actually asked questions
    $answersRequired = !empty($missingFields);
    $answersReady    = !$answersRequired || $hasAnswers;

    $needsGeneration = $isAi && !$genBusy && !$genClosed && ($cvMissing || $coverMissing);
    $canGenerate     = $needsGeneration && $answersReady;
    $waitingOnAnswers = $needsGeneration && !$answersReady;

    $genError     = $request->generation_error ?: $request->rewrite_error_message;

    $genLabel = $cvMissing && $coverMissing ? 'Generate CV & letter'
              : ($cvMissing ? 'Generate CV' : 'Generate cover letter');
@endphp

<div class="d-flex flex-column gap-5">

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- HEADER                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div class="border-bottom pb-4">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
            <div class="min-w-0 flex-grow-1">
                <div class="fs-4 fw-bold text-truncate">{{ $userName }}</div>
                @if($userEmail)
                    <div class="text-muted fs-7 text-truncate">{{ $userEmail }}</div>
                @endif
                @if($userPhone)
                    <div class="text-muted fs-7">📱 {{ $userPhone }}</div>
                @endif
                <div class="text-muted fs-8 font-monospace mt-1">#{{ substr($request->uuid, 0, 8) }}</div>
            </div>
            <div class="text-end flex-shrink-0">
                <div>{!! $request->status_badge !!}</div>
                <div class="mt-1">{!! $request->payment_badge !!}</div>
                @if($isSubscriber)
                    <span class="badge badge-light-info mt-1 fs-8">
                        <i class="ki-duotone ki-crown fs-7 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Subscriber
                    </span>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap gap-3 mt-3 fs-8">
            <div class="d-flex align-items-center gap-1">
                <i class="ki-duotone ki-dollar fs-6 text-primary"><span class="path1"></span><span class="path2"></span></i>
                <span class="fw-bold text-primary">{{ $request->formatted_amount }}</span>
            </div>
            @if($request->country_label)
                <div class="d-flex align-items-center gap-1">
                    <i class="ki-duotone ki-geolocation fs-6 text-muted"><span class="path1"></span><span class="path2"></span></i>
                    {!! $request->country_label !!}
                </div>
            @endif
            @if($request->created_at)
                <div class="d-flex align-items-center gap-1 text-muted">
                    <i class="ki-duotone ki-time fs-6"><span class="path1"></span><span class="path2"></span></i>
                    {{ $request->created_at->diffForHumans() }}
                </div>
            @endif
            @if($request->review_delivered && $request->review_delivered_at)
                <div class="d-flex align-items-center gap-1 text-success">
                    <i class="ki-duotone ki-check-circle fs-6"><span class="path1"></span><span class="path2"></span></i>
                    Delivered {{ $request->review_delivered_at->diffForHumans() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- GENERATION — CV + cover letter status and regenerate        --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($isAi)
        <div data-gen-busy="{{ $genBusy ? '1' : '0' }}">
            <div class="card border border-warning">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0 text-uppercase fs-8 text-muted letter-spacing-1">
                            <i class="ki-duotone ki-gear fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                            Generation
                        </h6>

                        @if($canGenerate)
                            <button type="button"
                                    class="btn btn-xs btn-warning"
                                    data-action="generate-missing"
                                    data-id="{{ $request->id }}">
                                <i class="ki-duotone ki-arrows-circle fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                                {{ $genLabel }}
                            </button>
                        @endif
                    </div>

                    <div class="d-flex flex-column gap-2 fs-8">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">CV</span>
                            @if($cvMissing)
                                <span class="badge badge-light-danger">Missing</span>
                            @else
                                <span class="badge badge-light-success">Ready</span>
                            @endif
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">Cover letter</span>
                            @if($coverMissing)
                                <span class="badge badge-light-danger">Missing</span>
                            @elseif($coverLetter?->status === 'generated')
                                <span class="badge badge-light-success">Ready</span>
                            @elseif($coverLetter?->status === 'failed')
                                <span class="badge badge-light-danger">Failed</span>
                            @else
                                <span class="badge badge-light-info">
                                    <span class="spinner-border spinner-border-sm me-1" style="width:10px;height:10px;"></span>
                                    Generating
                                </span>
                            @endif
                        </div>
                    </div>

                    @if($waitingOnAnswers)
                        <div class="alert alert-light-warning py-2 mt-3 mb-0 fs-8">
                            <i class="ki-duotone ki-information-5 fs-6 me-1">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            You can only generate once answers to the gap questions have been provided.
                            Fill them in below, or choose <strong>User already answered — skip</strong>.
                        </div>
                    @endif

                    @if($status === 'failed' && $genError)
                        <div class="alert alert-light-danger py-2 mt-3 mb-0 fs-8">
                            <div class="fw-bold">Failed after {{ (int) $request->generation_attempts }} attempt(s)</div>
                            <div class="text-break mt-1">{{ $genError }}</div>
                        </div>
                    @elseif($genBusy)
                        <div class="alert alert-light-info py-2 mt-3 mb-0 fs-8">
                            Generating… attempt {{ (int) $request->generation_attempts }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- TARGET JOB                                                 --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div>
        <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">
            <i class="ki-duotone ki-briefcase fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
            Target Job
        </h6>

        @if($request->target_job_title || $request->target_company_name || $request->target_job_description)
            <div class="card bg-light-secondary">
                <div class="card-body py-3">
                    @if($request->target_job_title)
                        <div class="fw-bold fs-6">🎯 {{ $request->target_job_title }}</div>
                    @endif
                    @if($request->target_company_name)
                        <div class="text-muted fs-7">{{ $request->target_company_name }}</div>
                    @endif
                    @if($request->job_post_id)
                        <div class="mt-1">
                            <span class="badge badge-light-primary fs-8">Linked to JobPost #{{ $request->job_post_id }}</span>
                        </div>
                    @endif
                    @if($request->target_job_description)
                        <details class="mt-2">
                            <summary class="cursor-pointer fw-semibold fs-7 text-muted">View full job description</summary>
                            <div class="text-muted fs-7 mt-2 pt-2 border-top" style="max-height:240px;overflow:auto;">
                                {!! nl2br(e($request->target_job_description)) !!}
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        @else
            <div class="text-muted fs-7 fst-italic">No target job — general CV review.</div>
        @endif
    </div>

    {{-- Rewrite mode --}}
    @if($mode)
        <div class="alert alert-light-{{ $isAi ? 'info' : 'warning' }} mb-0 py-3">
            <div class="fw-bold fs-7">
                @if($isAi)
                    <i class="ki-duotone ki-robot fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    AI Tool Rewrite
                @else
                    <i class="ki-duotone ki-user-tick fs-5 me-1"><span class="path1"></span><span class="path2"></span></i>
                    HR Expert Rewrite
                @endif
            </div>
            @if($isAi && $request->rewrite_generated_at)
                <div class="text-muted fs-8 mt-1">
                    Generated {{ $request->rewrite_generated_at->diffForHumans() }}
                    · {{ (int) $request->rewrite_regeneration_count }}/{{ (int) $request->rewrite_max_regenerations }} regenerations
                </div>
            @endif
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ORIGINAL CV                                                --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div>
        <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">
            <i class="ki-duotone ki-file fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
            Original CV
        </h6>

        @if($request->cv_download_url)
            <a href="{{ $request->cv_download_url }}" target="_blank" class="btn btn-sm btn-light-primary">
                <i class="ki-duotone ki-file-down fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                {{ $request->cv_display_name }}
            </a>
        @else
            <div class="alert alert-light-warning py-2 mb-0 fs-7">
                CV file not available
                <code class="ms-1 fs-8">{{ $request->original_cv_path ?: $request->seeker_cv_path ?: '—' }}</code>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- AI GAP REVIEW                                              --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold mb-0 text-uppercase fs-8 text-muted letter-spacing-1">
                <i class="ki-duotone ki-robot fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                AI Gap Review
            </h6>
            <div class="d-flex gap-1">
                @if(!$hasReview)
                    <button type="button" class="btn btn-xs btn-primary"
                            data-action="run-ai-review" data-id="{{ $request->id }}">
                        Run AI
                    </button>
                @else
                    <button type="button" class="btn btn-xs btn-light-primary"
                            data-action="run-ai-review" data-id="{{ $request->id }}">
                        Re-run
                    </button>
                    <button type="button" class="btn btn-xs btn-light" data-action="toggle-edit-review">
                        Edit
                    </button>
                @endif
            </div>
        </div>

        @if(!$hasReview)
            <div class="alert alert-light-info py-3 mb-0 fs-7">
                No review yet. Click <strong>Run AI</strong> to generate the gap analysis.
            </div>
        @else
            @if($wasEdited)
                <div class="alert alert-light-warning py-2 mb-2 fs-8">
                    Admin-edited — this is what the seeker sees.
                </div>
            @endif

            <div id="reviewView">
                <div class="card bg-light-info">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold fs-7">Score</div>
                            <div class="fs-3 fw-bold text-info">
                                {{ $effective['overall_score'] ?? '—' }}<span class="fs-7 text-muted">/100</span>
                            </div>
                        </div>

                        @if(!empty($effective['summary']))
                            <p class="text-muted fs-7 mb-2">{{ $effective['summary'] }}</p>
                        @endif

                        @if(!empty($effective['sections']) && is_array($effective['sections']))
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 fs-8">
                                    <tbody>
                                        @foreach($effective['sections'] as $sec)
                                            @php $s = $sec['status'] ?? ''; @endphp
                                            <tr>
                                                <td class="fw-semibold" style="width:30%">{{ $sec['section'] ?? '' }}</td>
                                                <td style="width:15%">
                                                    @if($s === 'ok')
                                                        <span class="badge badge-light-success fs-9">OK</span>
                                                    @elseif($s === 'weak')
                                                        <span class="badge badge-light-warning fs-9">Weak</span>
                                                    @else
                                                        <span class="badge badge-light-danger fs-9">Missing</span>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $sec['note'] ?? '' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif

                        @if(!empty($effective['missing_fields']))
                            <div class="mt-2 pt-2 border-top">
                                <div class="fw-bold mb-1 fs-8 text-muted">Missing</div>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($effective['missing_fields'] as $mf)
                                        <span class="badge badge-light-danger fs-9">{{ $mf }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div id="reviewEdit" class="d-none">
                <div class="card border border-primary">
                    <div class="card-body py-3">
                        <div class="row g-2 mb-2">
                            <div class="col-4">
                                <label class="fw-semibold fs-8 mb-1">Score</label>
                                <input type="number" min="0" max="100" class="form-control form-control-sm"
                                       id="edit_score" value="{{ $effective['overall_score'] ?? 0 }}">
                            </div>
                            <div class="col-8">
                                <label class="fw-semibold fs-8 mb-1">Summary</label>
                                <input type="text" class="form-control form-control-sm"
                                       id="edit_summary" value="{{ $effective['summary'] ?? '' }}">
                            </div>
                        </div>

                        <label class="fw-semibold fs-8 mb-1 d-block">Sections</label>
                        <div id="edit_sections_list">
                            @foreach(($effective['sections'] ?? []) as $sec)
                                <div class="row g-1 mb-1 section-row">
                                    <div class="col-4">
                                        <input type="text" class="form-control form-control-sm"
                                               data-field="section" value="{{ $sec['section'] ?? '' }}">
                                    </div>
                                    <div class="col-3">
                                        <select class="form-select form-select-sm" data-field="status">
                                            <option value="ok"      {{ ($sec['status'] ?? '') === 'ok' ? 'selected' : '' }}>OK</option>
                                            <option value="weak"    {{ ($sec['status'] ?? '') === 'weak' ? 'selected' : '' }}>Weak</option>
                                            <option value="missing" {{ ($sec['status'] ?? '') === 'missing' ? 'selected' : '' }}>Missing</option>
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="text" class="form-control form-control-sm"
                                               data-field="note" value="{{ $sec['note'] ?? '' }}">
                                    </div>
                                    <div class="col-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-light-danger"
                                                data-action="remove-section">
                                            <i class="ki-duotone ki-trash fs-6"><span class="path1"></span><span class="path2"></span></i>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-xs btn-light-primary mt-1" data-action="add-section">
                            Add Section
                        </button>

                        <label class="fw-semibold fs-8 mb-1 mt-2 d-block">Missing Fields (comma separated)</label>
                        <input type="text" class="form-control form-control-sm" id="edit_missing"
                               value="{{ implode(', ', $effective['missing_fields'] ?? []) }}">

                        <div class="d-flex justify-content-end gap-2 mt-2">
                            <button type="button" class="btn btn-xs btn-light" data-action="cancel-edit-review">Cancel</button>
                            <button type="button" class="btn btn-xs btn-primary"
                                    data-action="save-edited-review" data-id="{{ $request->id }}">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- GAP ANSWERS (fill or skip)                                 --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($canSubmitAnswers)
        <div>
            <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">
                Gap Questions ({{ count($missingFields) }})
            </h6>
            <div class="alert alert-light-info py-2 mb-2 fs-8">
                Fill in what you know, or skip if the user has provided these details elsewhere.
            </div>
            @include('service.cv-review.partials.answers-form', [
                'formId'  => 'adminAnswersForm',
                'mode'    => 'create',
                'fields'  => $missingFields,
                'answers' => $existingAnswers,
            ])
        </div>
    @endif

    @if($hasAnswers && !$canSubmitAnswers)
        {{-- READ-ONLY VIEW --}}
        <div id="answersViewWrap">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0 text-uppercase fs-8 text-muted letter-spacing-1">
                    Submitted Answers ({{ count($existingAnswers) }})
                </h6>
                @if($canEditAnswers)
                    <button type="button" class="btn btn-xs btn-light-primary" data-action="edit-answers">
                        <i class="ki-duotone ki-pencil fs-6 me-1"><span class="path1"></span><span class="path2"></span></i>
                        Edit answers
                    </button>
                @endif
            </div>
            <div class="card bg-light-success">
                <div class="card-body py-2">
                    @foreach($existingAnswers as $q => $a)
                        <div class="mb-1 pb-1 border-bottom border-light fs-8">
                            <div class="fw-semibold text-muted">{{ ucwords(str_replace('_', ' ', $q)) }}</div>
                            <div>{{ is_array($a) ? implode(', ', $a) : $a }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- PREFILLED EDIT FORM (hidden until "Edit answers" is clicked) --}}
        @if($canEditAnswers)
            <div id="answersEditWrap" class="d-none">
                <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">Edit Answers</h6>
                <div class="alert alert-light-warning py-2 mb-2 fs-8">
                    Saving updates the answers only. It does not regenerate the CV or cover letter.
                </div>
                @include('service.cv-review.partials.answers-form', [
                    'formId'  => 'adminAnswersEditForm',
                    'mode'    => 'edit',
                    'fields'  => $answerFields,
                    'answers' => $editAnswers,
                ])
            </div>
        @endif
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- DELIVERABLES                                               --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($hasRewrite || $hasCover || $isHuman)
    <div>
        <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">Deliverables</h6>

        @if($hasRewrite)
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-bold fs-7">
                    AI-Rewritten CV
                    @if($request->rewrite_is_user_edited)
                        <span class="badge badge-light-primary fs-9 ms-1">edited by seeker</span>
                    @endif
                </div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-xs btn-light-primary"
                            data-action="doc-edit" data-doc="cv" data-request-id="{{ $request->id }}">Edit</button>
                    <button type="button" class="btn btn-xs btn-light"
                            data-action="doc-copy" data-doc="cv" data-request-id="{{ $request->id }}">Copy</button>
                    <a href="{{ route('admin.cv-review.rewrite.download', $request->id) }}"
                    class="btn btn-xs btn-light-primary">PDF</a>
                </div>
            </div>
            <div class="card-body">
                <div style="max-height:480px;overflow-y:auto;">
                    <div class="cv-preview cv-doc" id="doc-cv-{{ $request->id }}">{!! $rewriteHtml !!}</div>
                </div>
                @if(!empty($originalHtml))
                    <details class="mt-3">
                        <summary class="fs-8 text-muted" style="cursor:pointer;">Show AI original</summary>
                        <div class="cv-preview cv-doc mt-2 pt-2 border-top" style="max-height:400px;overflow-y:auto;">
                            {!! $originalHtml !!}
                        </div>
                    </details>
                @endif
            </div>
        </div>
        @endif

        @if($hasCover && $coverLetter->status === 'generated' && $coverLetter->content)
        <div class="card mb-3">
            <div class="card-header py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-bold fs-7">Cover Letter</div>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-xs btn-light-primary"
                            data-action="doc-edit" data-doc="cover" data-request-id="{{ $request->id }}">Edit</button>
                    <button type="button" class="btn btn-xs btn-light"
                            data-action="doc-copy" data-doc="cover" data-request-id="{{ $request->id }}">Copy</button>
                    <a href="{{ route('admin.cv-review.cover-letter.download', $request->id) }}"
                    class="btn btn-xs btn-light-primary">PDF</a>
                </div>
            </div>
            <div class="card-body">
                <div style="max-height:480px;overflow-y:auto;">
                    <div class="cv-preview" id="doc-cover-{{ $request->id }}">{!! $coverLetter->content !!}</div>
                </div>
            </div>
        </div>
        @elseif($hasCover && $coverLetter->status === 'failed')
            <div class="alert alert-light-danger py-2 fs-8">Cover letter failed to generate.</div>
        @endif

        @if($isHuman)
            <div class="alert alert-light-warning py-3 mb-0 fs-7">
                <div class="fw-bold">HR Expert path</div>
                <div class="text-muted fs-8 mt-1">No AI-generated CV. Assign an expert, who uploads the delivered CV.</div>
            </div>
        @endif
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- DELIVER TO SEEKER                                          --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    @if($canDeliver)
        <div>
            <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">Deliver to Seeker</h6>
            <div class="card bg-light-secondary">
                <div class="card-body py-3">
                    @if($request->review_delivered && $request->review_delivered_at)
                        <div class="alert alert-light-success py-2 mb-2 fs-8">
                            <div class="fw-bold">Last delivered {{ $request->review_delivered_at->diffForHumans() }}</div>
                            <div class="text-muted">Via: {{ $request->review_delivery_channel ?? '—' }}</div>
                        </div>
                    @endif

                    <div class="d-flex gap-3 mb-2 fs-8">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="channel_email"
                                   {{ $userEmail ? 'checked' : 'disabled' }}>
                            <span class="form-check-label">
                                Email
                                @if($userEmail)
                                    <span class="text-muted">({{ $userEmail }})</span>
                                @else
                                    <span class="text-danger">(none)</span>
                                @endif
                            </span>
                        </label>
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="channel_whatsapp"
                                   {{ $userPhone ? '' : 'disabled' }}>
                            <span class="form-check-label">
                                WhatsApp
                                @if($userPhone)
                                    <span class="text-muted">({{ $userPhone }})</span>
                                @else
                                    <span class="text-danger">(none)</span>
                                @endif
                            </span>
                        </label>
                    </div>

                    <textarea class="form-control form-control-sm mb-2" rows="2"
                              id="delivery_message" placeholder="Optional message…"></textarea>

                    <button type="button" class="btn btn-sm btn-success"
                            data-action="open-deliver-modal" data-id="{{ $request->id }}">
                        Send Review
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- ADMINISTRATION                                             --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div>
        <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">Administration</h6>
        <div class="card bg-light">
            <div class="card-body py-2">
                <div class="row g-2 fs-8">
                    <div class="col-6">
                        <div class="text-muted">Assigned</div>
                        <div class="fw-semibold">{{ $request->assignedAdmin->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">SLA Due</div>
                        <div class="fw-semibold">{{ $request->sla_due_at?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Payment Ref</div>
                        <div class="fw-semibold text-truncate">{{ $request->payment_reference ?? '—' }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted">Delivered At</div>
                        <div class="fw-semibold">{{ $request->delivered_at?->format('M d, Y H:i') ?? '—' }}</div>
                    </div>
                </div>

                @if($request->admin_notes)
                    <div class="separator separator-dashed my-2"></div>
                    <div class="text-muted fs-8 mb-1">Internal Notes</div>
                    <div class="fs-8">{{ $request->admin_notes }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════ --}}
    {{-- CHANGE STATUS                                              --}}
    {{-- ═══════════════════════════════════════════════════════════ --}}
    <div>
        <h6 class="fw-bold mb-2 text-uppercase fs-8 text-muted letter-spacing-1">Change Status</h6>
        <div class="card bg-light-warning">
            <div class="card-body py-3">
                <div class="mb-2">
                    <label class="fw-semibold fs-8 mb-1">New Status</label>
                    <select class="form-select form-select-sm" id="status_change_select">
                        @foreach(\App\Models\Service\CvReviewRequest::statuses() as $key => $label)
                            @if($key === 'paid')
                                @continue
                            @endif
                            <option value="{{ $key }}" {{ $status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="text-muted fs-9 mt-1">
                        To record an off-platform payment, use <strong>Confirm Payment</strong> instead.
                    </div>
                </div>

                <div class="mb-2">
                    <label class="fw-semibold fs-8 mb-1">Note (audit trail)</label>
                    <input type="text" class="form-control form-control-sm" id="status_change_note"
                           placeholder="e.g. Payment received via MTN MoMo ref 12345" />
                </div>

                <button type="button" class="btn btn-sm btn-warning w-100"
                        data-action="change-status" data-id="{{ $request->id }}">
                    Apply Status Change
                </button>

                @php
                    $statusLogs = collect($request->delivery_log ?? [])
                        ->where('type', 'status_change')
                        ->sortByDesc('at')
                        ->take(3);
                @endphp
                @if($statusLogs->isNotEmpty())
                    <div class="separator separator-dashed my-3"></div>
                    <div class="fw-bold fs-8 mb-1">Recent Changes</div>
                    @foreach($statusLogs as $log)
                        <div class="d-flex justify-content-between align-items-start py-1 border-bottom border-light fs-9">
                            <div class="min-w-0">
                                <span class="text-muted">{{ $log['from'] ?? '?' }}</span>
                                <span class="mx-1">→</span>
                                <span class="fw-semibold">{{ $log['to'] ?? '?' }}</span>
                                @if(!empty($log['note']))
                                    <div class="text-muted text-truncate">{{ $log['note'] }}</div>
                                @endif
                            </div>
                            <div class="text-muted text-end flex-shrink-0 ms-2" style="min-width:90px;">
                                {{ !empty($log['at']) ? \Carbon\Carbon::parse($log['at'])->diffForHumans() : '' }}
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

<style>
    .cv-preview {
        font-family: Georgia, "Times New Roman", serif;
        font-size: 0.85rem;
        line-height: 1.5;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .cv-preview h1 { font-size: 1.3rem; font-weight: 700; }
    .cv-preview h2 { font-size: 1.1rem; font-weight: 700; margin-top: 1.2em; border-bottom: 1px solid #e4e6ef; padding-bottom: 0.25em; }
    .cv-preview h3 { font-size: 1rem; font-weight: 700; margin-top: 1em; }
    .cv-preview ul { padding-left: 1.3em; }
    .cv-preview table { max-width: 100%; }
    .letter-spacing-1 { letter-spacing: 0.5px; }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; line-height: 1.2; border-radius: 0.35rem; }
</style>