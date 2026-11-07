<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [PBI-16] Dilempar ApplicationService saat sebuah lamaran tidak boleh diterima karena aturan bisnis
 * (proyek tutup, profil belum memenuhi syarat, atau sudah pernah melamar).
 * Pesannya sudah berbahasa Indonesia dan aman langsung ditampilkan kepada kandidat.
 */
class ApplicationNotAllowedException extends RuntimeException
{
}
