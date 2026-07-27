<?php

namespace App\Services\Setting;

use App\Models\Setting;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use Illuminate\Support\Facades\DB;

class StoreSettingService
{
    /**
     * @var array<string, array{default: mixed, type: string, group: string}>
     */
    private array $definitions;

    /**
     * @var array<string, string|null>|null
     */
    private ?array $loadedValues = null;

    public function __construct(private readonly AuditLogService $auditLog)
    {
        $this->definitions = [
            'store.name' => ['default' => config('app.name', 'Toko'), 'type' => 'string', 'group' => 'store'],
            'store.logo_path' => ['default' => null, 'type' => 'string', 'group' => 'store'],
            'store.address' => ['default' => null, 'type' => 'string', 'group' => 'store'],
            'store.phone' => ['default' => null, 'type' => 'string', 'group' => 'store'],
            'receipt.footer_message' => ['default' => 'Terima kasih telah berbelanja.', 'type' => 'string', 'group' => 'receipt'],
            'receipt.additional_information' => ['default' => null, 'type' => 'string', 'group' => 'receipt'],
            'receipt.default_paper_width' => ['default' => 80, 'type' => 'integer', 'group' => 'receipt'],
            'receipt.show_logo' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_store_address' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_store_phone' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_branch_address' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_branch_phone' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_product_code' => ['default' => false, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_transaction_notes' => ['default' => false, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.show_copy_label' => ['default' => true, 'type' => 'boolean', 'group' => 'receipt'],
            'receipt.number_format' => ['default' => 'branch_date_sequence', 'type' => 'string', 'group' => 'receipt'],
            'receipt.number_prefix' => ['default' => null, 'type' => 'string', 'group' => 'receipt'],
            'receipt.number_separator' => ['default' => '-', 'type' => 'string', 'group' => 'receipt'],
            'receipt.sequence_digits' => ['default' => 4, 'type' => 'integer', 'group' => 'receipt'],
            'business.default_minimum_stock' => ['default' => '0.000', 'type' => 'decimal', 'group' => 'business'],
            'business.maximum_cashier_discount' => ['default' => '0.00', 'type' => 'decimal', 'group' => 'business'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $this->loadedValues = null;
        $values = [];

        foreach (array_keys($this->definitions) as $key) {
            $values[$key] = $this->get($key);
        }

        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = $this->definitions[$key] ?? null;

        if ($definition === null) {
            return $default;
        }

        $raw = $this->rawValue($key);

        if ($raw === null || ($definition['type'] === 'string' && trim($raw) === '')) {
            return $default ?? $definition['default'];
        }

        return match ($definition['type']) {
            'boolean' => $this->toBoolean($raw, (bool) $definition['default']),
            'integer' => $this->toInteger($key, $raw, (int) $definition['default']),
            'decimal' => $this->toDecimal($key, $raw, (string) $definition['default']),
            default => trim($raw),
        };
    }

    public function getString(string $key, ?string $default = null): ?string
    {
        $value = $this->get($key, $default);

        return $value === null ? null : (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        return (bool) $this->get($key, $default);
    }

    public function getInteger(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getDecimal(string $key, string $default = '0.00'): string
    {
        return (string) $this->get($key, $default);
    }

    public function hasStored(string $key): bool
    {
        $this->rawValue($key);

        return array_key_exists($key, $this->loadedValues ?? []);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function setMany(array $settings, User $actor): void
    {
        $groups = array_values(array_unique(array_map(
            fn (string $key): string => $this->definitions[$key]['group'] ?? 'unknown',
            array_keys($settings),
        )));

        if (count($groups) !== 1 || $groups[0] === 'unknown') {
            throw new \InvalidArgumentException('Pengaturan harus berasal dari satu group yang diizinkan.');
        }

        [$action, $description] = match ($groups[0]) {
            'store' => ['store_settings_updated', 'Informasi toko diperbarui.'],
            'receipt' => ['receipt_settings_updated', 'Pengaturan struk diperbarui.'],
            'business' => ['business_settings_updated', 'Aturan bisnis toko diperbarui.'],
            default => throw new \InvalidArgumentException('Group pengaturan tidak diizinkan.'),
        };

        $this->persist($settings, $actor, $action, $description);
    }

    /**
     * @return array<string, mixed>
     */
    public function generalSettings(): array
    {
        return $this->onlyGroup('store');
    }

    /**
     * @return array<string, mixed>
     */
    public function receiptSettings(): array
    {
        return $this->onlyGroup('receipt');
    }

    /**
     * @return array<string, mixed>
     */
    public function businessSettings(): array
    {
        return $this->onlyGroup('business');
    }

    /**
     * @return array<string, mixed>
     */
    public function allForOwnerPage(): array
    {
        return $this->all();
    }

    public function storeName(): string
    {
        $this->loadedValues = null;
        $name = trim((string) $this->get('store.name'));

        return $name !== '' ? $name : (trim((string) config('app.name')) ?: 'Toko');
    }

    public function defaultMinimumStock(): string
    {
        $this->loadedValues = null;

        return (string) $this->get('business.default_minimum_stock');
    }

    public function maximumCashierDiscount(): string
    {
        $this->loadedValues = null;

        return (string) $this->get('business.maximum_cashier_discount');
    }

    public function defaultPaperWidth(): int
    {
        return (int) $this->get('receipt.default_paper_width');
    }

    /**
     * @return array{format: string, prefix: string|null, separator: string, digits: int}
     */
    public function receiptNumberSettings(): array
    {
        $this->loadedValues = null;
        $format = (string) $this->get('receipt.number_format');
        $allowedFormats = [
            'branch_date_sequence',
            'prefix_branch_date_sequence',
            'branch_date_sequence_slash',
            'prefix_branch_date_sequence_slash',
        ];
        $format = in_array($format, $allowedFormats, true) ? $format : 'branch_date_sequence';
        $separator = str_ends_with($format, '_slash') ? '/' : '-';
        $prefix = preg_replace('/[^A-Z0-9]/', '', mb_strtoupper((string) $this->get('receipt.number_prefix')));
        $digits = (int) $this->get('receipt.sequence_digits');

        return [
            'format' => $format,
            'prefix' => $prefix !== '' ? mb_substr($prefix, 0, 10) : null,
            'separator' => $separator,
            'digits' => in_array($digits, [4, 5, 6], true) ? $digits : 4,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGeneral(array $data, User $actor): void
    {
        $this->persist([
            'store.name' => $data['store_name'],
            'store.address' => $data['store_address'] ?? null,
            'store.phone' => $data['store_phone'] ?? null,
        ], $actor, 'store_settings_updated', 'Informasi toko diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateReceipt(array $data, User $actor): void
    {
        $this->persist([
            'receipt.footer_message' => $data['receipt_footer_message'] ?? null,
            'receipt.additional_information' => $data['receipt_additional_information'] ?? null,
            'receipt.default_paper_width' => $data['default_paper_width'],
            'receipt.show_logo' => $data['show_logo'],
            'receipt.show_store_address' => $data['show_store_address'],
            'receipt.show_store_phone' => $data['show_store_phone'],
            'receipt.show_branch_address' => $data['show_branch_address'],
            'receipt.show_branch_phone' => $data['show_branch_phone'],
            'receipt.show_product_code' => $data['show_product_code'],
            'receipt.show_transaction_notes' => $data['show_transaction_notes'],
            'receipt.show_copy_label' => $data['show_copy_label'],
            'receipt.number_format' => $data['number_format'],
            'receipt.number_prefix' => $data['number_prefix'] ?? null,
            'receipt.number_separator' => $data['number_separator'],
            'receipt.sequence_digits' => $data['sequence_digits'],
        ], $actor, 'receipt_settings_updated', 'Pengaturan struk dan nomor nota diperbarui.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBusiness(array $data, User $actor): void
    {
        $this->persist([
            'business.default_minimum_stock' => $this->formatDecimal((string) $data['default_minimum_stock'], 3),
            'business.maximum_cashier_discount' => $this->formatDecimal((string) $data['maximum_cashier_discount'], 2),
        ], $actor, 'business_settings_updated', 'Aturan bisnis toko diperbarui.');
    }

    public function updateLogoPath(?string $path, User $actor, bool $removing = false): void
    {
        $this->persist(
            ['store.logo_path' => $path],
            $actor,
            $removing ? 'store_logo_removed' : 'store_logo_updated',
            $removing ? 'Logo toko dihapus.' : 'Logo toko diperbarui.',
            true,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function persist(
        array $values,
        User $actor,
        string $action,
        string $description,
        bool $logoMetadata = false,
    ): void {
        $unknownKeys = array_diff(array_keys($values), array_keys($this->definitions));

        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException('Key pengaturan tidak diizinkan.');
        }

        DB::transaction(function () use ($values, $actor, $action, $description, $logoMetadata): void {
            $existing = Setting::query()
                ->whereIn('key', array_keys($values))
                ->lockForUpdate()
                ->get()
                ->keyBy('key');
            $before = [];
            $after = [];
            $changed = [];

            foreach ($values as $key => $value) {
                $definition = $this->definitions[$key];
                $storedValue = $this->serialize($key, $definition['type'], $value);
                $setting = $existing->get($key) ?? new Setting(['key' => $key]);
                $before[$key] = $setting->exists ? $setting->value : null;
                $after[$key] = $storedValue;

                if ($before[$key] !== $storedValue) {
                    $changed[] = $key;
                }

                $setting->fill([
                    'value' => $storedValue,
                    'type' => $definition['type'],
                    'group' => $definition['group'],
                    'is_public' => false,
                    'updated_by' => $actor->getKey(),
                ])->save();
            }

            $metadata = $logoMetadata
                ? [
                    'old_logo_available' => ($before['store.logo_path'] ?? null) !== null,
                    'new_logo_available' => ($after['store.logo_path'] ?? null) !== null,
                    'logo_path' => $after['store.logo_path'] ?? null,
                ]
                : [
                    'changed_fields' => $changed,
                    'before' => $this->auditSafeValues($before),
                    'after' => $this->auditSafeValues($after),
                ];

            $this->auditLog->record(
                $action,
                'settings',
                $description,
                $actor,
                metadata: $metadata,
            );
        }, 3);

        $this->loadedValues = null;
    }

    private function rawValue(string $key): ?string
    {
        if ($this->loadedValues === null) {
            $keys = array_keys($this->definitions);
            $aliases = [
                'store_name',
                'receipt_width',
                'receipt_message',
                'default_minimum_stock',
                'maximum_cashier_discount',
            ];
            $this->loadedValues = Setting::query()
                ->whereIn('key', [...$keys, ...$aliases])
                ->pluck('value', 'key')
                ->all();
        }

        $aliases = [
            'store.name' => 'store_name',
            'receipt.default_paper_width' => 'receipt_width',
            'receipt.footer_message' => 'receipt_message',
            'business.default_minimum_stock' => 'default_minimum_stock',
            'business.maximum_cashier_discount' => 'maximum_cashier_discount',
        ];

        return $this->loadedValues[$key] ?? $this->loadedValues[$aliases[$key] ?? ''] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    private function onlyGroup(string $group): array
    {
        $this->loadedValues = null;
        $values = [];

        foreach ($this->definitions as $key => $definition) {
            if ($definition['group'] === $group) {
                $values[$key] = $this->get($key);
            }
        }

        return $values;
    }

    private function serialize(string $key, string $type, mixed $value): ?string
    {
        if ($value === null || ($type === 'string' && trim((string) $value) === '')) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => $this->serializeInteger($key, $value),
            'decimal' => $this->serializeDecimal($key, $value),
            default => trim((string) $value),
        };
    }

    private function serializeInteger(string $key, mixed $value): string
    {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            throw new \InvalidArgumentException("Nilai integer {$key} tidak valid.");
        }

        $integer = (int) $value;

        if ($key === 'receipt.default_paper_width' && ! in_array($integer, [58, 80], true)) {
            throw new \InvalidArgumentException('Ukuran struk tidak valid.');
        }

        if ($key === 'receipt.sequence_digits' && ! in_array($integer, [4, 5, 6], true)) {
            throw new \InvalidArgumentException('Jumlah digit urutan tidak valid.');
        }

        return (string) $integer;
    }

    private function serializeDecimal(string $key, mixed $value): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $value)) {
            throw new \InvalidArgumentException("Nilai decimal {$key} tidak valid.");
        }

        return $this->formatDecimal(
            $value,
            $key === 'business.default_minimum_stock' ? 3 : 2,
        );
    }

    private function toBoolean(string $value, bool $default): bool
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }

    private function toInteger(string $key, string $value, int $default): int
    {
        if (! preg_match('/^\d+$/', $value)) {
            return $default;
        }

        $integer = (int) $value;

        if ($key === 'receipt.default_paper_width' && ! in_array($integer, [58, 80], true)) {
            return 80;
        }

        if ($key === 'receipt.sequence_digits' && ! in_array($integer, [4, 5, 6], true)) {
            return 4;
        }

        return $integer;
    }

    private function toDecimal(string $key, string $value, string $default): string
    {
        if (! preg_match('/^\d+(?:\.\d+)?$/', $value)) {
            return $default;
        }

        $scale = $key === 'business.default_minimum_stock' ? 3 : 2;

        return $this->formatDecimal($value, $scale);
    }

    private function formatDecimal(string $value, int $scale): string
    {
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        $whole = ltrim($whole, '0') ?: '0';

        return $whole.'.'.substr(str_pad($fraction, $scale, '0'), 0, $scale);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function auditSafeValues(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($key === 'receipt.additional_information' && is_string($value) && mb_strlen($value) > 250) {
                $values[$key] = mb_substr($value, 0, 250).'…';
            }
        }

        return $values;
    }
}
