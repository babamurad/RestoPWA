<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Models\User;
use App\Models\VendorSettlement;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Removes what DemoDataSeeder put into the database, so a demo environment can be
 * turned into a clean one before launch without clearing tables by hand.
 *
 * Demo users are recognised by their e-mail (demo.*@restopwa.local); their orders, reviews,
 * addresses, payment methods, tickets, courier profiles and earnings go with them.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'demo:purge
        {--force : Delete for real. Without it the command only reports what would be deleted}
        {--include-restaurants : Also delete the restaurants of RestaurantSeeder and their owner (only those that have no orders of real customers)}';

    protected $description = 'Delete the demo data created by DemoDataSeeder';

    /** Slugs of the restaurants RestaurantSeeder creates */
    private const SEEDED_RESTAURANT_SLUGS = [
        'pepperoni-pizza', 'burger-house', 'sakura-sushi', 'green-salad', 'sweet-dreams', 'coffee-time',
    ];

    private const SEEDED_OWNER_EMAIL = 'owner@restopwa.local';

    public function handle(): int
    {
        $dryRun = ! $this->option('force');

        $demoUsers = User::where('email', 'like', 'demo.%@restopwa.local')->get();
        $userIds = $demoUsers->pluck('id')->all();
        $phones = $demoUsers->pluck('phone')->filter()->all();

        $orders = Order::whereIn('user_id', $userIds);

        $report = [
            ['Демо-пользователи (клиенты и курьеры)', count($userIds)],
            ['Их заказы', (clone $orders)->count()],
            ['Отказы при оформлении', DB::table('order_rejections')->whereIn('user_id', $userIds)->count()],
            ['SMS-журнал', $phones === [] ? 0 : DB::table('sms_logs')->whereIn('phone', $phones)->count()],
        ];

        $restaurants = $this->option('include-restaurants') ? $this->removableRestaurants($userIds) : collect();
        if ($this->option('include-restaurants')) {
            $report[] = ['Рестораны из сидера', $restaurants->count()];
        }

        $this->table(['Что', 'Сколько'], $report);

        if ($dryRun) {
            $this->warn('Ничего не удалено. Для удаления добавьте --force.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($userIds, $phones, $restaurants) {
            // Through the models: the review of a deleted order recalculates the restaurant's rating
            Order::whereIn('user_id', $userIds)->get()->each->delete();
            User::whereIn('id', $userIds)->get()->each->delete();

            DB::table('order_rejections')->whereIn('user_id', $userIds)->delete();
            if ($phones !== []) {
                DB::table('sms_logs')->whereIn('phone', $phones)->delete();
            }

            // A real settlement always has orders (settlements:generate never makes an empty one);
            // the demo ones are not linked to any
            VendorSettlement::whereNotIn('id', Order::whereNotNull('vendor_settlement_id')->select('vendor_settlement_id'))->delete();

            if ($restaurants->isNotEmpty()) {
                DB::table('order_rejections')->whereIn('vendor_id', $restaurants->pluck('id'))->delete();
                $restaurants->each->delete();

                $owner = User::where('email', self::SEEDED_OWNER_EMAIL)->first();
                if ($owner && ! Restaurant::where('owner_id', $owner->id)->exists()) {
                    $owner->delete();
                }
            }
        });

        $this->info('Демо-данные удалены.');

        return self::SUCCESS;
    }

    /**
     * Restaurants of the seeder that serve no real customer: one with orders of a person who is
     * not a demo user is a live restaurant that merely carries a seeder slug, and stays.
     *
     * @param  list<string>  $demoUserIds
     * @return Collection<int, Restaurant>
     */
    private function removableRestaurants(array $demoUserIds)
    {
        return Restaurant::withoutGlobalScopes()
            ->whereIn('slug', self::SEEDED_RESTAURANT_SLUGS)
            ->get()
            ->filter(fn (Restaurant $restaurant) => ! Order::where('vendor_id', $restaurant->id)
                ->whereNotIn('user_id', $demoUserIds)
                ->exists())
            ->values();
    }
}
