<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ Artisan, Auth };
use App\Models\User;
use Spatie\Permission\Models\Role;

class ArtisanCommandController extends Controller
{
    // Whitelist of allowed commands for security
    protected $allowedCommands = [
        'storage:link'         => 'Create storage symlink',
        'cache:clear'          => 'Clear application cache',
        'config:clear'         => 'Clear config cache',
        'config:cache'         => 'Cache configuration',
        'route:clear'          => 'Clear route cache',
        'route:cache'          => 'Cache routes',
        'view:clear'           => 'Clear compiled views',
        'optimize:clear'       => 'Clear all cached data',
        'optimize'             => 'Cache config, routes & views',
        'queue:restart'        => 'Restart queue workers',
        'migrate'              => 'Run database migrations (forced in production)',
        'migrate:status'       => 'Show migration status',
        'db:seed'              => 'Seed the database',
        'permissions:add-new'  => 'Add more permissions to the db',
        // 'migrate:fresh --seed' => '⚠️ DANGER: Migrate and Seed fresh (force required)',
    ];

    // Commands that automatically get --force in production
    protected $forceInProduction = [
        'migrate',
        'migrate:fresh --seed',
        'db:seed',
    ];

    public function index()
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            abort(403, __('payments.not_authorized'));
        }
        return view('admin.artisan.index', [
            'commands' => $this->allowedCommands,
        ]);
    }

    public function run(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('super_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('payments.not_authorized'),
            ]);
        }

        $request->validate([
            'command' => 'required|string',
        ]);

        $command = $request->input('command');
        $actualCommand = $command;

        // Security check — only allow whitelisted commands
        if (!array_key_exists($command, $this->allowedCommands)) {
            return response()->json([
                'success' => false,
                'output'  => '❌ Command not allowed.',
            ], 403);
        }

        // Force flag for specific commands in production
        if (app()->environment('production') && in_array($command, $this->forceInProduction)) {
            // Check if command already has --force
            if (!str_contains($command, '--force')) {
                $actualCommand = $command . ' --force';
            }
            
            // Special warning for destructive commands
            if ($command === 'migrate:fresh --seed') {
                // Log who is doing this dangerous operation
                \Log::warning('DANGEROUS: migrate:fresh --seed triggered by user', [
                    'user_id' => auth()->id(),
                    'user_email' => auth()->user()->email,
                    'ip' => request()->ip()
                ]);
            }
        }

        try {
            // Run the command with force flag if applicable
            Artisan::call($actualCommand);
            $output = Artisan::output();

            $message = $output ?: '✅ Command executed successfully with no output.';
            
            // Add note about --force if it was applied
            if ($actualCommand !== $command) {
                $message = "⚠️ Production mode: Added --force flag.\n\n" . $message;
            }

            return response()->json([
                'success' => true,
                'command' => 'php artisan ' . $actualCommand,
                'output'  => $message,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'command' => 'php artisan ' . ($actualCommand ?? $command),
                'output'  => '❌ Error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
