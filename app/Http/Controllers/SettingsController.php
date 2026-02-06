<?php

namespace App\Http\Controllers;

use App\Actions\Setting\ResetAllSettings;
use App\Actions\Setting\ResetSetting;
use App\Actions\Setting\UpdateSetting;
use App\Actions\Setting\UpdateSettingsBatch;
use App\Http\Requests\Setting\ResetSettingRequest;
use App\Http\Requests\Setting\UpdateSettingRequest;
use App\Http\Requests\Setting\UpdateSettingsBatchRequest;
use App\Models\Setting;
use App\Repositories\SettingRepository;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        protected SettingRepository $settingRepository
    ) {}

    public function index(): JsonResponse
    {
        $timezone = $this->settingRepository->getValue('general.timezone', 'Asia/Tbilisi');
        $now = now()->setTimezone($timezone);

        return response()->json([
            'settings' => $this->settingRepository->getAllSettings(),
            'defaults' => collect(Setting::DEFAULTS)->mapWithKeys(fn ($config, $key) => [$key => $config['value']]),
            'server_date' => $now->format('Y-m-d'),
            'server_time' => $now->format('H:i:s'),
        ]);
    }

    public function category(string $category): JsonResponse
    {
        return response()->json([
            'settings' => $this->settingRepository->getByCategory($category),
        ]);
    }

    public function update(UpdateSettingRequest $request, UpdateSetting $updateSetting): JsonResponse
    {
        $setting = $updateSetting($request->key, $request->value);

        return response()->json([
            'success' => true,
            'setting' => [
                'key' => $setting->key,
                'value' => $setting->value,
            ],
        ]);
    }

    public function updateBatch(UpdateSettingsBatchRequest $request, UpdateSettingsBatch $updateBatch): JsonResponse
    {
        $updated = $updateBatch($request->settings);

        return response()->json([
            'success' => true,
            'settings' => $updated,
        ]);
    }

    public function reset(ResetSettingRequest $request, ResetSetting $resetSetting): JsonResponse
    {
        $defaultValue = $resetSetting($request->key);

        return response()->json([
            'success' => true,
            'key' => $request->key,
            'value' => $defaultValue,
        ]);
    }

    public function resetAll(ResetAllSettings $resetAll): JsonResponse
    {
        $defaults = $resetAll();

        return response()->json([
            'success' => true,
            'settings' => $defaults,
        ]);
    }
}
