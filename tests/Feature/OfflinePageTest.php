<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The service worker caches one copy of /offline, so the page carries every language and
 * the browser picks one (see the script in offline.blade.php).
 */
class OfflinePageTest extends TestCase
{
    public function test_the_page_carries_every_language_and_the_picker(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('Нет сети')
            ->assertSee('Internet ýok')
            ->assertSee("localStorage.getItem('locale')", false)
            ->assertSee('data-offline="heading"', false);
    }
}
