<?php

namespace Database\Seeders;

use App\Models\Ad;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample ads (videos). Adjust count as needed.
        Ad::factory()->count(12)->create();

        // Optionally create a few inactive ads for testing
        Ad::factory()->count(3)->state(['is_active' => false])->create();
    }
}
