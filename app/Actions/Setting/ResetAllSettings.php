<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Repositories\SettingRepository;

class ResetAllSettings
{
    public function __construct(
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(): array
    {
        $this->settingRepository->deleteAll();

        return collect(Setting::DEFAULTS)
            ->mapWithKeys(fn ($config, $key) => [$key => $config['value']])
            ->toArray();
    }
}
