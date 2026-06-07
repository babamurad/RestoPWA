<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Domains\Vendor\Models\Restaurant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use App\Enums\UserRole;

#[Fillable(['name', 'email', 'password', 'phone', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected $attributes = [
        'role' => 'client',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isRestaurateur(): bool
    {
        return $this->role === UserRole::RESTAURATEUR;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->isAdmin();
        }

        if ($panel->getId() === 'vendor') {
            return $this->isAdmin() || $this->isRestaurateur();
        }

        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        if ($this->isAdmin()) {
            return Restaurant::all();
        }

        return Restaurant::where('vendor_id', $this->id)
            ->orWhere('owner_id', $this->id)
            ->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $tenant->vendor_id === $this->id || $tenant->owner_id === $this->id;
    }

    /**
     * Get the restaurant owned by the user (if they are a Restaurateur).
     */
    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class, 'vendor_id');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            // Геттер: всегда возвращает с +
            get: fn (?string $value) => $value ? (str_starts_with($value, '+') ? $value : '+'.$value) : null,
            
            // Сеттер: нормализует перед сохранением
            set: function (?string $value) {
                if (empty($value)) return ['phone' => null];
                
                // Убираем всё кроме цифр, потом добавляем +
                $digits = preg_replace('/\D+/', '', $value);
                
                // Защита от мусора
                if (strlen($digits) < 8) return ['phone' => null];
                
                return ['phone' => '+' . $digits];
            }
        );
    }
}
