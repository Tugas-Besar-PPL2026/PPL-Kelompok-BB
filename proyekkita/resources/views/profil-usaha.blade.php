<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Usaha - ProyekKita</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;font-family:Inter,system-ui,sans-serif}
body{margin:0;background:#F6F7F9;color:#111827;font-size:13px}
a{color:inherit;text-decoration:none}
.topbar{height:60px;background:#fff;border-bottom:1px solid #E5E7EB;display:flex;align-items:center;justify-content:space-between;padding:0 24px}
.brand{display:flex;align-items:center;gap:8px;font-weight:700;font-size:17px;color:#1E3A8A}
.user{display:flex;align-items:center;gap:10px}
.avatar{width:30px;height:30px;border-radius:8px;background:#DBEAFE;color:#1E40AF;font-weight:600;font-size:11px;display:flex;align-items:center;justify-content:center}
.small{font-size:11px;color:#5B6472}
.bell{position:relative;background:none;border:0;padding:8px;cursor:pointer;margin-right:12px}
.bell span{position:absolute;top:2px;right:2px;background:#DC2626;color:#fff;font-size:10px;font-weight:600;border-radius:9px;padding:1px 5px}
.layout{display:flex;min-height:calc(100vh - 60px)}
.sidebar{width:240px;flex-shrink:0;background:#fff;border-right:1px solid #E5E7EB;padding:16px 12px;display:flex;flex-direction:column;justify-content:space-between}
.biz{border:1px solid #E5E7EB;border-radius:10px;padding:10px;display:flex;gap:10px;align-items:center;margin-bottom:16px}
.badge{display:inline-flex;align-items:center;gap:4px;background:#DCFCE7;color:#166534;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:600}
.nav a{display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;font-weight:500;margin-bottom:4px}
.nav a.active{background:#2563EB;color:#fff}
.nav .count{margin-left:auto;background:#DBEAFE;color:#1E40AF;font-size:11px;font-weight:600;border-radius:9px;padding:1px 7px}
.logout{display:flex;align-items:center;gap:10px;padding:12px 10px;border-top:1px solid #E5E7EB;color:#B91C1C;font-weight:500}
.main{flex:1;min-width:0;padding:28px 32px}
.main h1{margin:0;font-size:24px}
.lead{margin:6px 0 20px;color:#5B6472}
.grid{display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start}
.col-main{flex:1 1 520px;min-width:0;display:flex;flex-direction:column;gap:20px}
.col-side{flex:1 1 300px;max-width:380px;display:flex;flex-direction:column;gap:20px}
.card{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:20px}
.card h2{margin:0 0 16px;font-size:14px;font-weight:600;display:flex;align-items:center;gap:8px}
.row{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:14px}
label{display:block;font-weight:500;font-size:12px;margin-bottom:6px}
input,select,textarea{width:100%;border:1px solid #D1D5DB;border-radius:8px;padding:0 12px;font-size:13px;color:#111827;background:#fff}
input,select{height:40px}
textarea{padding:10px 12px;line-height:1.5;resize:vertical}
input:focus,select:focus,textarea:focus{outline:2px solid #93C5FD;border-color:#2563EB}
.hint{margin-top:6px;font-size:11px;color:#5B6472}
.warn{background:#FFFBEB;border:1px solid #FCD34D;border-radius:10px;padding:14px;color:#78350F;font-size:12px;line-height:1.5;display:flex;gap:10px;margin-top:4px}
.warn b.t{display:block;font-size:12.5px;margin-bottom:4px}
.actions{margin-top:16px;display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap}
.btn{height:44px;padding:0 20px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer;display:flex;align-items:center;gap:8px}
.btn-outline{border:1px solid #D1D5DB;background:#fff;color:#111827}
.btn-primary{border:0;background:#2563EB;color:#fff}
.btn-primary:hover{background:#1D4ED8}
.timeline{border-left:2px solid #86EFAC;margin:16px 0 0 5px;padding-left:16px;display:flex;flex-direction:column;gap:14px}
.step{position:relative}
.step::before{content:"";position:absolute;left:-23px;top:3px;width:10px;height:10px;border-radius:50%;background:#16A34A}
.step b{display:block;font-size:12.5px}
.step small{display:block;color:#5B6472;font-size:11px;margin-top:2px}
.ok{margin-top:16px;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px;color:#166534;font-size:12px;line-height:1.5}
.score{display:flex;align-items:center;gap:12px}
.score strong{font-size:34px}
.stars{color:#F59E0B;font-size:16px;letter-spacing:1px}
.bars{margin-top:12px;display:flex;flex-direction:column;gap:7px;font-size:11.5px}
.bar{display:flex;align-items:center;gap:8px}
.bar .l{width:20px}.bar .n{width:12px;text-align:right}
.bar .track{flex:1;height:7px;border-radius:4px;background:#E5E7EB;overflow:hidden}
.bar .fill{height:100%;background:#F59E0B}
@media(max-width:860px){.sidebar{display:none}.main{padding:20px 16px}}
</style>
</head>
<body>
<header class="topbar">
  <div class="brand">ProyekKita</div>
  <div class="user">
    <button class="bell" aria-label="Notifikasi, 3 baru">🔔<span>3</span></button>
    <div class="avatar">KN</div>
    <div><b style="font-size:12px">{{ $usaha->nama ?? 'Kopi Nusantara' }}</b><div class="small">UMKM</div></div>
  </div>
</header>

<div class="layout">
  <aside class="sidebar">
    <div>
      <div class="biz">
        <div class="avatar">KN</div>
        <div><b style="font-size:12px">{{ $usaha->nama ?? 'Kopi Nusantara' }}</b><br><span class="badge">✓ Terverifikasi</span></div>
      </div>
      <nav class="nav">
        <a href="#" class="active" aria-current="page">Profil Usaha</a>
        <a href="#">Proyek Saya</a>
        <a href="#">Seleksi Kandidat</a>
        <a href="#">Kesepakatan &amp; Progres</a>
        <a href="#">Pembayaran</a>
        <a href="#">Evaluasi</a>
        <a href="#">Notifikasi <span class="count">3</span></a>
      </nav>
    </div>
    <a href="#" class="logout">Keluar</a>
  </aside>

  <main class="main">
    <h1>Profil Usaha</h1>
    <p class="lead">Kelola identitas dan legalitas usaha yang menjadi bahan verifikasi admin dan informasi bagi kandidat.</p>

    <form method="POST" action="{{ route('profil-usaha.update') }}" class="grid">
      @csrf

      <div class="col-main">
        <section class="card">
          <h2>Informasi Usaha</h2>
          <div class="row">
            <div><label for="nama">Nama usaha *</label>
              <input id="nama" name="nama" value="{{ old('nama', 'Kopi Nusantara') }}" required></div>
            <div><label for="bidang">Bidang usaha *</label>
              <select id="bidang" name="bidang">
                <option>Kuliner &amp; Minuman</option>
                <option>Fashion</option>
                <option>Kerajinan</option>
                <option>Jasa</option>
              </select></div>
          </div>
          <div style="margin-bottom:14px"><label for="deskripsi">Deskripsi usaha *</label>
            <textarea id="deskripsi" name="deskripsi" rows="3" required>{{ old('deskripsi', 'Kedai dan roastery kopi lokal di Bandung yang menjual biji kopi Nusantara kemasan 200 g dan 1 kg untuk pelanggan ritel serta reseller kafe.') }}</textarea></div>
          <div class="row">
            <div><label for="kontak">Nomor kontak *</label>
              <input id="kontak" name="kontak" value="{{ old('kontak', '0812-2045-7781') }}" required></div>
            <div><label for="kota">Kota *</label>
              <input id="kota" name="kota" value="{{ old('kota', 'Bandung') }}" required></div>
          </div>
          <div><label for="alamat">Alamat *</label>
            <input id="alamat" name="alamat" value="{{ old('alamat', 'Jl. Cihampelas No. 112, Kec. Coblong, Kota Bandung, Jawa Barat 40131') }}" required></div>
        </section>

        <section class="card">
          <h2>Informasi Legalitas Usaha</h2>
          <div class="row">
            <div><label for="nib">Nomor Induk Berusaha (NIB)</label>
              <input id="nib" name="nib" value="{{ old('nib', '9120304567812') }}">
              <div class="hint">13 digit NIB dari sistem OSS. Isi NIB dan/atau tautan informasi usaha.</div></div>
            <div><label for="tautan">Tautan informasi usaha</label>
              <input id="tautan" name="tautan" value="{{ old('tautan', 'instagram.com/kopinusantara.bdg') }}"></div>
          </div>
          <div class="warn">
            <span>⚠️</span>
            <div><b class="t">Perubahan data legalitas memerlukan verifikasi ulang</b>
              Mengubah nama usaha, NIB, atau tautan informasi usaha akan mengembalikan status verifikasi menjadi <b>Menunggu Pemeriksaan</b> hingga admin memeriksa kembali.</div>
          </div>
          <div class="actions">
            <button type="reset" class="btn btn-outline">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </div>
        </section>
      </div>

      <div class="col-side">
        <section class="card">
          <h2>Status Verifikasi</h2>
          <span class="badge">Terverifikasi</span> <span class="small">sejak 28 Okt 2026</span>
          <div class="timeline">
            <div class="step"><b>Profil diajukan untuk verifikasi</b><small>27 Okt 2026, 09.40</small></div>
            <div class="step"><b>Diperiksa admin</b><small>27 Okt 2026, 15.12</small><small style="color:#374151">NIB dan tautan usaha sesuai dengan nama usaha.</small></div>
            <div class="step"><b>Terverifikasi</b><small>28 Okt 2026, 08.05</small></div>
          </div>
          <div class="ok">✓ Akun terverifikasi dapat mengajukan proyek untuk dimoderasi dan dipublikasikan.</div>
        </section>

        <section class="card">
          <h2>Rata-rata Penilaian dari Kandidat</h2>
          <div class="score">
            <strong>4,7</strong>
            <div><div class="stars" aria-label="4,7 dari 5 bintang">★★★★★</div><div class="small">dari 6 evaluasi kandidat</div></div>
          </div>
          <div class="bars">
            @foreach ([5 => 4, 4 => 2, 3 => 0, 2 => 0, 1 => 0] as $bintang => $jumlah)
              <div class="bar">
                <span class="l">{{ $bintang }}★</span>
                <div class="track"><div class="fill" style="width: {{ $jumlah / 6 * 100 }}%"></div></div>
                <span class="n">{{ $jumlah }}</span>
              </div>
            @endforeach
          </div>
        </section>
      </div>
    </form>
  </main>
</div>
</body>
</html>