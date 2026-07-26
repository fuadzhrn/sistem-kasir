<?php

namespace App\Services\Audit;

class AuditMetadataSanitizer
{
    private const MAX_DEPTH = 4;

    private const MAX_KEYS = 50;

    private const MAX_STRING_LENGTH = 1000;

    /**
     * @var array<int, string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'password_hash',
        'current_password',
        'new_password',
        'token',
        'remember_token',
        '_token',
        'authorization',
        'cookie',
        'session',
        'checkout_token',
        'secret',
        'api_key',
    ];

    private int $processedKeys = 0;

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public function sanitize(array $metadata): array
    {
        $this->processedKeys = 0;

        return $this->sanitizeArray($metadata, 0);
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<array-key, mixed>
     */
    private function sanitizeArray(array $values, int $depth): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return ['_truncated' => 'Batas kedalaman metadata tercapai.'];
        }

        $sanitized = [];

        foreach ($values as $key => $value) {
            if ($this->processedKeys >= self::MAX_KEYS) {
                $sanitized['_truncated'] = 'Batas jumlah metadata tercapai.';
                break;
            }

            $normalizedKey = strtolower((string) $key);

            if ($this->isSensitiveKey($normalizedKey)) {
                continue;
            }

            $this->processedKeys++;
            $sanitized[$key] = $this->sanitizeValue($value, $depth + 1);
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value, int $depth): mixed
    {
        if (is_array($value)) {
            return $this->sanitizeArray($value, $depth);
        }

        if (is_string($value)) {
            return mb_substr($value, 0, self::MAX_STRING_LENGTH);
        }

        if (is_int($value) || is_float($value) || is_bool($value) || $value === null) {
            return $value;
        }

        return mb_substr((string) $value, 0, self::MAX_STRING_LENGTH);
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_ends_with($key, "_{$sensitiveKey}")) {
                return true;
            }
        }

        return false;
    }
}
