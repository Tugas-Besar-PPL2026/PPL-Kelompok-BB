@extends('layouts.app')

@section('content')
<div style="max-width: 1400px; margin: 0 auto;">
    <h1 style="font-size: 24px; font-weight: bold; color: #0f172a; margin-bottom: 4px;">Data Master</h1>
    <p style="font-size: 14px; color: #64748b; margin-bottom: 20px;">
        Kelola kategori proyek dan keterampilan yang menjadi pilihan pada profil kandidat, publikasi proyek, dan filter pencarian.
    </p>

    <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 14px; color: #334155;">
        <p style="font-weight: 600; color: #0f172a; margin-bottom: 2px;">Data master tidak dihapus</p>
        <p style="color: #475569; margin: 0;">
            Data yang dinonaktifkan tidak dapat dipilih saat membuat proyek atau mengisi profil baru, tetapi tetap ditampilkan pada proyek dan profil lama yang sudah menggunakannya.
        </p>
    </div>

    @if(session('status'))
        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Layout 2 Kolom Berdampingan Persis Mockup Figma --}}
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; align-items: start;">

        {{-- KOLOM 1: KATEGORI PROYEK (PBI-06) --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; min-height: 520px;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
                    <h2 style="font-weight: 600; color: #0f172a; font-size: 16px; margin: 0;">Kategori Proyek</h2>
                    <span style="font-size: 12px; color: #64748b;">
                        {{ $categories->count() }} kategori &middot; {{ $activeCategoriesCount }} aktif
                    </span>
                </div>

                <div style="overflow-x: auto; margin-top: 12px;">
                    <table style="width: 100%; text-align: left; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 1px solid #f1f5f9; font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase;">
                                <th style="padding-bottom: 10px;">NAMA</th>
                                <th style="padding-bottom: 10px;">DIPAKAI</th>
                                <th style="padding-bottom: 10px;">STATUS</th>
                                <th style="padding-bottom: 10px; text-align: right;">AKSI</th>
                            </tr>
                        </thead>
                        <tbody style="font-size: 14px;">
                            @forelse($categories as $category)
                                {{-- Baris Normal --}}
                                <tr id="cat-row-{{ $category->id }}" style="border-bottom: 1px solid #f8fafc;">
                                    <td style="padding: 12px 0; font-weight: 500; color: #1e293b;">
                                        {{ $category->name }}
                                    </td>

                                    <td style="padding: 12px 0; font-size: 12px; color: #64748b;">
                                        {{ $category->projects_count ?? 0 }} proyek
                                    </td>

                                    <td style="padding: 12px 0;">
                                        <form action="{{ route('admin.master.categories.toggle', $category) }}" method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" style="position: relative; display: inline-flex; height: 20px; width: 36px; cursor: pointer; border-radius: 9999px; border: none; background-color: {{ $category->is_active ? '#059669' : '#cbd5e1' }}; padding: 2px;">
                                                <span style="display: inline-block; height: 16px; width: 16px; border-radius: 9999px; background-color: white; transform: translateX({{ $category->is_active ? '16px' : '0px' }}); transition: transform 0.2s;"></span>
                                            </button>
                                            <span style="font-size: 12px; color: {{ $category->is_active ? '#334155' : '#94a3b8' }};">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </form>
                                    </td>

                                    <td style="padding: 12px 0; text-align: right;">
                                        <button type="button"
                                                onclick="toggleEditCategory({{ $category->id }})"
                                                title="Ubah Kategori"
                                                style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                                <path d="m15 5 4 4"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>

                                {{-- Baris Form Edit (Muncul rapi menggantikan 1 baris penuh saat klik ikon pensil) --}}
                                <tr id="cat-edit-{{ $category->id }}" style="display: none; border-bottom: 1px solid #f8fafc; background: #f8fafc;">
                                    <td colspan="4" style="padding: 10px 8px;">
                                        <form action="{{ route('admin.master.categories.update', $category) }}" method="POST" style="display: flex; align-items: center; gap: 8px; width: 100%;">
                                            @csrf
                                            @method('PUT')
                                            <input type="text"
                                                   name="name"
                                                   value="{{ $category->name }}"
                                                   required
                                                   style="flex: 1; border: 1px solid #60a5fa; border-radius: 6px; padding: 6px 10px; font-size: 13px; background: #ffffff; outline: none;">
                                            <button type="submit" style="font-size: 12px; font-weight: 500; background: #2563eb; color: white; padding: 6px 12px; border-radius: 6px; border: none; cursor: pointer;">Simpan</button>
                                            <button type="button" onclick="toggleEditCategory({{ $category->id }})" style="font-size: 12px; font-weight: 500; color: #475569; background: #e2e8f0; padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer;">Batal</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" style="padding: 16px 0; text-align: center; font-size: 12px; color: #94a3b8;">Belum ada data kategori proyek.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <form action="{{ route('admin.master.categories.store') }}" method="POST" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
                @csrf
                <label for="name" style="display: block; font-size: 12px; font-weight: 500; color: #334155; margin-bottom: 8px;">Tambah kategori baru</label>
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="Contoh: Penerjemahan & Bahasa" required style="flex: 1; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px 12px; font-size: 14px; outline: none;">
                    <button type="submit" style="background: #2563eb; color: white; font-size: 14px; font-weight: 500; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer;">+ Tambah</button>
                </div>
                @error('name')
                    <p style="margin-top: 6px; font-size: 12px; color: #dc2626;">{{ $message }}</p>
                @enderror
            </form>
        </div>

        {{-- KOLOM 2: KETERAMPILAN (PBI-07) --}}
        @include('admin.master._keterampilan')

    </div>
</div>

<script>
    function toggleEditCategory(id) {
        const normalRow = document.getElementById('cat-row-' + id);
        const editRow = document.getElementById('cat-edit-' + id);
        if (editRow.style.display === 'none' || editRow.style.display === '') {
            normalRow.style.display = 'none';
            editRow.style.display = 'table-row';
        } else {
            normalRow.style.display = 'table-row';
            editRow.style.display = 'none';
        }
    }
</script>
@endsection