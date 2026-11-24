<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [PBI-24 / PBI-25] Dilempar saat aksi pada kesepakatan melanggar aturan bisnis, misalnya kesepakatan
 * sudah dikirim lalu dikirim ulang, atau kesepakatan sudah ditanggapi lalu ditanggapi lagi.
 * Pesannya berbahasa Indonesia dan aman ditampilkan langsung kepada pengguna.
 */
class AgreementNotAllowedException extends RuntimeException
{
}
