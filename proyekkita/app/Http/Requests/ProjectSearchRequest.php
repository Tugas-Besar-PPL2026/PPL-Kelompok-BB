<?php

namespace App\Http\Requests;

use App\Services\ProjectDiscoveryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * [PBI-15 / FR-08] Validasi parameter pencarian, filter, dan pengurutan di halaman Eksplor Proyek.
 * Semua parameter dikirim lewat query string (GET) supaya hasil pencarian bisa di-bookmark dan tetap
 * terbawa saat pindah halaman (pagination).
 */
class ProjectSearchRequest extends FormRequest
{
    /** Kalau validasi gagal, kembali ke daftar tanpa filter (galat tampil di panel filter). */
    protected $redirectRoute = 'kandidat.proyek.index';

    /** Halaman ini khusus kandidat (middleware peran belum ada di repository). */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isKandidat();
    }

    public function rules(): array
    {
        $maks = ['nullable', 'numeric', 'min:0'];
        // gte hanya dipasang kalau minimum diisi. Tanpa ini, mengisi maksimum saja akan selalu gagal.
        if ($this->filled('kompensasi_min')) {
            $maks[] = 'gte:kompensasi_min';
        }

        return [
            'cari' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', 'integer', 'exists:categories,id'],
            'keterampilan' => ['nullable', 'array'],
            'keterampilan.*' => ['integer', 'exists:skills,id'],
            'kompensasi_min' => ['nullable', 'numeric', 'min:0'],
            'kompensasi_maks' => $maks,
            'tenggat_sebelum' => ['nullable', 'date'],
            'urut' => ['nullable', Rule::in(array_keys(ProjectDiscoveryService::SORTS))],
        ];
    }

    public function messages(): array
    {
        return [
            'kompensasi_maks.gte' => 'Kompensasi maksimum tidak boleh lebih kecil dari kompensasi minimum.',
            'kategori.exists' => 'Kategori yang dipilih tidak valid.',
            'keterampilan.*.exists' => 'Keterampilan yang dipilih tidak valid.',
            'urut.in' => 'Pilihan urutan tidak valid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cari' => 'kata kunci',
            'kategori' => 'kategori',
            'keterampilan' => 'keterampilan',
            'kompensasi_min' => 'kompensasi minimum',
            'kompensasi_maks' => 'kompensasi maksimum',
            'tenggat_sebelum' => 'tenggat lamaran',
            'urut' => 'urutan',
        ];
    }

    /**
     * Filter yang sudah dibersihkan dan lengkap (semua kunci selalu ada), siap dipakai service dan view.
     *
     * @return array{cari: string, kategori: ?int, keterampilan: list<int>, kompensasi_min: ?string, kompensasi_maks: ?string, tenggat_sebelum: ?string, urut: string}
     */
    public function filters(): array
    {
        $data = $this->validated();

        return [
            'cari' => trim((string) ($data['cari'] ?? '')),
            'kategori' => isset($data['kategori']) ? (int) $data['kategori'] : null,
            'keterampilan' => array_values(array_map('intval', $data['keterampilan'] ?? [])),
            'kompensasi_min' => isset($data['kompensasi_min']) ? (string) $data['kompensasi_min'] : null,
            'kompensasi_maks' => isset($data['kompensasi_maks']) ? (string) $data['kompensasi_maks'] : null,
            'tenggat_sebelum' => $data['tenggat_sebelum'] ?? null,
            'urut' => $data['urut'] ?? ProjectDiscoveryService::DEFAULT_SORT,
        ];
    }
}
