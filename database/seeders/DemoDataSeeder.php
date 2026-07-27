<?php

namespace Database\Seeders;

use App\Services\Demo\DemoDataService;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;
use LogicException;

class DemoDataSeeder extends Seeder
{
    public function __construct(
        private readonly DemoDataService $service,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function seed(
        string $profile,
        int $randomSeed,
        string $password,
        Command $output,
        bool $includeSettings = true,
    ): array {
        return $this->service->seed(
            $profile,
            $randomSeed,
            $password,
            $output,
            $includeSettings,
        );
    }

    public function run(): void
    {
        throw new LogicException(
            'DemoDataSeeder hanya boleh dijalankan melalui command demo:seed yang memiliki guard keamanan.',
        );
    }
}
