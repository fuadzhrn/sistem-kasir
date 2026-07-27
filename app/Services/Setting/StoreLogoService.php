<?php

namespace App\Services\Setting;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StoreLogoService
{
    public function __construct(private readonly StoreSettingService $settings) {}

    public function update(UploadedFile $logo, User $actor): string
    {
        $oldPath = $this->logoPath();
        $newPath = $logo->store('store', 'public');

        if ($newPath === false) {
            throw new \RuntimeException('Logo toko tidak dapat disimpan.');
        }

        try {
            $this->settings->updateLogoPath($newPath, $actor);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($newPath);

            throw $exception;
        }

        if ($this->isManagedPath($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }

    public function remove(User $actor): void
    {
        $oldPath = $this->logoPath();
        $this->settings->updateLogoPath(null, $actor, true);

        if ($this->isManagedPath($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }
    }

    public function logoPath(): ?string
    {
        $path = trim((string) $this->settings->get('store.logo_path'));

        return $this->isManagedPath($path) ? $path : null;
    }

    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        return $path !== null && Storage::disk('public')->exists($path)
            ? Storage::disk('public')->url($path)
            : null;
    }

    private function isManagedPath(?string $path): bool
    {
        return $path !== null
            && str_starts_with($path, 'store/')
            && ! str_contains($path, '..')
            && ! str_starts_with($path, '/')
            && ! preg_match('/^[A-Z]:/i', $path);
    }
}
