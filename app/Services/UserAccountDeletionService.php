<?php

namespace App\Services;

use App\Models\MobileUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserAccountDeletionService
{
    public function delete(User $user): void
    {
        $businesses = $user->businesses()
            ->with(['images:id,business_id,image_url', 'products:id,business_id,image_url'])
            ->get();

        $storageTargets = [];

        foreach ($businesses as $business) {
            $storageTargets[] = ['disk' => config('filesystems.default'), 'directory' => 'businesses/' . $business->id];
            $storageTargets[] = $this->fileTarget($business->image_url);
            $storageTargets[] = $this->fileTarget($business->owner_image_url);

            foreach ($business->images as $image) {
                $storageTargets[] = $this->fileTarget($image->image_url);
            }

            foreach ($business->products as $product) {
                $storageTargets[] = $this->fileTarget($product->image_url);
            }
        }

        DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            MobileUser::query()
                ->when($user->email, fn ($query) => $query->orWhere('email', $user->email))
                ->when($user->phone, fn ($query) => $query->orWhere('phone', $user->phone))
                ->delete();

            $user->delete();
        });

        $this->deleteStorageTargets($storageTargets);
    }

    private function deleteStorageTargets(array $storageTargets): void
    {
        $uniqueTargets = collect($storageTargets)
            ->filter()
            ->unique(fn (array $target) => ($target['disk'] ?? '') . '|' . ($target['type'] ?? 'file') . '|' . ($target['path'] ?? $target['directory'] ?? ''))
            ->values();

        foreach ($uniqueTargets as $target) {
            $disk = Storage::disk($target['disk']);

            try {
                if (($target['type'] ?? 'file') === 'directory') {
                    $disk->deleteDirectory($target['directory']);
                    continue;
                }

                $disk->delete($target['path']);
            } catch (\Throwable $e) {
                Log::warning('User account deletion storage cleanup failed', [
                    'disk' => $target['disk'],
                    'path' => $target['path'] ?? $target['directory'] ?? null,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function fileTarget(?string $url): ?array
    {
        if (blank($url)) {
            return null;
        }

        if (str_starts_with($url, '/storage/')) {
            return [
                'disk' => 'public',
                'type' => 'file',
                'path' => ltrim(str_replace('/storage/', '', $url), '/'),
            ];
        }

        foreach ($this->candidateDisks() as $diskName) {
            $diskUrl = Storage::disk($diskName)->url('');

            if ($diskUrl !== '' && str_starts_with($url, $diskUrl)) {
                return [
                    'disk' => $diskName,
                    'type' => 'file',
                    'path' => ltrim(substr($url, strlen($diskUrl)), '/'),
                ];
            }
        }

        return null;
    }

    private function candidateDisks(): array
    {
        return array_values(array_unique([
            config('filesystems.default'),
            'public',
        ]));
    }
}