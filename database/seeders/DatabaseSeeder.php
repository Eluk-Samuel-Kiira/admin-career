<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            CurrencySeeder::class,
            PaymentSourceSeeder::class,
            PaymentPurposeSeeder::class,
            PaymentMethodSeeder::class,
            DepartmentSeeder::class, 
            UsersSeeder::class,       
            DepositSeeder::class,
            ExpenseCategorySeeder::class, 
            ExpenseSeeder::class, 
            SalaryStructureSeeder::class,   
            EmployeeSalarySeeder::class,
            EmployeeSalarySeeder::class,
            IndustrySeeder::class,
            JobLocationSeeder::class,
            SalaryRangeSeeder::class,
            ExperienceLevelSeeder::class,
            EducationLevelSeeder::class,
            JobTypeSeeder::class,
            PageSeeder::class,
            SocialMediaPlatformSeeder::class,

        ]);
    }
}
