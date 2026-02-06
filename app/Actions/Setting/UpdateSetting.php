<?php

namespace App\Actions\Setting;

use App\Models\Setting;
use App\Repositories\SettingRepository;

class UpdateSetting
{
    public function __construct(
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(string $key, mixed $value): Setting
    {
        return $this->settingRepository->setValue($key, $value);
    }
}
