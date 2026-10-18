<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Domains\Geo\Models\LocalPlace;
use App\Enums\UserRole;
use App\Filament\Resources\LocalPlaceResource\Pages\CreateLocalPlace;
use App\Filament\Resources\LocalPlaceResource\Pages\EditLocalPlace;
use App\Filament\Resources\LocalPlaceResource\Pages\ListLocalPlaces;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Admin "Локальные адреса": the form used the Filament 3 Section class and
 * crashed on open; the type column sorted by an accessor, not a DB column.
 */
class LocalPlaceResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function place(array $attributes = []): LocalPlace
    {
        return LocalPlace::create($attributes + [
            'name' => 'Рынок Гулистан',
            'city' => 'Ашхабад',
            'type' => 'market',
            'lat' => 37.9401,
            'lon' => 58.3791,
        ]);
    }

    public function test_admin_creates_a_place(): void
    {
        Livewire::test(CreateLocalPlace::class)
            ->fillForm([
                'name' => 'Русский базар',
                'city' => 'Ашхабад',
                'type' => 'market',
                'aliases' => ['Гулистан', 'Gülistan'],
                'lat' => 37.9401,
                'lon' => 58.3791,
                'is_verified' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $place = LocalPlace::where('name', 'Русский базар')->sole();
        $this->assertSame(['Гулистан', 'Gülistan'], $place->aliases);
        $this->assertEqualsWithDelta(37.9401, $place->lat, 0.00001);
        $this->assertTrue($place->is_verified);
    }

    public function test_admin_edits_a_place(): void
    {
        $place = $this->place();

        Livewire::test(EditLocalPlace::class, ['record' => $place->getRouteKey()])
            ->assertFormSet(['name' => 'Рынок Гулистан'])
            ->fillForm(['type' => 'landmark', 'popularity' => 5])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('landmark', $place->fresh()->type);
        $this->assertSame(5, $place->fresh()->popularity);
    }

    public function test_list_sorts_and_filters_by_type(): void
    {
        $market = $this->place();
        $school = $this->place(['name' => 'Школа №1', 'type' => 'school']);

        Livewire::test(ListLocalPlaces::class)
            ->assertCanSeeTableRecords([$market, $school])
            ->sortTable('type', 'desc')
            ->assertCanSeeTableRecords([$school, $market], inOrder: true)
            ->sortTable('type', 'asc')
            ->assertCanSeeTableRecords([$market, $school], inOrder: true)
            ->filterTable('type', 'school')
            ->assertCanSeeTableRecords([$school])
            ->assertCanNotSeeTableRecords([$market]);
    }
}
