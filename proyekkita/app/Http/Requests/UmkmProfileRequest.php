<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UmkmProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isUmkm() ?? false;
    }

    public function rules(): array
    {
        return [
            'business_name'  => ['required', 'string', 'max:150'],
            'business_field' => ['required', 'string', 'max:100'],
            'description'    => ['required', 'string', 'max:1000'],
            'contact_phone'  => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'city'           => ['required', 'string', 'max:100'],
            'address'        => ['required', 'string', 'max:500'],
            'nib'            => ['nullable', 'digits:13', 'required_without:business_link'],
            'business_link'  => ['nullable', 'string', 'max:255', 'required_without:nib'],
        ];
    }

    public function messages(): array
    {
        return [
            'business_name.required'  => 'Nama usaha wajib diisi.',
            'business_field.required' => 'Bidang usaha wajib diisi.',
            'description.required'    => 'Deskripsi usaha wajib diisi.',
            'contact_phone.required'  => 'Nomor kontak wajib diisi.',
            'contact_phone.regex'     => 'Nomor kontak hanya boleh berisi angka, spasi, +, -, dan tanda kurung.',
            'city.required'           => 'Kota wajib diisi.',
            'address.required'        => 'Alamat wajib diisi.',
            'nib.digits'              => 'NIB harus terdiri dari 13 digit angka.',
            'nib.required_without'    => 'Isi NIB atau tautan informasi usaha.',
            'business_link.required_without' => 'Isi tautan informasi usaha atau NIB.',
        ];
    }
}