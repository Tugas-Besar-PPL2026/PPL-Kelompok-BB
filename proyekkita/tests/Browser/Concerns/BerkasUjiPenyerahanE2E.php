<?php

namespace Tests\Browser\Concerns;

use RuntimeException;

/**
 * [PBI-31] Fixture berkas unggahan untuk Dusk E2E penyerahan hasil pekerjaan.
 *
 * Berkas dibuat saat test berjalan (deterministik, tanpa ekstensi PHP tambahan seperti zip/gd) di
 * storage/framework/testing/dusk-pbi31 (sudah di-.gitignore). Berkas besar (8-31 MB) tidak disimpan di repository.
 * Berkas yang sudah ada dengan ukuran sama dipakai ulang agar test dapat diulang dengan cepat.
 *
 * Jenis: pdf | jpg | png | zip | docx | teks | kosong. $ukuran = ukuran persis dalam byte (pdf dan zip).
 */
trait BerkasUjiPenyerahanE2E
{
    /** 1 MB = 1024 x 1024 byte; 25 MB = 26.214.400 byte (batas aplikasi). */
    protected const MB = 1048576;

    private const PNG_8X8 = 'iVBORw0KGgoAAAANSUhEUgAAAAgAAAAICAIAAABLbSncAAAAFklEQVR4XmNUTX7NgA0wYRVlYBikEgAlQwGDBxGBDwAAAABJRU5ErkJggg==';

    private const JPEG_8X8 = '/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAAIAAgDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDn6KKK/RT5o//Z';

    protected function berkasUji(string $nama, string $jenis, ?int $ukuran = null): string
    {
        $folder = storage_path('framework'.DIRECTORY_SEPARATOR.'testing'.DIRECTORY_SEPARATOR.'dusk-pbi31');
        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException("Folder fixture tidak dapat dibuat: {$folder}");
        }

        // Subfolder per jenis+ukuran: nama berkas sama (mis. katalog.pdf) dengan isi berbeda tidak saling menimpa.
        $folder .= DIRECTORY_SEPARATOR.$jenis.'-'.($ukuran ?? 'std');
        if (! is_dir($folder) && ! mkdir($folder, 0775, true) && ! is_dir($folder)) {
            throw new RuntimeException("Folder fixture tidak dapat dibuat: {$folder}");
        }

        $path = $folder.DIRECTORY_SEPARATOR.$nama;

        $isi = match ($jenis) {
            'png' => base64_decode(self::PNG_8X8),
            'jpg' => base64_decode(self::JPEG_8X8),
            'teks' => '<?php echo 1;',
            'kosong' => '',
            default => null,
        };

        if ($isi !== null) {
            if (! is_file($path) || file_get_contents($path) !== $isi) {
                file_put_contents($path, $isi);
            }

            return $path;
        }

        $ukuran ??= match ($jenis) {
            'pdf' => 24 * 1024,
            default => 16 * 1024,
        };

        clearstatcache(true, $path);
        if (is_file($path) && filesize($path) === $ukuran) {
            return $path;
        }

        match ($jenis) {
            'pdf' => $this->tulisPdf($path, $ukuran),
            'zip' => $this->tulisZip($path, 'hasil/aset-label.ai', $ukuran),
            'docx' => $this->tulisZip($path, '[Content_Types].xml', $ukuran),
            default => throw new RuntimeException("Jenis fixture tidak dikenal: {$jenis}"),
        };

        clearstatcache(true, $path);
        if (filesize($path) !== $ukuran) {
            throw new RuntimeException("Ukuran fixture {$nama} tidak sesuai: ".filesize($path)." byte, diharapkan {$ukuran}.");
        }

        return $path;
    }

    /** PDF valid sederhana (diawali %PDF-), ukurannya dipenuhi dengan baris komentar sebelum %%EOF. */
    private function tulisPdf(string $path, int $ukuran): void
    {
        $kepala = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            ."2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            ."3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]>>endobj\n"
            ."trailer<</Root 1 0 R>>\n";
        $ekor = "%%EOF\n";
        $sisa = $ukuran - strlen($kepala) - strlen($ekor);
        if ($sisa < 0) {
            throw new RuntimeException('Ukuran PDF fixture terlalu kecil.');
        }

        $f = fopen($path, 'wb');
        fwrite($f, $kepala);
        $baris = '%'.str_repeat('P', 1022)."\n";
        while ($sisa >= strlen($baris)) {
            fwrite($f, $baris);
            $sisa -= strlen($baris);
        }
        if ($sisa > 0) {
            fwrite($f, $sisa === 1 ? "\n" : '%'.str_repeat('P', $sisa - 2)."\n");
        }
        fwrite($f, $ekor);
        fclose($f);
    }

    /** ZIP valid (metode STORE, satu entri) dengan ukuran total persis $ukuran byte. */
    private function tulisZip(string $path, string $namaEntri, int $ukuran): void
    {
        $n = strlen($namaEntri);
        $data = $ukuran - (30 + $n) - (46 + $n) - 22;
        if ($data < 0) {
            throw new RuntimeException('Ukuran ZIP fixture terlalu kecil.');
        }

        $potongan = str_repeat('PBI31-DUSK-FIXTURE-', 55188); // +/- 1 MB
        $hash = hash_init('crc32b');
        for ($sisa = $data; $sisa > 0; $sisa -= strlen($potongan)) {
            hash_update($hash, $sisa >= strlen($potongan) ? $potongan : substr($potongan, 0, $sisa));
        }
        $crc = hexdec(hash_final($hash));

        $f = fopen($path, 'wb');
        $lokal = pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, 0, 0x21, $crc, $data, $data, $n, 0).$namaEntri;
        fwrite($f, $lokal);
        for ($sisa = $data; $sisa > 0; $sisa -= strlen($potongan)) {
            fwrite($f, $sisa >= strlen($potongan) ? $potongan : substr($potongan, 0, $sisa));
        }
        $pusat = pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 0, 0, 0x21, $crc, $data, $data, $n, 0, 0, 0, 0, 0, 0).$namaEntri;
        fwrite($f, $pusat);
        fwrite($f, pack('VvvvvVVv', 0x06054B50, 0, 0, 1, 1, strlen($pusat), strlen($lokal) + $data, 0));
        fclose($f);
    }
}
