<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class LeaveDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(LeaveRequest::class)) {
            return;
        }

        $operasional = Division::query()->where('name', 'Operasional')->first();
        $it = Division::query()->where('name', 'IT')->first();

        $hod = User::query()->where('email', 'hod@example.com')->first();
        $manager = User::query()->where('email', 'manager@example.com')->first();

        $today = Carbon::today();

        if ($manager) {
            LeaveRequest::firstOrCreate(
                [
                    'user_id' => $manager->id,
                    'type' => 'Cuti Tahunan',
                    'start_date' => $today->copy()->addDays(1)->toDateString(),
                    'end_date' => $today->copy()->addDays(3)->toDateString(),
                ],
                [
                    'division_id' => $operasional?->id ?? $manager->division_id,
                    'reason' => 'Acara keluarga di luar kota. Sudah koordinasi dengan tim.',
                    'status' => 'pending',
                ],
            );

            LeaveRequest::firstOrCreate(
                [
                    'user_id' => $manager->id,
                    'type' => 'Izin Pribadi',
                    'start_date' => $today->copy()->subDays(3)->toDateString(),
                    'end_date' => $today->copy()->subDays(3)->toDateString(),
                ],
                [
                    'division_id' => $operasional?->id ?? $manager->division_id,
                    'reason' => 'Urusan administratif ke kantor pemerintah.',
                    'status' => 'approved',
                    'approved_by' => User::query()->where('email', 'admin@example.com')->value('id'),
                    'approved_at' => now()->subDays(2),
                ],
            );
        }

        if ($hod) {
            LeaveRequest::firstOrCreate(
                [
                    'user_id' => $hod->id,
                    'type' => 'Izin Sakit',
                    'start_date' => $today->copy()->subDays(1)->toDateString(),
                    'end_date' => $today->copy()->subDays(1)->toDateString(),
                ],
                [
                    'division_id' => $operasional?->id ?? $hod->division_id,
                    'reason' => 'Demam tinggi, surat dokter terlampir.',
                    'status' => 'rejected',
                    'rejected_by' => User::query()->where('email', 'director@example.com')->value('id'),
                    'rejected_at' => now()->subDays(1),
                ],
            );
        }

        // Extra sample: user IT kalau ada.
        $itUser = User::query()->where('role', 'manager')->whereHas('division', fn ($q) => $q->where('name', 'IT'))->first();
        if ($itUser) {
            LeaveRequest::firstOrCreate(
                [
                    'user_id' => $itUser->id,
                    'type' => 'Cuti Tahunan',
                    'start_date' => $today->copy()->addDays(5)->toDateString(),
                    'end_date' => $today->copy()->addDays(6)->toDateString(),
                ],
                [
                    'division_id' => $it?->id ?? $itUser->division_id,
                    'reason' => 'Keperluan keluarga.',
                    'status' => 'pending',
                ],
            );
        }
    }
}

