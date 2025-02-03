<?php

namespace AuroraWebSoftware\AConfig;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            if (!class_exists('CreateAConfigTable')) {
                $timestamp = date('Y_m_d_His', time());
                $this->publishes([
                    __DIR__.'/../database/migrations/create_aconfig_table.php.stub'
                    => database_path('migrations/'.$timestamp.'_create_aconfig_table.php'),
                ], 'migrations');
            }

            $this->publishes([
                __DIR__.'/../config/aconfig.php' => config_path('aconfig.php'),
            ], 'config');
        }

        $this->initConfig();
    }

    private function initConfig()
    {
        if (!Schema::hasTable(config('aconfig.table'))) {
            Log::error(sprintf(
                "%s: Dinamik konfigürasyon tablosu `%s` bulunamadı. Lütfen migrasyonları çalıştırın.",
                __CLASS__,
                config('aconfig.table')
            ));
            return;
        }

        $configKeys = config('aconfig.keys', []);

        $flattened = $this->prefixKey(null, $configKeys);

        config(['aconfig.defaults' => $flattened]);

        foreach ($flattened as $_key => $_value) {
            AConfig::firstOrCreate(
                ['key' => $_key],
                ['value' => $_value]
            );
        }

        $dbConfigs = AConfig::all();

        if (config('aconfig.auto_delete_orphan_keys') === true) {
            $dbKeys = $dbConfigs->pluck('value', 'key')->toArray();
            $orphanKeys = array_diff_key($dbKeys, $flattened);

            if (!empty($orphanKeys)) {
                AConfig::whereIn('key', array_keys($orphanKeys))->delete();
            }
        }

        $dbConfigs->each(function ($configModel) {
            config([$configModel->key => $configModel]);
        });
    }

    public function prefixKey($prefix, $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newPrefix = $prefix ? $prefix.$key : $key;
            if (is_array($value)) {
                $result = array_merge($result, $this->prefixKey($newPrefix.'.', $value));
            } else {
                $result[$newPrefix] = $value;
            }
        }
        return $result;
    }
}
