<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Domains\Vendor\Models\Restaurant;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'email', 'password', 'phone', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return (bool) $this->is_admin;
        }

        if ($panel->getId() === 'vendor') {
            return $this->is_admin || Restaurant::where('vendor_id', $this->id)->exists();
        }

        return true;
    }

    public function getTenants(Panel $panel): Collection
    {
        if ($this->is_admin) {
            return Restaurant::all();
        }

        return Restaurant::where('vendor_id', $this->id)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $tenant->vendor_id === $this->id;
    }

    /**
     * Get the restaurant owned by the user (if they are a Restaurateur).
     */
    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class, 'vendor_id');
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
