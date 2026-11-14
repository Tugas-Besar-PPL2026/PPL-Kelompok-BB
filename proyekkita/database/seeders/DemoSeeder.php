<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Models\Category;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk pengembangan: dua akun UMKM (terverifikasi dan menunggu verifikasi), satu kandidat
 * lengkap dengan keterampilan, pengalaman, dan portofolio, serta satu proyek yang sudah dipublikasikan.
 * Semua akun demo memakai kata sandi: password
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Akun UMKM yang sudah terverifikasi
        $umkmUser = User::updateOrCreate(['email' => 'umkm@proyekkita.test'], [
            'name' => 'Rina Kartika', 'password' => 'password', 'role' => UserRole::Umkm,
        ]);
        $umkm = $umkmUser->umkmProfile()->updateOrCreate([], [
            'business_name' => 'Kopi Nusantara',
            'description' => 'Kedai dan roastery kopi lokal di Bandung yang menjual biji kopi Nusantara kemasan 200 g dan 1 kg.',
            'business_field' => 'Kuliner & Minuman',
            'contact_phone' => '0812-2045-7781',
            'city' => 'Bandung',
            'address' => 'Jl. Cihampelas No. 112, Kec. Coblong, Kota Bandung, Jawa Barat 40131',
            'nib' => '9120304567812',
            'business_link' => 'instagram.com/kopinusantara.bdg',
            'verification_status' => VerificationStatus::Verified,
            'verified_at' => now(),
        ]);

        // Akun UMKM yang masih menunggu verifikasi admin
        $pendingUser = User::updateOrCreate(['email' => 'umkm.pending@proyekkita.test'], [
            'name' => 'Hendra Gunawan', 'password' => 'password', 'role' => UserRole::Umkm,
        ]);
        $pendingUser->umkmProfile()->updateOrCreate([], [
            'business_name' => 'Laundry Bersih Kilat',
            'description' => 'Laundry kiloan dan satuan dengan layanan antar-jemput untuk area Bandung Timur.',
            'business_field' => 'Jasa Laundry',
            'contact_phone' => '0857-2211-9034',
            'city' => 'Bandung',
            'address' => 'Jl. Cikutra No. 45, Kec. Cibeunying Kidul, Kota Bandung',
            'nib' => '8120015566702',
            'verification_status' => VerificationStatus::Pending,
        ]);

        // Akun kandidat beserta profil, keterampilan, pengalaman, dan portofolio
        $kandidatUser = User::updateOrCreate(['email' => 'kandidat@proyekkita.test'], [
            'name' => 'Fajar Pratama', 'password' => 'password', 'role' => UserRole::Kandidat,
        ]);
        $kandidat = $kandidatUser->candidateProfile()->updateOrCreate([], [
            'full_name' => 'Fajar Pratama',
            'headline' => 'Desainer grafis & kemasan produk UMKM',
            'university' => 'Telkom University',
            'major' => 'S1 Desain Komunikasi Visual',
            'city' => 'Kota Bandung',
            'summary' => 'Mahasiswa DKV yang berfokus pada desain kemasan dan identitas visual produk UMKM.',
        ]);
        $kandidat->skills()->sync(Skill::whereIn('name', ['Figma', 'Desain Grafis', 'Desain Kemasan', 'Ilustrasi', 'Canva'])->pluck('id'));
        $kandidat->experiences()->firstOrCreate(['work_type' => 'Desain kemasan produk UMKM (freelance)'], [
            'duration' => '8 bulan',
            'description' => 'Merancang label dan kemasan untuk 4 UMKM makanan ringan di Bandung, termasuk penyiapan file siap cetak untuk percetakan.',
        ]);
        $kandidat->experiences()->firstOrCreate(['work_type' => 'Desainer grafis Himpunan Mahasiswa DKV'], [
            'duration' => '6 bulan',
            'description' => 'Membuat materi publikasi acara dan konten Instagram himpunan.',
        ]);
        $kandidat->portfolios()->firstOrCreate(['title' => 'Label Kemasan Sambal Bu Iyah'], [
            'description' => 'Redesain label 3 varian sambal kemasan botol, siap cetak.',
            'link' => 'https://behance.net/fajarpratama/sambal-bu-iyah',
        ]);
        $kandidat->portfolios()->firstOrCreate(['title' => 'Katalog Produk Roti Kampung'], [
            'description' => 'Katalog digital 12 halaman untuk reseller.',
            'file_path' => 'portfolios/demo/katalog-roti-kampung.pdf',
        ]);
        $kandidat->portfolios()->firstOrCreate(['title' => 'Identitas Visual Kedai Seduh'], [
            'description' => 'Logo, kartu menu, dan stiker gelas.',
            'link' => 'https://dribbble.com/fajarp/kedai-seduh',
        ]);

        // Proyek yang sudah dipublikasikan dengan skema pembayaran 2 termin
        $project = $umkm->projects()->updateOrCreate(['title' => 'Redesain Katalog Digital & Label Kemasan'], [
            'category_id' => Category::where('name', 'Desain Grafis & Branding')->value('id'),
            'description' => 'Memperbarui label kemasan tiga varian biji kopi dan menyusun katalog produk digital berformat PDF.',
            'expected_output' => "Desain label kemasan 3 SKU siap cetak\nKatalog produk digital 8 halaman (PDF)\nFile sumber Figma",
            'application_deadline' => now()->addDays(16)->toDateString(),
            'target_completion_date' => now()->addDays(41)->toDateString(),
            'quota' => 2,
            'total_compensation' => 2500000,
            'status' => ProjectStatus::Published,
            'published_at' => now(),
        ]);
        $project->skills()->sync(Skill::whereIn('name', ['Figma', 'Desain Grafis', 'Desain Kemasan'])->pluck('id'));
        $project->paymentTerms()->updateOrCreate(['term_number' => 1], ['amount' => 1250000, 'condition' => 'Uang muka setelah kesepakatan berstatus aktif']);
        $project->paymentTerms()->updateOrCreate(['term_number' => 2], ['amount' => 1250000, 'condition' => 'Pelunasan setelah hasil akhir disetujui UMKM']);
    }
}
