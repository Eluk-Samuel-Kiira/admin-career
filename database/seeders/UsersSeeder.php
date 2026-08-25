<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get departments by code for logical assignment
        $itDept = Department::where('code', 'IT')->first();
        $hrDept = Department::where('code', 'HR')->first();
        $opsDept = Department::where('code', 'OPS')->first();
        $bdDept = Department::where('code', 'BD')->first();
        $maidDept = Department::where('code', 'MAID')->first();
        $jobDept = Department::where('code', 'JOB')->first();
        $cwmDept = Department::where('code', 'CWM')->first();
        $wfmDept = Department::where('code', 'WFM')->first();
        $finDept = Department::where('code', 'FIN')->first();
        $recDept = Department::where('code', 'REC')->first();
        $consDept = Department::where('code', 'CONS')->first();
        $cvsDept = Department::where('code', 'CVS')->first();
        
        // Create Super Admin users (no department)
        $superAdmins = [
            [
                'first_name' => 'Samuel',
                'last_name' => 'Kiraelu',
                'name' => 'Samuel Kiraelu',
                'email' => 'samuelkiiraeluk@gmail.com',
                'password' => 'Samuel@13',
                'phone' => '+256712345678',
                'country_code' => '+256',
                'is_active' => true,
                'department_id' => null,
            ],
            [
                'first_name' => 'Martin',
                'last_name' => 'Mub',
                'name' => 'Martin Mub',
                'email' => 'mubmart7@gmail.com',
                'password' => 'Martin123#)',
                'phone' => '+256723456789',
                'country_code' => '+256',
                'is_active' => true,
                'department_id' => null,
            ],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'name' => 'Super Admin',
                'email' => 'superadmin@lafab.com',
                'password' => 'Admin@1234',
                'phone' => '+256734567890',
                'country_code' => '+256',
                'is_active' => true,
                'department_id' => null,
            ],
        ];

        foreach ($superAdmins as $adminData) {
            $user = User::firstOrCreate(
                ['email' => $adminData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'department_id' => $adminData['department_id'],
                    'first_name' => $adminData['first_name'],
                    'last_name' => $adminData['last_name'],
                    'name' => $adminData['name'],
                    'password' => Hash::make($adminData['password']),
                    'phone' => $adminData['phone'],
                    'country_code' => $adminData['country_code'],
                    'is_active' => $adminData['is_active'],
                    'email_verified_at' => now(),
                ]
            );
            
            $user->assignRole('super_admin');
            $this->command->info("Super Admin created: {$user->email}");
        }

        // Create users with logical department assignments based on role
        $additionalUsers = [
            // Admin users (IT or Operations department)
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@lafab.com',
                'password' => 'Admin@123',
                'phone' => '+256745678901',
                'role' => 'super_admin',
                'department_id' => $itDept?->id ?? $opsDept?->id,
                'is_active' => true,
            ],
            [
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith@lafab.com',
                'password' => 'Admin@123',
                'phone' => '+256756789012',
                'role' => 'admin',
                'department_id' => $hrDept?->id,
                'is_active' => true,
            ],
            
            // Supervisor users (Operations or Workforce Management)
            [
                'first_name' => 'Michael',
                'last_name' => 'Johnson',
                'email' => 'michael.johnson@lafab.com',
                'password' => 'Supervisor@123',
                'phone' => '+256767890123',
                'role' => 'supervisor',
                'department_id' => $opsDept?->id,
                'is_active' => true,
            ],
            [
                'first_name' => 'Sarah',
                'last_name' => 'Williams',
                'email' => 'sarah.williams@lafab.com',
                'password' => 'Supervisor@123',
                'phone' => '+256778901234',
                'role' => 'supervisor',
                'department_id' => $wfmDept?->id,
                'is_active' => true,
            ],
            
            // Moderator users (HR or Customer Support)
            [
                'first_name' => 'David',
                'last_name' => 'Brown',
                'email' => 'david.brown@lafab.com',
                'password' => 'Moderator@123',
                'phone' => '+256789012345',
                'role' => 'moderator',
                'department_id' => $hrDept?->id,
                'is_active' => true,
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Davis',
                'email' => 'emily.davis@lafab.com',
                'password' => 'Moderator@123',
                'phone' => '+256790123456',
                'role' => 'moderator',
                'department_id' => $cvsDept?->id,
                'is_active' => true,
            ],
            
            // Job Poster users (Job Posting or Recruitment)
            [
                'first_name' => 'Robert',
                'last_name' => 'Wilson',
                'email' => 'robert.wilson@lafab.com',
                'password' => 'JobPoster@123',
                'phone' => '+256801234567',
                'role' => 'job_poster',
                'department_id' => $jobDept?->id,
                'is_active' => true,
            ],
            [
                'first_name' => 'Lisa',
                'last_name' => 'Martinez',
                'email' => 'lisa.martinez@lafab.com',
                'password' => 'JobPoster@123',
                'phone' => '+256812345678',
                'role' => 'job_poster',
                'department_id' => $recDept?->id,
                'is_active' => true,
            ],
        ];

        foreach ($additionalUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'uuid' => (string) Str::uuid(),
                    'department_id' => $userData['department_id'],
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'country_code' => '+256',
                    'is_active' => $userData['is_active'],
                    'email_verified_at' => now(),
                ]
            );
            
            $user->assignRole($userData['role']);
            
            $deptName = $user->department ? $user->department->name : 'No Department';
            $this->command->info("User created: {$user->email} as {$userData['role']} - Department: {$deptName}");
        }

        $this->command->info('Users seeded successfully! Total users: ' . User::count());
    }
}