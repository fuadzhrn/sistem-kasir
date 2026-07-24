<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemCheckController extends Controller
{
    public function __invoke(): View
    {
        abort_unless(app()->environment('local'), 404);

        $databaseIsConnected = $this->databaseIsConnected();
        $storageIsWritable = $this->storageIsWritable();
        $assetsAreAvailable = $this->assetsAreAvailable();

        return view('pages.system-check.index', [
            'checks' => [
                ['label' => 'Aplikasi Laravel', 'value' => 'Berjalan', 'status' => true],
                [
                    'label' => 'Koneksi database',
                    'value' => $databaseIsConnected ? 'Tersambung' : 'Tidak tersambung',
                    'status' => $databaseIsConnected,
                ],
                [
                    'label' => 'Environment aplikasi',
                    'value' => app()->environment(),
                    'status' => app()->environment('local'),
                ],
                [
                    'label' => 'Versi PHP',
                    'value' => PHP_VERSION,
                    'status' => version_compare(PHP_VERSION, '8.3.0', '>='),
                ],
                ['label' => 'Versi Laravel', 'value' => app()->version(), 'status' => true],
                [
                    'label' => 'APP_KEY',
                    'value' => filled(config('app.key')) ? 'Tersedia' : 'Belum tersedia',
                    'status' => filled(config('app.key')),
                ],
                [
                    'label' => 'Storage',
                    'value' => $storageIsWritable ? 'Dapat ditulis' : 'Tidak dapat ditulis',
                    'status' => $storageIsWritable,
                ],
                [
                    'label' => 'Folder asset',
                    'value' => $assetsAreAvailable ? 'Tersedia' : 'Belum lengkap',
                    'status' => $assetsAreAvailable,
                ],
            ],
            'checkedAt' => now(),
        ]);
    }

    private function databaseIsConnected(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    private function storageIsWritable(): bool
    {
        return is_writable(storage_path())
            && is_writable(storage_path('framework'))
            && is_writable(storage_path('logs'));
    }

    private function assetsAreAvailable(): bool
    {
        $requiredAssets = [
            public_path('assets/css/base/global.css'),
            public_path('assets/css/pages/system-check.css'),
            public_path('assets/js/core/app.js'),
            public_path('assets/js/pages/system-check.js'),
        ];

        foreach ($requiredAssets as $asset) {
            if (! is_file($asset)) {
                return false;
            }
        }

        return true;
    }
}
