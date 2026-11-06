<aside class="w-64 bg-white border-r border-gray-200 min-h-screen p-4">
    <div class="mb-8 px-2">
        <h2 class="text-xl font-bold text-gray-800">ProyekKita</h2>
        <span class="text-xs text-gray-500 font-medium">Panel UMKM</span>
    </div>

    <nav class="space-y-1">
        <!-- Menu Utama UMKM Lainnya (Opsional) -->
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-lg">
            Dashboard
        </a>
        <a href="#" class="flex items-center px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 rounded-lg">
            Proyek Saya
        </a>

        <!-- Menu PBI-19: Seleksi Kandidat (Utama) -->
        <a href="{{ route('umkm.seleksi.index') }}" class="flex items-center px-4 py-2.5 text-sm font-semibold text-blue-700 bg-blue-50 rounded-lg transition">
            <svg class="w-5 h-5 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Seleksi Kandidat
        </a>
    </nav>
</aside>