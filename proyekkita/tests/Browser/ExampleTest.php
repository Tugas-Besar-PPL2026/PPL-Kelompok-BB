<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ExampleTest extends DuskTestCase
{
    /**
     * A basic browser test example.
     */
    public function test_basic_example(): void
    {
        $this->browse(function (Browser $browser) {
            // Halaman utama sekarang adalah Landing Page ProyekKita (FR-01), bukan halaman bawaan Laravel.
            $browser->visit('/')
                ->waitForText('Cara kerja ProyekKita');
        });
    }
}
