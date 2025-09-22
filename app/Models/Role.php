<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description'
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function hasPermission($permission)
    {
        \Log::info('Role hasPermission called for: ' . $permission);
        \Log::info('Role name: ' . $this->name);
        \Log::info('Role permissions count: ' . $this->permissions->count());
        
        if (is_string($permission)) {
            $hasPermission = $this->permissions->contains('name', $permission);
            \Log::info('String permission check result: ' . ($hasPermission ? 'Found' : 'Not found'));
            return $hasPermission;
        }
        
        $hasPermission = !!$permission->intersect($this->permissions)->count();
        \Log::info('Object permission check result: ' . ($hasPermission ? 'Found' : 'Not found'));
        return $hasPermission;
    }
}
