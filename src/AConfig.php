<?php

namespace AuroraWebSoftware\AConfig;

use Illuminate\Database\Eloquent\Model;

class AConfig extends Model
{
    protected $guarded = ['id'];


    protected $casts = [

    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('aconfig.table'));
    }

    public function setTo($value)
    {
        $this->update(['value' => $value]);

        config([$this->key => $this]);

        return $this;
    }

    public function default()
    {
        $defaults = config('aconfig.defaults', []);
        return $defaults[$this->key] ?? null;
    }

    public function revert()
    {
        $defaultValue = $this->default();
        return $this->setTo($defaultValue);
    }

    public function __toString()
    {
        if (is_scalar($this->value)) {
            return (string) $this->value;
        }

        return json_encode($this->value);
    }
}
