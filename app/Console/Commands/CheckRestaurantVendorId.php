<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Read-only report on the legacy restaurants.vendor_id column.
 *
 * The application no longer uses it to find a restaurant, but User::canAccessTenant()
 * still honours it as a second way to grant panel access.
 */
class CheckRestaurantVendorId extends Command
{
    protected $signature = 'restaurants:check-vendor-id';

    protected $description = 'Показывает рестораны с устаревшим vendor_id (ничего не меняет)';

    public function handle(): int
    {
        $userIds = User::pluck('id')->flip();
        $groups = [];

        foreach (Restaurant::query()->get(['id', 'name', 'owner_id', 'vendor_id']) as $restaurant) {
            $vendorId = $restaurant->vendor_id;

            $kind = match (true) {
                $vendorId === null || $vendorId === '' => 'пусто',
                $vendorId === $restaurant->id => 'равен id ресторана',
                $vendorId === $restaurant->owner_id => 'равен id владельца',
                $userIds->has($vendorId) => 'ДРУГОЙ пользователь (имеет доступ к панели)',
                default => 'посторонняя строка (безвредно)',
            };

            $groups[$kind][] = [$restaurant->id, $restaurant->name, (string) $vendorId];
        }

        foreach ($groups as $kind => $rows) {
            $this->info($kind.': '.count($rows));

            if (str_starts_with($kind, 'ДРУГОЙ')) {
                $this->table(['id', 'name', 'vendor_id'], $rows);
            }
        }

        $this->line('Меню и заказы от этого поля не зависят. Если есть строки «ДРУГОЙ пользователь», проверьте, что у него должен быть доступ.');

        return self::SUCCESS;
    }
}
