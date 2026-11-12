@extends('layouts.admin')

@section('title', 'Subscriptions & Payments')
@section('page_title', 'Subscriptions & Payments')

@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">
        @if($initialRole === 'employer') Employers
        @elseif($initialRole === 'seeker') Job Seekers
        @else Subscriptions
        @endif
    </li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-500 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Subscriptions &amp; Payments</li>
@endsection

@section('content')
@can('view subscriptions')

<input type="hidden" id="filterRole" value="{{ $initialRole }}">

{{-- ══════════════════════════════════════════════════════ --}}
{{-- STATS STRIP                                            --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="row g-5 g-xl-8 mb-6">
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-success">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-success" id="statActive">—</div>
                <div class="fw-semibold text-gray-600">Active Subscriptions</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-info">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-info" id="statTrialing">—</div>
                <div class="fw-semibold text-gray-600">Trialing</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-danger">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-danger" id="statPastDue">—</div>
                <div class="fw-semibold text-gray-600">Past Due · Needs Attention</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush bg-light-primary">
            <div class="card-body py-5">
                <div class="fs-2 fw-bold text-primary" id="statPaidMonth">—</div>
                <div class="fw-semibold text-gray-600">Collected This Month</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5 g-xl-8 mb-6">
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-gray-800" id="statCancelled">—</div>
                <div class="text-muted fs-7">Cancelling at period end</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-gray-800" id="statFailed30d">—</div>
                <div class="text-muted fs-7">Failed payments (30 days)</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-gray-800" id="statRefunds30d">—</div>
                <div class="text-muted fs-7">Refunds (30 days)</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-flush">
            <div class="card-body py-4">
                <div class="fs-3 fw-bold text-gray-800" id="statPaidToday">—</div>
                <div class="text-muted fs-7">Collected today</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- MAIN CARD — TABS (Subscriptions | Payments)            --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="card card-flush">
    <div class="card-header align-items-center py-5">
        <h3 class="card-title fw-bold mb-0">
            @if($initialRole === 'employer')
                Employer Subscriptions &amp; Payments
            @elseif($initialRole === 'seeker')
                Seeker Subscriptions &amp; Payments
            @else
                All Subscriptions &amp; Payments
            @endif
        </h3>
        <div class="card-toolbar">
            <button class="btn btn-sm btn-light" id="reloadBtn">
                <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                Reload
            </button>
        </div>
    </div>

    <div class="card-body pt-0">

        {{-- Tabs --}}
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-6 fs-6" id="mainTabs">
            <li class="nav-item">
                <a class="nav-link active" data-tab="subscriptions" href="javascript:;">
                    Subscriptions
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-tab="payments" href="javascript:;">
                    Payments
                    <span class="badge badge-light-primary ms-2" id="paymentsCountBadge">0</span>
                </a>
            </li>
        </ul>

        {{-- ══════════════════════════════════════════════════ --}}
        {{-- PANE: Subscriptions                                --}}
        {{-- ══════════════════════════════════════════════════ --}}
        <div id="paneSubscriptions">

            {{-- Filters --}}
            <div class="row g-3 mb-5">
                <div class="col-md-3">
                    <input type="text" id="search" class="form-control form-control-solid"
                           placeholder="Search by customer or service...">
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select form-select-solid">
                        <option value="">Any Status</option>
                        <option value="trialing">Trialing</option>
                        <option value="active">Active</option>
                        <option value="past_due">Past Due</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterKind" class="form-select form-select-solid">
                        <option value="">Any Kind</option>
                        <option value="subscription">Subscription</option>
                        <option value="one_time">One-Time</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterCountry" class="form-select form-select-solid">
                        <option value="">All Countries</option>
                        @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                            <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <input type="date" id="filterFrom" class="form-control form-control-solid">
                        <input type="date" id="filterTo" class="form-control form-control-solid">
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                    <button class="btn btn-sm btn-light-primary" id="applyFilters">
                        <i class="ki-duotone ki-magnifier fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Apply
                    </button>
                    <button class="btn btn-sm btn-light" id="clearFilters">Clear</button>

                    <span class="text-muted fs-8 ms-auto" id="countsRow">
                        <span class="badge badge-light-secondary me-1" id="countAll">0</span> total
                        <span class="badge badge-light-success ms-2 me-1" id="countActive">0</span> active
                        <span class="badge badge-light-info ms-2 me-1" id="countTrialing">0</span> trialing
                        <span class="badge badge-light-danger ms-2 me-1" id="countPastDue">0</span> past due
                    </span>

                    <select id="perPage" class="form-select form-select-sm w-auto ms-2">
                        <option value="10">10 / page</option>
                        <option value="25" selected>25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                </div>
            </div>

            <div id="tableWrap" class="text-center py-10">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="paginationWrap" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
                <div id="paginationInfo" class="text-muted fs-7"></div>
                <nav><ul class="pagination m-0" id="paginationList"></ul></nav>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════ --}}
        {{-- PANE: Payments                                     --}}
        {{-- ══════════════════════════════════════════════════ --}}
        <div id="panePayments" class="d-none">

            {{-- Filters --}}
            <div class="row g-3 mb-5">
                <div class="col-md-3">
                    <input type="text" id="paySearch" class="form-control form-control-solid"
                           placeholder="Search by customer or reference...">
                </div>
                <div class="col-md-2">
                    <select id="payFilterStatus" class="form-select form-select-solid">
                        <option value="">Any Status</option>
                        <option value="completed">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="payFilterCountry" class="form-select form-select-solid">
                        <option value="">All Countries</option>
                        @foreach(\App\Models\Job\Country::where('is_active', true)->orderBy('name')->get(['code','name','flag']) as $c)
                            <option value="{{ $c->code }}">{{ $c->flag ?? '' }} {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <input type="date" id="payFilterFrom" class="form-control form-control-solid">
                        <input type="date" id="payFilterTo" class="form-control form-control-solid">
                    </div>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                    <button class="btn btn-sm btn-light-primary" id="payApplyFilters">
                        <i class="ki-duotone ki-magnifier fs-4 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        Apply
                    </button>
                    <button class="btn btn-sm btn-light" id="payClearFilters">Clear</button>

                    <span class="text-muted fs-8 ms-auto">
                        <span class="badge badge-light-secondary me-1" id="payCountAll">0</span> total
                        <span class="badge badge-light-success ms-2 me-1" id="payCountCompleted">0</span> paid
                        <span class="badge badge-light-warning ms-2 me-1" id="payCountPending">0</span> pending
                        <span class="badge badge-light-danger ms-2 me-1" id="payCountFailed">0</span> failed
                    </span>

                    <select id="payPerPage" class="form-select form-select-sm w-auto ms-2">
                        <option value="10">10 / page</option>
                        <option value="25" selected>25 / page</option>
                        <option value="50">50 / page</option>
                        <option value="100">100 / page</option>
                    </select>
                </div>
            </div>

            <div id="payTableWrap" class="text-center py-10">
                <div class="spinner-border text-primary"></div>
            </div>

            <div id="payPaginationWrap" class="d-none d-flex flex-wrap justify-content-between align-items-center gap-3 mt-5">
                <div id="payPaginationInfo" class="text-muted fs-7"></div>
                <nav><ul class="pagination m-0" id="payPaginationList"></ul></nav>
            </div>
        </div>

    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- DETAIL MODAL                                           --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="kt_subscription_modal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="fw-bold mb-0">Subscription Details</h3>
                <button type="button" class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>
            <div class="modal-body" id="subDetailBody">
                <div class="text-center py-10">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endcan
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const CSRF  = '{{ csrf_token() }}';
    const ROLE  = document.getElementById('filterRole')?.value || 'all';

    // ─────────────────────────────────────────────────────────
    // Shared state
    // ─────────────────────────────────────────────────────────
    const subsState = {
        page: 1, perPage: 25,
        search: '', status: '', kind: '', country: '', from: '', to: '',
    };

    const payState = {
        page: 1, perPage: 25,
        search: '', status: '', country: '', from: '', to: '',
        loadedOnce: false,
    };

    // ─────────────────────────────────────────────────────────
    // Utilities
    // ─────────────────────────────────────────────────────────
    function esc(t) {
        if (t === null || t === undefined) return '';
        const div = document.createElement('div');
        div.textContent = String(t);
        return div.innerHTML;
    }
    function formatDate(d) {
        if (!d) return '—';
        return new Date(d).toLocaleString();
    }
    function formatMoney(cents, currency = 'UGX') {
        const amount = (cents || 0);
        const decimals = currency === 'UGX' ? 0 : 2;
        const divisor  = currency === 'UGX' ? 1 : 100;
        return `${currency} ${(amount / divisor).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })}`;
    }

    function debounce(fn, wait) {
        let timer;
        return (...a) => { clearTimeout(timer); timer = setTimeout(() => fn(...a), wait); };
    }
    function roleBadge(role) {
        if (role === 'employer')   return '<span class="badge badge-light-primary ms-1">Employer</span>';
        if (role === 'job_seeker') return '<span class="badge badge-light-success ms-1">Seeker</span>';
        return '';
    }
    function emptyState(msg) {
        return `
            <div class="text-center py-15">
                <i class="ki-duotone ki-information-5 fs-5tx text-muted mb-4 d-block">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
                <h4 class="fw-bold text-gray-800 mb-2">Nothing here</h4>
                <p class="text-muted">${esc(msg)}</p>
            </div>`;
    }

    // ─────────────────────────────────────────────────────────
    // Reusable paginator
    // ─────────────────────────────────────────────────────────
    function renderPager(listId, infoId, meta, onPage) {
        const el   = document.getElementById(listId);
        const info = document.getElementById(infoId);
        if (!el || !info) return;

        el.innerHTML = '';
        info.textContent = meta.total
            ? `Showing ${meta.from || 0} to ${meta.to || 0} of ${meta.total}`
            : '0 records';

        if (!meta || meta.last_page <= 1) return;

        const add = (page, text, active = false, disabled = false) => {
            const li = document.createElement('li');
            li.className = `page-item ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = text;
            if (!disabled && !active) {
                a.addEventListener('click', (e) => { e.preventDefault(); onPage(page); });
            }
            li.appendChild(a);
            el.appendChild(li);
        };

        add(meta.current_page - 1, 'Prev', false, meta.current_page <= 1);
        const start = Math.max(1, meta.current_page - 2);
        const end   = Math.min(meta.last_page, meta.current_page + 2);
        if (start > 1) add(1, '1');
        if (start > 2) el.insertAdjacentHTML('beforeend',
            '<li class="page-item disabled"><span class="page-link">…</span></li>');
        for (let i = start; i <= end; i++) add(i, String(i), i === meta.current_page);
        if (end < meta.last_page - 1) el.insertAdjacentHTML('beforeend',
            '<li class="page-item disabled"><span class="page-link">…</span></li>');
        if (end < meta.last_page) add(meta.last_page, String(meta.last_page));
        add(meta.current_page + 1, 'Next', false, meta.current_page >= meta.last_page);
    }

    // ─────────────────────────────────────────────────────────
    // Tabs
    // ─────────────────────────────────────────────────────────
    function bindTabs() {
        document.querySelectorAll('#mainTabs [data-tab]').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('#mainTabs .nav-link').forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                const which = tab.dataset.tab;
                document.getElementById('paneSubscriptions').classList.toggle('d-none', which !== 'subscriptions');
                document.getElementById('panePayments').classList.toggle('d-none', which !== 'payments');

                if (which === 'payments' && !payState.loadedOnce) {
                    payState.loadedOnce = true;
                    loadPayments();
                }
            });
        });
    }

    // ─────────────────────────────────────────────────────────
    // STATS
    // ─────────────────────────────────────────────────────────
    async function loadStats() {
        try {
            const url = new URL('{{ route("admin.employer-subscriptions.stats") }}');
            url.searchParams.set('role', ROLE);

            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.success) return;

            const s = data.stats;
            document.getElementById('statActive').textContent    = s.active_subs ?? 0;
            document.getElementById('statTrialing').textContent  = s.trialing_subs ?? 0;
            document.getElementById('statPastDue').textContent   = s.past_due_subs ?? 0;
            document.getElementById('statCancelled').textContent = s.cancelled_subs ?? 0;
            document.getElementById('statFailed30d').textContent = s.failed_payments_30d ?? 0;
            document.getElementById('statRefunds30d').textContent= s.refunds_30d ?? 0;
            
            document.getElementById('statPaidMonth').textContent = formatMoney(s.paid_this_month ?? 0, s.currency || 'UGX');
            document.getElementById('statPaidToday').textContent = formatMoney(s.paid_today ?? 0, s.currency || 'UGX');
        } catch (e) { /* ignore */ }
    }

    // ─────────────────────────────────────────────────────────
    // SUBSCRIPTIONS
    // ─────────────────────────────────────────────────────────
    async function loadSubs() {
        const wrap  = document.getElementById('tableWrap');
        const pager = document.getElementById('paginationWrap');
        pager.classList.add('d-none');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams({
            page: subsState.page,
            per_page: subsState.perPage,
            role: ROLE,
        });
        ['search','status','kind','country','from','to'].forEach(k => {
            if (subsState[k]) params.set(k, subsState[k]);
        });

        try {
            const res = await fetch(`{{ route("admin.employer-subscriptions.data") }}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (data.counts) {
                document.getElementById('countAll').textContent      = data.counts.all ?? 0;
                document.getElementById('countActive').textContent   = data.counts.active ?? 0;
                document.getElementById('countTrialing').textContent = data.counts.trialing ?? 0;
                document.getElementById('countPastDue').textContent  = data.counts.past_due ?? 0;
            }

            const rows = data.data?.data || [];
            if (!data.success || rows.length === 0) {
                wrap.innerHTML = emptyState('No subscriptions match.');
                return;
            }

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start min-w-220px">Customer</th>
                                <th class="text-start min-w-180px">Service</th>
                                <th class="text-start min-w-100px">Kind</th>
                                <th class="text-start min-w-120px">Amount</th>
                                <th class="text-start min-w-90px">Payments</th>
                                <th class="text-start min-w-150px">Status</th>
                                <th class="text-start min-w-140px">Created</th>
                                <th class="text-end min-w-100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${rows.map(subscriptionRow).join('')}
                        </tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('[data-view]').forEach(btn => {
                btn.addEventListener('click', () => openDetail(btn.dataset.view));
            });

            renderPager('paginationList', 'paginationInfo', data.pagination, (page) => {
                subsState.page = page;
                loadSubs();
            });
            pager.classList.remove('d-none');
        } catch (e) {
            wrap.innerHTML = '<div class="alert alert-danger">Failed to load subscriptions.</div>';
        }
    }

    function subscriptionRow(s) {
        return `
            <tr>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">
                        ${esc(s.user)}
                        ${ROLE === 'all' ? roleBadge(s.user_role) : ''}
                    </div>
                    <div class="text-muted fs-8">${esc(s.email || '')}</div>
                </td>
                <td class="text-start">
                    <div class="fw-semibold text-gray-800">${esc(s.service_name)}</div>
                    <div class="text-muted fs-8">${esc(s.service_key)}</div>
                </td>
                <td class="text-start">
                    <span class="badge badge-light-${s.kind === 'one_time' ? 'primary' : 'info'}">
                        ${esc(s.kind_label)}
                    </span>
                </td>
                <td class="text-start fw-bold text-gray-800">${esc(s.amount_formatted)}</td>
                <td class="text-start">
                    <span class="badge badge-light-secondary me-1">${s.payment_count}</span>
                    <span class="text-muted fs-8">${s.payment_success} ok</span>
                </td>
                <td class="text-start">${s.status_badge}</td>
                <td class="text-start text-muted fs-8">${formatDate(s.created_at)}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-icon btn-light" data-view="${esc(s.uuid)}" title="View">
                        <i class="ki-duotone ki-eye fs-3">
                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                        </i>
                    </button>
                </td>
            </tr>`;
    }

    // ─────────────────────────────────────────────────────────
    // PAYMENTS
    // ─────────────────────────────────────────────────────────
    async function loadPayments() {
        const wrap  = document.getElementById('payTableWrap');
        const pager = document.getElementById('payPaginationWrap');
        pager.classList.add('d-none');
        wrap.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        const params = new URLSearchParams({
            page: payState.page,
            per_page: payState.perPage,
            role: ROLE,
        });
        ['search','status','country','from','to'].forEach(k => {
            if (payState[k]) params.set(k, payState[k]);
        });

        try {
            const res = await fetch(`{{ route("admin.employer-subscriptions.payments") }}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (data.counts) {
                document.getElementById('payCountAll').textContent       = data.counts.all ?? 0;
                document.getElementById('payCountCompleted').textContent = data.counts.completed ?? 0;
                document.getElementById('payCountPending').textContent   = data.counts.pending ?? 0;
                document.getElementById('payCountFailed').textContent    = data.counts.failed ?? 0;
                document.getElementById('paymentsCountBadge').textContent = data.counts.all ?? 0;
            }

            if (!data.success || !data.data?.length) {
                wrap.innerHTML = emptyState('No payments match.');
                return;
            }

            wrap.innerHTML = `
                <div class="table-responsive">
                    <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                        <thead>
                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                <th class="text-start min-w-220px">Customer</th>
                                <th class="text-start min-w-160px">Purpose</th>
                                <th class="text-start min-w-140px">Amount</th>
                                <th class="text-start min-w-120px">Status</th>
                                <th class="text-start min-w-240px">Reference</th>
                                <th class="text-start min-w-160px">When</th>
                                <th class="text-end min-w-100px">Subscription</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.data.map(paymentRow).join('')}
                        </tbody>
                    </table>
                </div>`;

            wrap.querySelectorAll('[data-open-sub]').forEach(btn => {
                btn.addEventListener('click', () => openDetail(btn.dataset.openSub));
            });

            renderPager('payPaginationList', 'payPaginationInfo', data.pagination, (page) => {
                payState.page = page;
                loadPayments();
            });
            pager.classList.remove('d-none');
        } catch (e) {
            wrap.innerHTML = '<div class="alert alert-danger">Failed to load payments.</div>';
        }
    }

    function paymentRow(p) {
        const color = {
            completed: 'success',
            pending:   'info',
            failed:    'danger',
            cancelled: 'secondary',
            approved:  'primary',
            refunded:  'warning',
        }[p.status] || 'secondary';

        const purposeColor = {
            subscription:     'primary',
            cv_review:        'info',
            cv_rewrite:       'success',
            cv_rewrite_human: 'success',
            cover_letter:     'warning',
            job_submission:   'dark',
        }[p.purpose_type] || 'secondary';

        return `
            <tr>
                <td class="text-start">
                    <div class="fw-bold text-gray-800">
                        ${esc(p.user)}
                        ${ROLE === 'all' ? roleBadge(p.user_role) : ''}
                    </div>
                    <div class="text-muted fs-8">${esc(p.email || '')}</div>
                </td>
                <td class="text-start">
                    <span class="badge badge-light-${purposeColor}">${esc(p.purpose_label)}</span>
                    ${p.description ? `<div class="text-muted fs-8 mt-1">${esc(p.description.substring(0, 50))}</div>` : ''}
                </td>
                <td class="text-start fw-bold text-gray-800">${esc(p.amount_formatted)}</td>
                <td class="text-start">
                    <span class="badge badge-light-${color}">${esc(p.status_label)}</span>
                </td>
                <td class="text-start text-muted fs-8">
                    <div>${esc(p.gateway)}</div>
                    <div style="font-family:monospace;">${esc(p.reference || '—')}</div>
                </td>
                <td class="text-start text-muted fs-8">
                    ${p.paid_at ? 'Paid ' + formatDate(p.paid_at) : 'Attempted ' + formatDate(p.created_at)}
                </td>
                <td class="text-end">
                    ${['subscription', 'premium_alerts', 'featured_applicant', 'auto_apply_subscription'].includes(p.purpose_type) && p.purpose_reference
                        ? `<button class="btn btn-sm btn-icon btn-light" data-open-sub="${esc(p.purpose_reference)}" title="View subscription">
                            <i class="ki-duotone ki-eye fs-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                        </button>`
                        : '<span class="text-muted fs-8">—</span>'}
                </td>
            </tr>`;
    }

    // ─────────────────────────────────────────────────────────
    // DETAIL MODAL
    // ─────────────────────────────────────────────────────────
    async function openDetail(uuid) {
        const body = document.getElementById('subDetailBody');
        body.innerHTML = '<div class="text-center py-10"><div class="spinner-border text-primary"></div></div>';

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('kt_subscription_modal'));
        modal.show();

        try {
            const res = await fetch(`{{ url('admin/employer-subscriptions') }}/${uuid}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.success) {
                body.innerHTML = `<div class="alert alert-danger">${esc(data.message || 'Failed to load.')}</div>`;
                return;
            }

            body.innerHTML = renderDetail(data);
        } catch (e) {
            body.innerHTML = '<div class="alert alert-danger">Network error.</div>';
        }
    }

    function renderDetail(data) {
        const s = data.subscription;
        const payments = data.payments || [];
        const webhooks = data.webhooks || [];

        const paymentRows = payments.length
            ? payments.map(p => `
                <tr>
                    <td class="text-start">
                        <span class="fw-bold">${esc(p.gateway || '—')}</span>
                        <div class="text-muted fs-8">${esc(p.gateway_reference || '')}</div>
                    </td>
                    <td class="text-start">${esc(p.amount_formatted)}</td>
                    <td class="text-start">${p.status_badge}</td>
                    <td class="text-start text-muted fs-8">${p.failure_reason ? esc(p.failure_reason) : '—'}</td>
                    <td class="text-start text-muted fs-8">${p.paid_at ? formatDate(p.paid_at) : formatDate(p.created_at)}</td>
                </tr>`).join('')
            : '<tr><td colspan="5" class="text-center text-muted py-4">No payments recorded.</td></tr>';

        const webhookRows = webhooks.length
            ? webhooks.map(w => `
                <tr>
                    <td class="text-start">${esc(w.gateway)}</td>
                    <td class="text-start">${esc(w.event_type || '—')}</td>
                    <td class="text-start">
                        <span class="badge badge-light-${w.signature_valid ? 'success' : 'danger'}">
                            ${w.signature_valid ? 'Valid' : 'Invalid'}
                        </span>
                    </td>
                    <td class="text-start">
                        <span class="badge badge-light-${w.status === 'processed' ? 'success' : (w.status === 'failed' ? 'danger' : 'secondary')}">
                            ${esc(w.status)}
                        </span>
                    </td>
                    <td class="text-start text-muted fs-8">
                        ${w.error_message ? esc(w.error_message.substring(0, 60)) : '—'}
                    </td>
                    <td class="text-start text-muted fs-8">${formatDate(w.created_at)}</td>
                </tr>`).join('')
            : '<tr><td colspan="6" class="text-center text-muted py-4">No webhook events for this subscription.</td></tr>';

        return `
            <div class="row g-5 mb-5">
                <div class="col-md-6">
                    <div class="d-flex align-items-center gap-3">
                        <div class="symbol symbol-50px">
                            <span class="symbol-label bg-light-primary">
                                <i class="ki-duotone ki-profile-circle fs-2x text-primary">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fw-bold fs-5">
                                ${esc(s.user)}
                                ${roleBadge(s.user_role)}
                            </div>
                            <div class="text-muted fs-7">${esc(s.email || '')}</div>
                            <div class="text-muted fs-8">${esc(s.phone || '')}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="fw-bold fs-4">${esc(s.service_name)}</div>
                    <div class="text-muted fs-7">${esc(s.service_key)}</div>
                    <div class="mt-2">${s.status_badge}</div>
                </div>
            </div>

            <div class="separator my-5"></div>

            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Kind</div>
                    <div class="fw-bold">${esc(s.kind === 'one_time' ? 'One-Time' : 'Subscription')}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Amount</div>
                    <div class="fw-bold">${esc(s.amount_formatted)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Interval</div>
                    <div class="fw-bold">${esc(s.interval)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Country</div>
                    <div class="fw-bold">${esc(s.country_code)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Starts At</div>
                    <div class="fw-semibold">${formatDate(s.starts_at)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Current Period Start</div>
                    <div class="fw-semibold">${formatDate(s.current_period_start)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Current Period End</div>
                    <div class="fw-semibold">${formatDate(s.current_period_end)}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-8 text-uppercase">Trial Ends At</div>
                    <div class="fw-semibold">${s.trial_ends_at ? formatDate(s.trial_ends_at) : '—'}</div>
                </div>
                ${s.cancel_at_period_end ? `
                    <div class="col-md-12">
                        <div class="alert alert-light-warning mb-0">
                            <strong>Cancels at period end.</strong>
                            Access ends ${formatDate(s.current_period_end)}.
                        </div>
                    </div>
                ` : ''}
            </div>

            <h5 class="fw-bold mt-6 mb-3">Payments</h5>
            <div class="table-responsive mb-6">
                <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase">
                            <th class="text-start">Gateway</th>
                            <th class="text-start">Amount</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Reason</th>
                            <th class="text-start">Date</th>
                        </tr>
                    </thead>
                    <tbody>${paymentRows}</tbody>
                </table>
            </div>

            <h5 class="fw-bold mb-3">Webhook Events</h5>
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed table-row-gray-300 fs-7 gy-3">
                    <thead>
                        <tr class="text-start text-gray-500 fw-bold fs-8 text-uppercase">
                            <th class="text-start">Gateway</th>
                            <th class="text-start">Event</th>
                            <th class="text-start">Signature</th>
                            <th class="text-start">Status</th>
                            <th class="text-start">Error</th>
                            <th class="text-start">When</th>
                        </tr>
                    </thead>
                    <tbody>${webhookRows}</tbody>
                </table>
            </div>`;
    }

    // ─────────────────────────────────────────────────────────
    // Filter bindings — Subscriptions
    // ─────────────────────────────────────────────────────────
    document.getElementById('applyFilters')?.addEventListener('click', () => {
        subsState.search  = document.getElementById('search').value.trim();
        subsState.status  = document.getElementById('filterStatus').value;
        subsState.kind    = document.getElementById('filterKind').value;
        subsState.country = document.getElementById('filterCountry').value;
        subsState.from    = document.getElementById('filterFrom').value;
        subsState.to      = document.getElementById('filterTo').value;
        subsState.page    = 1;
        loadSubs();
    });

    document.getElementById('clearFilters')?.addEventListener('click', () => {
        ['search','filterStatus','filterKind','filterCountry','filterFrom','filterTo']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        Object.assign(subsState, { search:'', status:'', kind:'', country:'', from:'', to:'', page:1 });
        loadSubs();
    });

    document.getElementById('search')?.addEventListener('input', debounce(() => {
        document.getElementById('applyFilters').click();
    }, 400));

    document.getElementById('perPage')?.addEventListener('change', function () {
        subsState.perPage = parseInt(this.value) || 25;
        subsState.page = 1;
        loadSubs();
    });

    // ─────────────────────────────────────────────────────────
    // Filter bindings — Payments
    // ─────────────────────────────────────────────────────────
    document.getElementById('payApplyFilters')?.addEventListener('click', () => {
        payState.search  = document.getElementById('paySearch').value.trim();
        payState.status  = document.getElementById('payFilterStatus').value;
        payState.country = document.getElementById('payFilterCountry').value;
        payState.from    = document.getElementById('payFilterFrom').value;
        payState.to      = document.getElementById('payFilterTo').value;
        payState.page    = 1;
        loadPayments();
    });

    document.getElementById('payClearFilters')?.addEventListener('click', () => {
        ['paySearch','payFilterStatus','payFilterCountry','payFilterFrom','payFilterTo']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        Object.assign(payState, { search:'', status:'', country:'', from:'', to:'', page:1 });
        loadPayments();
    });

    document.getElementById('paySearch')?.addEventListener('input', debounce(() => {
        document.getElementById('payApplyFilters').click();
    }, 400));

    document.getElementById('payPerPage')?.addEventListener('change', function () {
        payState.perPage = parseInt(this.value) || 25;
        payState.page = 1;
        loadPayments();
    });

    // ─────────────────────────────────────────────────────────
    // Reload
    // ─────────────────────────────────────────────────────────
    document.getElementById('reloadBtn')?.addEventListener('click', () => {
        loadStats();
        loadSubs();
        if (payState.loadedOnce) loadPayments();
    });

    // ─────────────────────────────────────────────────────────
    // Boot
    // ─────────────────────────────────────────────────────────
    bindTabs();
    loadStats();
    loadSubs();

    // Load payments count on boot so the badge isn't stuck at 0
    (async () => {
        try {
            const url = new URL('{{ route("admin.employer-subscriptions.payments") }}');
            url.searchParams.set('role', ROLE);
            url.searchParams.set('per_page', '1');

            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.success && data.counts) {
                document.getElementById('paymentsCountBadge').textContent = data.counts.all ?? 0;
            }
        } catch (e) { /* ignore */ }
    })();
})();
</script>
@endpush