<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageUploadService
{
    private int   $maxWidth  = 1200;
    private int   $quality   = 85;
    private string $disk     = 'public';

    // ── Upload Single ─────────────────────────────────────────────────────
    public function upload(
        UploadedFile $file,
        string $folder = 'uploads',
        ?int $maxWidth = null
    ): string {
        $filename  = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $maxWidth  = $maxWidth ?? $this->maxWidth;

        // Simpan langsung tanpa resize jika Intervention Image tidak ada
        $path = $file->storeAs($folder, $filename, $this->disk);

        return $path;
    }

    // ── Upload Multiple ───────────────────────────────────────────────────
    public function uploadMany(
        array $files,
        string $folder = 'uploads'
    ): array {
        $paths = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $paths[] = $this->upload($file, $folder);
            }
        }
        return $paths;
    }

    // ── Delete Single ─────────────────────────────────────────────────────
    public function delete(string $path): bool
    {
        if (Storage::disk($this->disk)->exists($path)) {
            return Storage::disk($this->disk)->delete($path);
        }
        return false;
    }

    // ── Delete Multiple ───────────────────────────────────────────────────
    public function deleteMany(array $paths): void
    {
        foreach ($paths as $path) {
            $this->delete($path);
        }
    }

    // ── Get Public URL ────────────────────────────────────────────────────
    public function getUrl(string $path): string
    {
        return Storage::disk($this->disk)->path($path);
    }

    // ── Get Multiple URLs ─────────────────────────────────────────────────
    public function getUrls(array $paths): array
    {
        return array_map(fn($p) => $this->getUrl($p), $paths);
    }
}
