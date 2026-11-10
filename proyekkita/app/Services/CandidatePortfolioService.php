<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Portfolio;
use App\Support\SelectionPresenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CandidatePortfolioService
{
    private const FILE_DISKS = ['local', 'public'];

    /** Menyiapkan koleksi portofolio kandidat beserta metadata tampilan tautan dan dokumen. */
    public function formatItems(Collection $portfolios): Collection
    {
        return $portfolios->map(function (Portfolio $portfolio) {
            $url = SelectionPresenter::safeUrl($portfolio->link);
            $file = $this->locateFile($portfolio);

            return [
                'model' => $portfolio,
                'id' => $portfolio->id,
                'title' => $portfolio->title,
                'description' => $portfolio->description,
                'link' => $portfolio->link,
                'url' => $url,
                'url_label' => $url ? SelectionPresenter::displayUrl($url) : null,
                'has_file' => filled($portfolio->file_path),
                'file_available' => $file !== null,
                'file_name' => filled($portfolio->file_path) ? basename($portfolio->file_path) : null,
                'file_size' => $file ? SelectionPresenter::fileSize($file['size']) : null,
            ];
        });
    }

    /** Menambahkan portofolio baru ke profil kandidat. */
    public function store(CandidateProfile $profile, array $data, ?UploadedFile $file = null): Portfolio
    {
        return DB::transaction(function () use ($profile, $data, $file) {
            $filePath = $file ? $this->storeFile($profile->id, $file) : null;
            $link = filled($data['link'] ?? null) ? SelectionPresenter::safeUrl($data['link']) : null;

            return $profile->portfolios()->create([
                'title' => trim($data['title']),
                'description' => (string) ($data['description'] ?? ''),
                'link' => $link,
                'file_path' => $filePath,
            ]);
        });
    }

    /** Memperbarui data portofolio kandidat (dan mengganti dokumen bila diunggah baru). */
    public function update(Portfolio $portfolio, array $data, ?UploadedFile $file = null, bool $removeFile = false): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $data, $file, $removeFile) {
            $filePath = $portfolio->file_path;

            if ($file !== null) {
                $this->deleteStoredFile($portfolio);
                $filePath = $this->storeFile($portfolio->candidate_profile_id, $file);
            } elseif ($removeFile) {
                $this->deleteStoredFile($portfolio);
                $filePath = null;
            }

            $link = filled($data['link'] ?? null) ? SelectionPresenter::safeUrl($data['link']) : null;

            $portfolio->update([
                'title' => trim($data['title']),
                'description' => (string) ($data['description'] ?? ''),
                'link' => $link,
                'file_path' => $filePath,
            ]);

            return $portfolio;
        });
    }

    /** Menghapus portofolio beserta berkas dokumennya dari penyimpanan. */
    public function delete(Portfolio $portfolio): void
    {
        DB::transaction(function () use ($portfolio) {
            $this->deleteStoredFile($portfolio);
            $portfolio->delete();
        });
    }

    /** Mencari berkas portofolio pada disk yang dikenal. */
    public function locateFile(Portfolio $portfolio): ?array
    {
        $path = (string) $portfolio->file_path;
        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        foreach (self::FILE_DISKS as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return [
                    'disk' => $disk,
                    'path' => $path,
                    'size' => (int) Storage::disk($disk)->size($path),
                ];
            }
        }

        return null;
    }

    private function storeFile(int $profileId, UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'pdf');
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBase = Str::slug($baseName) ?: 'portofolio';
        $fileName = $safeBase . '.' . $ext;
        $dir = 'portfolios/' . $profileId . '/' . Str::lower(Str::random(10));

        return $file->storeAs($dir, $fileName, 'public');
    }

    private function deleteStoredFile(Portfolio $portfolio): void
    {
        $located = $this->locateFile($portfolio);
        if ($located !== null) {
            Storage::disk($located['disk'])->delete($located['path']);
        }
    }
}
