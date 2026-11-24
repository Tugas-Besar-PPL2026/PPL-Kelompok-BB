<?php

namespace Tests\Browser\PBI02;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class TC_PBI2_008_Test extends DuskTestCase
{
    public function test_unggah_foto_jpg_valid(): void
    {
        $userData = [
            'name'     => 'Kandidat Upload Foto',
            'password' => Hash::make('password123'),
        ];

        if (Schema::hasColumn('users', 'role')) {
            $userData['role'] = 'kandidat';
        }

        $kandidat = User::firstOrCreate(
            ['email' => 'kandidat@proyekkita.test'],
            $userData
        );

        if (Schema::hasColumn('users', 'role') && $kandidat->role !== 'kandidat') {
            $kandidat->update(['role' => 'kandidat']);
        }

        // Pastikan file JPG valid tersedia di storage/testing/foto-profil.jpg
        $testingDir = storage_path('testing');
        if (! is_dir($testingDir)) {
            mkdir($testingDir, 0777, true);
        }

        $photoPath = $testingDir . DIRECTORY_SEPARATOR . 'foto-profil.jpg';
        if (! file_exists($photoPath)) {
            if (function_exists('imagecreatetruecolor') && function_exists('imagejpeg')) {
                $image = imagecreatetruecolor(240, 240);
                $bgColor = imagecolorallocate($image, 37, 99, 235);
                imagefill($image, 0, 0, $bgColor);
                imagejpeg($image, $photoPath, 90);
                imagedestroy($image);
            } else {
                file_put_contents(
                    $photoPath,
                    base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=')
                );
            }
        }

        $this->browse(function (Browser $browser) use ($kandidat, $photoPath) {
            $browser->loginAs($kandidat)
                ->visit('/kandidat/profil/data-diri')
                ->assertPathIs('/kandidat/profil/data-diri')
                ->waitFor('input[type="file"]');

            // Lengkapi field wajib jika kosong agar penyimpanan tidak gagal validasi
            $defaults = [
                'full_name'  => 'Kandidat Upload Foto',
                'domicile'   => 'Kota Bandung',
                'domisili'   => 'Kota Bandung',
                'city'       => 'Kota Bandung',
                'headline'   => 'Desainer produk digital',
                'university' => 'Telkom University',
                'major'      => 'S1 Sistem Informasi',
                'summary'    => 'Profil kandidat untuk uji unggah foto JPG.',
            ];

            foreach ($defaults as $name => $sampleValue) {
                $selector = "input[name=\"{$name}\"], textarea[name=\"{$name}\"], select[name=\"{$name}\"]";
                if ($browser->element($selector) !== null) {
                    $currentVal = (string) $browser->value($selector);
                    if (trim($currentVal) === '') {
                        if ($browser->element("select[name=\"{$name}\"]") !== null) {
                            $browser->select($name, $sampleValue);
                        } else {
                            $browser->type($name, $sampleValue);
                        }
                    }
                }
            }

            // Deteksi nama atribut input file foto (default: photo)
            $fileField = 'photo';
            foreach (['photo', 'avatar', 'profile_photo', 'foto'] as $candidateInput) {
                if ($browser->element("input[type=\"file\"][name=\"{$candidateInput}\"]") !== null) {
                    $fileField = $candidateInput;
                    break;
                }
            }

            $browser->attach($fileField, $photoPath)
                ->press('Simpan Profil')
                ->pause(700);

            $result = $browser->script('return document.body ? document.body.innerText : "";');
            $bodyText = is_array($result) ? ($result[0] ?? '') : (string) $result;
            $cleanText = preg_replace('/\s+/', ' ', trim($bodyText));

            if (! str_contains(strtolower($cleanText), 'berhasil')) {
                $this->fail(
                    'Unggah foto belum menampilkan pesan berhasil. Cuplikan halaman: '
                    . mb_substr($cleanText, 200, 1000)
                );
            }

            $browser->assertSee('berhasil');
        });
    }
}