<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            // Income Categories
            [
                'name' => 'Member Contributions',
                'type' => 'income',
                'color' => '#10B981', // green-500
                'description' => 'Regular member monthly contributions',
                'is_active' => true,
            ],
            [
                'name' => 'Investment Returns',
                'type' => 'income',
                'color' => '#059669', // green-600
                'description' => 'Returns from group investments',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Interest',
                'type' => 'income',
                'color' => '#047857', // green-700
                'description' => 'Interest earned from bank deposits',
                'is_active' => true,
            ],
            [
                'name' => 'Loan Interest',
                'type' => 'income',
                'color' => '#065F46', // green-800
                'description' => 'Interest from member loans',
                'is_active' => true,
            ],
            [
                'name' => 'Fines & Penalties',
                'type' => 'income',
                'color' => '#FCD34D', // yellow-300
                'description' => 'Fines and penalties from members',
                'is_active' => true,
            ],

            // Expense Categories
            [
                'name' => 'Office Supplies',
                'type' => 'expense',
                'color' => '#EF4444', // red-500
                'description' => 'Stationery, printing, and office materials',
                'is_active' => true,
            ],
            [
                'name' => 'Meeting Costs',
                'type' => 'expense',
                'color' => '#DC2626', // red-600
                'description' => 'Venue, refreshments, and meeting expenses',
                'is_active' => true,
            ],
            [
                'name' => 'Investment Fees',
                'type' => 'expense',
                'color' => '#B91C1C', // red-700
                'description' => 'Fees for investment transactions and management',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Charges',
                'type' => 'expense',
                'color' => '#991B1B', // red-800
                'description' => 'Bank transaction fees and charges',
                'is_active' => true,
            ],
            [
                'name' => 'Legal & Professional',
                'type' => 'expense',
                'color' => '#7C2D12', // red-900
                'description' => 'Legal fees, auditing, and professional services',
                'is_active' => true,
            ],
            [
                'name' => 'Transport & Travel',
                'type' => 'expense',
                'color' => '#F97316', // orange-500
                'description' => 'Travel expenses for group activities',
                'is_active' => true,
            ],
            [
                'name' => 'Training & Education',
                'type' => 'expense',
                'color' => '#EA580C', // orange-600
                'description' => 'Member training and educational programs',
                'is_active' => true,
            ],
            [
                'name' => 'Insurance',
                'type' => 'expense',
                'color' => '#C2410C', // orange-700
                'description' => 'Group insurance premiums',
                'is_active' => true,
            ],

            // Payment Categories
            [
                'name' => 'Monthly Contribution',
                'type' => 'payment',
                'color' => '#3B82F6', // blue-500
                'description' => 'Regular monthly member payments',
                'is_active' => true,
            ],
            [
                'name' => 'Special Levy',
                'type' => 'payment',
                'color' => '#2563EB', // blue-600
                'description' => 'Special contributions for specific projects',
                'is_active' => true,
            ],
            [
                'name' => 'Loan Repayment',
                'type' => 'payment',
                'color' => '#1D4ED8', // blue-700
                'description' => 'Member loan repayments',
                'is_active' => true,
            ],
            [
                'name' => 'Registration Fee',
                'type' => 'payment',
                'color' => '#1E40AF', // blue-800
                'description' => 'New member registration fees',
                'is_active' => true,
            ],

            // Investment Categories
            [
                'name' => 'Fixed Deposits',
                'type' => 'investment',
                'color' => '#8B5CF6', // violet-500
                'description' => 'Bank fixed deposit investments',
                'is_active' => true,
            ],
            [
                'name' => 'Treasury Bills',
                'type' => 'investment',
                'color' => '#7C3AED', // violet-600
                'description' => 'Government treasury bill investments',
                'is_active' => true,
            ],
            [
                'name' => 'Money Market',
                'type' => 'investment',
                'color' => '#6D28D9', // violet-700
                'description' => 'Money market fund investments',
                'is_active' => true,
            ],
            [
                'name' => 'Equity Investments',
                'type' => 'investment',
                'color' => '#5B21B6', // violet-800
                'description' => 'Stock market and equity investments',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            TransactionCategory::create(array_merge($category, [
                'created_by' => 1, // Admin user
                'updated_by' => 1,
            ]));
        }
    }
}