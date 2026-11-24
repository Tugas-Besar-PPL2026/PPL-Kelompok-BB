<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Exceptions\AgreementNotAllowedException;
use App\Models\Agreement;
use Illuminate\Support\Facades\DB;

/**
 * [PBI-25 / FR-12] Kandidat menanggapi kesepakatan yang dikirim UMKM: menyetujui atau menolak dengan alasan.
 *
 *   Dikirim -> Aktif    : approve(), waktu persetujuan disimpan di approved_at
 *   Dikirim -> Ditolak  : reject(),  alasan di rejection_reason dan waktunya di rejected_at
 *
 * Kesepakatan yang sudah ditanggapi (Aktif, Ditolak, Selesai, Dibatalkan) tidak dapat ditanggapi lagi.
 * Status dibaca ulang dari baris yang dikunci (lockForUpdate), sehingga halaman lama di jendela lain
 * tidak dapat menimpa tanggapan yang sudah tersimpan. Tidak ada fitur negosiasi atau pengiriman ulang.
 */
class AgreementResponseService
{
    public const ALREADY_RESPONDED_MESSAGE = 'Kesepakatan ini sudah ditanggapi.';
    public const REASON_REQUIRED_MESSAGE = 'Alasan penolakan wajib diisi.';

    public function approve(Agreement $agreement): Agreement
    {
        return DB::transaction(function () use ($agreement) {
            $locked = $this->lockSent($agreement);

            $locked->status = AgreementStatus::Active;
            $locked->approved_at = now();
            $locked->rejection_reason = null;
            $locked->save();

            return $locked;
        });
    }

    public function reject(Agreement $agreement, string $reason): Agreement
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new AgreementNotAllowedException(self::REASON_REQUIRED_MESSAGE);
        }

        return DB::transaction(function () use ($agreement, $reason) {
            $locked = $this->lockSent($agreement);

            $locked->status = AgreementStatus::Rejected;
            $locked->rejection_reason = $reason;
            $locked->rejected_at = now();
            $locked->save();

            return $locked;
        });
    }

    /** Kunci baris kesepakatan dan pastikan statusnya masih Dikirim (menunggu persetujuan). */
    private function lockSent(Agreement $agreement): Agreement
    {
        $locked = Agreement::query()->whereKey($agreement->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== AgreementStatus::Sent) {
            throw new AgreementNotAllowedException(self::ALREADY_RESPONDED_MESSAGE);
        }

        return $locked;
    }
}
