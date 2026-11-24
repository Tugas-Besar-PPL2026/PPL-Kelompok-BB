<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * [PBI-32] Dilempar SubmissionReviewService saat keputusan peninjauan tidak dapat disimpan:
 * kesepakatan tidak aktif, tidak ada versi yang menunggu tinjauan, versi yang ditinjau sudah berubah,
 * atau batas revisi sudah habis. Pesannya aman langsung ditampilkan kepada UMKM.
 */
class SubmissionReviewNotAllowedException extends RuntimeException
{
}
