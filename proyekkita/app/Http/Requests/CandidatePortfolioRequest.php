<?php

namespace App\Http\Requests;

use App\Models\Portfolio;
use App\Support\SelectionPresenter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CandidatePortfolioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isKandidat() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'link' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $rawLink = trim((string) $this->input('link', ''));
                $hasLink = $rawLink !== '';

                if ($hasLink && SelectionPresenter::safeUrl($rawLink) === null) {
                    $validator->errors()->add('link', 'Tautan portofolio harus berupa URL http/https yang valid.');
                }

                $hasNewFile = $this->hasFile('file');
                $portfolio = $this->route('portfolio');
                $hasExistingFile = $portfolio instanceof Portfolio
                    && filled($portfolio->file_path)
                    && ! $this->boolean('remove_file');

                if (! $hasLink && ! $hasNewFile && ! $hasExistingFile) {
                    $validator->errors()->add('link', 'Isi minimal salah satu: tautan atau dokumen.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max' => ':attribute maksimal :max karakter.',
            'file.file' => 'Dokumen portofolio tidak valid.',
            'file.mimes' => 'Dokumen portofolio harus berformat PDF, JPG, atau PNG.',
            'file.max' => 'Ukuran dokumen portofolio maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'Judul portofolio',
            'description' => 'Deskripsi singkat',
            'link' => 'Tautan portofolio',
            'file' => 'Dokumen portofolio',
        ];
    }
}
