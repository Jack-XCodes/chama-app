<?php

namespace Database\Seeders;

use App\Models\TransactionTag;
use Illuminate\Database\Seeder;

class TransactionTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // Priority Tags
            [
                'name' => 'Urgent',
                'color' => 'red',
                'description' => 'Requires immediate attention',
                'is_active' => true,
            ],
            [
                'name' => 'High Priority',
                'color' => 'orange',
                'description' => 'High priority transaction',
                'is_active' => true,
            ],
            [
                'name' => 'Low Priority',
                'color' => 'gray',
                'description' => 'Low priority transaction',
                'is_active' => true,
            ],

            // Status Tags
            [
                'name' => 'Recurring',
                'color' => 'blue',
                'description' => 'Recurring monthly transaction',
                'is_active' => true,
            ],
            [
                'name' => 'One-time',
                'color' => 'green',
                'description' => 'One-time transaction',
                'is_active' => true,
            ],
            [
                'name' => 'Annual',
                'color' => 'purple',
                'description' => 'Annual transaction',
                'is_active' => true,
            ],

            // Payment Method Tags
            [
                'name' => 'Cash',
                'color' => 'green',
                'description' => 'Cash payment',
                'is_active' => true,
            ],
            [
                'name' => 'Bank Transfer',
                'color' => 'blue',
                'description' => 'Bank transfer payment',
                'is_active' => true,
            ],
            [
                'name' => 'Mobile Money',
                'color' => 'yellow',
                'description' => 'Mobile money payment (M-Pesa, etc.)',
                'is_active' => true,
            ],
            [
                'name' => 'Check',
                'color' => 'indigo',
                'description' => 'Check payment',
                'is_active' => true,
            ],

            // Special Tags
            [
                'name' => 'Emergency Fund',
                'color' => 'red',
                'description' => 'Emergency fund related transaction',
                'is_active' => true,
            ],
            [
                'name' => 'Project Fund',
                'color' => 'purple',
                'description' => 'Special project funding',
                'is_active' => true,
            ],
            [
                'name' => 'Welfare',
                'color' => 'pink',
                'description' => 'Member welfare related',
                'is_active' => true,
            ],
            [
                'name' => 'Development',
                'color' => 'teal',
                'description' => 'Group development activities',
                'is_active' => true,
            ],
            [
                'name' => 'Training',
                'color' => 'cyan',
                'description' => 'Training and education related',
                'is_active' => true,
            ],

            // Location Tags
            [
                'name' => 'Local',
                'color' => 'green',
                'description' => 'Local transaction',
                'is_active' => true,
            ],
            [
                'name' => 'Regional',
                'color' => 'blue',
                'description' => 'Regional transaction',
                'is_active' => true,
            ],
            [
                'name' => 'National',
                'color' => 'purple',
                'description' => 'National level transaction',
                'is_active' => true,
            ],

            // Verification Tags
            [
                'name' => 'Verified',
                'color' => 'green',
                'description' => 'Transaction has been verified',
                'is_active' => true,
            ],
            [
                'name' => 'Pending Verification',
                'color' => 'yellow',
                'description' => 'Awaiting verification',
                'is_active' => true,
            ],
            [
                'name' => 'Large Amount',
                'color' => 'orange',
                'description' => 'Large amount transaction requiring special attention',
                'is_active' => true,
            ],

            // Administrative Tags
            [
                'name' => 'Tax Deductible',
                'color' => 'indigo',
                'description' => 'Tax deductible expense',
                'is_active' => true,
            ],
            [
                'name' => 'Audit Required',
                'color' => 'red',
                'description' => 'Requires audit attention',
                'is_active' => true,
            ],
            [
                'name' => 'Budget Approved',
                'color' => 'green',
                'description' => 'Pre-approved in budget',
                'is_active' => true,
            ],
            [
                'name' => 'Over Budget',
                'color' => 'red',
                'description' => 'Exceeds budgeted amount',
                'is_active' => true,
            ],
        ];

        foreach ($tags as $tag) {
            TransactionTag::create(array_merge($tag, [
                'created_by' => 1, // Admin user
                'updated_by' => 1,
            ]));
        }
    }
}