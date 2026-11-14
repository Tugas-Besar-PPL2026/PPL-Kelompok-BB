<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUmkm() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('is_active', true)],
            'skills' => ['required', 'array', 'min:1'],
            'skills.*' => ['integer', Rule::exists('skills', 'id')->where('is_active', true)],
            'description' => ['required', 'string'],
            'expected_output' => ['required', 'string'],
            'application_deadline' => ['required', 'date', 'after:today'],
            'target_completion_date' => ['required', 'date', 'after:application_deadline'],
            'quota' => ['required', 'integer', 'min:1', 'max:1000'],
            'total_compensation' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'payment_method' => ['required', Rule::in(['sekali', 'bertahap'])],
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.amount' => ['required', 'numeric', 'min:1', 'max:9999999999'],
            'terms.*.condition' => ['required', 'string', 'max:255'],
        ];
    }

    /** Aturan yang melibatkan beberapa termin sekaligus. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $terms = array_values($this->input('terms', []));
                $method = $this->input('payment_method');

                if ($method === 'sekali' && count($terms) !== 1) {
                    $validator->errors()->add('terms', 'Pembayaran sekali bayar hanya terdiri dari satu termin.');
                }

                if ($method === 'bertahap' && count($terms) < 2) {
                    $validator->errors()->add('terms', 'Pembayaran bertahap minimal terdiri dari dua termin.');
                }

                // Jumlah termin baru dibandingkan kalau total kompensasi dan nominal tiap termin sudah valid.
                if ($validator->errors()->hasAny(['total_compensation', 'terms.*.amount'])) {
                    return;
                }

                $sum = collect($terms)->sum(fn ($term) => (float) ($term['amount'] ?? 0));

                if (round($sum, 2) !== round((float) $this->input('total_compensation'), 2)) {
                    $validator->errors()->add('terms', 'Jumlah nominal termin harus sama dengan total kompensasi.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min.',
            'integer' => ':attribute harus berupa angka.',
            'numeric' => ':attribute harus berupa angka.',
            'date' => ':attribute harus berupa tanggal.',
            'exists' => ':attribute yang dipilih tidak tersedia.',
            'skills.min' => 'Pilih minimal satu keterampilan.',
            'application_deadline.after' => 'Tenggat lamaran paling cepat besok.',
            'target_completion_date.after' => 'Target penyelesaian harus setelah tenggat lamaran.',
            'quota.max' => 'Kuota kandidat maksimal :max orang.',
            'total_compensation.max' => 'Total kompensasi maksimal Rp9.999.999.999.',
            'payment_method.in' => 'Cara pembayaran tidak valid.',
            'terms.required' => 'Tambahkan minimal satu termin pembayaran.',
            'terms.*.amount.min' => 'Nominal termin minimal Rp1.',
            'terms.*.amount.max' => 'Nominal termin maksimal Rp9.999.999.999.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul proyek',
            'category_id' => 'Kategori',
            'skills' => 'Keterampilan',
            'skills.*' => 'Keterampilan',
            'description' => 'Deskripsi pekerjaan',
            'expected_output' => 'Hasil yang diharapkan',
            'application_deadline' => 'Tenggat lamaran',
            'target_completion_date' => 'Target penyelesaian',
            'quota' => 'Kuota kandidat',
            'total_compensation' => 'Total kompensasi',
            'payment_method' => 'Cara pembayaran',
            'terms' => 'Termin pembayaran',
            'terms.*.amount' => 'Nominal termin',
            'terms.*.condition' => 'Kondisi pembayaran',
        ];
    }
}
