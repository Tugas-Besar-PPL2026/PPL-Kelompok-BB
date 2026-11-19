<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_004_Test extends DuskTestCase
{
    public function test_buat_profil_kandidat_pertama_kali(): void
    {
        $newKandidat = User::factory()->create(['role' => 'kandidat']);

        $this->browse(function (Browser $browser) use ($newKandidat) {
            $browser->loginAs($newKandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[name="full_name"], input[name="headline"]');

            $fillField = function (string $name, string $value) use ($browser): void {
                $inputSelector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"]";
                $selectSelector = "select[name=\"{$name}\"]";

                if ($browser->element($inputSelector) !== null) {
                    $browser->clear($name)->type($name, $value);
                } elseif ($browser->element($selectSelector) !== null) {
                    $browser->select($name, $value);
                }
            };

            $fillField('full_name', 'Siti Aminah');

            // Cari nama field domisili/kota yang tersedia di form
            $domicileField = null;
            foreach (['domicile', 'domisili', 'city'] as $candidateField) {
                if ($browser->element("input[name=\"{$candidateField}\"], select[name=\"{$candidateField}\"]") !== null) {
                    $domicileField = $candidateField;
                    $fillField($candidateField, 'Kota Bandung');
                    break;
                }
            }

            if ($domicileField === null) {
                $inputs = $browser->script(
                    'return Array.from(document.querySelectorAll("input, select, textarea")).map(el => el.name).filter(Boolean);'
                );
                $this->fail(
                    'Field domisili/kota tidak ditemukan. Daftar name di form: '
                    . json_encode($inputs[0] ?? $inputs)
                );
            }

            $fillField('headline', 'Desainer grafis untuk UMKM');
            $fillField('university', 'Telkom University');
            $fillField('major', 'S1 Desain Komunikasi Visual');
            $fillField('summary', 'Mahasiswa DKV semester 7 yang fokus pada desain kemasan.');

            $browser->press('Simpan Profil')
                ->pause(600);

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            $expectedDb = [
                'user_id'   => $newKandidat->id,
                'full_name' => 'Siti Aminah',
            ];

            if ($domicileField === 'domicile' || $domicileField === 'domisili' || $domicileField === 'city') {
                $expectedDb[$domicileField] = 'Kota Bandung';
            }

            $this->assertDatabaseHas('candidate_profiles', $expectedDb);
        });
    }
}