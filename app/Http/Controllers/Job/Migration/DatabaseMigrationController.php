<?php

namespace App\Http\Controllers\Job\Migration;

use App\Http\Controllers\Controller;
use App\Services\Migration\MigrationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Log, DB, Schema };
use Illuminate\Validation\Rule;

class DatabaseMigrationController extends Controller
{
    protected $migrationService;

    public function __construct(MigrationService $migrationService)
    {
        $this->migrationService = $migrationService;
    }

    /**
     * Display migration dashboard
     */
    public function index()
    {
        return view('migrations.dashboard');
    }

    /**
     * List of countries registered for migration (config-driven).
     */
    public function getCountries()
    {
        return response()->json([
            'success' => true,
            'countries' => $this->migrationService->getAvailableCountries(),
            'default_country' => config('migration.default_country', 'AU'),
        ]);
    }

    /**
     * Validate a country code against the registered country list.
     * Returns a JsonResponse on failure, or null if valid.
     */
    protected function validateCountry(?string $country)
    {
        $registered = array_column($this->migrationService->getAvailableCountries(), 'code');

        if ($country && !in_array($country, $registered)) {
            return response()->json([
                'success' => false,
                'message' => "Country [{$country}] is not registered. Add it to config/migration.php ('countries') first.",
                'available_countries' => $registered,
            ], 422);
        }

        return null;
    }

    /**
     * Check legacy database connection and tables
     */
    public function checkLegacyConnection()
    {
        try {
            $pdo = DB::connection('legacy')->getPdo();

            $dbName = DB::connection('legacy')->select('SELECT DATABASE() as db');
            $dbName = $dbName[0]->db ?? 'unknown';

            $tables = DB::connection('legacy')->select('SHOW TABLES');
            $tableList = array_map(function ($table) {
                return (array) $table;
            }, $tables);

            $hasCompaniesTable = Schema::connection('legacy')->hasTable('icop0_js_job_companies');
            $companiesCount = 0;
            $companiesSample = null;

            if ($hasCompaniesTable) {
                $companiesCount = DB::connection('legacy')->table('icop0_js_job_companies')->count();
                $companiesSample = DB::connection('legacy')->table('icop0_js_job_companies')->limit(3)->get();
            }

            return response()->json([
                'success' => true,
                'database' => $dbName,
                'tables' => $tableList,
                'has_companies_table' => $hasCompaniesTable,
                'companies_count' => $companiesCount,
                'companies_sample' => $companiesSample,
                'migration_country' => config('migration.default_country', 'AU'),
                'connection_config' => [
                    'host' => config('database.connections.legacy.host'),
                    'database' => config('database.connections.legacy.database'),
                    'username' => config('database.connections.legacy.username'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Get available tables for migration
     */
    public function getTables()
    {
        try {
            $tables = $this->migrationService->getAvailableTables();

            return response()->json([
                'success' => true,
                'tables' => $tables,
                'migration_country' => config('migration.default_country', 'AU'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get tables: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tables: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get migration statistics for a specific table
     */
    public function getStats(Request $request)
    {
        $tableKey = $request->input('table');
        $country = $request->input('country', config('migration.default_country', 'AU'));

        if (!$tableKey) {
            return response()->json([
                'success' => false,
                'message' => 'Table key is required',
            ], 400);
        }

        if ($invalid = $this->validateCountry($country)) {
            return $invalid;
        }

        try {
            $stats = $this->migrationService->getStats($tableKey, $country);

            if (!$stats) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found',
                ], 404);
            }

            $stats['country_filter'] = $country;

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all stats for all tables with country filter
     */
    public function getAllStats(Request $request)
    {
        $country = $request->input('country', config('migration.default_country', 'AU'));

        if ($invalid = $this->validateCountry($country)) {
            return $invalid;
        }

        try {
            $configs = $this->migrationService->getTableConfigs();
            $stats = [];
            $totalLegacy = 0;
            $totalMigrated = 0;
            $totalPending = 0;

            foreach (array_keys($configs) as $key) {
                try {
                    $stat = $this->migrationService->getStats($key, $country);
                    if ($stat) {
                        $stat['country_filter'] = $country;
                        $stats[$key] = $stat;
                        $totalLegacy += $stat['legacy_count'] ?? 0;
                        $totalMigrated += $stat['migrated'] ?? 0;
                        $totalPending += $stat['pending'] ?? 0;
                    }
                } catch (\Exception $e) {
                    Log::warning("Could not get stats for {$key}: " . $e->getMessage());
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'stats' => $stats,
                'country' => $country,
                'totals' => [
                    'legacy' => $totalLegacy,
                    'migrated' => $totalMigrated,
                    'pending' => $totalPending,
                    'progress' => $totalLegacy > 0 ? round(($totalMigrated / $totalLegacy) * 100, 2) : 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get all stats: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stats: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start migration for a specific table with forced country code + forced id
     */
    public function migrate(Request $request)
    {
        if (!auth()->user()->role('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to migrate legacy data.'
            ]);
        }
        $request->validate([
            'table' => 'required|string',
            'country' => 'nullable|string|size:2',
            'batch_size' => 'nullable|integer|min:1|max:500',
            'offset' => 'nullable|integer|min:0',
        ]);

        $countryCode = $request->input('country') ?? config('migration.default_country', 'AU');

        if ($invalid = $this->validateCountry($countryCode)) {
            return $invalid;
        }

        try {
            $tableKey = $request->input('table');
            $batchSize = $request->input('batch_size', config('migration.batch_size', 100));
            $offset = $request->input('offset', 0);

            // Log::info("Starting migration for {$tableKey} with country: {$countryCode}, offset: {$offset}");

            $results = $this->migrationService->migrate(
                $tableKey,
                $countryCode,
                $batchSize,
                $offset
            );

            $stats = $this->migrationService->getStats($tableKey, $countryCode);

            return response()->json([
                'success' => true,
                'message' => "Migrated {$results['imported']} record(s), skipped {$results['skipped']}, errors " . count($results['errors']) . '.',
                'results' => $results,
                'stats' => $stats,
                'country_used' => $countryCode,
                'has_more' => ($results['processed'] == $batchSize),
                'next_offset' => $offset + $results['processed'],
            ]);
        } catch (\Exception $e) {
            Log::error('Migration failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run full migration for all tables with forced country code
     */
    public function migrateAll(Request $request)
    {
        $request->validate([
            'country' => 'nullable|string|size:2',
        ]);

        $countryCode = $request->input('country') ?? config('migration.default_country', 'AU');

        if ($invalid = $this->validateCountry($countryCode)) {
            return $invalid;
        }

        try {
            $configs = $this->migrationService->getTableConfigs();

            // Log::info("Starting full migration with country: {$countryCode}");

            $results = [];
            $totalImported = 0;
            $totalSkipped = 0;
            $errors = [];
            $tableResults = [];
            $allSteps = [];

            foreach (array_keys($configs) as $tableKey) {
                try {
                    // Log::info("Migrating table: {$tableKey}");
                    $result = $this->migrationService->migrate($tableKey, $countryCode);
                    $results[$tableKey] = $result;
                    $totalImported += $result['imported'];
                    $totalSkipped += $result['skipped'];
                    $allSteps[$tableKey] = $result['steps'] ?? [];

                    $tableResults[] = [
                        'table' => $tableKey,
                        'imported' => $result['imported'],
                        'skipped' => $result['skipped'],
                        'total' => $result['processed'],
                    ];

                    if (!empty($result['errors'])) {
                        $errors[$tableKey] = $result['errors'];
                    }
                } catch (\Exception $e) {
                    $results[$tableKey] = ['error' => $e->getMessage()];
                    Log::error("Migration failed for {$tableKey}: " . $e->getMessage());
                    $errors[$tableKey] = [$e->getMessage()];
                }
            }

            $allStats = [];
            foreach (array_keys($configs) as $key) {
                try {
                    $allStats[$key] = $this->migrationService->getStats($key, $countryCode);
                } catch (\Exception $e) {
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Migrated {$totalImported} record(s) total, skipped {$totalSkipped}.",
                'results' => $results,
                'table_results' => $tableResults,
                'total_imported' => $totalImported,
                'total_skipped' => $totalSkipped,
                'errors' => $errors,
                'steps' => $allSteps,
                'country_used' => $countryCode,
                'stats' => $allStats,
            ]);
        } catch (\Exception $e) {
            Log::error('Full migration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Full migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get table configuration
     */
    public function getTableConfig($tableKey)
    {
        try {
            $configs = $this->migrationService->getTableConfigs();

            if (!isset($configs[$tableKey])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found',
                ], 404);
            }

            $config = $configs[$tableKey];

            return response()->json([
                'success' => true,
                'config' => [
                    'legacy_table' => $config['legacy_table'],
                    'mapping' => $config['mapping'],
                    'defaults' => $config['defaults'],
                    'fields' => array_keys($config['mapping']),
                    'country_forced' => config('migration.default_country', 'AU'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get table config: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test legacy database connection
     */
    public function testConnection()
    {
        try {
            DB::connection('legacy')->getPdo();

            $tables = $this->migrationService->getAvailableTables();

            return response()->json([
                'success' => true,
                'message' => 'Legacy database connection successful',
                'tables_found' => count($tables),
                'tables' => $tables,
                'migration_country' => config('migration.default_country', 'AU'),
                'will_force_country' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Legacy database connection failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Legacy database connection failed: ' . $e->getMessage(),
                'solution' => 'Please check your .env file and make sure LEGACY_DB_* variables are set correctly.',
            ], 500);
        }
    }

    /**
     * Debug - Check legacy table data
     */
    public function debugData(Request $request)
    {
        $tableKey = $request->input('table', 'companies');
        $limit = $request->input('limit', 5);
        $country = $request->input('country', config('migration.default_country', 'AU'));

        try {
            $configs = $this->migrationService->getTableConfigs();
            $config = $configs[$tableKey] ?? null;

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => "Table configuration not found for: {$tableKey}",
                ], 404);
            }

            $legacyTable = $config['legacy_table'];
            $model = $config['model'];
            $countryField = $config['country_field'] ?? 'country_code';

            $records = DB::connection('legacy')
                ->table($legacyTable)
                ->limit($limit)
                ->get();

            $columns = Schema::connection('legacy')->getColumnListing($legacyTable);

            $migratedCount = $model::where($countryField, $country)->count();
            $pendingCount = $model::where($countryField, $country)->whereNull('migrated_at')->count();

            return response()->json([
                'success' => true,
                'table' => $legacyTable,
                'columns' => $columns,
                'sample_records' => $records->map(function ($record) {
                    return (array) $record;
                }),
                'record_count' => DB::connection('legacy')->table($legacyTable)->count(),
                'mapping' => $config['mapping'],
                'country_forced' => $country,
                'migrated_in_country' => $migratedCount,
                'pending_in_country' => $pendingCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Debug data failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get migration progress for all tables
     */
    public function getProgress(Request $request)
    {
        $country = $request->input('country', config('migration.default_country', 'AU'));

        if ($invalid = $this->validateCountry($country)) {
            return $invalid;
        }

        try {
            $configs = $this->migrationService->getTableConfigs();

            $progress = [];
            $totalLegacy = 0;
            $totalMigrated = 0;

            foreach (array_keys($configs) as $key) {
                try {
                    $stat = $this->migrationService->getStats($key, $country);
                    if ($stat) {
                        $legacyCount = $stat['legacy_count'] ?? 0;
                        $migratedCount = $stat['migrated'] ?? 0;
                        $totalLegacy += $legacyCount;
                        $totalMigrated += $migratedCount;

                        $progress[$key] = [
                            'legacy' => $legacyCount,
                            'migrated' => $migratedCount,
                            'pending' => $stat['pending'] ?? 0,
                            'progress' => $legacyCount > 0 ? round(($migratedCount / $legacyCount) * 100, 2) : 0,
                            'status' => $legacyCount == $migratedCount ? 'completed' : ($migratedCount > 0 ? 'in_progress' : 'pending'),
                        ];
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            return response()->json([
                'success' => true,
                'progress' => $progress,
                'country' => $country,
                'overall' => [
                    'total_legacy' => $totalLegacy,
                    'total_migrated' => $totalMigrated,
                    'overall_progress' => $totalLegacy > 0 ? round(($totalMigrated / $totalLegacy) * 100, 2) : 0,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get progress: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get progress: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset migration status for a table - DELETE all records for the country.
     * "id" is auto-increment and is not reset by this (MySQL keeps counting up),
     * which is fine: identity/lookup is always legacy_id + country_code, never
     * the raw id, so a fresh id after re-migrating changes nothing downstream.
     */
    public function resetMigration(Request $request)
    {
        $request->validate([
            'table' => 'required|string',
            'country' => 'nullable|string|size:2',
        ]);

        $country = $request->input('country', config('migration.default_country', 'AU'));

        if ($invalid = $this->validateCountry($country)) {
            return $invalid;
        }

        try {
            $tableKey = $request->input('table');

            $configs = $this->migrationService->getTableConfigs();
            $config = $configs[$tableKey] ?? null;

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Table not found',
                ], 404);
            }

            $model = $config['model'];
            $countryField = $config['country_field'] ?? 'country_code';

            // IMPORTANT: JobPost (and any future model) may use SoftDeletes.
            // A plain ->delete() on a soft-deleting model just sets deleted_at -
            // the row is still physically present and will keep colliding with
            // unique constraints (slug, legacy_id) the moment you re-migrate.
            // withTrashed() + forceDelete() actually removes the rows.
            $usesSoftDeletes = in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($model)
            );

            if ($usesSoftDeletes) {
                $count = $model::withTrashed()->where($countryField, $country)->forceDelete();
            } else {
                $count = $model::where($countryField, $country)->delete();
            }

            // Log::info(($usesSoftDeletes ? "Force-deleted (soft-delete model) " : "Deleted ") . "{$count} records from {$tableKey} in country {$country}");

            return response()->json([
                'success' => true,
                'message' => "Deleted {$count} record(s) from {$tableKey} for country {$country}. Re-running migration will re-create them with the same forced ids.",
                'count' => $count,
                'country' => $country,
                'table' => $tableKey,
            ]);
        } catch (\Exception $e) {
            Log::error('Reset migration failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Reset migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get migration summary
     */
    public function getSummary(Request $request)
    {
        try {
            $country = $request->input('country', config('migration.default_country', 'AU'));
            $configs = $this->migrationService->getTableConfigs();

            $summary = [
                'country' => $country,
                'tables' => [],
                'totals' => [
                    'legacy' => 0,
                    'migrated' => 0,
                    'pending' => 0,
                ],
            ];

            foreach (array_keys($configs) as $key) {
                try {
                    $stat = $this->migrationService->getStats($key, $country);
                    if ($stat) {
                        $summary['tables'][$key] = [
                            'legacy' => $stat['legacy_count'] ?? 0,
                            'migrated' => $stat['migrated'] ?? 0,
                            'pending' => $stat['pending'] ?? 0,
                            'progress' => ($stat['legacy_count'] ?? 0) > 0
                                ? round((($stat['migrated'] ?? 0) / ($stat['legacy_count'] ?? 0)) * 100, 2)
                                : 0,
                        ];
                        $summary['totals']['legacy'] += $stat['legacy_count'] ?? 0;
                        $summary['totals']['migrated'] += $stat['migrated'] ?? 0;
                        $summary['totals']['pending'] += $stat['pending'] ?? 0;
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $summary['totals']['progress'] = $summary['totals']['legacy'] > 0
                ? round(($summary['totals']['migrated'] / $summary['totals']['legacy']) * 100, 2)
                : 0;

            return response()->json([
                'success' => true,
                'summary' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get summary: ' . $e->getMessage(),
            ], 500);
        }
    }
}