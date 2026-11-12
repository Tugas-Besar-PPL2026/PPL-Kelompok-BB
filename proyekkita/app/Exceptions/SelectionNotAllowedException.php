<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [PBI-20/21] Dilempar saat keputusan seleksi UMKM melanggar aturan bisnis
 * (perubahan status tidak diizinkan, atau kuota proyek sudah penuh).
 * Pesannya berbahasa Indonesia dan aman ditampilkan langsung kepada UMKM.
 */
class SelectionNotAllowedException extends RuntimeException
{
}
