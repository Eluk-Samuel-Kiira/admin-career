<?php

namespace App\Services\Migration;

use Illuminate\Support\Facades\{DB, Log, Schema};
use Illuminate\Support\Str;
use Carbon\Carbon;

class MigrationService
{
    protected $legacyConnection;
    protected $mainConnection;
    protected $batchSize;
    protected $legacyConnectionName = 'legacy';
    protected $forceCountry;

    public function __construct()
    {
        $this->mainConnection = DB::connection();
        $this->batchSize = config('migration.batch_size', 100);
        $this->forceCountry = config('migration.default_country', 'AU');

        $connections = config('database.connections');

        if (!isset($connections[$this->legacyConnectionName])) {
            Log::error('Legacy database connection not configured');
            throw new \Exception('Legacy database connection [legacy] not configured. Please check your config/database.php');
        }

        $this->legacyConnection = DB::connection($this->legacyConnectionName);
    }

    public function getTableConfigs()
    {
        return [
            'companies' => [
                'legacy_table' => 'icop0_js_job_companies',
                'model' => \App\Models\Job\Company::class,
                'country_field' => 'country_code',
                'default_country' => 'AU',
                'mapping' => [
                    'id' => 'legacy_id',
                    'name' => 'name',
                    'alias' => 'legacy_alias',
                    'url' => 'website',
                    'logofilename' => 'logo',
                    'description' => 'description',
                    'contactname' => 'contact_name',
                    'contactphone' => 'contact_phone',
                    'contactemail' => 'contact_email',
                    'address1' => 'address1',
                    'companysize' => 'company_size',
                    'status' => 'is_active',
                    'isgoldcompany' => 'is_gold',
                    'isfeaturedcompany' => 'is_featured',
                    'startgolddate' => 'gold_start_date',
                    'endgolddate' => 'gold_end_date',
                    'startfeatureddate' => 'featured_start_date',
                    'endfeatureddate' => 'featured_end_date',
                    'hits' => 'hits',
                    'packageid' => 'package_id',
                    'paymenthistoryid' => 'payment_history_id',
                    'uid' => 'legacy_uid',
                ],
                'defaults' => [
                    'is_active' => true,
                    'is_verified' => false,
                    'is_gold' => false,
                    'is_featured' => false,
                    'country_code' => 'AU',
                ],
                'casts' => [
                    'status' => 'boolean',
                    'isgoldcompany' => 'boolean',
                    'isfeaturedcompany' => 'boolean',
                ],
                'dates' => [
                    'startgolddate',
                    'endgolddate',
                    'startfeatureddate',
                    'endfeatureddate',
                    'created',
                    'modified',
                ],
            ],

            'job_categories' => [
                'legacy_table' => 'icop0_js_job_categories',
                'model' => \App\Models\Job\JobCategory::class,
                'country_field' => 'country_code',
                'default_country' => 'AU',
                'mapping' => [
                    'id' => 'legacy_id',
                    'cat_title' => 'name',
                    'alias' => 'slug',
                    'cat_value' => 'legacy_cat_value',
                    'isactive' => 'is_active',
                    'isdefault' => 'is_default',
                    'ordering' => 'sort_order',
                ],
                'defaults' => [
                    'is_active' => true,
                    'country_code' => 'AU',
                    'sort_order' => 0,
                    'is_default' => false,
                ],
                'casts' => [
                    'isactive' => 'boolean',
                    'isdefault' => 'boolean',
                ],
                'dates' => [],
            ],

            'job_posts' => [
                'legacy_table' => 'icop0_js_job_jobs',
                'model' => \App\Models\Job\JobPost::class,
                'country_field' => 'country_code',
                'default_country' => 'AU',
                'mapping' => [
                    'id' => 'legacy_id',
                    'uid' => 'legacy_uid',
                    'companyid' => 'legacy_company_id',
                    'title' => 'job_title',
                    'alias' => 'slug',
                    'description' => 'job_description',
                    'jobcategory' => 'job_category_id',
                    'qualifications' => 'qualifications',
                    'prefferdskills' => 'skills',
                    'applyinfo' => 'application_procedure',
                    'company' => 'company',
                    'country' => 'country',
                    'state' => 'state',
                    'city' => 'city',
                    'zipcode' => 'zipcode',
                    'address1' => 'street_address',
                    'contactname' => 'contact_name',
                    'contactphone' => 'telephone',
                    'contactemail' => 'email',
                    'reference' => 'job_reference',
                    'duration' => 'duration',
                    'heighestfinisheducation' => 'heighest_finished_education',
                    'hits' => 'view_count',
                    'experience' => 'experience_months',
                    'jobstatus' => 'is_active',
                    'isgoldjob' => 'is_urgent',
                    'isfeaturedjob' => 'is_featured',
                    'salaryrangefrom' => 'salary_range_from',
                    'salaryrangeto' => 'salary_range_to',
                    'startpublishing' => 'published_at',
                    'stoppublishing' => 'published_until',
                    'stoppublishing' => 'deadline',
                    'created' => 'created_at',
                ],
                'defaults' => [
                    'is_active' => true,
                    'is_verified' => false,
                    'is_featured' => false,
                    'is_urgent' => false,
                    'currency' => 'AUD',
                    'country_code' => 'AU',
                    'view_count' => 0,
                    'application_count' => 0,
                    'click_count' => 0,
                    'experience_months' => 0,
                ],
                'casts' => [
                    'jobstatus' => 'boolean',
                    'isgoldjob' => 'boolean',
                    'isfeaturedjob' => 'boolean',
                ],
                'dates' => [
                    'created',
                    'modified',
                    'startpublishing',
                    'stoppublishing',
                ],
                'relations' => [
                    'company_id' => [
                        'model' => \App\Models\Job\Company::class,
                        'legacy_field' => 'companyid',
                        'foreign_key' => 'company_id',
                        'lookup_field' => 'legacy_id',
                    ],
                ],
            ],
        ];
    }

    /**
     * Countries currently registered for migration (config-driven, so adding
     * a new one later is a one-line change in config/migration.php).
     */
    public function getAvailableCountries()
    {
        $countries = config('migration.countries', []);

        $result = [];
        foreach ($countries as $code => $meta) {
            $result[] = [
                'code' => $code,
                'name' => $meta['name'] ?? $code,
                'flag' => $meta['flag'] ?? '',
            ];
        }

        return $result;
    }

    /**
     * Whether the given model class uses Laravel's SoftDeletes trait.
     * Needed so exists/uniqueness checks don't miss trashed rows that are
     * still physically in the table (and would collide on unique columns).
     */
    protected function usesSoftDeletes($model)
    {
        return in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($model));
    }

    /**
     * Get migration statistics for a table
     */
    public function getStats($tableKey, $countryCode = null)
    {
        $configs = $this->getTableConfigs();
        $config = $configs[$tableKey] ?? null;

        if (!$config) {
            return null;
        }

        $model = $config['model'];
        $legacyTable = $config['legacy_table'];
        $countryField = $config['country_field'] ?? 'country_code';
        $forceCountry = $countryCode ?? $this->forceCountry;

        $legacyCount = 0;
        try {
            $legacyCount = $this->legacyConnection->table($legacyTable)->count();
        } catch (\Exception $e) {
            Log::warning("Legacy table {$legacyTable} not found: " . $e->getMessage());
        }

        $migratedCount = $model::whereNotNull('migrated_at')->where($countryField, $forceCountry)->count();
        $pendingCount = $model::whereNull('migrated_at')->where($countryField, $forceCountry)->count();
        $totalCount = $model::where($countryField, $forceCountry)->count();

        return [
            'table_key' => $tableKey,
            'legacy_table' => $legacyTable,
            'legacy_count' => $legacyCount,
            'total' => $totalCount,
            'migrated' => $migratedCount,
            'pending' => $pendingCount,
            'active' => $model::where('is_active', true)->where($countryField, $forceCountry)->count(),
            'inactive' => $model::where('is_active', false)->where($countryField, $forceCountry)->count(),
            'country_code' => $forceCountry,
        ];
    }

    /**
     * Migrate data from legacy database - FORCE COUNTRY CODE AND ID
     */
    public function migrate($tableKey, $countryCode = null, $limit = null, $offset = 0)
    {
        $configs = $this->getTableConfigs();
        $config = $configs[$tableKey] ?? null;

        if (!$config) {
            throw new \Exception("Table configuration not found for: {$tableKey}");
        }

        $legacyTable = $config['legacy_table'];
        $model = $config['model'];
        $mapping = $config['mapping'];
        $defaults = $config['defaults'];
        $casts = $config['casts'] ?? [];
        $dateFields = $config['dates'] ?? [];
        $relations = $config['relations'] ?? [];
        $countryField = $config['country_field'] ?? 'country_code';

        // Use passed country code, or fallback to config, or default
        $forceCountry = $countryCode ?? $this->forceCountry ?? 'AU';

        if (!isset(config('migration.countries', [])[$forceCountry])) {
            throw new \Exception(
                "Country [{$forceCountry}] is not registered in config/migration.php ('countries')."
            );
        }

        // Log::info("Forcing country code: {$forceCountry} for migration");

        $steps = [];
        $steps[] = ['step' => 'country_check', 'status' => 'success', 'message' => "Using country [{$forceCountry}]"];

        // Verify legacy connection
        try {
            $this->legacyConnection->getPdo();
            $currentDb = $this->legacyConnection->select('SELECT DATABASE() as db');
            $currentDb = $currentDb[0]->db ?? 'unknown';
            $steps[] = ['step' => 'connect', 'status' => 'success', 'message' => "Connected to legacy database [{$currentDb}]"];
        } catch (\Exception $e) {
            $steps[] = ['step' => 'connect', 'status' => 'error', 'message' => $e->getMessage()];
            throw new \Exception("Legacy database connection failed: " . $e->getMessage());
        }

        // Check if legacy table exists
        if (!Schema::connection('legacy')->hasTable($legacyTable)) {
            $allTables = Schema::connection('legacy')->getTables();
            $tableNames = array_map(function ($table) {
                return $table['name'] ?? $table->name ?? null;
            }, $allTables);

            $steps[] = ['step' => 'verify_table', 'status' => 'error', 'message' => "Table {$legacyTable} not found"];
            Log::error("Legacy table {$legacyTable} not found. Available tables: " . implode(', ', array_filter($tableNames)));
            throw new \Exception("Legacy table {$legacyTable} not found.");
        }

        // Get actual columns from legacy table
        $legacyColumns = Schema::connection('legacy')->getColumnListing($legacyTable);
        $steps[] = ['step' => 'verify_table', 'status' => 'success', 'message' => "Table {$legacyTable} verified with " . count($legacyColumns) . ' columns'];

        // Build query - NO COUNTRY FILTER on legacy data
        $query = $this->legacyConnection->table($legacyTable)->orderBy('id');

        // Apply limit
        if ($limit) {
            $query->limit($limit)->offset($offset);
        }

        $records = $query->get();
        $steps[] = ['step' => 'fetch_records', 'status' => 'success', 'message' => "Fetched {$records->count()} record(s) (offset {$offset}, limit " . ($limit ?: 'all') . ')'];

        // Log::info("Found {$records->count()} records in legacy table {$legacyTable}");

        $results = [
            'imported' => 0,
            'skipped' => 0,
            'errors' => [],
            'processed' => $records->count(),
            'legacy_columns' => $legacyColumns,
            'database' => $currentDb,
            'table' => $legacyTable,
            'country_forced' => $forceCountry,
            'offset' => $offset,
            'steps' => $steps,
        ];

        // If no records found, check if table has data
        if ($records->isEmpty()) {
            $count = $this->legacyConnection->table($legacyTable)->count();
            Log::warning("No records returned, but table has {$count} records.");
        }

        foreach ($records as $record) {
            try {
                $legacyId = $record->id ?? null;

                if (!$legacyId) {
                    $results['skipped']++;
                    continue;
                }

                // Check if already migrated for this country (include trashed rows -
                // they're still physically present and would otherwise collide)
                $existsQuery = $this->usesSoftDeletes($model) ? $model::withTrashed() : $model::query();
                $exists = $existsQuery
                    ->where('legacy_id', $legacyId)
                    ->where($countryField, $forceCountry)
                    ->exists();

                if ($exists) {
                    $results['skipped']++;
                    continue;
                }

                // Build data array
                $data = [];

                // Map fields
                foreach ($mapping as $legacyField => $newField) {
                    if (!in_array($legacyField, $legacyColumns)) {
                        continue;
                    }

                    $value = $record->{$legacyField} ?? null;

                    if (isset($casts[$legacyField])) {
                        $value = $this->castValue($value, $casts[$legacyField]);
                    }

                    if (in_array($legacyField, $dateFields)) {
                        $value = $this->sanitizeDate($value);
                    }

                    $data[$newField] = $value;
                }

                // Apply defaults
                foreach ($defaults as $field => $value) {
                    if (!isset($data[$field]) || $data[$field] === null || $data[$field] === '') {
                        $data[$field] = $value;
                    }
                }

                // FORCE COUNTRY CODE - This overrides anything from legacy
                $data[$countryField] = $forceCountry;

                // Ensure hits has a value (if not set, use 0)
                if (!isset($data['hits']) || $data['hits'] === null) {
                    $data['hits'] = 0;
                }

                // Generate slug
                $data['slug'] = $this->generateSlug($tableKey, $data, $legacyId, $countryField, $forceCountry, $model);

                // Final safety check for edge cases (extremely unlikely with legacy_id appended)
                $slugQuery = $this->usesSoftDeletes($model) ? $model::withTrashed() : $model::query();
                $counter = 0;
                $originalSlug = $data['slug'];
                while ($slugQuery->where('slug', $data['slug'])->where($countryField, $forceCountry)->exists()) {
                    $counter++;
                    $data['slug'] = $originalSlug . '-' . $counter;
                }

                // Final safety check for edge cases (extremely unlikely with legacy_id appended)
                $slugQuery = $this->usesSoftDeletes($model) ? $model::withTrashed() : $model::query();
                $counter = 0;
                $originalSlug = $data['slug'];
                while ($slugQuery->where('slug', $data['slug'])->where($countryField, $forceCountry)->exists()) {
                    $counter++;
                    $data['slug'] = $originalSlug . '-' . $counter;
                }

                // Handle relations
                foreach ($relations as $foreignKey => $relationConfig) {
                    $legacyField = $relationConfig['legacy_field'];
                    if (in_array($legacyField, $legacyColumns) && property_exists($record, $legacyField) && $record->{$legacyField}) {
                        $relatedModel = $relationConfig['model'];
                        $lookupField = $relationConfig['lookup_field'];
                        $related = $relatedModel::where($lookupField, $record->{$legacyField})
                            ->where($countryField, $forceCountry)
                            ->first();
                        if ($related) {
                            $data[$foreignKey] = $related->id;
                        }
                    }
                }

                // Add timestamps
                $data['migrated_at'] = now();
                if (!isset($data['created_at'])) {
                    $data['created_at'] = now();
                }
                if (!isset($data['updated_at'])) {
                    $data['updated_at'] = now();
                }

                // Store full legacy metadata
                $data['legacy_metadata'] = (array) $record;

                $created = $model::create($data);

                $results['imported']++;

                // Log::info("Migrated {$tableKey} record: legacy_id {$legacyId} -> new id {$created->id} (country {$forceCountry})");
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'id' => $record->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
                $results['skipped']++;
                Log::error("Failed to migrate {$tableKey} record: " . $e->getMessage());
            }
        }

        $results['steps'][] = [
            'step' => 'import',
            'status' => count($results['errors']) > 0 ? 'warning' : 'success',
            'message' => "Imported {$results['imported']}, skipped {$results['skipped']}, errors " . count($results['errors']),
        ];

        return $results;
    }

    /**
     * Get available tables for migration
     */
    public function getAvailableTables()
    {
        $configs = $this->getTableConfigs();
        $available = [];

        foreach ($configs as $key => $config) {
            try {
                $legacyCount = $this->legacyConnection->table($config['legacy_table'])->count();
                $available[] = [
                    'key' => $key,
                    'table' => $config['legacy_table'],
                    'record_count' => $legacyCount,
                    'country_field' => $config['country_field'] ?? null,
                ];
            } catch (\Exception $e) {
                continue;
            }
        }

        return $available;
    }

    /**
     * Generate a unique slug with prefix and legacy_id
     */
    protected function generateSlug($tableKey, $data, $legacyId, $countryField, $forceCountry, $model)
    {
        $prefixes = [
            'job_posts' => 'job',
            'companies' => 'company',
            'job_categories' => 'category',
        ];
        
        $prefix = $prefixes[$tableKey] ?? 'item';
        
        // Get base slug from alias, name, or title
        if (!empty($data['slug'])) {
            $baseSlug = $data['slug'];
        } elseif (!empty($data['name'])) {
            $baseSlug = Str::slug($data['name']);
        } elseif (!empty($data['job_title'])) {
            $baseSlug = Str::slug($data['job_title']);
        } else {
            $baseSlug = $prefix;
        }
        
        // For job_posts, companies, and job_categories - prefix with type and append legacy_id
        if (in_array($tableKey, ['job_posts', 'companies', 'job_categories'])) {
            $data['slug'] = $prefix . '-' . $baseSlug . '-' . $legacyId;
        } else {
            // For other tables, just append legacy_id
            $data['slug'] = $baseSlug . '-' . $legacyId;
        }
        
        // Final safety check
        $slugQuery = $this->usesSoftDeletes($model) ? $model::withTrashed() : $model::query();
        $counter = 0;
        $originalSlug = $data['slug'];
        while ($slugQuery->where('slug', $data['slug'])->where($countryField, $forceCountry)->exists()) {
            $counter++;
            $data['slug'] = $originalSlug . '-' . $counter;
        }
        
        return $data['slug'];
    }

    /**
     * Cast value based on type
     */
    protected function castValue($value, $type)
    {
        if ($value === null || $value === '') {
            return null;
        }

        switch ($type) {
            case 'boolean':
                if (is_string($value)) {
                    $value = strtolower(trim($value));
                    return in_array($value, ['1', 'true', 'yes', 'on']);
                }
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'string':
                return (string) $value;
            default:
                return $value;
        }
    }

    /**
     * Sanitize date values
     */
    protected function sanitizeDate($value)
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if ($value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        if (strpos($value, '1970-01-01') === 0) {
            return null;
        }

        if (is_numeric($value) && strlen($value) <= 4) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
            if ($date->year <= 1970) {
                return null;
            }
            return $date;
        } catch (\Exception $e) {
            return null;
        }
    }
}