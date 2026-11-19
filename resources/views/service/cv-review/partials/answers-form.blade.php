@php
    $formId = $formId ?? 'adminAnswersForm';
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<form id="{{ $formId }}" data-id="{{ $request->id }}">
    @csrf
    @foreach($fields as $field)
        @php
            $val = $answers[$field] ?? '';
            $val = is_array($val) ? implode(', ', $val) : $val;
        @endphp
        <div class="mb-2">
            <label class="fw-semibold fs-8 mb-1">{{ ucwords(str_replace('_', ' ', $field)) }}</label>
            <textarea name="answers[{{ $field }}]" rows="2" class="form-control form-control-sm"
                      placeholder="Enter the answer...">{{ $val }}</textarea>
        </div>
    @endforeach

    <div class="d-flex justify-content-between gap-2 mt-3">
        @if($isEdit)
            <button type="button" class="btn btn-sm btn-light" data-action="cancel-edit-answers">Cancel</button>
        @else
            <button type="button" class="btn btn-sm btn-light-warning"
                    data-action="skip-answers" data-id="{{ $request->id }}">
                User already answered — skip
            </button>
        @endif

        <button type="submit" class="btn btn-sm btn-primary"
                id="{{ $isEdit ? 'saveAnswersEditBtn' : 'submitAnswersBtn' }}">
            <span class="indicator-label">{{ $isEdit ? 'Save Changes' : 'Save Answers' }}</span>
            <span class="indicator-progress">Saving…
                <span class="spinner-border spinner-border-sm ms-2"></span>
            </span>
        </button>
    </div>
</form>