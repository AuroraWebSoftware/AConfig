<?php

namespace AuroraWebSoftware\AConfig;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use mysql_xdevapi\Exception;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * Bootstrap services.
     *
     * @return void
     * @throws \Exception
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            if (!class_exists('CreateAConfigTable')) {
                $timestamp = date('Y_m_d_His', time());
                $this->publishes([
                    __DIR__ . '/../database/migrations/create_aconfig_table.php.stub' => database_path('migrations/' . $timestamp . '_create_aconfig_table.php'),
                ], 'migrations');
            }

            $this->publishes([
                __DIR__ . '/../config/aconfig.php' => config_path('aconfig.php'),
            ], 'config');
        }

        $this->initConfig();
    }

    private function initConfig()
    {

        # Check if the table exists
        if (!Schema::hasTable(config('aconfig.table'))) {

            # Don't crash, Log the error instead
            Log::error(sprintf(
                    get_class($this) . " is missing the the dynamic config table [`%s`]. you might need to do `php artisan vendor:publish` && `php artisan migrate`",
                    config('aconfig.table'))
            );

            return false;
        }

        # Create a new collection of what's dynamic
        $DefaultConfig = collect([]);

        # Return the config entries containing ['dynamic'=>true] key
        collect(config()->all())->each(function ($value, $key) use (&$DefaultConfig) {

            # Check if the current config key has dynamic key set to it, and it's true
            if (array_key_exists(config('aconfig.dynamic_key'), $value)
                && $value[config('aconfig.dynamic_key')] == true) {

                # unset that dynamic value
                unset($value[config('aconfig.dynamic_key')]);

                # Add that to the DynamicConfig collection
                $DefaultConfig->put($key, $value);
            }

        });

        # Keep the defaults for reference
        config([config('aconfig.defaults_key') => $DefaultConfig]);

        # Flatten the config table data
        $prefixedKeys = $this->prefixKey(null, $DefaultConfig->all());

        # Insert the flattened data into database
        foreach ($prefixedKeys as $_key => $_value) {

            # Get the row from database if it exists,
            # If not, add it using the value from the actual config file.
            AConfig::firstOrCreate(['key' => $_key], ['value' => $_value]);

        }

        # Build the Config array
        $AConfig = AConfig::all();

        # Check if auto deleting orphan keys is enabled
        # and delete those if they don't exists in the actual config file
        if (config('aconfig.auto_delete_orphan_keys') == true) {

            # Check for orphan keys
            $orphanKeys = array_diff_assoc($AConfig->pluck('value', 'key')->toArray(), $prefixedKeys);

            # Delete orphan keys
            AConfig::whereIn('key', array_keys($orphanKeys))->delete();

        }

        # Store these config into the config() helper, but as model objects
        # Thus making Model's method accessible from here
        # example: config('app.name')->revert().
        # Available methods are `revert`, `default` and `setTo($value)`
        $AConfig->map(function ($config) use ($DefaultConfig) {
            config([$config->key => $config]);
        });

    }

    public function prefixKey($prefix, $array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::prefixKey($prefix . $key . '.', $value));
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
}
