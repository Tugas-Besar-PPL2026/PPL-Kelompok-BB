@extends('layouts.app')

@section('content')
<div style="max-width: 1200px; margin: 0 auto; padding: 24px;">
    {{-- Header --}}
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 24px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">Moderasi Proyek</h1>
        <p style="color: #64748b; font-size: 14px; margin: 0;">Periksa kelayakan dan kelengkapan proyek sebelum ditampilkan kepada kandidat.</p>
    </div>

    {{-- Alert Sukses --}}
    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;">
            {{ session('success') }}
        </div>
    @endif

    {{-- Alert Validasi Error --}}
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 14px;">
            <ul style="margin: 0; padding-left: 18px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Layout 2 Kolom sesuai Mockup Figma --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; align-items: start;">

        {{-- KOLOM KIRI: Antrean Moderasi --}}
        <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
                <span style="font-size: 16px; font-weight: 700; color: #0f172a;">Antrean Moderasi</span>
            </div>

            {{-- Tab Filter Status (Sesuai key $counts di Controller) --}}
            @php
                $tabs = [
                    'pending_review'     => ['label' => 'Menunggu', 'count' => $counts['pending_review'] ?? 0],
                    'published'          => ['label' => 'Disetujui', 'count' => $counts['published'] ?? 0],
                    'revision_requested' => ['label' => 'Perlu Perbaikan', 'count' => $counts['revision_requested'] ?? 0],
                    'rejected'           => ['label' => 'Ditolak', 'count' => $counts['rejected'] ?? 0],
                ];
            @endphp

            <div style="display: flex; gap: 16px; border-bottom: 1px solid #e2e8f0; margin-bottom: 16px; overflow-x: auto;">
                @foreach($tabs as $key => $tab)
                    @php
                        $isActive = $statusFilter === $key;
                    @endphp
                    <a href="{{ route('admin.moderasi.index', ['status' => $key]) }}"
                       style="padding-bottom: 10px; font-size: 13px; text-decoration: none; white-space: nowrap; display: inline-flex; align-items: center; gap: 6px;
                       {{ $isActive ? 'color: #2563eb; font-weight: 700; border-bottom: 2px solid #2563eb;' : 'color: #64748b; font-weight: 500; border-bottom: 2px solid transparent;' }}">
                        <span>{{ $tab['label'] }}</span>
                        <span style="padding: 2px 8px; border-radius: 999px; font-size: 11px; {{ $isActive ? 'background: #eff6ff; color: #2563eb;' : 'background: #f1f5f9; color: #475569;' }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            {{-- Daftar Proyek di Antrean --}}
            @if($projects->isEmpty())
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 12px; padding: 36px 16px; text-align: center; color: #64748b; font-size: 13px;">
                    Belum ada proyek pada status ini.
                </div>
            @else
                <div style="display: grid; gap: 10px;">
                    @foreach($projects as $project)
                        @php
                            $isSelected = $selectedProject && $selectedProject->id === $project->id;
                            $isResubmitted = ($project->moderations_count ?? 0) > 0;
                        @endphp
                        <a href="{{ route('admin.moderasi.index', ['status' => $statusFilter, 'project' => $project->id]) }}"
                           style="display: block; text-decoration: none; padding: 14px 16px; border-radius: 12px; transition: all 0.15s;
                           {{ $isSelected ? 'background: #f8fbff; border: 1.5px solid #2563eb;' : 'background: #ffffff; border: 1px solid #e2e8f0;' }}">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                <div>
                                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 4px;">
                                        {{ $project->title }}
                                    </div>
                                    <div style="font-size: 12px; color: #64748b;">
                                        {{ $project->umkmProfile->business_name ?? '-' }} &bull; {{ $project->updated_at?->format('d M Y, H.i') }}
                                    </div>
                                </div>

                                @if($statusFilter === 'pending_review')
                                    @if($isResubmitted)
                                        <span style="padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #f3e8ff; color: #7e22ce; white-space: nowrap;">
                                            Pengajuan ulang
                                        </span>
                                    @else
                                        <span style="padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; background: #eff6ff; color: #2563eb; white-space: nowrap;">
                                            Baru
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- KOLOM KANAN: Detail Proyek, Form Keputusan & Riwayat Moderasi --}}
        <div style="display: grid; gap: 20px;">
            @if(!$selectedProject)
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 48px 24px; text-align: center; color: #64748b; font-size: 14px;">
                    Pilih proyek dari daftar antrean di sebelah kiri untuk melihat detail dan melakukan moderasi.
                </div>
            @else
                @php
                    $rawStatus = is_object($selectedProject->status) ? $selectedProject->status->value : $selectedProject->status;
                    $statusBadge = match ($rawStatus) {
                        'published'          => ['label' => 'Disetujui', 'bg' => '#ecfdf5', 'color' => '#047857'],
                        'closed'             => ['label' => 'Ditutup', 'bg' => '#f1f5f9', 'color' => '#334155'],
                        'revision_requested' => ['label' => 'Perlu Perbaikan', 'bg' => '#fffbeb', 'color' => '#b45309'],
                        'rejected'           => ['label' => 'Ditolak', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
                        default              => ['label' => 'Menunggu Moderasi', 'bg' => '#fffbeb', 'color' => '#b45309'],
                    };
                @endphp

                {{-- Card 1: Detail Proyek --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 10px;">
                        <div>
                            <h2 style="font-size: 18px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0;">{{ $selectedProject->title }}</h2>
                            <div style="font-size: 12px; color: #64748b;">
                                Diajukan {{ $selectedProject->updated_at?->format('d M Y, H.i') }}
                            </div>
                        </div>
                        <span style="padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; background: {{ $statusBadge['bg'] }}; color: {{ $statusBadge['color'] }}; white-space: nowrap;">
                            {{ $statusBadge['label'] }}
                        </span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;">
                        <span style="padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #ecfdf5; color: #047857;">
                            {{ $selectedProject->umkmProfile->business_name ?? '-' }}
                        </span>
                        <span style="font-size: 12px; color: #64748b;">
                            {{ $selectedProject->category->name ?? '-' }} &bull; PRJ-{{ $selectedProject->created_at?->format('Y') }}-{{ str_pad((string) $selectedProject->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <p style="font-size: 14px; color: #334155; line-height: 1.6; margin: 0 0 18px 0;">
                        {{ $selectedProject->description }}
                    </p>

                    <div style="border-top: 1px solid #f1f5f9; padding-top: 16px; display: grid; gap: 12px; font-size: 13px;">
                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px;">
                            <span style="color: #64748b;">Hasil pekerjaan</span>
                            <span style="color: #0f172a; white-space: pre-line;">{{ $selectedProject->expected_output ?? '-' }}</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px; align-items: center;">
                            <span style="color: #64748b;">Keterampilan</span>
                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                @forelse($selectedProject->skills as $skill)
                                    <span style="padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; background: #eff6ff; color: #1d4ed8;">
                                        {{ $skill->name }}
                                    </span>
                                @empty
                                    <span style="color: #94a3b8;">-</span>
                                @endforelse
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px;">
                            <span style="color: #64748b;">Kuota kandidat</span>
                            <span style="color: #0f172a;">{{ $selectedProject->quota }} orang</span>
                        </div>

                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px;">
                            <span style="color: #64748b;">Jadwal</span>
                            <span style="color: #0f172a;">
                                Tenggat lamaran {{ $selectedProject->application_deadline?->format('d M Y') ?? '-' }} &bull;
                                Target selesai {{ $selectedProject->target_completion_date?->format('d M Y') ?? '-' }}
                            </span>
                        </div>

                        <div style="display: grid; grid-template-columns: 140px 1fr; gap: 8px;">
                            <span style="color: #64748b;">Kompensasi</span>
                            <span style="color: #0f172a; font-weight: 600;">
                                Rp{{ number_format((float) $selectedProject->total_compensation, 0, ',', '.') }}
                                <span style="font-weight: 400; color: #475569;">
                                    &bull; {{ $selectedProject->paymentTerms->count() }} termin
                                    @if($selectedProject->paymentTerms->first()?->condition)
                                        ({{ $selectedProject->paymentTerms->first()->condition }})
                                    @endif
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Card 2: Pemeriksaan & Keputusan (Muncul saat status Menunggu Moderasi) --}}
                @if($rawStatus === 'pending_review')
                    <form action="{{ route('admin.moderasi.decide', $selectedProject) }}" method="POST"
                          style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;">
                        @csrf
                        <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">Pemeriksaan & Keputusan</h3>

                        {{-- Checklist Pemeriksaan --}}
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; margin-bottom: 18px; font-size: 13px; color: #334155;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked style="accent-color: #2563eb;">
                                <span>Akun UMKM terverifikasi</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked style="accent-color: #2563eb;">
                                <span>Deskripsi dan hasil pekerjaan jelas</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked style="accent-color: #2563eb;">
                                <span>Ruang lingkup sesuai proyek jangka pendek</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked style="accent-color: #2563eb;">
                                <span>Kompensasi wajar untuk ruang lingkup</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="checkbox" checked style="accent-color: #2563eb;">
                                <span>Tidak memuat konten terlarang</span>
                            </label>
                        </div>

                        {{-- Pilihan Keputusan (Sesuai Enum ModerationDecision) --}}
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px;">
                            <label style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                <input type="radio" name="decision" value="{{ \App\Enums\ModerationDecision::Approved->value }}"
                                       {{ old('decision', \App\Enums\ModerationDecision::Approved->value) === \App\Enums\ModerationDecision::Approved->value ? 'checked' : '' }}
                                       style="margin-top: 3px; accent-color: #2563eb;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Setujui</div>
                                    <div style="font-size: 11px; color: #64748b;">Dipublikasikan</div>
                                </div>
                            </label>

                            <label style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                <input type="radio" name="decision" value="{{ \App\Enums\ModerationDecision::RevisionRequested->value }}"
                                       {{ old('decision') === \App\Enums\ModerationDecision::RevisionRequested->value ? 'checked' : '' }}
                                       style="margin-top: 3px; accent-color: #d97706;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Minta perbaikan</div>
                                    <div style="font-size: 11px; color: #64748b;">Kembali ke UMKM</div>
                                </div>
                            </label>

                            <label style="border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; cursor: pointer; display: flex; align-items: flex-start; gap: 8px;">
                                <input type="radio" name="decision" value="{{ \App\Enums\ModerationDecision::Rejected->value }}"
                                       {{ old('decision') === \App\Enums\ModerationDecision::Rejected->value ? 'checked' : '' }}
                                       style="margin-top: 3px; accent-color: #dc2626;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Tolak</div>
                                    <div style="font-size: 11px; color: #64748b;">Tidak tayang</div>
                                </div>
                            </label>
                        </div>

                        {{-- Catatan Moderasi --}}
                        <div style="margin-bottom: 16px;">
                            <label for="note" style="display: block; font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Catatan moderasi</label>
                            <textarea id="note" name="note" rows="3"
                                      placeholder="Tuliskan catatan kelayakan, poin perbaikan, atau alasan penolakan..."
                                      style="width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 13px; font-family: inherit;">{{ old('note') }}</textarea>
                            <div style="font-size: 12px; color: #64748b; margin-top: 6px;">
                                Wajib diisi untuk permintaan perbaikan dan penolakan. Catatan dapat dilihat UMKM.
                            </div>
                        </div>

                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit"
                                    style="background: #2563eb; color: #ffffff; border: none; padding: 10px 20px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                                Simpan Keputusan
                            </button>
                        </div>
                    </form>
                @endif

                {{-- Card 3: Riwayat Moderasi Proyek Ini --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 16px 0;">Riwayat Moderasi Proyek Ini</h3>

                    <div style="display: grid; gap: 14px;">
                        @forelse($selectedProject->moderations->sortByDesc('created_at') as $log)
                            @php
                                $decisionVal = is_object($log->decision) ? $log->decision->value : $log->decision;
                                $decisionTitle = match ($decisionVal) {
                                    'approved'           => 'Disetujui oleh Admin',
                                    'revision_requested' => 'Diminta Perbaikan oleh Admin',
                                    'rejected'           => 'Ditolak oleh Admin',
                                    default              => strtoupper((string) $decisionVal),
                                };
                            @endphp
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <span style="width: 9px; height: 9px; border-radius: 999px; background: #2563eb; margin-top: 5px; flex-shrink: 0;"></span>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">{{ $decisionTitle }}</div>
                                    <div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">{{ $log->created_at?->format('d M Y, H.i') }}</div>
                                    @if($log->note)
                                        <div style="font-size: 13px; color: #334155;">{{ $log->note }}</div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div style="display: flex; align-items: flex-start; gap: 10px;">
                                <span style="width: 9px; height: 9px; border-radius: 999px; border: 2px solid #d97706; background: #ffffff; margin-top: 4px; flex-shrink: 0;"></span>
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">Diajukan oleh UMKM</div>
                                    <div style="font-size: 12px; color: #64748b; margin-bottom: 2px;">{{ $selectedProject->updated_at?->format('d M Y, H.i') }}</div>
                                    <div style="font-size: 13px; color: #475569;">Pengajuan pertama.</div>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection