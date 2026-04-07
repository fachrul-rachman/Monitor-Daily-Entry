<?php

namespace App\Livewire\Admin;

use App\Models\ReportSetting;
use Livewire\Component;

class ReportSettingsPage extends Component
{
    public string $planOpenTime = '07:00';

    public string $planCloseTime = '10:00';

    public string $realizationOpenTime = '15:00';

    public string $realizationCloseTime = '23:00';

    public array $currentSettings = [];

    public bool $hasWarning = false;

    public string $warningMessage = '';

    public function mount(): void
    {
        $setting = ReportSetting::current();

        // Normalisasi ke format H:i agar cocok dengan input type="time" dan validasi date_format:H:i
        $this->planOpenTime = \Illuminate\Support\Carbon::parse($setting->plan_open_time)->format('H:i');
        $this->planCloseTime = \Illuminate\Support\Carbon::parse($setting->plan_close_time)->format('H:i');
        $this->realizationOpenTime = \Illuminate\Support\Carbon::parse($setting->realization_open_time)->format('H:i');
        $this->realizationCloseTime = \Illuminate\Support\Carbon::parse($setting->realization_close_time)->format('H:i');

        $this->refreshCurrentSettings();
        $this->evaluateWarning();
    }

    protected function rules(): array
    {
        return [
            'planOpenTime' => 'required|date_format:H:i',
            'planCloseTime' => 'required|date_format:H:i',
            'realizationOpenTime' => 'required|date_format:H:i',
            'realizationCloseTime' => 'required|date_format:H:i',
        ];
    }

    public function updated($field): void
    {
        $this->validateOnly($field);
        $this->evaluateWarning();
    }

    public function save(): void
    {
        $this->validate();
        $this->evaluateWarning();

        // Update pengaturan aktif saat ini (tidak menambah baris baru setiap kali).
        /** @var \App\Models\ReportSetting|null $setting */
        $setting = ReportSetting::where('is_active', true)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();

        if (! $setting) {
            $setting = new ReportSetting([
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]);
        }

        $setting->plan_open_time = $this->planOpenTime;
        $setting->plan_close_time = $this->planCloseTime;
        $setting->realization_open_time = $this->realizationOpenTime;
        $setting->realization_close_time = $this->realizationCloseTime;
        $setting->is_active = true;
        $setting->effective_from = $setting->effective_from ?? now()->toDateString();
        $setting->save();

        $this->refreshCurrentSettings();
    }

    protected function evaluateWarning(): void
    {
        $this->hasWarning = false;
        $this->warningMessage = '';

        if ($this->planCloseTime <= $this->planOpenTime) {
            $this->hasWarning = true;
            $this->warningMessage = 'Jam tutup plan harus lebih besar dari jam buka.';
        } elseif ($this->realizationCloseTime <= $this->realizationOpenTime) {
            $this->hasWarning = true;
            $this->warningMessage = 'Jam tutup realisasi harus lebih besar dari jam buka.';
        }
    }

    protected function refreshCurrentSettings(): void
    {
        $this->currentSettings = [
            'plan_open' => $this->planOpenTime,
            'plan_close' => $this->planCloseTime,
            'realization_open' => $this->realizationOpenTime,
            'realization_close' => $this->realizationCloseTime,
        ];
    }

    public function render()
    {
        return view('livewire.admin.report-settings-page')
            ->layout('components.layouts.app', [
                'title' => 'Pengaturan Window Laporan',
            ]);
    }
}
