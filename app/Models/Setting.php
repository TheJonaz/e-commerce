<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public static function get(string $key, mixed $default = null): mixed
    {
        $cached = Cache::rememberForever('settings.all', function () {
            return self::all()->pluck('value', 'key')->all();
        });

        return $cached[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings.all');
    }

    public static function many(array $values): void
    {
        foreach ($values as $k => $v) {
            self::updateOrCreate(['key' => $k], ['value' => $v]);
        }
        Cache::forget('settings.all');
    }
}
