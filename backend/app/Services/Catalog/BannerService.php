<?php

namespace App\Services\Catalog;

use App\Models\Banner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BannerService
{
    public function listActive(): SupportCollection
    {
        return Banner::query()
            ->active()
            ->with('media')
            ->orderBy('sort_order')
            ->get();
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Banner::query()
            ->with('media')
            ->when(isset($filters['is_active']), fn ($q) => $q->where('is_active', $filters['is_active']))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    public function create(array $data): Banner
    {
        return DB::transaction(function () use ($data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            $banner = Banner::query()->create($data);
            $this->syncImage($banner, $image);
            activity()->performedOn($banner)->log('إنشاء بانر');

            return $banner->load('media');
        });
    }

    public function update(Banner $banner, array $data): Banner
    {
        return DB::transaction(function () use ($banner, $data) {
            $image = $data['image'] ?? null;
            unset($data['image']);

            $banner->update($data);
            if ($image !== null) {
                $this->syncImage($banner, $image);
            }
            activity()->performedOn($banner)->log('تحديث بانر');

            return $banner->fresh('media');
        });
    }

    public function delete(Banner $banner): void
    {
        DB::transaction(function () use ($banner) {
            activity()->performedOn($banner)->log('حذف بانر');
            $banner->clearMediaCollection('image');
            $banner->delete();
        });
    }

    /**
     * يحفظ صورة البانر في storage/app/public عبر Media Library.
     *
     * @param  UploadedFile|string|null  $image  ملف مرفوع أو مسار نسبي على قرص public
     */
    public function syncImage(Banner $banner, UploadedFile|string|null $image): void
    {
        if ($image === null || $image === '') {
            return;
        }

        if ($image instanceof UploadedFile) {
            $banner
                ->addMedia($image)
                ->usingFileName($this->safeFileName($image->getClientOriginalName()))
                ->toMediaCollection('image');

            return;
        }

        $path = ltrim(str_replace('\\', '/', (string) $image), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        // تجنّب إعادة إضافة نفس ملف الوسائط الحالي
        $current = $banner->getFirstMedia('image');
        if ($current && $current->getPathRelativeToRoot() === $path) {
            return;
        }

        $banner
            ->addMediaFromDisk($path, 'public')
            ->preservingOriginal()
            ->usingFileName(basename($path))
            ->toMediaCollection('image');

        // احذف النسخة المؤقتة من مجلد الرفع (بعد نسخها لمكتبة الوسائط)
        if (str_starts_with($path, 'banners/') && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    protected function safeFileName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', $name) ?: 'banner.jpg';

        return strtolower($name);
    }
}
