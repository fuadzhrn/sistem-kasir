<?php

namespace App\Services\Demo;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;

class DemoSafetyService
{
    public function environmentAllowed(): bool
    {
        return app()->environment(['local', 'testing']);
    }

    public function databaseAllowed(bool $allowNonDemoDatabase): bool
    {
        if (app()->environment('testing') && config('database.default') === 'sqlite') {
            $database = (string) config('database.connections.sqlite.database');

            if ($database === ':memory:' || str_contains(mb_strtolower($database), 'test')) {
                return true;
            }
        }

        $connection = (string) config('database.default');
        $database = mb_strtolower((string) config("database.connections.{$connection}.database"));

        if (str_contains($database, 'demo') || str_contains($database, 'test')) {
            return true;
        }

        return app()->environment('local') && $allowNonDemoDatabase;
    }

    /**
     * @return array<int, string>
     */
    public function duplicateIndicators(): array
    {
        $indicators = [];

        if (Branch::query()->where('code', 'DMO1')->exists()) {
            $indicators[] = 'cabang DMO1';
        }

        if (User::query()->where('username', 'demo_owner')->exists()) {
            $indicators[] = 'pengguna demo_owner';
        }

        if (Product::query()->where('code', 'DMO-0001')->exists()) {
            $indicators[] = 'produk DMO-0001';
        }

        return $indicators;
    }
}
