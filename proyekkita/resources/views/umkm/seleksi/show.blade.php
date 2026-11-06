@extends('layouts.app')

@section('content')
<div class="p-6 bg-[#FAF9FF] min-h-screen">
    <!-- Breadcrumb & Navigasi -->
    <div class="mb-6 flex justify-between items-center">
        <div>
            <nav class="flex text-sm text-gray-500 mb-2 font-medium" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="{{ route('umkm.seleksi.index') }}" class="hover:text-gray-900 transition">Seleksi Kandidat</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mx-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span class="hover:text-gray-900 transition">{{ $project->title }}</span>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-4 h-4 mx-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            <span class="text-gray-800">{{ $application->candidateProfile->full_name }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-[#191D2B]">Detail Pelamar</h1>
        </div>
        <div>
            <a href="{{ route('umkm.seleksi.index', ['project_id' => $project->id]) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                <svg class="-ml-1 mr-2 h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Informasi Kandidat -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Kartu Profil -->
            <div class="bg-white p-7 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-start">
                    <div class="flex-shrink-0 h-[72px] w-[72px] bg-[#EDF2FF] rounded-xl flex items-center justify-center text-blue-700 font-bold text-2xl">
                        {{ strtoupper(substr($application->candidateProfile->full_name, 0, 2)) }}
                    </div>
                    <div class="ml-5 flex-1">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-bold text-[#191D2B] flex items-center gap-3">
                                    {{ $application->candidateProfile->full_name }}
                                    @if($application->status->value === 'pending')
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-[#FFFBEB] text-[#B45309]">Menunggu Keputusan</span>
                                    @elseif($application->status->value === 'shortlisted')
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-[#F3E8FF] text-[#7E22CE]">Terpilih Sementara</span>
                                    @elseif($application->status->value === 'accepted')
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-[#ECFDF5] text-[#047857]">Diterima</span>
                                    @elseif($application->status->value === 'rejected')
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold bg-[#FEF2F2] text-[#B91C1C]">Ditolak</span>
                                    @endif
                                </h2>
                                <p class="text-gray-600 text-sm mt-1.5">{{ $application->candidateProfile->headline ?? 'Mahasiswa/Lulusan Baru' }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-gray-600">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path></svg>
                                {{ $application->candidateProfile->university }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                {{ $application->candidateProfile->major }}
                            </div>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.243-4.243a8 8 0 1111.314 0z"></path></svg>
                                Kota {{ $application->candidateProfile->city }}
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center text-sm">
                            <div class="flex text-yellow-400">
                                @for($i=0; $i<5; $i++)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                @endfor
                            </div>
                            <span class="font-bold text-[#191D2B] ml-2">4,5</span>
                            <span class="text-gray-500 ml-1.5">rata-rata dari 2 evaluasi UMKM · 2 proyek selesai</span>
                        </div>
                        
                        <div class="mt-6 border-t border-gray-100 pt-5">
                            <h3 class="text-sm font-bold text-[#191D2B] mb-2">Ringkasan diri</h3>
                            <p class="text-gray-600 text-sm leading-relaxed">
                                {{ $application->candidateProfile->summary ?? 'Belum ada ringkasan diri yang ditambahkan.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pesan Lamaran -->
            <div class="bg-white p-7 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <h3 class="text-[17px] font-bold text-[#191D2B]">Pesan Lamaran</h3>
                </div>
                <p class="text-gray-600 text-sm whitespace-pre-line leading-relaxed">{{ $application->message ?? 'Tidak ada pesan lamaran.' }}</p>
                <p class="text-xs text-gray-400 mt-4">Dikirim {{ $application->created_at->format('j M Y, H:i') }}</p>
            </div>

            <!-- Keterampilan & Pengalaman -->
            <div class="bg-white p-7 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                    <h3 class="text-[17px] font-bold text-[#191D2B]">Keterampilan & Pengalaman</h3>
                </div>
                
                <div class="mb-3 flex flex-wrap gap-2">
                    @php
                        $projectSkillIds = $project->skills->pluck('id')->toArray();
                    @endphp
                    @forelse($application->candidateProfile->skills as $skill)
                        <span class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium border {{ in_array($skill->id, $projectSkillIds) ? 'bg-[#EDF2FF] text-blue-700 border-blue-200' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                            {{ $skill->name }}
                        </span>
                    @empty
                        <span class="text-sm text-gray-500">Belum ada keterampilan yang ditambahkan.</span>
                    @endforelse
                </div>
                <p class="text-xs text-gray-500 mb-6">Keterampilan yang dibutuhkan proyek ditandai lebih tebal.</p>

                <div class="space-y-4">
                    @forelse($application->candidateProfile->experiences as $exp)
                    <div class="flex gap-4 p-5 bg-white border border-gray-100 rounded-xl shadow-sm">
                        <div class="mt-0.5">
                            <div class="bg-[#F3F4F6] text-blue-600 p-2.5 rounded-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-[#191D2B]">{{ $exp->work_type }}</h4>
                            <p class="text-xs text-gray-500 mb-2 mt-0.5">{{ $exp->duration }}</p>
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $exp->description }}</p>
                        </div>
                    </div>
                    @empty
                        <div class="p-4 border border-gray-100 rounded-xl">
                            <p class="text-sm text-gray-500">Belum ada pengalaman kerja.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Portofolio -->
            <div class="bg-white p-7 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002 2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    <h3 class="text-[17px] font-bold text-[#191D2B]">Portofolio</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @forelse($application->candidateProfile->portfolios as $portfolio)
                    <div class="border border-gray-100 rounded-xl p-4 flex gap-3 shadow-sm">
                        <div class="bg-[#EDF2FF] rounded-lg text-blue-600 h-11 w-11 flex items-center justify-center shrink-0">
                            @if($portfolio->file_path)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center">
                            <h4 class="text-sm font-bold text-[#191D2B] truncate">{{ $portfolio->title }}</h4>
                            <p class="text-xs text-gray-500 truncate mt-1">
                                @if($portfolio->link)
                                    <a href="{{ $portfolio->link }}" target="_blank" class="text-gray-500 hover:text-blue-600 transition">{{ parse_url($portfolio->link, PHP_URL_HOST) ?? 'Tautan Luar' }}</a>
                                @else
                                    Dokumen Terlampir
                                @endif
                            </p>
                        </div>
                        @if($portfolio->link)
                            <a href="{{ $portfolio->link }}" target="_blank" class="text-gray-400 hover:text-gray-700 shrink-0 self-center">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        @endif
                    </div>
                    @empty
                        <p class="text-sm text-gray-500 col-span-2">Belum ada portofolio yang ditambahkan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Panel Keputusan & Riwayat -->
        <div class="space-y-6">
            
            <!-- Panel Keputusan Seleksi (Sesuai Batasan PBI-19 - Statis) -->
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-5">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <h3 class="text-[17px] font-bold text-[#191D2B]">Keputusan Seleksi</h3>
                </div>
                
                <div class="mb-5 bg-[#F9FAFB] p-3 rounded-lg border border-gray-100">
                    <div class="flex justify-between items-center text-sm mb-2">
                        <span class="font-bold text-gray-800">Kuota kandidat</span>
                        <span class="text-gray-600 font-medium">1 dari {{ $project->quota }} terisi</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: 50%"></div>
                    </div>
                </div>

                <form>
                    <!-- Mockup Radio Buttons sesuai referensi Screenshot -->
                    <div class="space-y-3">
                        <label class="block border border-blue-500 bg-[#F0F5FF] rounded-lg p-4 cursor-pointer transition">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-5 w-5 flex items-center justify-center mt-0.5">
                                    <input type="radio" name="decision" value="accept" checked class="h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-[#191D2B]">Terima pelamar</span>
                                    <span class="block text-[13px] text-gray-500 mt-0.5 leading-tight">Sisa kuota 1. Draf kesepakatan pekerjaan dibuat otomatis.</span>
                                </div>
                            </div>
                        </label>
                        
                        <label class="block border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-5 w-5 flex items-center justify-center mt-0.5">
                                    <input type="radio" name="decision" value="reject" class="h-4 w-4 text-gray-400 border-gray-300 focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-[#191D2B]">Tolak dengan catatan</span>
                                    <span class="block text-[13px] text-gray-500 mt-0.5 leading-tight">Catatan wajib diisi dan terlihat oleh kandidat.</span>
                                </div>
                            </div>
                        </label>

                        <label class="block border border-gray-200 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-5 w-5 flex items-center justify-center mt-0.5">
                                    <input type="radio" name="decision" value="shortlist" class="h-4 w-4 text-gray-400 border-gray-300 focus:ring-blue-500">
                                </div>
                                <div class="ml-3">
                                    <span class="block text-sm font-bold text-gray-400">Masukkan ke terpilih sementara</span>
                                    <span class="block text-[13px] text-gray-400 mt-0.5">Status saat ini</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6">
                        <label for="note" class="block text-sm font-bold text-[#191D2B] mb-2">Catatan seleksi</label>
                        <textarea id="note" name="note" rows="3" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-lg p-3 text-gray-700" placeholder="Gaya ilustrasi cocok untuk varian Liberika Riau. Selamat bergabung!"></textarea>
                        <p class="mt-2 text-xs text-gray-400">Opsional saat menerima, wajib saat menolak.</p>
                    </div>

                    <div class="mt-6">
                        <button type="button" class="w-full flex justify-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Simpan Keputusan
                        </button>
                    </div>
                </form>
            </div>

            <!-- Timeline Riwayat Status -->
            <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                <div class="flex items-center gap-3 mb-6">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-[17px] font-bold text-[#191D2B]">Riwayat Status Lamaran</h3>
                </div>

                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @forelse($application->statusHistories as $index => $history)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                    <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-4 items-start">
                                    <div class="mt-0.5">
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-4 ring-white
                                            @if($history->status->value === 'pending') bg-green-600
                                            @elseif($history->status->value === 'shortlisted') border-[2.5px] border-blue-600 bg-white
                                            @elseif($history->status->value === 'accepted') bg-blue-600
                                            @elseif($history->status->value === 'rejected') bg-red-600
                                            @endif">
                                            @if($history->status->value === 'shortlisted')
                                                <div class="h-2.5 w-2.5 bg-blue-600 rounded-full"></div>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1">
                                        <div>
                                            <p class="text-[14px] font-bold text-[#191D2B]">
                                                @if($history->status->value === 'pending') Lamaran diterima sistem
                                                @elseif($history->status->value === 'shortlisted') Terpilih sementara
                                                @elseif($history->status->value === 'accepted') Diterima
                                                @elseif($history->status->value === 'rejected') Ditolak
                                                @endif
                                            </p>
                                            <p class="text-xs text-gray-400 mt-1">{{ $history->created_at->format('j M Y, H:i') }}</p>
                                            
                                            @if($history->note)
                                                <p class="text-sm text-gray-600 mt-2 leading-relaxed">Catatan: {{ $history->note }}</p>
                                            @endif
                                            
                                            @if($loop->last && $history->status->value === 'pending')
                                                <p class="text-sm text-gray-500 mt-2">Status awal: Menunggu Keputusan</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada riwayat status.</p>
                        @endforelse
                    </ul>
                </div>
            </div>
            
        </div>
    </div>
</div>
@endsection