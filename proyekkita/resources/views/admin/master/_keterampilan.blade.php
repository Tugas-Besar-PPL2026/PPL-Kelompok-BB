{{-- PBI-07: kartu Keterampilan pada halaman Data Master admin. Variabel: $skills, $activeSkillsCount. --}}
@php
    $hasCreateError = $errors->getBag('skillCreate')->has('skill_name');
    $skillPairs = $skills->map(fn ($s) => [mb_strtolower($s->name), $s->name])->values();
@endphp

@push('styles')
<style>
    .skill-add-btn { background: #2563eb; color: #fff; font-size: 14px; font-weight: 500; padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; }
    .skill-add-btn:disabled { background: #e3e5ec; color: #9a9ead; cursor: not-allowed; }
</style>
@endpush

<div dusk="kartu-keterampilan" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.05); display: flex; flex-direction: column; justify-content: space-between; min-height: 520px;">
    <div>
        <div style="display: flex; align-items: center; justify-content: space-between; padding-bottom: 16px; border-bottom: 1px solid #f1f5f9;">
            <h2 style="font-weight: 600; color: #0f172a; font-size: 16px; margin: 0;">Keterampilan</h2>
            <span dusk="ringkasan-keterampilan" style="font-size: 12px; color: #64748b;">{{ $skills->count() }} keterampilan &middot; {{ $activeSkillsCount }} aktif</span>
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
                    @forelse ($skills as $skill)
                        @php
                            $updateBag = $errors->getBag('skillUpdate-' . $skill->id);
                            $hasUpdateError = $updateBag->has('skill_name');
                        @endphp
                        <tr dusk="baris-keterampilan-{{ $skill->id }}" style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 12px 0; font-weight: 500; color: #1e293b;">
                                <span id="skill-label-{{ $skill->id }}" dusk="nama-keterampilan-{{ $skill->id }}" style="{{ $hasUpdateError ? 'display: none;' : '' }}">{{ $skill->name }}</span>

                                <form id="skill-form-{{ $skill->id }}"
                                      dusk="form-ubah-keterampilan-{{ $skill->id }}"
                                      action="{{ route('admin.master.skills.update', $skill) }}"
                                      method="POST"
                                      style="display: {{ $hasUpdateError ? 'flex' : 'none' }}; align-items: center; gap: 8px; margin-top: 4px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="text"
                                           name="skill_name"
                                           dusk="input-ubah-keterampilan-{{ $skill->id }}"
                                           value="{{ $hasUpdateError ? old('skill_name') : $skill->name }}"
                                           maxlength="100"
                                           required
                                           style="border: 1px solid {{ $hasUpdateError ? '#dc2626' : '#60a5fa' }}; border-radius: 4px; padding: 4px 8px; font-size: 12px; outline: none;">
                                    <button type="submit" dusk="simpan-ubah-keterampilan-{{ $skill->id }}" style="font-size: 12px; background: #2563eb; color: white; padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer;">Simpan</button>
                                    <button type="button" onclick="toggleEditSkill({{ $skill->id }})" style="font-size: 12px; color: #64748b; background: none; border: none; cursor: pointer;">Batal</button>
                                </form>

                                @if ($hasUpdateError)
                                    <p id="skill-error-{{ $skill->id }}" dusk="galat-ubah-keterampilan-{{ $skill->id }}" style="margin: 6px 0 0; font-size: 12px; font-weight: 400; color: #dc2626;">{{ $updateBag->first('skill_name') }}</p>
                                @endif
                            </td>

                            <td dusk="pakai-keterampilan-{{ $skill->id }}" style="padding: 12px 0; font-size: 12px; color: #64748b;">
                                {{ $skill->candidate_profiles_count + $skill->projects_count }} data
                            </td>

                            <td style="padding: 12px 0;">
                                <form action="{{ route('admin.master.skills.toggle', $skill) }}" method="POST" style="display: inline-flex; align-items: center; gap: 8px;">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            dusk="toggle-keterampilan-{{ $skill->id }}"
                                            aria-label="{{ $skill->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $skill->name }}"
                                            style="position: relative; display: inline-flex; height: 20px; width: 36px; cursor: pointer; border-radius: 9999px; border: none; background-color: {{ $skill->is_active ? '#059669' : '#cbd5e1' }};">
                                        <span style="display: inline-block; height: 16px; width: 16px; margin: 2px; border-radius: 9999px; background-color: white; transform: translateX({{ $skill->is_active ? '16px' : '0px' }}); transition: transform 0.2s;"></span>
                                    </button>
                                    <span dusk="status-keterampilan-{{ $skill->id }}" style="font-size: 12px; color: {{ $skill->is_active ? '#334155' : '#94a3b8' }};">{{ $skill->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </form>
                            </td>

                            <td style="padding: 12px 0; text-align: right;">
                                <button type="button"
                                        onclick="toggleEditSkill({{ $skill->id }})"
                                        dusk="ubah-keterampilan-{{ $skill->id }}"
                                        title="Ubah nama"
                                        aria-label="Ubah nama {{ $skill->name }}"
                                        style="display: inline-flex; color: #475569; background: #f1f5f9; padding: 6px; border-radius: 6px; border: none; cursor: pointer;">
                                    <i data-lucide="pencil" width="14" height="14"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="padding: 16px 0; text-align: center; font-size: 12px; color: #94a3b8;">Belum ada data keterampilan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <form action="{{ route('admin.master.skills.store') }}" method="POST" style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
        @csrf
        <label for="skill_name" style="display: block; font-size: 12px; font-weight: 500; color: #334155; margin-bottom: 8px;">Tambah keterampilan baru</label>
        <div style="display: flex; gap: 8px;">
            <input type="text"
                   id="skill_name"
                   name="skill_name"
                   dusk="input-keterampilan-baru"
                   value="{{ $hasCreateError ? old('skill_name') : '' }}"
                   maxlength="100"
                   placeholder="Contoh: Figma"
                   required
                   autocomplete="off"
                   style="flex: 1; border-radius: 8px; border: 1px solid {{ $hasCreateError ? '#dc2626' : '#cbd5e1' }}; padding: 8px 12px; font-size: 14px; outline: none; {{ $hasCreateError ? 'background: #fef2f2;' : '' }}">
            <button type="submit" id="skill_add_btn" dusk="tambah-keterampilan" class="skill-add-btn" @disabled($hasCreateError)>+ Tambah</button>
        </div>
        <p id="skill_name_error"
           dusk="galat-tambah-keterampilan"
           style="margin-top: 6px; font-size: 12px; color: #dc2626; {{ $hasCreateError ? '' : 'display: none;' }}">{{ $errors->getBag('skillCreate')->first('skill_name') }}</p>
    </form>
</div>

@push('scripts')
<script>
    // Buka/tutup form ubah nama pada satu baris keterampilan.
    function toggleEditSkill(id) {
        const label = document.getElementById('skill-label-' + id);
        const form = document.getElementById('skill-form-' + id);
        const error = document.getElementById('skill-error-' + id);
        const opening = form.style.display === 'none';

        label.style.display = opening ? 'none' : 'inline';
        form.style.display = opening ? 'flex' : 'none';
        if (error) error.style.display = opening ? '' : 'none';
        if (opening) form.querySelector('input[name="skill_name"]').focus();
    }

    // Cek duplikat langsung saat mengetik (tidak peka huruf besar/kecil). Server tetap memvalidasi ulang.
    (() => {
        const existing = new Map(@json($skillPairs));
        const input = document.getElementById('skill_name');
        const button = document.getElementById('skill_add_btn');
        const error = document.getElementById('skill_name_error');
        if (!input) return;

        input.addEventListener('input', () => {
            const value = input.value.trim();
            const found = existing.get(value.toLowerCase());
            const message = found ? `Keterampilan "${found}" sudah terdaftar. Nama keterampilan harus unik.` : '';

            error.textContent = message;
            error.style.display = message ? '' : 'none';
            input.style.borderColor = message ? '#dc2626' : '#cbd5e1';
            input.style.background = message ? '#fef2f2' : '';
            button.disabled = message !== '' || value === '';
        });
    })();
</script>
@endpush