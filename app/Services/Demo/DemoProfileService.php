<?php

namespace App\Services\Demo;

use InvalidArgumentException;

class DemoProfileService
{
    /**
     * @return array<string, int|float|string>
     */
    public function get(string $name): array
    {
        $profiles = [
            'small' => [
                'name' => 'small',
                'branches' => 4,
                'owners' => 1,
                'admins' => 4,
                'cashiers' => 8,
                'products' => 50,
                'receipts' => 24,
                'sales' => 250,
                'expenses' => 30,
                'adjustments' => 15,
                'price_changes' => 20,
                'transfers' => 4,
                'void_rate' => 0.03,
                'reprint_rate' => 0.02,
                'today_sales_per_branch' => 3,
            ],
            'medium' => [
                'name' => 'medium',
                'branches' => 4,
                'owners' => 1,
                'admins' => 4,
                'cashiers' => 12,
                'products' => 150,
                'receipts' => 100,
                'sales' => 2000,
                'expenses' => 150,
                'adjustments' => 60,
                'price_changes' => 80,
                'transfers' => 12,
                'void_rate' => 0.035,
                'reprint_rate' => 0.03,
                'today_sales_per_branch' => 8,
            ],
            'large' => [
                'name' => 'large',
                'branches' => 4,
                'owners' => 1,
                'admins' => 4,
                'cashiers' => 12,
                'products' => 300,
                'receipts' => 240,
                'sales' => 5000,
                'expenses' => 400,
                'adjustments' => 150,
                'price_changes' => 200,
                'transfers' => 24,
                'void_rate' => 0.04,
                'reprint_rate' => 0.03,
                'today_sales_per_branch' => 12,
            ],
            'testing' => [
                'name' => 'testing',
                'branches' => 4,
                'owners' => 1,
                'admins' => 4,
                'cashiers' => 8,
                'products' => 12,
                'receipts' => 4,
                'sales' => 20,
                'expenses' => 8,
                'adjustments' => 4,
                'price_changes' => 4,
                'transfers' => 2,
                'void_rate' => 0.05,
                'reprint_rate' => 0.05,
                'today_sales_per_branch' => 3,
            ],
        ];

        if (! isset($profiles[$name])) {
            throw new InvalidArgumentException("Profile data demo tidak dikenal: {$name}.");
        }

        if ($name === 'testing' && ! app()->environment('testing')) {
            throw new InvalidArgumentException('Profile testing hanya tersedia pada environment testing.');
        }

        return $profiles[$name];
    }

    /**
     * @return array<int, string>
     */
    public function commandProfiles(): array
    {
        return ['small', 'medium', 'large'];
    }
}
