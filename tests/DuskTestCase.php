<?php

declare(strict_types=1);

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;

/**
 * Browser (E2E) tests of the Vue app and the panels. See docs/testing-e2e.md.
 *
 * Environment variables, all optional:
 *   DUSK_CHROMEDRIVER_PATH  a ChromeDriver other than the one Dusk downloaded (it must match Chrome)
 *   DUSK_CHROME_BINARY      a Chrome other than the system one
 *   DUSK_NO_SANDBOX=1       needed when Chrome runs as root or in a container
 *   DUSK_HEADLESS_DISABLED  show the browser window
 */
abstract class DuskTestCase extends BaseTestCase
{
    #[BeforeClass]
    public static function prepare(): void
    {
        if (static::runningInSail()) {
            return;
        }

        if ($path = getenv('DUSK_CHROMEDRIVER_PATH')) {
            static::useChromedriver($path);
        }

        static::startChromeDriver(['--port=9515']);
    }

    /**
     * Dusk keeps the first browser for the whole class, with its cookies, local storage and
     * service workers: the language chosen in one test would then be there in the next. A fresh
     * browser per test costs about a second and makes the tests independent.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        static::closeAll();
    }

    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1280,1000',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--lang=ru',
            // The tests do not depend on the internet: fonts, stock photos and the map service are not
            // reached (and do not slow the pages down while the connection times out)
            '--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1, EXCLUDE localhost',
        ])->when(getenv('DUSK_NO_SANDBOX'), function (Collection $items) {
            return $items->merge(['--no-sandbox', '--disable-dev-shm-usage']);
        })->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        // After a sign-in Chrome offers to save the password; its bubble takes the keyboard and
        // mouse focus away from the page and the next clicks go nowhere
        $options->setExperimentalOption('prefs', [
            'credentials_enable_service' => false,
            'profile.password_manager_enabled' => false,
            'profile.password_manager_leak_detection' => false,
        ]);

        if ($binary = getenv('DUSK_CHROME_BINARY')) {
            $options->setBinary($binary);
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            ),
            // A browser command that gets no answer fails the test after a minute instead of
            // holding the whole run for the library's default three
            30000,
            60000,
        );
    }
}
