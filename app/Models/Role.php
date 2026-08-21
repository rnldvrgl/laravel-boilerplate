<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'guard_name'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function givePermissionTo(Permission|string $permission): self
    {
        $permissionModel = $permission instanceof Permission ? $permission : Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'web',
        ]);

        $this->permissions()->syncWithoutDetaching([$permissionModel->id]);

        return $this;
    }
}
