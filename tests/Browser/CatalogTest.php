<?php

declare(strict_types=1);

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use PHPUnit\Framework\Attributes\Group;
use Tests\Browser\Concerns\BuildsRestaurants;
use Tests\DuskTestCase;

/** What a visitor sees before signing in: the catalog, search, cuisine filter, language, empty cart. */
class CatalogTest extends DuskTestCase
{
    use BuildsRestaurants;
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();

        $sushi = $this->makeRestaurant('Sakura Sushi', 'sakura-sushi', ['tags' => ['sushi']]);
        $grill = $this->makeRestaurant('Altyn Grill', 'altyn-grill', ['tags' => ['grill', 'national']]);
        $this->makeDish($sushi, 'Филадельфия', 25.0);
        $this->makeDish($grill, 'Плов по-туркменски', 18.0);
    }

    #[Group('smoke')]
    public function test_visitor_sees_the_restaurants(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('@restaurant-sakura-sushi')
                ->assertSee('Sakura Sushi')
                ->assertSee('Altyn Grill');
        });
    }

    #[Group('smoke')]
    public function test_search_finds_a_restaurant_by_the_name_of_its_dish(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('@restaurant-altyn-grill')
                ->click('@search-toggle')
                ->waitFor('@search-input')
                ->type('@search-input', 'плов')
                ->waitUntilMissing('@restaurant-sakura-sushi')
                ->waitFor('@restaurant-altyn-grill');

            // Nothing matches: the visitor is told so, not left with an empty page
            $browser->type('@search-input', 'лагман')
                ->waitForText('Ничего не найдено')
                ->assertMissing('@restaurant-altyn-grill');
        });
    }

    public function test_cuisine_category_filters_the_list(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('@restaurant-sakura-sushi')
                ->click('@category-sushi')
                ->waitUntilMissing('@restaurant-altyn-grill')
                ->assertPresent('@restaurant-sakura-sushi')
                ->click('@category-all')
                ->waitFor('@restaurant-altyn-grill')
                ->assertPresent('@restaurant-sakura-sushi');
        });
    }

    public function test_visitor_can_switch_the_language_and_it_stays(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('@restaurant-sakura-sushi')
                ->assertSee('Корзина')
                ->press('TK')
                ->waitForText('Sebet')
                ->assertDontSee('Корзина')
                ->refresh()
                ->waitFor('@restaurant-sakura-sushi')
                ->assertSee('Sebet');
        });
    }

    #[Group('smoke')]
    public function test_empty_cart_says_so(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/cart')->waitForText('Корзина пуста');
        });
    }
}
