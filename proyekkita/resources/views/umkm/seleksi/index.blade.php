@extends('layouts.app')

@section('content')
<div class="p-6 bg-[#FAF9FF] min-h-screen">
    <!-- Header & Dropdown Proyek -->
    <div class="mb-8 flex flex-col md:flex-row md:justify-between md:items-center">
        <div class="mb-4 md:mb-0">
            <h1 class="text-2xl font-bold text-[#191D2B]">Seleksi Kandidat</h1>
            <p class="text-[#6B7280] text-sm mt-1">Tinjau pelamar, masukkan ke daftar terpilih sementara, lalu terima atau tolak sesuai kuota proyek.</p>
        </div>
        @if($projects->isNotEmpty())
        <div>
            <form action="{{ route('umkm.seleksi.index') }}" method="GET" class="flex items-center bg-white border border-gray-200 rounded-lg px-3 py-2 shadow-sm">
                <label for="project_id" class="mr-2 text-sm font-medium text-gray-500 whitespace-nowrap">Proyek:</label>
                <select name="project_id" id="project_id" onchange="this.form.submit()" class="border-none text-sm font-semibold text-gray-900 focus:ring-0 cursor-pointer">
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}" {{ $selectedProject->id == $project->id ? 'selected' : '' }}>
                            {{ $project->title }}
                        </option>
                    @endforeach
                </select>
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
            </form>
        </div>
        @endif
    </div>

    @if($projects->isEmpty())
        <div class="bg-white p-8 rounded-xl border border-gray-200 text-center shadow-sm">
            <p class="text-gray-500">Anda belum memiliki proyek. Silakan buat proyek terlebih dahulu.</p>
        </div>
    @else
        <!-- Kartu Statistik -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm font-medium text-gray-500 mb-2">Total pelamar</p>
                <p class="text-3xl font-bold text-[#191D2B]">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-400 mt-2">sejak {{ $selectedProject->created_at->format('j M Y') }}</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm font-medium text-gray-500 mb-2">Menunggu keputusan</p>
                <p class="text-3xl font-bold text-[#191D2B]">{{ $stats['menunggu'] }}</p>
                <p class="text-xs text-gray-400 mt-2">perlu ditinjau</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm font-medium text-gray-500 mb-2">Terpilih sementara</p>
                <p class="text-3xl font-bold text-[#191D2B]">{{ $stats['terpilih_sementara'] }}</p>
                <p class="text-xs text-gray-400 mt-2">daftar pendek</p>
            </div>
            <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                <p class="text-sm font-medium text-gray-500 mb-2">Diterima</p>
                <div class="flex items-baseline gap-1">
                    <p class="text-3xl font-bold text-[#191D2B]">{{ $stats['diterima'] }}</p>
                    <p class="text-lg font-medium text-gray-400">/ {{ $selectedProject->quota }}</p>
                </div>
                <p class="text-xs text-gray-400 mt-2">kuota kandidat</p>
            </div>
        </div>

        <!-- Progress Kuota -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm mb-6 flex items-center justify-between">
            <div class="flex items-center space-x-3 w-3/4">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <span class="text-sm font-semibold text-gray-800">Kuota kandidat terisi {{ $stats['diterima'] }} dari {{ $selectedProject->quota }}</span>
                <div class="flex-1 ml-4 bg-gray-100 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $selectedProject->quota > 0 ? min(100, ($stats['diterima'] / $selectedProject->quota) * 100) : 0 }}%"></div>
                </div>
            </div>
            <div class="text-sm font-medium text-gray-500">
                Sisa {{ max(0, $selectedProject->quota - $stats['diterima']) }} kandidat dapat diterima
            </div>
        </div>

        <!-- Tab Filter & Tabel -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="border-b border-gray-100">
                <nav class="flex px-2" aria-label="Tabs">
                    @php
                        $tabs = [
                            'all' => ['label' => 'Semua', 'count' => $stats['total']],
                            'pending' => ['label' => 'Menunggu Keputusan', 'count' => $stats['menunggu']],
                            'shortlisted' => ['label' => 'Terpilih Sementara', 'count' => $stats['terpilih_sementara']],
                            'accepted' => ['label' => 'Diterima', 'count' => $stats['diterima']],
                            'rejected' => ['label' => 'Ditolak', 'count' => $stats['ditolak']]
                        ];
                        $currentTab = request('status', 'all');
                    @endphp
                    @foreach($tabs as $key => $tab)
                        <a href="{{ route('umkm.seleksi.index', ['project_id' => $selectedProject->id, 'status' => $key]) }}" 
                           class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 {{ $currentTab === $key ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            {{ $tab['label'] }}
                            <span class="{{ $currentTab === $key ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }} py-0.5 px-2 rounded-md text-xs">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pelamar</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterampilan</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pengalaman</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Penilaian</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Dilamar</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($applications as $app)
                        <tr class="hover:bg-gray-50/50 transition duration-150">
                            <td class="px-6 py-5 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-[#EDF2FF] rounded-lg flex items-center justify-center text-blue-700 font-bold">
                                        {{ strtoupper(substr($app->candidateProfile->full_name, 0, 2)) }}
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-semibold text-[#191D2B]">{{ $app->candidateProfile->full_name }}</div>
                                        <div class="text-sm text-gray-500 mt-0.5">{{ $app->candidateProfile->university }}</div>
                                        <div class="text-xs text-gray-400 mt-0.5">{{ $app->candidateProfile->city }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-5">
                                <div class="flex flex-wrap gap-1.5 max-w-[200px]">
                                    @foreach($app->candidateProfile->skills->take(3) as $skill)
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-[#EDF2FF] text-blue-700 text-xs font-medium border border-[#DCE4FF]">
                                            {{ $skill->name }}
                                        </span>
                                    @endforeach
                                    @if($app->candidateProfile->skills->count() > 3)
                                        <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-600 text-xs font-medium">
                                            +{{ $app->candidateProfile->skills->count() - 3 }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600">
                                <div class="font-medium text-gray-700">{{ $app->candidateProfile->experiences->count() }} pengalaman</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600">
                                @php
                                    $rating = rand(4, 5) . '.' . rand(0, 9); // Pengganti statis jika tabel evaluasi agregat belum ada
                                    $completed = rand(1, 5);
                                @endphp
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-yellow-400 mr-1.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span class="font-bold text-[#191D2B]">{{ $rating }}</span>
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ $completed }} proyek selesai</div>
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-sm text-gray-600">
                                {{ $app->created_at->format('j M Y') }}
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                @if($app->status->value === 'pending')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#FFFBEB] text-[#B45309]">Menunggu Keputusan</span>
                                @elseif($app->status->value === 'shortlisted')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#F3E8FF] text-[#7E22CE]">Terpilih Sementara</span>
                                @elseif($app->status->value === 'accepted')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#ECFDF5] text-[#047857]">Diterima</span>
                                @elseif($app->status->value === 'rejected')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-[#FEF2F2] text-[#B91C1C]">Ditolak</span>
                                @endif
                            </td>
                            <td class="px-6 py-5 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('umkm.seleksi.show', ['projectId' => $selectedProject->id, 'applicationId' => $app->id]) }}" class="text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition">Lihat</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 bg-gray-50/30">
                                Belum ada pelamar dengan status ini.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection