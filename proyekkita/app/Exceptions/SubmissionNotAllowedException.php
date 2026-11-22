<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [PBI-31] Dilempar SubmissionService saat penyerahan hasil pekerjaan tidak dapat disimpan:
 * kesepakatan tidak aktif, sudah ada versi yang sedang ditinjau/disetujui/perlu revisi,
 * atau penyimpanan berkas/database gagal.
 * Pesannya sudah berbahasa Indonesia dan aman langsung ditampilkan kepada kandidat.
 */
class SubmissionNotAllowedException extends RuntimeException
{
}
