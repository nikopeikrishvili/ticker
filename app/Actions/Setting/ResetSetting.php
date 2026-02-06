<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Repositories\SettingRepository;

class ResetSetting
{
    public function __construct(
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(string $key): mixed
    {
        $this->settingRepository->deleteByKey($key);

        return Setting::DEFAULTS[$key]['value'] ?? null;
    }
}
