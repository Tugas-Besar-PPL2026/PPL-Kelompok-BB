@extends('layouts.app')

@section('content')
<div style="max-width: 1120px; margin: 0 auto; padding: 24px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Moderasi Proyek</h1>
        <p style="color: #64748b; margin: 0;">Tinjau proyek yang diajukan UMKM sebelum dipublikasikan ke halaman eksplorasi kandidat.</p>
    </div>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px;">
        @php
            $statuses = [
                'all' => 'Semua (' . ($counts['all'] ?? 0) . ')',
                'pending' => 'Menunggu (' . ($counts['pending'] ?? 0) . ')',
                'published' => 'Disetujui (' . ($counts['published'] ?? 0) . ')',
                'revision_required' => 'Perlu Perbaikan (' . ($counts['revision_required'] ?? 0) . ')',
                'rejected' => 'Ditolak (' . ($counts['rejected'] ?? 0) . ')',
            ];
            $currentStatus = request('status', 'pending');
        @endphp

        @foreach($statuses as $key => $label)
            <a href="{{ route('admin.moderasi.index', ['status' => $key]) }}"
               style="padding: 8px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; text-decoration: none;
               {{ $currentStatus === $key ? 'background: #0f172a; color: #ffffff;' : 'background: #f1f5f9; color: #334155;' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div style="display: grid; gap: 16px;">
        @forelse($projects as $project)
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 12px;">
                    <div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">{{ $project->title }}</h2>
                        <div style="font-size: 13px; color: #64748b;">
                            UMKM: <strong>{{ $project->umkm->name ?? '-' }}</strong>
                            &bull; Kategori: <strong>{{ $project->category->name ?? '-' }}</strong>
                        </div>
                    </div>
                    <span style="padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: #f1f5f9; color: #334155;">
                        {{ is_object($project->status) ? $project->status->value : $project->status }}
                    </span>
                </div>

                <p style="color: #334155; font-size: 14px; line-height: 1.6; margin-bottom: 16px;">
                    {{ $project->description }}
                </p>

                @if($project->moderationLogs && $project->moderationLogs->count() > 0)
                    <div style="background: #f8fafc; border-radius: 10px; padding: 12px; margin-bottom: 16px; font-size: 13px;">
                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 6px;">Riwayat Moderasi:</div>
                        @foreach($project->moderationLogs as $log)
                            <div style="color: #475569; margin-bottom: 4px;">
                                <strong>{{ strtoupper($log->action) }}</strong> ({{ $log->created_at->format('d M Y H:i') }}): {{ $log->note ?? 'Tanpa catatan' }}
                            </div>
                        @endforeach
                    </div>
                @endif

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; border-top: 1px solid #f1f5f9; padding-top: 16px;">
                    <form action="{{ route('admin.moderasi.approve', $project) }}" method="POST">
                        @csrf
                        <input type="text" name="note" placeholder="Catatan opsional..." style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; margin-bottom: 8px;">
                        <button type="submit" style="width: 100%; background: #16a34a; color: #fff; border: none; padding: 9px 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Setujui & Publikasikan
                        </button>
                    </form>

                    <form action="{{ route('admin.moderasi.revision', $project) }}" method="POST">
                        @csrf
                        <input type="text" name="note" required placeholder="Catatan perbaikan wajib diisi..." style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; margin-bottom: 8px;">
                        <button type="submit" style="width: 100%; background: #d97706; color: #fff; border: none; padding: 9px 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Minta Perbaikan
                        </button>
                    </form>

                    <form action="{{ route('admin.moderasi.reject', $project) }}" method="POST">
                        @csrf
                        <input type="text" name="note" required placeholder="Alasan penolakan wajib diisi..." style="width: 100%; padding: 8px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; margin-bottom: 8px;">
                        <button type="submit" style="width: 100%; background: #dc2626; color: #fff; border: none; padding: 9px 12px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                            Tolak Proyek
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 32px; text-align: center; color: #64748b;">
                Belum ada proyek pada status ini.
            </div>
        @endforelse
    </div>
</div>
@endsection