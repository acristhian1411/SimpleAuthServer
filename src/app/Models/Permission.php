<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $primaryKey = 'permission_id';

    public $timestamps = true;

    /**
     * Get the permission's ID (map 'id' attribute to 'permission_id').
     */
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->permission_id,
            set: fn($value) => $this->attributes['permission_id'] = $value,
        );
    }

    /**
     * Map Spatie's expected `name` attribute to `permission_name`.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->attributes['permission_name'] ?? $this->attributes['name'] ?? null,
            set: fn($value) => $this->attributes['permission_name'] = $value,
        );
    }
}
