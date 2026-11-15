<?php

namespace App\Models;

use App\Support\Permissions;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_number',
        'full_name',
        'email',
        'phone',
        'password',
        'department_id',
        'work_shift_id',
        'position',
        'work_location',
        'status',
        'is_demo',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    /** Cached permission codes for the current request. */
    protected ?Collection $permissionCache = null;

    // ---------------------------------------------------------------- relations

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function workShift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('is_primary')
            ->withTimestamps()
            ->orderBy('roles.sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProductionAssignment::class);
    }

    public function productionReports(): HasMany
    {
        return $this->hasMany(ProductionReport::class);
    }

    public function driverShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'driver_id');
    }

    // ----------------------------------------------------------- authorization

    public function hasRole(string $code): bool
    {
        return $this->roles->contains(fn (Role $role) => $role->code === $code);
    }

    /** @param array<int, string> $codes */
    public function hasAnyRole(array $codes): bool
    {
        return $this->roles->contains(fn (Role $role) => in_array($role->code, $codes, true));
    }

    /**
     * All permission codes granted through this user's roles.
     *
     * @return Collection<int, string>
     */
    public function permissionCodes(): Collection
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        $this->loadMissing('roles.permissions');

        return $this->permissionCache = $this->roles
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
            ->unique()
            ->values();
    }

    public function hasPermission(string $code): bool
    {
        return $this->permissionCodes()->contains($code);
    }

    public function canUseBeesha(): bool
    {
        return $this->hasPermission(Permissions::BEESHA_USE);
    }

    /** The role a multi-role user is currently working as. */
    public function activeRole(): ?Role
    {
        $code = session('phm.active_role');

        if ($code !== null) {
            $match = $this->roles->firstWhere('code', $code);

            if ($match !== null) {
                return $match;
            }
        }

        return $this->primaryRole();
    }

    public function primaryRole(): ?Role
    {
        return $this->roles->firstWhere('pivot.is_primary', true) ?? $this->roles->first();
    }

    // ---------------------------------------------------------------- helpers

    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name)) ?: [];
        $letters = array_map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));

        return implode('', $letters) ?: '?';
    }

    /** First name only — used in greetings so headings stay short. */
    public function firstName(): string
    {
        $parts = preg_split('/\s+/', trim($this->full_name)) ?: [];

        return $parts[0] ?? $this->full_name;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
