<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class AuditLogService
{
    public function __construct(
        private readonly AuditActionRegistry $registry,
        private readonly AuditMetadataSanitizer $sanitizer,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $module,
        string $description,
        ?User $actor = null,
        Branch|int|null $branch = null,
        ?Model $reference = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ActivityLog {
        $this->registry->assertValid($action, $module);
        $actor ??= auth()->user();
        $request = request();
        $branchId = $branch instanceof Branch ? $branch->getKey() : $branch;

        return ActivityLog::query()->create([
            'user_id' => $actor?->getKey(),
            'branch_id' => $branchId,
            'action' => $action,
            'module' => $module,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'description' => mb_substr(trim($description), 0, 2000),
            'metadata' => $this->sanitizer->sanitize($metadata),
            'ip_address' => mb_substr((string) ($ipAddress ?? $request?->ip()), 0, 45) ?: null,
            'user_agent' => mb_substr((string) ($userAgent ?? $request?->userAgent()), 0, 1000) ?: null,
        ]);
    }

    /**
     * Digunakan untuk audit autentikasi yang tidak boleh menggagalkan login/logout.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordSafely(
        string $action,
        string $module,
        string $description,
        ?User $actor = null,
        Branch|int|null $branch = null,
        ?Model $reference = null,
        array $metadata = [],
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): ?ActivityLog {
        try {
            return $this->record(
                $action,
                $module,
                $description,
                $actor,
                $branch,
                $reference,
                $metadata,
                $ipAddress,
                $userAgent,
            );
        } catch (Throwable $exception) {
            Log::warning('Pencatatan audit non-transaksional gagal.', [
                'action' => $action,
                'module' => $module,
                'exception' => $exception::class,
            ]);

            return null;
        }
    }
}
