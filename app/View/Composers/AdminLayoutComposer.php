<?php

namespace App\View\Composers;

use App\Models\SosAlert;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function __construct(private SettingsService $settings) {}

    public function compose(View $view): void
    {
        $view->with([
            'headerActiveSos' => SosAlert::where('status', 'active')->count(),
            'headerUserCount' => User::count(),
            'maintenanceMode' => (bool) $this->settings->get('maintenance_mode', false),
        ]);
    }
}
