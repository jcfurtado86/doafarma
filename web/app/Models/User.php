<?php

declare(strict_types = 1);

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property UserRole $role
 * @property UserStatus $status
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasApiTokens;
    use LogsActivity;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'cpf',
        'cpf_hash',
        'role',
        'status',
        'status_changed_at',
        'status_changed_by',
        'password',
        'phone_number',
        'terms_accepted',
        'terms_accepted_at',
    ];

    /**
     * Boot the model.
     */
    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            // Auto-generate cpf_hash when cpf changes
            if ($user->isDirty('cpf') && $user->cpf !== null) {
                $user->cpf_hash = hash('sha256', $user->cpf);
            } elseif ($user->cpf === null) {
                $user->cpf_hash = null;
            }
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'status_changed_at' => 'datetime',
            'password'          => 'hashed',
            'cpf'               => 'encrypted',
            'role'              => UserRole::class,
            'status'            => UserStatus::class,
        ];
    }

    /**
     * Check if user is approved and can access the system.
     */
    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    /**
     * Check if user is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === UserStatus::Pending;
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    /**
     * Check if user is a doctor.
     */
    public function isDoctor(): bool
    {
        return $this->role === UserRole::Doctor;
    }

    /**
     * Check if user is a receptor.
     */
    public function isReceptor(): bool
    {
        return $this->role === UserRole::Receptor;
    }

    /**
     * Get the user's addresses.
     *
     * @return HasMany<Address, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    /**
     * Get the user's doctor.
     *
     * @return HasOne<Doctor, $this>
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * Get the medication offerings for the user through the doctor.
     *
     * @return HasManyThrough<MedicationOffering, Doctor, $this>
     */
    public function medicationOfferings(): HasManyThrough
    {
        return $this->hasManyThrough(MedicationOffering::class, Doctor::class);
    }

    /**
     * Get the medication requests made by this user (as receptor).
     *
     * @return HasMany<MedicationRequest, $this>
     */
    public function medicationRequests(): HasMany
    {
        return $this->hasMany(MedicationRequest::class, 'receptor_id');
    }

    /**
     * Get the push tokens for this user.
     *
     * @return HasMany<PushToken, $this>
     */
    public function pushTokens(): HasMany
    {
        return $this->hasMany(PushToken::class);
    }

    /**
     * Get the admin who changed this user's status.
     *
     * @return BelongsTo<User, $this>
     */
    public function statusChangedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by');
    }

    /**
     * Configure activity logging options.
     *
     * Note: CPF and phone_number are NOT logged for LGPD compliance.
     * These are sensitive personal data that should not be stored in audit logs.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName): string => match ($eventName) {
                'created' => "Usuário {$this->name} foi criado",
                'updated' => "Usuário {$this->name} foi atualizado",
                'deleted' => "Usuário {$this->name} foi removido",
                default   => "Usuário {$this->name}: {$eventName}",
            });
    }
}
