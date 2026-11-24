# PBI-33 — Dusk E2E: Melihat Catatan Revisi dan Mengirim Versi Perbaikan (Kandidat)

Sumber test case: sheet **PBI-33** di `Test_Case_Kelompok_BB_SI4801_2.xlsx` (16 test case). Satu test case = satu file Dusk = satu method.

## Cara menjalankan

```bash
php artisan dusk --group=pbi33
php artisan dusk --group=TC-PBI33-001
```

Syarat lingkungan sama dengan PBI-31 (lihat `tests/Browser/Shahira/PBI31/README.md`): database MySQL testing terpisah, `DUSK_IZINKAN_SIMULASI_DB=true`, batas unggah server 30M/32M, hak membuat TRIGGER.

## Exception: setup database minimum

| Simulasi | Alasan | Dipakai di |
| --- | --- | --- |
| Baris `agreements` (termasuk `revision_limit` 0/1/2) untuk lamaran yang diterima lewat UI | PBI-24/25 belum diimplementasikan | Semua TC |
| Status kesepakatan menjadi Dibatalkan | PBI-28 belum ada | TC-009 |
| Trigger MySQL sementara yang menolak INSERT penyerahan | Kegagalan database tidak dapat dipicu dari UI | TC-014 |

Tidak ada simulasi status penyerahan atau revisi: Versi 1 (PBI-31), keputusan UMKM (PBI-32), dan versi perbaikan (PBI-33) seluruhnya dibuat lewat UI.

## Mapping test case → method Dusk → expected result

| ID Test Case | File / method Dusk | Expected result (Excel) |
| --- | --- | --- |
| TC-PBI33-001 | `TC_PBI33_001_Test::test_TC_PBI33_001_melihat_banner_catatan_revisi_dan_form_perbaikan` | Banner 'Permintaan revisi dari Kopi Nusantara · {waktu WIB}' berisi catatan revisi dan 'Revisi ke-1 dari batas 2 kali.'. Kartu 'Kirim Versi Perbaikan · Versi 2' dengan label 'Deskripsi perubahan *' dan tombol 'Kirim Versi 2'. Riwayat: 'Versi 2 · Sedang disiapkan' di atas Versi 1 (Perlu Revisi, 'Catatan UMKM: Warna label terlalu gelap, ...'). Badge ringkasan 'Perlu Revisi'. |
| TC-PBI33-002 | `TC_PBI33_002_Test::test_TC_PBI33_002_mengirim_versi_2_berkas_tautan_deskripsi` | Pesan 'Hasil pekerjaan Versi 2 berhasil diserahkan dan berstatus Menunggu Tinjauan.'; banner revisi hilang; info 'Versi 2 sedang ditinjau UMKM.'. Riwayat: Versi 2 (Menunggu Tinjauan) di atas Versi 1 (tetap Perlu Revisi dengan catatannya). Data: version_number 2, status submitted, file di submissions/{id}/v2/katalog-kopi-nusantara-v2.pdf; Versi 1 tidak berubah. |
| TC-PBI33-003 | `TC_PBI33_003_Test::test_TC_PBI33_003_versi_2_hanya_tautan_atau_hanya_zip` | A dan B diterima sebagai Versi 2; A tanpa file_path, B tanpa result_link dengan file di folder v2. |
| TC-PBI33-004 | `TC_PBI33_004_Test::test_TC_PBI33_004_validasi_isian_versi_perbaikan` | A: 'Isi minimal salah satu: berkas hasil pekerjaan atau tautan hasil pekerjaan.'. B: 'Deskripsi hasil pekerjaan wajib diisi.'. C: 'Deskripsi hasil pekerjaan maksimal 2000 karakter.'. Banner revisi dan judul 'Kirim Versi Perbaikan · Versi 2' tetap tampil; tidak ada Versi 2 tersimpan. |
| TC-PBI33-005 | `TC_PBI33_005_Test::test_TC_PBI33_005_validasi_berkas_versi_perbaikan` | A: 'Berkas hasil pekerjaan harus berformat PDF, JPG, PNG, atau ZIP.'. B: 'Ukuran berkas hasil pekerjaan maksimal 25 MB.'. Tidak ada Versi 2 maupun berkas tersimpan. |
| TC-PBI33-006 | `TC_PBI33_006_Test::test_TC_PBI33_006_validasi_tautan_versi_perbaikan` | A: 'Tautan hasil pekerjaan harus berupa URL http/https yang valid.'. B: 'Tautan hasil pekerjaan maksimal 255 karakter.'. Tidak ada Versi 2 tersimpan. |
| TC-PBI33-007 | `TC_PBI33_007_Test::test_TC_PBI33_007_umpan_balik_form_versi_perbaikan` | Setelah galat: tautan dan deskripsi tetap terisi, penghitung 2001/2000, label tetap 'Deskripsi perubahan'. Penghitung 45/2000; setelah diklik tombol 'Mengirim…' dan nonaktif. |
| TC-PBI33-008 | `TC_PBI33_008_Test::test_TC_PBI33_008_versi_2_sedang_ditinjau_menolak_versi_baru` | Banner revisi dan form tidak tampil; info 'Versi 2 sedang ditinjau UMKM.'. Request ditolak 'Hasil pekerjaan Versi 2 sedang ditinjau UMKM. Versi baru belum dapat dikirim.'. Jumlah versi tetap 2. |
| TC-PBI33-009 | `TC_PBI33_009_Test::test_TC_PBI33_009_kesepakatan_dibatalkan_menolak_versi_perbaikan` | A: form tidak tampil, info 'Kesepakatan sudah dibatalkan. Hasil pekerjaan tidak dapat diserahkan.'; request ditolak dengan pesan sama. B: ditolak dengan pesan sama. Tidak ada Versi 2. |
| TC-PBI33-010 | `TC_PBI33_010_Test::test_TC_PBI33_010_kiriman_ganda_versi_2_hanya_satu` | Hanya Versi 2 yang tersimpan; kiriman kedua ditolak 'Hasil pekerjaan Versi 2 sedang ditinjau UMKM. Versi baru belum dapat dikirim.' tanpa error 500. |
| TC-PBI33-011 | `TC_PBI33_011_Test::test_TC_PBI33_011_manipulasi_nomor_versi_dan_status_diabaikan` | Tersimpan sebagai Versi 2 berstatus submitted; field tambahan diabaikan. |
| TC-PBI33-012 | `TC_PBI33_012_Test::test_TC_PBI33_012_siklus_revisi_kedua_dan_versi_3` | Banner 'Revisi ke-2 dari batas 2 kali.' dengan catatan kedua; form 'Kirim Versi Perbaikan · Versi 3'. Versi 3 tersimpan (Menunggu Tinjauan). Di halaman UMKM kuota 'Revisi terpakai 2 dari 2' dan opsi 'Minta revisi' nonaktif. |
| TC-PBI33-013 | `TC_PBI33_013_Test::test_TC_PBI33_013_hak_akses_versi_perbaikan` | UMKM 403; Admin 403; kandidat lain 404. Tidak ada Versi 2 tersimpan. |
| TC-PBI33-014 | `TC_PBI33_014_Test::test_TC_PBI33_014_kegagalan_simpan_versi_2_membersihkan_berkas` | Pesan 'Hasil pekerjaan gagal disimpan. Silakan coba lagi.'; isian dipertahankan; banner revisi tetap tampil; tidak ada Versi 2 dan folder v2 kosong; Versi 1 tetap Perlu Revisi. |
| TC-PBI33-015 | `TC_PBI33_015_Test::test_TC_PBI33_015_script_pada_catatan_revisi_tampil_sebagai_teks` | Catatan tampil sebagai teks mentah di banner dan riwayat; tidak ada dialog alert. |
| TC-PBI33-016 | `TC_PBI33_016_Test::test_TC_PBI33_016_siklus_penuh_revisi_hingga_disetujui` | Kandidat akhirnya melihat info 'Hasil pekerjaan sudah disetujui UMKM.'; riwayat Versi 2 Disetujui dan Versi 1 Perlu Revisi dengan catatan; tidak ada form. UMKM melihat 'Hasil pekerjaan Versi 2 sudah disetujui.'. |
