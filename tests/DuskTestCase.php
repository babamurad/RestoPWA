<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Laravel\Dusk\Browser;

abstract class DuskTestCase extends BaseTestCase
{
    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Browser::macro('clearPwaCache', function () {
            /** @var Browser $this */
            $this->script([
                'window.localStorage.clear();',
                'window.sessionStorage.clear();',
                "if(window.indexedDB) { window.indexedDB.databases().then(dbs => { dbs.forEach(db => window.indexedDB.deleteDatabase(db.name)) }) }"
            ]);
            return $this;
        });

        Browser::macro('setAddress', function (string $address, float $lat, float $lon) {
            /** @var Browser $this */
            $this->script("
                if (window.Livewire) {
                    let el = document.querySelector('[dusk=\"checkout-wizard\"]');
                    if (el) {
                        let component = Livewire.find(el.getAttribute('wire:id'));
                        component.set('address', { 
                            address: '$address', 
                            lat: $lat, 
                            lon: $lon 
                        });
                        component.call('calculateTotals');
                    }
                }
            ");
            return $this;
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
