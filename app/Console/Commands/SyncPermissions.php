<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;

class SyncPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:sync
                            {--dry-run : Show what would be added without actually adding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync permissions - adds new permissions and assigns them to super_admin role';

    /**
     * Define all permissions grouped by modules
     */
    protected function getPermissions(): array
    {
        return [
            // ============================================================
            // JOBS REPORTS PERMISSIONS
            // ============================================================
            
            'view social media platforms',
            'create social media platforms',
            'edit social media platforms',
            'delete social media platforms',

            // ============================================================
            // Add more permissions here as needed
            // ============================================================
        ];
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = $this->getPermissions();
        $dryRun = $this->option('dry-run');

        // Get or create super_admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        $this->info('🔄 Syncing permissions...');
        $this->newLine();

        $added = 0;
        $skipped = 0;

        foreach ($permissions as $permissionName) {
            // Check if permission already exists
            $exists = Permission::where('name', $permissionName)->exists();

            if ($exists) {
                $this->line("  ⏭️  [SKIPPED] {$permissionName} (already exists)");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("  📝 [DRY RUN] Would add: {$permissionName}");
                $added++;
                continue;
            }

            try {
                // Create the permission
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web'
                ]);

                // Assign to super_admin role
                $superAdminRole->givePermissionTo($permission);

                $this->line("  ✅ [ADDED & ASSIGNED] {$permissionName} → super_admin");
                $added++;
            } catch (\Exception $e) {
                $this->error("  ❌ [ERROR] Failed to add {$permissionName}: " . $e->getMessage());
                $skipped++;
            }
        }

        // Reset cached permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
        $this->line(str_repeat('=', 50));

        if ($dryRun) {
            $this->info('📊 DRY RUN SUMMARY:');
            $this->line("  Would add: {$added}");
            $this->line("  Would skip: {$skipped}");
            $this->newLine();
            $this->info('Run without --dry-run to actually add these permissions.');
        } else {
            $this->info('📊 SYNC SUMMARY:');
            $this->line("  ✅ Added & Assigned to super_admin: {$added}");
            $this->line("  ⏭️  Skipped (already exist): {$skipped}");
            $this->newLine();
            
            if ($added > 0) {
                $this->info('✅ Permissions synced and assigned to super_admin successfully!');
            } else {
                $this->info('ℹ️  No new permissions to add.');
            }
        }

        return 0;
    }
}