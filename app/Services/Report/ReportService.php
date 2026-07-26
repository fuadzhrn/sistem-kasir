<?php

namespace App\Services\Report;

use App\Models\User;

interface ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function build(User $user, array $filters, bool $forPrint = false): array;
}
