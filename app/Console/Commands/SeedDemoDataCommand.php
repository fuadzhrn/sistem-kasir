<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoProfileService;
use App\Services\Demo\DemoSafetyService;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SeedDemoDataCommand extends Command
{
    protected $signature = 'demo:seed
        {--profile=small : Profile small, medium, atau large}
        {--seed=20260726 : Angka random seed deterministik}
        {--confirm= : Token konfirmasi SEED-DEMO}
        {--dry-run : Tampilkan rencana tanpa menulis data atau file}
        {--allow-non-demo-database : Izinkan database tanpa kata demo/test, khusus local}
        {--no-settings : Jangan mengubah pengaturan toko}';

    protected $description = 'Membuat data simulasi lengkap pada database demo lokal/testing';

    public function handle(
        DemoProfileService $profiles,
        DemoSafetyService $safety,
        DemoDataSeeder $seeder,
    ): int {
        if (! $safety->environmentAllowed()) {
            $this->components->error('Data demo hanya boleh dibuat pada APP_ENV local atau testing.');

            return self::FAILURE;
        }

        $profileName = (string) $this->option('profile');

        $allowedProfiles = $profiles->commandProfiles();

        if (app()->environment('testing')) {
            $allowedProfiles[] = 'testing';
        }

        if (! in_array($profileName, $allowedProfiles, true)) {
            $this->components->error('Profile tidak valid. Gunakan small, medium, atau large.');

            return self::INVALID;
        }

        $seedInput = (string) $this->option('seed');

        if (! ctype_digit($seedInput) || (int) $seedInput < 1) {
            $this->components->error('Random seed harus berupa bilangan bulat positif.');

            return self::INVALID;
        }

        $allowNonDemo = (bool) $this->option('allow-non-demo-database');

        if (! $safety->databaseAllowed($allowNonDemo)) {
            $this->components->error(
                'Database ditolak. Gunakan database yang namanya memuat demo/test, '
                .'atau --allow-non-demo-database hanya pada environment local.',
            );

            return self::FAILURE;
        }

        $profile = $profiles->get($profileName);
        $this->displayPlan($profile, (int) $seedInput);

        if ((bool) $this->option('dry-run')) {
            $this->components->info('DRY RUN selesai. Tidak ada data, file, service bisnis, atau log yang ditulis.');

            return self::SUCCESS;
        }

        if (! hash_equals('SEED-DEMO', (string) $this->option('confirm'))) {
            $this->components->error('Token konfirmasi tidak valid. Gunakan --confirm=SEED-DEMO.');

            return self::FAILURE;
        }

        $duplicates = $safety->duplicateIndicators();

        if ($duplicates !== []) {
            $this->components->error(
                'Seed dibatalkan karena indikator data demo sudah ada: '.implode(', ', $duplicates).'.',
            );

            return self::FAILURE;
        }

        if (
            $this->input->isInteractive()
            && ! $this->confirm('Lanjutkan membuat data demo pada database ini?', false)
        ) {
            $this->components->warn('Seed data demo dibatalkan pengguna.');

            return self::FAILURE;
        }

        $configuredPassword = getenv('DEMO_USER_PASSWORD');
        $password = is_string($configuredPassword) && $configuredPassword !== ''
            ? $configuredPassword
            : Str::password(24, true, true, true, false);

        try {
            $statistics = $seeder->seed(
                $profileName,
                (int) $seedInput,
                $password,
                $this,
                ! (bool) $this->option('no-settings'),
            );
        } catch (Throwable $exception) {
            Storage::disk('local')->put(
                'demo/demo-seed-manifest.json',
                json_encode([
                    'profile' => $profileName,
                    'random_seed' => (int) $seedInput,
                    'finished_at' => now()->toIso8601String(),
                    'database' => $this->databaseName(),
                    'status' => 'failed',
                    'exception' => $exception::class,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            );
            $this->components->error('Seed berhenti pada error: '.$exception->getMessage());

            if ($this->output->isVerbose()) {
                $this->line($exception->getTraceAsString());
            }

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Data demo berhasil dibuat.');
        $this->table(
            ['Metrik', 'Nilai'],
            collect($statistics['counts'])
                ->map(fn (int $value, string $key): array => [$key, number_format($value, 0, ',', '.')])
                ->values()
                ->all(),
        );
        $this->line('Durasi: '.$statistics['duration_seconds'].' detik');
        $this->newLine();
        $this->warn('Credential ini hanya untuk database demo lokal.');
        $this->line('Password demo: '.$password);
        $this->table(
            ['Username', 'Role', 'Cabang'],
            $this->credentialRows($profile),
        );

        return self::SUCCESS;
    }

    /**
     * @param  array<string, int|float|string>  $profile
     */
    private function displayPlan(array $profile, int $seed): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $safeDatabaseName = $database === ':memory:' ? ':memory:' : basename(str_replace('\\', '/', $database));
        $this->components->info('Rencana data demo');
        $this->table([
            'Parameter',
            'Nilai',
        ], [
            ['Environment', app()->environment()],
            ['Database', $safeDatabaseName],
            ['Profile', $profile['name']],
            ['Random seed', $seed],
            ['Rentang data', now()->subYear()->toDateString().' s.d. '.now()->toDateString()],
            ['Cabang', $profile['branches']],
            ['Produk', $profile['products']],
            ['Penjualan', $profile['sales']],
            ['Barang masuk', $profile['receipts']],
            ['Pengeluaran', $profile['expenses']],
        ]);
    }

    /**
     * @param  array<string, int|float|string>  $profile
     * @return array<int, array<int, string>>
     */
    private function credentialRows(array $profile): array
    {
        $rows = [['demo_owner', 'Owner', 'Semua cabang']];
        $cashiersPerBranch = (int) ceil(((int) $profile['cashiers']) / 4);

        foreach (range(1, 4) as $branch) {
            $code = "DMO{$branch}";
            $suffix = mb_strtolower($code);
            $rows[] = ["demo_admin_{$suffix}", 'Admin', $code];

            foreach (range(1, $cashiersPerBranch) as $number) {
                $rows[] = [
                    sprintf('demo_kasir_%s_%02d', $suffix, $number),
                    'Kasir',
                    $code,
                ];
            }
        }

        return $rows;
    }

    private function databaseName(): string
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        return $database === ':memory:'
            ? $database
            : basename(str_replace('\\', '/', $database));
    }
}
