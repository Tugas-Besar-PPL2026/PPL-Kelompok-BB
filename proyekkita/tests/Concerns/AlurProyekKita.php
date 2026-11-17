<?php

namespace Tests\Concerns;

use Laravel\Dusk\Browser;

trait AlurProyekKita
{
    protected function loginKandidat(Browser $browser): void
    {
        $browser->visit('/login')
            ->waitFor('input[name="email"]')
            ->type('email', 'kandidat@proyekkita.test')
            ->type('password', 'password')
            ->click('@masuk')
            ->pause(1500)
            ->visit('/kandidat/proyek')
            ->waitFor('@form-filter', 10);
    }

    protected function terapkanFilter(
        Browser $browser,
        array $filters
    ): void {
        $browser->visit('/kandidat/proyek')
            ->waitFor('@form-filter', 10);

        $browser->script("
            const setValue = (selector, value) => {
                const input = document.querySelector(selector);
                if (input && value !== null && value !== undefined) {
                    input.value = String(value);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };
        ");

        if (isset($filters['cari'])) {
            $browser->type('@cari-proyek', $filters['cari']);
        }

        if (isset($filters['kategori'])) {
            $browser->select('@filter-kategori', (string) $filters['kategori']);
        }

        if (isset($filters['kompensasi_min'])) {
            $browser->script(
                "document.querySelector('[dusk=\"filter-kompensasi-min\"]').value = " .
                json_encode((string) $filters['kompensasi_min']) . ";
                document.querySelector('[dusk=\"filter-kompensasi-min\"]').dispatchEvent(new Event('input', {bubbles:true}));"
            );
        }

        if (isset($filters['kompensasi_maks'])) {
            $browser->script(
                "document.querySelector('[dusk=\"filter-kompensasi-maks\"]').value = " .
                json_encode((string) $filters['kompensasi_maks']) . ";
                document.querySelector('[dusk=\"filter-kompensasi-maks\"]').dispatchEvent(new Event('input', {bubbles:true}));"
            );
        }

        if (isset($filters['tenggat_sebelum'])) {
            $browser->script(
                "document.querySelector('[dusk=\"filter-tenggat\"]').value = " .
                json_encode($filters['tenggat_sebelum']) . ";
                document.querySelector('[dusk=\"filter-tenggat\"]').dispatchEvent(new Event('input', {bubbles:true}));
                document.querySelector('[dusk=\"filter-tenggat\"]').dispatchEvent(new Event('change', {bubbles:true}));"
            );
        }

        if (isset($filters['urut'])) {
            $browser->select('@urutkan', $filters['urut']);
        }

        dump('NILAI FILTER:', $browser->script("
            return {
                min: document.querySelector('[dusk=\"filter-kompensasi-min\"]')?.value,
                maks: document.querySelector('[dusk=\"filter-kompensasi-maks\"]')?.value,
                tenggat: document.querySelector('[dusk=\"filter-tenggat\"]')?.value
            };
        "));

        $browser->script("
            const form = document.querySelector('[dusk=\"form-filter\"]');
            if (form) form.requestSubmit();
        ");

        $browser->pause(1500);

        dump('URL HASIL FILTER:', $browser->driver->getCurrentURL());

        $browser->screenshot('debug-hasil-pencarian');
    }

    protected function ambilScreenshot(
        Browser $browser,
        string $nama
    ): void {
        $browser->screenshot($nama);
    }
}
