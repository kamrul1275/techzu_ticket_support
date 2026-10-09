<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Default ticket categories
        $categories = [
            'Technical Support',
            'Billing & Payments',
            'Account & Login',
            'Software Bug',
            'Feature Request',
            'General Inquiry',
        ];

        foreach ($categories as $category) {

            TicketCategory::firstOrCreate(
                ['name' => $category],
                ['is_active' => true]
            );
        }
    }
}