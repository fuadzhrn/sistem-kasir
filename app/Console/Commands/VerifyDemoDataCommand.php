<?php

namespace App\Console\Commands;

use App\Services\Demo\DemoIntegrityService;
use App\Services\Demo\DemoSafetyService;
use Illuminate\Console\Command;

class VerifyDemoDataCommand extends Command
{
    protected $signature = 'demo:verify
        {--strict : Perlakukan warning sebagai kegagalan}
        {--json : Keluarkan hasil dalam JSON}';

    protected $description = 'Memverifikasi integritas data demo tanpa melakukan perubahan';

    public function handle(
        DemoSafetyService $safety,
        DemoIntegrityService $integrity,
    ): int {
        if (! $safety->environmentAllowed()) {
            $this->components->error('Verifikasi data demo hanya boleh dijalankan pada local atau testing.');

            return self::FAILURE;
        }

        $startedAt = microtime(true);
        $result = $integrity->verify();
        $result['duration_seconds'] = round(microtime(true) - $startedAt, 3);

        if ((bool) $this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        } else {
            $this->displayResult($result);
        }

        if ($result['failures'] !== []) {
            return self::FAILURE;
        }

        if ((bool) $this->option('strict') && $result['warnings'] !== []) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function displayResult(array $result): void
    {
        $rows = [];

        foreach ($result['passes'] as $message) {
            $rows[] = ['PASS', $message];
        }

        foreach ($result['warnings'] as $message) {
            $rows[] = ['WARNING', $message];
        }

        foreach ($result['failures'] as $message) {
            $rows[] = ['FAIL', $message];
        }

        $this->table(['Status', 'Pemeriksaan'], $rows);
        $this->table(
            ['Metrik', 'Nilai'],
            collect($result['metrics'])
                ->map(fn (mixed $value, string $key): array => [$key, (string) $value])
                ->values()
                ->all(),
        );
        $this->line('Durasi verifikasi: '.$result['duration_seconds'].' detik');
    }
}
