<?php

namespace Tests\Unit;

use App\Services\Audit\AuditMetadataSanitizer;
use PHPUnit\Framework\TestCase;

class AuditMetadataSanitizerTest extends TestCase
{
    public function test_sensitive_keys_are_removed_recursively_and_strings_are_bounded(): void
    {
        $sanitized = (new AuditMetadataSanitizer)->sanitize([
            'name' => 'Aman',
            'password' => 'rahasia',
            'nested' => [
                'authorization' => 'Bearer secret',
                'safe' => str_repeat('x', 1500),
                'password_hash' => 'hash',
            ],
        ]);

        $this->assertSame('Aman', $sanitized['name']);
        $this->assertArrayNotHasKey('password', $sanitized);
        $this->assertArrayNotHasKey('authorization', $sanitized['nested']);
        $this->assertArrayNotHasKey('password_hash', $sanitized['nested']);
        $this->assertSame(1000, mb_strlen($sanitized['nested']['safe']));
    }

    public function test_key_count_and_depth_are_limited(): void
    {
        $metadata = array_fill_keys(array_map(fn (int $i): string => "key_{$i}", range(1, 70)), 'value');
        $sanitized = (new AuditMetadataSanitizer)->sanitize([
            'deep' => ['one' => ['two' => ['three' => ['four' => 'stop']]]],
            ...$metadata,
        ]);

        $this->assertLessThanOrEqual(52, count($sanitized));
        $this->assertArrayHasKey('_truncated', $sanitized['deep']['one']['two']['three']);
    }
}
