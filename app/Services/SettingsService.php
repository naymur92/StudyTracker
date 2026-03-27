<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    protected $cacheKey = 'app_settings';
    protected $cacheDuration = 3600; // 1 hour

    /**
     * Get a setting value with caching
     */
    public function get($key, $default = null)
    {
        $settings = $this->getAllCached();

        if (isset($settings[$key])) {
            return $this->castValue($settings[$key]['value'], $settings[$key]['type']);
        }

        return $default;
    }

    /**
     * Set a setting value and update cache
     */
    public function set($key, $value, $type = 'string', $group = 'general', $description = null)
    {
        $setting = Setting::set($key, $value, $type, $group, $description);

        // Clear cache
        $this->clearCache();

        return $setting;
    }

    /**
     * Get all settings with caching
     */
    public function getAllCached()
    {
        return Cache::remember($this->cacheKey, $this->cacheDuration, function () {
            return Setting::all()->keyBy('key')->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public function getByGroup($group)
    {
        return Setting::where('group', $group)->get()->keyBy('key');
    }

    /**
     * Clear settings cache
     */
    public function clearCache()
    {
        Cache::forget($this->cacheKey);
    }

    /**
     * Cast value based on type — delegates to Setting model to avoid duplication.
     */
    protected function castValue($value, $type)
    {
        return \App\Models\Setting::castValueStatic($value, $type);
    }

    /**
     * Seed default settings
     */
    public function seedDefaults()
    {
        $defaults = [
            [
                'key' => 'app_name',
                'value' => config('app.name'),
                'type' => 'string',
                'group' => 'site',
                'description' => 'Application name',
            ],
            [
                'key' => 'timezone',
                'value' => config('app.timezone'),
                'type' => 'string',
                'group' => 'site',
                'description' => 'Application timezone',
            ],
            [
                'key' => 'maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'site',
                'description' => 'Enable maintenance mode',
            ],
            [
                'key' => 'max_upload_size',
                'value' => '10240',
                'type' => 'integer',
                'group' => 'general',
                'description' => 'Maximum upload size in KB',
            ],
            [
                'key' => 'session_lifetime',
                'value' => '120',
                'type' => 'integer',
                'group' => 'general',
                'description' => 'Session lifetime in minutes',
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }

        $this->clearCache();
    }
}
