<?php

namespace AuroraWebSoftware\AConfig;

use Illuminate\Database\Eloquent\Model;

/**
 * Class DynamicConfig
 *
 * @property mixed value
 * @package EmadHa\DynamicConfig
 */
class AConfig extends Model
{
    /**
     * @var array
     */
    protected $guarded = ['id'];

    protected $casts = [
        'value' => 'array',
    ];


    /**
     * DynamicConfig constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('aconfig.table'));
    }

    /**
     * Update the current key value
     *
     * @param $value
     *
     * @return bool
     */
    public function setTo($value)
    {
        return $this->update(['value' => $value]);
    }

    /**
     * Get the default value of the specified key
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function default()
    {
        return config(
            config('aconfig.defaults_key') . '.' . $this->key
        );
    }

    /**
     * Revert the current key to it's original value
     * from the actual config file
     *
     * @return mixed
     */
    public function revert()
    {
        return config($this->key)->setTo(
            config(config('aconfig.defaults_key') . '.' . $this->key)
        );
    }

}

