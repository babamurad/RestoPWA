<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Logistics\Models\Courier;
use App\Domains\Logistics\Models\CourierEarning;
use App\Domains\Order\Models\Order;
use App\Domains\Vendor\Models\Restaurant;
use App\Enums\UserRole;
use App\Filament\Resources\CourierResource\Pages\EditCourier;
use App\Filament\Resources\CourierResource\RelationManagers\EarningsRelationManager;
use App\Filament\Resources\VendorSettlementResource\Pages\ManageVendorSettlements;
use App\Filament\Vendor\Resources\VendorSettlementResource\Pages\ListVendorSettlements;
use App\Models\User;
use App\Models\VendorSettlement;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Cash flow (variant B): the courier hands the food money to the restaurant, the restaurant
 * pays the platform its commission against a periodic invoice (a VendorSettlement).
 */
class CashSettlementsTest extends TestCase
{
    use RefreshDatabase;

    private function settlement(Restaurant $restaurant, string $status = 'draft'): VendorSettlement
    {
        return VendorSettlement::create([
            'restaurant_id' => $restaurant->id, 'period_from' => now()->subWeek(), 'period_to' => now(),
            'gross_amount' => 150, 'commission_amount' => 15, 'net_payable' => 135, 'status' => $status,
        ]);
    }

    public function test_admin_issues_an_invoice_and_marks_it_paid(): void
    {
        $settlement = $this->settlement(Restaurant::factory()->create());

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageVendorSettlements::class)
            ->assertCanSeeTableRecords([$settlement])
            ->callTableAction('issue', $settlement);
        $this->assertSame('approved', $settlement->fresh()->status);

        Livewire::test(ManageVendorSettlements::class)->callTableAction('markPaid', $settlement);
        $settlement->refresh();
        $this->assertSame('paid', $settlement->status);
        $this->assertNotNull($settlement->paid_at);
    }

    public function test_bulk_mark_paid_skips_nothing_that_is_already_paid(): void
    {
        $restaurant = Restaurant::factory()->create();
        $a = $this->settlement($restaurant, 'approved');
        $b = $this->settlement($restaurant, 'draft');

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(ManageVendorSettlements::class)->callTableBulkAction('markPaid', [$a, $b]);

        $this->assertSame(['paid', 'paid'], [$a->fresh()->status, $b->fresh()->status]);
    }

    public function test_restaurateur_sees_only_own_invoices_even_with_a_legacy_vendor_id(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $mine = Restaurant::factory()->create(['owner_id' => $owner->id, 'vendor_id' => fake()->uuid()]);
        $other = Restaurant::factory()->create();
        $own = $this->settlement($mine);
        $foreign = $this->settlement($other);

        $this->actingAs($owner);
        Filament::setCurrentPanel(Filament::getPanel('vendor'));
        Filament::setTenant($mine);

        Livewire::test(ListVendorSettlements::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_owner_relation_uses_owner_id(): void
    {
        $owner = User::factory()->create(['role' => UserRole::RESTAURATEUR]);
        $restaurant = Restaurant::factory()->create(['owner_id' => $owner->id, 'vendor_id' => fake()->uuid()]);

        $this->assertSame($restaurant->id, $owner->restaurant->id);
    }

    public function test_admin_marks_courier_earnings_paid_in_bulk(): void
    {
        $courier = Courier::create(['user_id' => User::factory()->create(['role' => UserRole::COURIER])->id]);
        $restaurant = Restaurant::factory()->create();
        $earnings = collect(range(1, 2))->map(fn () => CourierEarning::create([
            'order_id' => Order::factory()->create(['vendor_id' => $restaurant->id])->id,
            'courier_id' => $courier->id, 'amount' => 10, 'status' => 'pending',
        ]));

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EarningsRelationManager::class, ['ownerRecord' => $courier, 'pageClass' => EditCourier::class])
            ->callTableBulkAction('markPaid', $earnings->all());

        $this->assertSame(0, CourierEarning::where('status', 'pending')->count());
    }
}
