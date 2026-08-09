<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jakarta = Warehouse::query()->where('code', 'WH-JKT')->first();
        $surabaya = Warehouse::query()->where('code', 'WH-SBY')->first();

        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@example.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $superAdmin->assignRole('Super Admin');
        // Super Admin doesn't need warehouse_user rows — access-all is handled
        // in hasAccessToWarehouse() by checking the role first.

        $warehouseAdmin = User::query()->firstOrCreate(
            ['email' => 'wh.admin.jkt@example.com'],
            ['name' => 'Warehouse Admin (Jakarta)', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $warehouseAdmin->assignRole('Warehouse Admin');
        if ($jakarta) {
            $warehouseAdmin->warehouses()->syncWithoutDetaching([$jakarta->id]);
        }

        $supervisor = User::query()->firstOrCreate(
            ['email' => 'supervisor.jkt@example.com'],
            ['name' => 'Supervisor (Jakarta)', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $supervisor->assignRole('Supervisor');
        if ($jakarta) {
            $supervisor->warehouses()->syncWithoutDetaching([$jakarta->id]);
        }

        // A second Warehouse Admin scoped to Surabaya only, to demo that
        // warehouse-scoping actually restricts access (not just role checks).
        $warehouseAdminSby = User::query()->firstOrCreate(
            ['email' => 'wh.admin.sby@example.com'],
            ['name' => 'Warehouse Admin (Surabaya)', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $warehouseAdminSby->assignRole('Warehouse Admin');
        if ($surabaya) {
            $warehouseAdminSby->warehouses()->syncWithoutDetaching([$surabaya->id]);
        }
    }
}
