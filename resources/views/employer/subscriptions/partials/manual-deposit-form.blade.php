@php
    $currencies = \App\Models\Currency::active()
        ->orderByDesc('is_default')
        ->orderBy('code')
        ->get(['id', 'code', 'symbol', 'decimal_places', 'is_default']);

    $defaultCurrency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
@endphp

<form id="manualDepositForm">
    <div class="row g-4">

        {{-- ── User picker ─────────────────────────────────── --}}
        <div class="col-12">
            <label class="required fw-semibold fs-7 mb-2">User (seeker or employer)</label>
            <select id="md_user" name="user_id" class="form-select form-select-solid" required></select>
            <div class="text-muted fs-8 mt-1">Type a name, email or phone number.</div>
        </div>

        {{-- ── Service picker ──────────────────────────────── --}}
        <div class="col-md-7">
            <label class="required fw-semibold fs-7 mb-2">Service</label>
            <select id="md_service" name="service_key" class="form-select form-select-solid" required>
                <option value="">Select a service…</option>
            </select>
            <div class="text-muted fs-8 mt-1" id="md_service_price">—</div>
        </div>

        {{-- ── Currency + Amount ───────────────────────────── --}}
        <div class="col-md-5">
            <label class="required fw-semibold fs-7 mb-2">Amount</label>
            <div class="input-group">
                <select name="currency_id" id="md_currency_id"
                        class="form-select form-select-solid"
                        style="max-width: 130px;">
                    @forelse($currencies as $cur)
                        <option value="{{ $cur->id }}"
                                data-symbol="{{ $cur->symbol }}"
                                data-decimals="{{ $cur->decimal_places }}"
                                @selected($defaultCurrency && $defaultCurrency->id === $cur->id)>
                            {{ $cur->code }}
                        </option>
                    @empty
                        <option value="">No currency configured</option>
                    @endforelse
                </select>
                <input type="number"
                       step="0.01"
                       min="0"
                       id="md_amount_display"
                       class="form-control form-control-solid"
                       placeholder="0.00"
                       required>
            </div>
            <div class="text-muted fs-8 mt-1">
                Auto-filled from the service price. Editable — the server converts to the base unit.
            </div>
        </div>

        {{-- ── Deposit method ──────────────────────────────── --}}
        <div class="col-md-4">
            <label class="required fw-semibold fs-7 mb-2">Payment method</label>
            <select name="deposit_method" id="md_deposit_method" class="form-select form-select-solid" required>
                <option value="mobile_money">Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="cheque">Cheque</option>
            </select>
        </div>

        {{-- ── Payment source ──────────────────────────────── --}}
        <div class="col-md-4">
            <label class="required fw-semibold fs-7 mb-2">Payment source</label>
            <select name="payment_source_id" class="form-select form-select-solid" required>
                <option value="">Select source…</option>
                @foreach($sources as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ── Gateway channel ─────────────────────────────── --}}
        <div class="col-md-4">
            <label class="required fw-semibold fs-7 mb-2">Gateway channel</label>
            <select name="payment_method_id" class="form-select form-select-solid" required>
                <option value="">Select channel…</option>
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- ── Reference + date ────────────────────────────── --}}
        <div class="col-md-6">
            <label class="fw-semibold fs-7 mb-2">Payment reference (optional)</label>
            <input type="text" name="payment_reference"
                   class="form-control form-control-solid"
                   placeholder="e.g. MPESA-9827341">
        </div>
        <div class="col-md-6">
            <label class="fw-semibold fs-7 mb-2">Deposit date</label>
            <input type="date" name="deposit_date"
                   class="form-control form-control-solid"
                   value="{{ now()->toDateString() }}">
        </div>

        {{-- ── Note ────────────────────────────────────────── --}}
        <div class="col-12">
            <label class="fw-semibold fs-7 mb-2">Note (optional)</label>
            <textarea name="note" rows="2" class="form-control form-control-solid"
                      placeholder="Reason for manual activation, e.g. 'Paid via Airtel Money, receipt shared on WhatsApp'"></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-6">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" id="md_submit">
            <span class="indicator-label">Record Deposit &amp; Activate</span>
            <span class="indicator-progress">Saving… <span class="spinner-border spinner-border-sm ms-2"></span></span>
        </button>
    </div>
</form>