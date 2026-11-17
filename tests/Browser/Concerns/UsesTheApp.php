<?php

declare(strict_types=1);

namespace Tests\Browser\Concerns;

use App\Domains\Menu\Models\Product;
use App\Domains\Menu\Services\ProductModifiers;
use App\Models\User;
use Laravel\Dusk\Browser;

/** Things a person does in the customer app, as steps the tests can chain. */
trait UsesTheApp
{
    /** Sign in through the login form (the way a person does it). */
    protected function signIn(Browser $browser, User $user, ?string $redirect = null): Browser
    {
        return $browser->visit('/login'.($redirect ? '?redirect='.urlencode($redirect) : ''))
            ->waitFor('@login-email')
            ->type('@login-email', $user->email)
            ->type('@login-password', self::PASSWORD)
            ->click('@login-submit');
    }

    /** The id of an option of the dish, as the menu API gives it to the app. */
    protected function optionDusk(Product $product, string $group, string $option): string
    {
        foreach (ProductModifiers::groups($product->modifiers) as $g) {
            foreach ($g['options'] as $o) {
                if ($g['name'] === $group && $o['name'] === $option) {
                    return '@option-'.$o['id'];
                }
            }
        }

        $this->fail("The dish has no option {$group} / {$option}");
    }

    /** Open the dish's card from the restaurant's menu. */
    protected function openDish(Browser $browser, string $restaurantSlug, Product $product): Browser
    {
        return $browser->visit('/restaurants/'.$restaurantSlug)
            ->waitFor('@product-'.$product->id)
            ->click('@product-'.$product->id)
            ->waitFor('@add-to-cart');
    }
}
