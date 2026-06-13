<?php

namespace Database\Seeders;

use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect(range(1, 60))->each(function (int $number): void {
            Workspace::query()->firstOrCreate([
                'number' => $number,
            ], [
                'label' => null,
                'zone' => 'Общий зал',
                'sort_order' => $number,
                'is_active' => true,
            ]);
        });
    }
}
