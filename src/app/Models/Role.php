<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $primaryKey = 'role_id';

    public $timestamps = true;

    /**
     * Get the role's ID (map 'id' attribute to 'role_id').
     */
    protected function id(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->role_id,
            set: fn($value) => $this->attributes['role_id'] = $value,
        );
    }

    /**
     * Map Spatie's expected `name` attribute to `role_name`.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->attributes['role_name'] ?? $this->attributes['name'] ?? null,
            set: fn($value) => $this->attributes['role_name'] = $value,
        );
    }
}
