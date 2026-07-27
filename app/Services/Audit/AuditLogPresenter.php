<?php

namespace App\Services\Audit;

use App\Models\ActivityLog;
use App\Models\User;
use App\Support\Format\Quantity;

class AuditLogPresenter
{
    /**
     * @var array<int, string>
     */
    private const ADMIN_HIDDEN_KEYS = [
        'old_purchase_price',
        'new_purchase_price',
        'purchase_price',
        'average_cost',
        'average_cost_before',
        'average_cost_after',
        'unit_cost',
        'inventory_value',
        'total_cost',
        'hpp',
    ];

    public function __construct(
        private readonly AuditActionRegistry $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function presentForUser(ActivityLog $activityLog, User $viewer): array
    {
        $metadata = $activityLog->metadata ?? [];

        if (! $viewer->isOwner()) {
            $metadata = $this->withoutInternalFinancials($metadata);
        }

        $metadata = $this->formatQuantities($metadata);

        return [
            'id' => $activityLog->getKey(),
            'action' => $activityLog->action,
            'action_label' => $this->registry->actionLabel($activityLog->action),
            'module' => $activityLog->module,
            'module_label' => $this->registry->moduleLabel($activityLog->module),
            'description' => $activityLog->description,
            'metadata' => $metadata,
            'reference_type' => $activityLog->reference_type,
            'reference_id' => $activityLog->reference_id,
            'ip_address' => $viewer->isOwner() ? $activityLog->ip_address : null,
            'user_agent' => $viewer->isOwner() ? $activityLog->user_agent : null,
            'created_at' => $activityLog->created_at,
            'user' => $activityLog->user,
            'branch' => $activityLog->branch,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $metadata
     * @return array<array-key, mixed>
     */
    private function withoutInternalFinancials(array $metadata): array
    {
        $filtered = [];

        foreach ($metadata as $key => $value) {
            if (in_array(strtolower((string) $key), self::ADMIN_HIDDEN_KEYS, true)) {
                continue;
            }

            $filtered[$key] = is_array($value)
                ? $this->withoutInternalFinancials($value)
                : $value;
        }

        return $filtered;
    }

    /**
     * @param  array<array-key, mixed>  $metadata
     * @return array<array-key, mixed>
     */
    private function formatQuantities(array $metadata, ?string $parentKey = null): array
    {
        $formatted = [];

        foreach ($metadata as $key => $value) {
            $keyName = mb_strtolower((string) $key);

            if (is_array($value)) {
                $formatted[$key] = $this->formatQuantities($value, $keyName);

                continue;
            }

            $formatted[$key] = $this->isQuantityKey($keyName)
                || ($parentKey !== null && $this->isQuantityKey($parentKey))
                    ? Quantity::format(is_scalar($value) ? $value : null)
                    : $value;
        }

        return $formatted;
    }

    private function isQuantityKey(string $key): bool
    {
        return in_array($key, [
            'quantity',
            'quantity_before',
            'quantity_change',
            'quantity_after',
            'target_quantity',
            'minimum_stock',
            'available_quantity',
            'requested_quantity',
            'source_quantity_before',
            'source_quantity_after',
            'destination_quantity_before',
            'destination_quantity_after',
        ], true);
    }
}
