<?php

namespace Database\Seeders;

use App\Models\BigRock;
use App\Models\Division;
use App\Models\HodAssignment;
use App\Models\LeaveRequest;
use App\Models\ReportSetting;
use App\Models\RoadmapItem;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed example divisions.
        $divisionNames = ['Operasional', 'Keuangan', 'IT', 'Marketing'];
        $divisions = [];

        foreach ($divisionNames as $name) {
            $divisions[$name] = Division::firstOrCreate(
                ['name' => $name],
                ['status' => 'active'],
            );
        }

        // Seed example users for each role to simplify testing.
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'division' => null,
            ],
            [
                'name' => 'Direktur Utama',
                'email' => 'director@example.com',
                'role' => 'director',
                'division' => null,
            ],
            [
                'name' => 'Budi Hartono',
                'email' => 'hod@example.com',
                'role' => 'hod',
                'division' => 'Operasional',
            ],
            [
                'name' => 'Rudi Santoso',
                'email' => 'manager@example.com',
                'role' => 'manager',
                'division' => 'Operasional',
            ],
        ];

        foreach ($users as $data) {
            $divisionId = $data['division'] && isset($divisions[$data['division']])
                ? $divisions[$data['division']]->id
                : null;

            User::updateOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'role' => $data['role'],
                'division_id' => $divisionId,
                'status' => 'active',
                'password' => 'password',
            ]);
        }

        // Seed a default report setting if none exists yet.
        if (! ReportSetting::query()->exists()) {
            ReportSetting::create([
                'plan_open_time' => '07:00',
                'plan_close_time' => '10:00',
                'realization_open_time' => '15:00',
                'realization_close_time' => '23:00',
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        // Seed a sample HoD assignment for Operasional division to make the
        // admin Assignment page immediately meaningful.
        $operasional = $divisions['Operasional'] ?? null;
        $hodUser = User::where('email', 'hod@example.com')->first();

        if ($operasional && $hodUser) {
            HodAssignment::firstOrCreate(
                [
                    'division_id' => $operasional->id,
                ],
                [
                    'hod_id' => $hodUser->id,
                ],
            );
        }

        // Seed example Big Rocks and Roadmaps for HoD and Manager so that
        // Daily Entry dropdowns have meaningful options.
        if ($hodUser) {
            $hodBigRock = BigRock::firstOrCreate(
                [
                    'user_id' => $hodUser->id,
                    'title' => 'Optimasi Proses Operasional Q3',
                ],
                [
                    'description' => 'Meningkatkan efisiensi operasional melalui perbaikan SOP dan sistem.',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->copy()->addMonths(3)->toDateString(),
                    'status' => 'active',
                ],
            );

            RoadmapItem::firstOrCreate(
                [
                    'big_rock_id' => $hodBigRock->id,
                    'title' => 'Implementasi SOP Baru',
                ],
                [
                    'status' => 'planned',
                    'sort_order' => 1,
                ],
            );

            RoadmapItem::firstOrCreate(
                [
                    'big_rock_id' => $hodBigRock->id,
                    'title' => 'Audit Proses Existing',
                ],
                [
                    'status' => 'planned',
                    'sort_order' => 2,
                ],
            );
        }

        if ($managerUser = User::where('email', 'manager@example.com')->first()) {
            $managerBigRock = BigRock::firstOrCreate(
                [
                    'user_id' => $managerUser->id,
                    'title' => 'Perbaikan Kualitas Layanan Cabang',
                ],
                [
                    'description' => 'Meningkatkan kepuasan pelanggan melalui perbaikan proses di cabang.',
                    'start_date' => now()->toDateString(),
                    'end_date' => now()->copy()->addMonths(3)->toDateString(),
                    'status' => 'active',
                ],
            );

            RoadmapItem::firstOrCreate(
                [
                    'big_rock_id' => $managerBigRock->id,
                    'title' => 'Survey Kepuasan Pelanggan',
                ],
                [
                    'status' => 'planned',
                    'sort_order' => 1,
                ],
            );

            RoadmapItem::firstOrCreate(
                [
                    'big_rock_id' => $managerBigRock->id,
                    'title' => 'Perbaikan Proses Antrian',
                ],
                [
                    'status' => 'planned',
                    'sort_order' => 2,
                ],
            );
        }

        // Seed demo data 1 minggu terakhir (khusus HoD & Manager) agar UI terasa lebih "real".
        $this->call(WeeklyDemoDataSeeder::class);

        // Seed demo cuti/izin agar halaman Admin/Director "Cuti & Izin" tidak kosong saat testing.
        if (class_exists(LeaveRequest::class)) {
            $this->call(LeaveDemoSeeder::class);
        }
    }
}
