<?php

namespace App\Actions\Setting;

use App\Repositories\SettingRepository;

class UpdateSettingsBatch
{
    public function __construct(
        protected SettingRepository $settingRepository
    ) {}

    public function __invoke(array $settings): array
    {
        $updated = [];

        foreach ($settings as $item) {
            $setting = $this->settingRepository->setValue($item['key'], $item['value']);
            $updated[$setting->key] = $setting->value;
        }

        return $updated;
    }
}
