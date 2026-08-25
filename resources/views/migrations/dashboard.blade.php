@extends('layouts.admin')

@section('title', 'Database Migration')
@section('page_title', 'Database Migration Dashboard')

@role('super_admin')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">
        <a href="{{ route('admin.dashboard') }}" class="text-muted text-hover-primary">Home</a>
    </li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Migration</li>
    <li class="breadcrumb-item">
        <span class="bullet bg-gray-500 w-5px h-2px"></span>
    </li>
    <li class="breadcrumb-item text-muted">Database Migration</li>
@endsection

@section('content')
<div class="card card-flush">
    <div class="card-header mt-6">
        <div class="card-title">
            <h2 class="fw-bold">Database Migration Dashboard</h2>
        </div>
        <div class="card-toolbar d-flex gap-3">
            <button type="button" class="btn btn-primary" onclick="testConnection()">
                <i class="ki-duotone ki-database fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i> Test Connection
            </button>
            <button type="button" class="btn btn-success" id="migrateAllBtn" onclick="migrateAll()">
                <i class="ki-duotone ki-arrow-right-square fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i> Migrate All
            </button>
            <button type="button" class="btn btn-warning" onclick="resetMigration()">
                <i class="ki-duotone ki-refresh fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i> Reset Migration
            </button>
        </div>
    </div>

    <div class="card-body pt-0">

        <!-- Country selector -->
        <div class="alert alert-primary d-flex align-items-center mb-7">
            <i class="ki-duotone ki-flag fs-2tx me-3">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <div class="flex-grow-1">
                <strong>Migration Country</strong>
                <div class="d-flex align-items-center gap-3 mt-2">
                    <select id="countrySelect" class="form-select form-select-solid w-auto" style="min-width:220px" onchange="onCountryChange()">
                        <option value="">Loading countries...</option>
                    </select>
                    <span class="text-muted">All records fetched/migrated will be tagged with this country.</span>
                </div>
                <small class="text-muted d-block mt-1">
                    Legacy Database: {{ env('LEGACY_DB_DATABASE', 'Not Set') }}
                </small>
            </div>
        </div>

        <!-- Connection Status -->
        <div id="connectionStatus" class="alert alert-info d-flex align-items-center mb-7">
            <i class="ki-duotone ki-information-5 fs-2tx me-3"></i>
            <div>
                <strong>Database Connection</strong><br>
                <span id="connectionMessage">Testing connection...</span>
            </div>
        </div>

        <!-- Result banner (last action outcome, errors, everything backend sends) -->
        <div id="resultBanner" class="d-none mb-7"></div>

        <!-- Summary Cards -->
        <div class="row g-5 g-xl-10 mb-5" id="statsContainer">
            <div class="col-12">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Migration Overview</h5>
                            <span class="badge badge-light-primary" id="lastUpdated">Last updated: Loading...</span>
                        </div>
                        <div class="row g-3" id="statsCards">
                            <div class="col text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                        <div id="overallProgressContainer" class="mt-4 d-none">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="fw-semibold">Overall Migration Progress</span>
                                <span class="fw-semibold" id="overallProgressPercentage">0%</span>
                            </div>
                            <div class="progress h-25px">
                                <div id="overallProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                            </div>
                            <div class="mt-2 text-muted" id="overallProgressDetails">
                                Total: 0 records | Migrated: 0 | Pending: 0
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Selection -->
        <div class="row g-5 g-xl-10 mb-5">
            <div class="col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <label class="fw-semibold fs-6 mb-2">Select Table to Migrate</label>
                        <select id="tableSelect" class="form-select form-select-solid" onchange="loadTableStats()">
                            <option value="">Loading tables...</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body">
                        <label class="fw-semibold fs-6 mb-2">Actions</label>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success flex-fill" id="migrateSelectedBtn" onclick="migrateTable()">
                                <i class="ki-duotone ki-arrow-right-square fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i> Migrate Selected
                            </button>
                            <button type="button" class="btn btn-light flex-fill" onclick="loadTableStats(); loadAllStats();">
                                <i class="ki-duotone ki-arrows-circle fs-2">
                                    <span class="path1"></span><span class="path2"></span>
                                </i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div id="progressContainer" class="d-none mb-5">
            <div class="card card-flush shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="fw-semibold" id="progressLabel">Migration in progress...</span>
                        <span class="fw-semibold" id="progressPercentage">0%</span>
                    </div>
                    <div class="progress h-25px">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;"></div>
                    </div>
                    <div class="mt-2 text-muted" id="progressDetails">Processing records...</div>
                </div>
            </div>
        </div>

        <!-- Live step status -->
        <div id="stepsContainer" class="d-none mb-5">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Migration Steps</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group" id="stepsList"></ul>
                </div>
            </div>
        </div>

        <!-- Table Stats -->
        <div id="tableStatsContainer" class="d-none">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h5 class="card-title" id="tableStatsTitle">Table Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="tableStatsContent"></div>
                </div>
            </div>
        </div>

        <!-- Record-level errors -->
        <div id="errorsContainer" class="d-none mb-5">
            <div class="card card-flush shadow-sm border border-danger">
                <div class="card-header">
                    <h5 class="card-title mb-0 text-danger">Record Errors (<span id="errorsCount">0</span>)</h5>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light" onclick="document.getElementById('errorsContainer').classList.add('d-none')">Dismiss</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-row-dashed align-middle mb-0">
                            <thead>
                                <tr class="text-muted fw-semibold">
                                    <th class="ps-4">Legacy ID</th>
                                    <th>Table</th>
                                    <th>Error</th>
                                </tr>
                            </thead>
                            <tbody id="errorsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Migration Log -->
        <div id="migrationLog" class="d-none">
            <div class="card card-flush shadow-sm">
                <div class="card-header">
                    <h5 class="card-title">Migration Log</h5>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light" onclick="clearLog()">
                            Clear Log
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="logContent" class="bg-dark p-3 rounded" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-muted" id="logPlaceholder">No migration activity yet.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@endrole

@push('scripts')
<script>
let currentTable = '';
let isMigrating = false;
let currentCountry = '';
let availableTables = [];

document.addEventListener('DOMContentLoaded', function() {
    testConnection();
    loadCountries();
    loadTables();
});

/* ---------------------------------------------------------------- *
 * Small helpers
 * ---------------------------------------------------------------- */

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

async function fetchJson(url, options) {
    const res = await fetch(url, options);
    let data;
    try {
        data = await res.json();
    } catch (e) {
        throw new Error(`Server returned a non-JSON response (HTTP ${res.status})`);
    }
    if (!res.ok && data && data.message === undefined) {
        throw new Error(`Request failed (HTTP ${res.status})`);
    }
    return data;
}

function showBanner(type, title, detail) {
    const banner = document.getElementById('resultBanner');
    banner.classList.remove('d-none');
    banner.className = `alert alert-${type} d-flex flex-column mb-7`;
    banner.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="ki-duotone ki-${type === 'success' ? 'check-circle' : (type === 'warning' ? 'information-5' : 'cross-circle')} fs-2tx me-3"></i>
            <div>
                <strong>${escapeHtml(title)}</strong>
                ${detail ? `<div class="mt-1">${detail}</div>` : ''}
            </div>
        </div>
    `;
}

/* ---------------------------------------------------------------- *
 * Countries
 * ---------------------------------------------------------------- */

function loadCountries() {
    const select = document.getElementById('countrySelect');
    fetchJson('/admin/migration/countries')
        .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed to load countries');
            select.innerHTML = '';
            data.countries.forEach(c => {
                const option = document.createElement('option');
                option.value = c.code;
                option.textContent = `${c.flag ? c.flag + ' ' : ''}${c.name} (${c.code})`;
                select.appendChild(option);
            });
            currentCountry = data.default_country || (data.countries[0] ? data.countries[0].code : 'AU');
            select.value = currentCountry;
            loadAllStats();
        })
        .catch(err => {
            select.innerHTML = '<option value="">Failed to load countries</option>';
            showBanner('danger', 'Could not load country list', escapeHtml(err.message));
        });
}

function onCountryChange() {
    currentCountry = document.getElementById('countrySelect').value;
    loadAllStats();
    if (currentTable) {
        loadTableStats();
    }
}

/* ---------------------------------------------------------------- *
 * Connection / tables / stats
 * ---------------------------------------------------------------- */

function testConnection() {
    const statusDiv = document.getElementById('connectionStatus');
    const messageSpan = document.getElementById('connectionMessage');

    statusDiv.className = 'alert alert-info d-flex align-items-center mb-7';
    messageSpan.textContent = 'Testing connection...';

    fetchJson('/admin/migration/test-connection')
        .then(data => {
            if (data.success) {
                statusDiv.className = 'alert alert-success d-flex align-items-center mb-7';
                messageSpan.textContent = `${data.message} (Found ${data.tables_found} tables)`;
            } else {
                statusDiv.className = 'alert alert-danger d-flex align-items-center mb-7';
                messageSpan.innerHTML = `<strong>${escapeHtml(data.message)}</strong><br><small>${escapeHtml(data.solution || '')}</small>`;
            }
        })
        .catch(err => {
            statusDiv.className = 'alert alert-danger d-flex align-items-center mb-7';
            messageSpan.textContent = 'Connection test failed: ' + err.message;
        });
}

function loadTables() {
    const select = document.getElementById('tableSelect');
    select.innerHTML = '<option value="">Loading...</option>';

    fetchJson('/admin/migration/tables')
        .then(data => {
            select.innerHTML = '<option value="">Select a table...</option>';
            if (data.success && data.tables) {
                availableTables = data.tables;
                data.tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table.key;
                    option.textContent = `${table.table} (${table.record_count} records)`;
                    select.appendChild(option);
                });
            }
        })
        .catch(err => {
            select.innerHTML = '<option value="">Failed to load tables</option>';
            showBanner('danger', 'Could not load tables', escapeHtml(err.message));
        });
}

function loadAllStats() {
    if (!currentCountry) return;
    const container = document.getElementById('statsCards');
    container.innerHTML = `
        <div class="col text-center py-3">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
        </div>
    `;

    fetchJson(`/admin/migration/all-stats?country=${encodeURIComponent(currentCountry)}`)
        .then(data => {
            if (data.success) {
                renderStats(data.stats);
                updateOverallProgress(data.totals);
                document.getElementById('lastUpdated').textContent = 'Last updated: ' + new Date().toLocaleString();
            } else {
                showBanner('danger', 'Failed to load stats', escapeHtml(data.message || ''));
            }
        })
        .catch(err => {
            container.innerHTML = '<div class="col text-center text-danger py-3">Failed to load statistics</div>';
        });
}

function updateOverallProgress(totals) {
    const container = document.getElementById('overallProgressContainer');
    if (!totals || totals.legacy === 0) {
        container.classList.add('d-none');
        return;
    }

    container.classList.remove('d-none');
    const progress = totals.progress || 0;
    document.getElementById('overallProgressBar').style.width = progress + '%';
    document.getElementById('overallProgressPercentage').textContent = progress + '%';
    document.getElementById('overallProgressDetails').textContent =
        `Total: ${totals.legacy} records | Migrated: ${totals.migrated} | Pending: ${totals.pending}`;
}

function renderStats(stats) {
    const container = document.getElementById('statsCards');
    let html = '';
    const colors = ['primary', 'success', 'warning', 'info', 'danger', 'dark'];
    let colorIndex = 0;

    for (const [key, stat] of Object.entries(stats)) {
        if (!stat) continue;
        const color = colors[colorIndex % colors.length];
        colorIndex++;
        const progress = stat.legacy_count > 0 ? Math.round((stat.migrated / stat.legacy_count) * 100) : 0;

        html += `
            <div class="col-xxl-2 col-xl-3 col-lg-4 col-md-6">
                <div class="card card-flush shadow-sm">
                    <div class="card-body py-3 px-4">
                        <div class="d-flex align-items-center">
                            <div class="symbol symbol-35px symbol-circle bg-light-${color} me-2">
                                <i class="ki-duotone ki-tablet fs-2 text-${color}">
                                    <span class="path1"></span><span class="path2"></span>
                                </i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <span class="text-gray-600 fw-semibold d-block text-truncate fs-7">${escapeHtml(key)}</span>
                                <span class="fw-bold text-gray-800" style="font-size: clamp(0.8rem, 2vw, 1.2rem);">
                                    ${stat.migrated || 0}/${stat.legacy_count || 0}
                                </span>
                                <span class="text-muted fs-8 d-block">Pending: ${stat.pending || 0}</span>
                                <div class="progress mt-1" style="height: 4px;">
                                    <div class="progress-bar bg-${color}" role="progressbar" style="width: ${progress}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    container.innerHTML = html || '<div class="col text-center text-muted py-3">No data</div>';
}

function loadTableStats() {
    const tableKey = document.getElementById('tableSelect').value;
    const container = document.getElementById('tableStatsContainer');
    const content = document.getElementById('tableStatsContent');

    if (!tableKey) {
        container.classList.add('d-none');
        return;
    }

    currentTable = tableKey;
    container.classList.remove('d-none');
    document.getElementById('tableStatsTitle').textContent = `Statistics for: ${tableKey} (Country: ${currentCountry})`;
    content.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

    fetchJson(`/admin/migration/stats?table=${encodeURIComponent(tableKey)}&country=${encodeURIComponent(currentCountry)}`)
        .then(data => {
            if (data.success && data.stats) {
                const stat = data.stats;
                const progress = stat.legacy_count > 0 ? Math.round((stat.migrated / stat.legacy_count) * 100) : 0;

                content.innerHTML = `
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">Legacy Records</div>
                            <div class="fw-bold fs-2">${stat.legacy_count || 0}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">Migrated</div>
                            <div class="fw-bold fs-2 text-success">${stat.migrated || 0}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">Pending</div>
                            <div class="fw-bold fs-2 text-warning">${stat.pending || 0}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="border rounded p-3 text-center">
                            <div class="text-muted fs-7">Progress</div>
                            <div class="fw-bold fs-2 text-primary">${progress}%</div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${progress}%;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 mt-3">
                        <div class="border rounded p-3">
                            <div class="text-muted fs-7">Country Filter</div>
                            <div class="fw-bold">${escapeHtml(stat.country_filter || currentCountry)}</div>
                        </div>
                    </div>
                `;
            } else {
                content.innerHTML = `<div class="col-12 text-center text-danger py-3">${escapeHtml(data.message || 'Failed to load statistics')}</div>`;
            }
        })
        .catch(err => {
            content.innerHTML = '<div class="col-12 text-center text-danger py-3">Failed to load statistics</div>';
        });
}

/* ---------------------------------------------------------------- *
 * Steps / progress / errors UI
 * ---------------------------------------------------------------- */

function showProgress(show) {
    document.getElementById('progressContainer').classList.toggle('d-none', !show);
}

function updateProgress(percentage, details) {
    percentage = Math.max(0, Math.min(100, percentage));
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressPercentage').textContent = Math.round(percentage) + '%';
    document.getElementById('progressDetails').textContent = details || 'Processing...';
}

function resetSteps() {
    document.getElementById('stepsContainer').classList.remove('d-none');
    document.getElementById('stepsList').innerHTML = '';
}

function iconForStatus(status) {
    if (status === 'success') return 'text-success ki-check';
    if (status === 'warning') return 'text-warning ki-information-5';
    return 'text-danger ki-cross';
}

function pushSteps(steps, prefix) {
    if (!steps || !steps.length) return;
    const list = document.getElementById('stepsList');
    steps.forEach(step => {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex align-items-center';
        const badgeClass = step.status === 'success' ? 'success' : (step.status === 'warning' ? 'warning' : 'danger');
        li.innerHTML = `
            <span class="badge badge-light-${badgeClass} me-3">${escapeHtml(step.status)}</span>
            <span>${prefix ? `<strong>[${escapeHtml(prefix)}]</strong> ` : ''}${escapeHtml(step.message)}</span>
        `;
        list.appendChild(li);
    });
    list.scrollTop = list.scrollHeight;
}

function pushErrors(errors, tableKey) {
    if (!errors || !errors.length) return;
    const container = document.getElementById('errorsContainer');
    const body = document.getElementById('errorsTableBody');
    container.classList.remove('d-none');

    errors.forEach(err => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4">${escapeHtml(err.id)}</td>
            <td>${escapeHtml(tableKey)}</td>
            <td class="text-danger">${escapeHtml(err.error)}</td>
        `;
        body.appendChild(tr);
    });

    document.getElementById('errorsCount').textContent = body.querySelectorAll('tr').length;
}

function clearErrors() {
    document.getElementById('errorsTableBody').innerHTML = '';
    document.getElementById('errorsCount').textContent = '0';
    document.getElementById('errorsContainer').classList.add('d-none');
}

/* ---------------------------------------------------------------- *
 * Migration (auto-batched so large tables don't silently stop at
 * the first 100 records)
 * ---------------------------------------------------------------- */

async function runBatchedMigration(tableKey, { onBatch } = {}) {
    let offset = 0;
    const batchSize = 100;
    let totalImported = 0;
    let totalSkipped = 0;
    let totalErrors = 0;
    let hasMore = true;
    let guard = 0; // safety valve against infinite loops

    while (hasMore) {
        guard++;
        if (guard > 5000) {
            throw new Error(`Stopped after ${guard} batches - check the table for a runaway loop.`);
        }

        const data = await fetchJson('/admin/migration/migrate', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table: tableKey,
                country: currentCountry,
                batch_size: batchSize,
                offset: offset
            })
        });

        if (!data.success) {
            addLog('danger', `Migration failed for ${tableKey} at offset ${offset}: ${data.message}`);
            throw new Error(data.message || `Migration failed for ${tableKey}`);
        }

        totalImported += data.results.imported || 0;
        totalSkipped += data.results.skipped || 0;
        totalErrors += (data.results.errors || []).length;

        pushSteps(data.results.steps, tableKey);
        pushErrors(data.results.errors, tableKey);

        if (onBatch) {
            onBatch({ tableKey, data, offset, totalImported, totalSkipped, totalErrors });
        }

        addLog(
            (data.results.errors || []).length ? 'warning' : 'success',
            `${tableKey}: batch offset ${offset} -> imported ${data.results.imported}, skipped ${data.results.skipped}, errors ${(data.results.errors || []).length}`
        );

        hasMore = !!data.has_more && data.results.processed > 0;
        offset = data.next_offset ?? (offset + batchSize);
    }

    return { tableKey, totalImported, totalSkipped, totalErrors };
}

function migrateTable() {
    const tableKey = document.getElementById('tableSelect').value;

    if (!tableKey) {
        showBanner('warning', 'Please select a table to migrate');
        return;
    }
    if (isMigrating) {
        showBanner('warning', 'A migration is already in progress');
        return;
    }
    if (!confirm(`Migrate "${tableKey}" for country ${currentCountry}?`)) {
        return;
    }

    isMigrating = true;
    document.getElementById('migrateSelectedBtn').disabled = true;
    document.getElementById('migrateAllBtn').disabled = true;
    clearErrors();
    resetSteps();
    showProgress(true);
    updateProgress(0, `Starting migration for ${tableKey} (Country: ${currentCountry})...`);

    // Use current legacy count as the denominator for a meaningful progress bar.
    fetchJson(`/admin/migration/stats?table=${encodeURIComponent(tableKey)}&country=${encodeURIComponent(currentCountry)}`)
        .then(statData => {
            const legacyTotal = (statData.success && statData.stats) ? (statData.stats.legacy_count || 0) : 0;
            let migratedSoFar = (statData.success && statData.stats) ? (statData.stats.migrated || 0) : 0;

            return runBatchedMigration(tableKey, {
                onBatch: ({ data, totalImported, totalSkipped }) => {
                    migratedSoFar += (data.results.imported || 0);
                    const pct = legacyTotal > 0 ? (migratedSoFar / legacyTotal) * 100 : 0;
                    updateProgress(pct, `${tableKey}: ${migratedSoFar}/${legacyTotal} migrated so far (imported ${totalImported}, skipped ${totalSkipped})`);
                }
            });
        })
        .then(summary => {
            showProgress(false);
            const msg = `Imported ${summary.totalImported}, skipped ${summary.totalSkipped}, errors ${summary.totalErrors}.`;
            if (summary.totalErrors > 0) {
                showBanner('warning', `Migration finished for ${tableKey} with some errors`, msg);
            } else {
                showBanner('success', `Migration completed for ${tableKey}`, msg);
            }
            loadAllStats();
            loadTableStats();
        })
        .catch(err => {
            showProgress(false);
            showBanner('danger', `Migration failed for ${tableKey}`, escapeHtml(err.message));
        })
        .finally(() => {
            isMigrating = false;
            document.getElementById('migrateSelectedBtn').disabled = false;
            document.getElementById('migrateAllBtn').disabled = false;
        });
}

async function migrateAll() {
    if (isMigrating) {
        showBanner('warning', 'A migration is already in progress');
        return;
    }
    if (!availableTables.length) {
        showBanner('warning', 'No tables loaded yet - try Refresh first');
        return;
    }
    if (!confirm(`Migrate ALL tables for country ${currentCountry}? This may take a while.`)) {
        return;
    }

    isMigrating = true;
    document.getElementById('migrateSelectedBtn').disabled = true;
    document.getElementById('migrateAllBtn').disabled = true;
    clearErrors();
    resetSteps();
    showProgress(true);

    let grandImported = 0, grandSkipped = 0, grandErrors = 0;
    const perTableSummary = [];

    try {
        for (let i = 0; i < availableTables.length; i++) {
            const tableKey = availableTables[i].key;
            updateProgress(
                Math.round((i / availableTables.length) * 100),
                `Migrating ${tableKey} (${i + 1}/${availableTables.length})...`
            );

            const summary = await runBatchedMigration(tableKey);
            grandImported += summary.totalImported;
            grandSkipped += summary.totalSkipped;
            grandErrors += summary.totalErrors;
            perTableSummary.push(`${tableKey}: ${summary.totalImported} imported / ${summary.totalSkipped} skipped / ${summary.totalErrors} errors`);
        }

        updateProgress(100, 'All tables processed.');
        showProgress(false);

        const detail = `Total imported: ${grandImported}, skipped: ${grandSkipped}, errors: ${grandErrors}.<br><small>${perTableSummary.map(escapeHtml).join('<br>')}</small>`;
        if (grandErrors > 0) {
            showBanner('warning', 'Full migration finished with some errors', detail);
        } else {
            showBanner('success', 'Full migration completed', detail);
        }

        loadAllStats();
        if (currentTable) loadTableStats();
    } catch (err) {
        showProgress(false);
        showBanner('danger', 'Full migration failed', escapeHtml(err.message));
    } finally {
        isMigrating = false;
        document.getElementById('migrateSelectedBtn').disabled = false;
        document.getElementById('migrateAllBtn').disabled = false;
    }
}

/* ---------------------------------------------------------------- *
 * Reset
 * ---------------------------------------------------------------- */

function resetMigration() {
    const tableKey = document.getElementById('tableSelect').value;

    if (!tableKey) {
        showBanner('warning', 'Please select a table to reset');
        return;
    }
    if (isMigrating) {
        showBanner('warning', 'Please wait for the current migration to finish first');
        return;
    }

    fetchJson(`/admin/migration/stats?table=${encodeURIComponent(tableKey)}&country=${encodeURIComponent(currentCountry)}`)
        .then(data => {
            if (!data.success || !data.stats) {
                showBanner('danger', 'Could not check current record count', escapeHtml(data.message || ''));
                return;
            }

            const count = data.stats.total || 0;
            const legacyCount = data.stats.legacy_count || 0;

            if (count === 0) {
                showBanner('info', 'Nothing to reset', `No records found for "${tableKey}" in country ${currentCountry}.`);
                return;
            }

            const confirmMessage = `⚠️ WARNING: This will DELETE all ${count} records from "${tableKey}" for country ${currentCountry}.\n\n` +
                                  `Legacy records available: ${legacyCount}\n` +
                                  `This action cannot be undone!\n\nContinue?`;

            if (!confirm(confirmMessage)) return;

            fetchJson('/admin/migration/reset', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ table: tableKey, country: currentCountry })
            })
            .then(resetData => {
                if (resetData.success) {
                    showBanner('success', 'Reset complete', escapeHtml(resetData.message));
                    addLog('danger', `RESET: Deleted ${resetData.count} records from ${tableKey} (Country: ${currentCountry})`);
                    clearErrors();
                    loadAllStats();
                    loadTableStats();
                } else {
                    showBanner('danger', 'Reset failed', escapeHtml(resetData.message));
                    addLog('danger', `Reset failed for ${tableKey}: ${resetData.message}`);
                }
            })
            .catch(err => {
                showBanner('danger', 'Reset failed', escapeHtml(err.message));
                addLog('danger', `Reset error for ${tableKey}: ${err.message}`);
            });
        })
        .catch(err => {
            showBanner('danger', 'Could not check current record count', escapeHtml(err.message));
        });
}

/* ---------------------------------------------------------------- *
 * Log
 * ---------------------------------------------------------------- */

function addLog(type, message) {
    const logContainer = document.getElementById('migrationLog');
    const logContent = document.getElementById('logContent');
    const placeholder = document.getElementById('logPlaceholder');

    logContainer.classList.remove('d-none');
    if (placeholder) placeholder.remove();

    const entry = document.createElement('div');
    const timestamp = new Date().toLocaleString();
    entry.className = `text-${type} mb-1`;
    entry.innerHTML = `<span class="text-muted">[${timestamp}]</span> ${escapeHtml(message)}`;
    logContent.appendChild(entry);
    logContent.scrollTop = logContent.scrollHeight;
}

function clearLog() {
    document.getElementById('logContent').innerHTML = '<div class="text-muted" id="logPlaceholder">No migration activity yet.</div>';
    document.getElementById('migrationLog').classList.add('d-none');
}

// Auto-refresh stats every 30 seconds (paused while a migration is running)
setInterval(() => {
    if (!isMigrating && currentCountry) {
        loadAllStats();
    }
}, 30000);
</script>
@endpush