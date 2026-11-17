<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

class MediaSync
{
    /**
     * مزامنة صورة واحدة (مجموعة singleFile).
     *
     * @param  UploadedFile|string|null  $image
     */
    public function syncSingle(
        HasMedia $model,
        UploadedFile|string|null $image,
        string $collection = 'image',
        string $tempDirectory = 'uploads',
    ): void {
        if ($image === null || $image === '') {
            return;
        }

        if ($image instanceof UploadedFile) {
            $model
                ->addMedia($image)
                ->usingFileName($this->safeFileName($image->getClientOriginalName()))
                ->toMediaCollection($collection);

            return;
        }

        $path = $this->normalizePublicPath((string) $image);
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $current = $model->getFirstMedia($collection);
        if ($current && $current->getPathRelativeToRoot() === $path) {
            return;
        }

        $model
            ->addMediaFromDisk($path, 'public')
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->toMediaCollection($collection);

        $this->deleteTempIfNeeded($path, $tempDirectory);
    }

    /**
     * مزامنة عدة صور (يستبدل المجموعة إن وُجدت ملفات جديدة).
     *
     * @param  list<UploadedFile|string>|UploadedFile|string|null  $images
     */
    public function syncMany(
        HasMedia $model,
        mixed $images,
        string $collection = 'images',
        string $tempDirectory = 'uploads',
        bool $replace = true,
    ): void {
        if ($images === null || $images === '' || $images === []) {
            return;
        }

        $items = is_array($images) ? array_values($images) : [$images];
        $items = array_values(array_filter($items, fn ($item) => filled($item)));

        if ($items === []) {
            return;
        }

        // إن كانت كل المسارات هي الوسائط الحالية نفسها، لا نعيد الرفع
        $currentPaths = $model->getMedia($collection)
            ->map(fn ($media) => $media->getPathRelativeToRoot())
            ->filter()
            ->values()
            ->all();

        $incomingPaths = collect($items)
            ->filter(fn ($item) => is_string($item))
            ->map(fn ($item) => $this->normalizePublicPath((string) $item))
            ->filter()
            ->values()
            ->all();

        $hasUploads = collect($items)->contains(fn ($item) => $item instanceof UploadedFile);
        if (! $hasUploads && $incomingPaths !== [] && $incomingPaths === $currentPaths) {
            return;
        }

        if ($replace) {
            $model->clearMediaCollection($collection);
        }

        foreach ($items as $item) {
            if ($item instanceof UploadedFile) {
                $model
                    ->addMedia($item)
                    ->usingFileName($this->safeFileName($item->getClientOriginalName()))
                    ->toMediaCollection($collection);

                continue;
            }

            $path = $this->normalizePublicPath((string) $item);
            if ($path === null || ! Storage::disk('public')->exists($path)) {
                continue;
            }

            $model
                ->addMediaFromDisk($path, 'public')
                ->preservingOriginal()
                ->usingFileName(basename($path))
                ->toMediaCollection($collection);

            $this->deleteTempIfNeeded($path, $tempDirectory);
        }
    }

    protected function normalizePublicPath(string $image): ?string
    {
        $path = ltrim(str_replace('\\', '/', $image), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path !== '' ? $path : null;
    }

    protected function deleteTempIfNeeded(string $path, string $tempDirectory): void
    {
        $prefix = rtrim($tempDirectory, '/').'/';
        if (str_starts_with($path, $prefix) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function safeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'image.jpg';

        return strtolower($name);
    }
}
