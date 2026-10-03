<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /**
     * @return BelongsToMany<Permission, $this>
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * Determine if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->contains('name', $permission);
    }

    /**
     * Grant a permission to the role.
     */
    public function givePermissionTo(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::firstOrCreate(['name' => $permission], ['module' => explode('.', $permission)[0]])
            : $permission;

        $this->permissions()->syncWithoutDetaching([$perm->id]);
    }

    /**
     * Revoke a permission from the role.
     */
    public function revokePermission(Permission|string $permission): void
    {
        $perm = is_string($permission)
            ? Permission::where('name', $permission)->first()
            : $permission;

        if ($perm) {
            $this->permissions()->detach($perm->id);
        }
    }
}
