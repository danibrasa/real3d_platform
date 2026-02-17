<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Unit;
use App\Models\UnitTypology;
use Illuminate\Database\Seeder;

class DemoProjectSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('slug', 'salado')->first();

        if (!$project) {
            $this->command->warn('Project "salado" not found. Skipping demo seeder.');
            return;
        }

        // Update landing fields
        $project->update([
            'tagline' => 'Vivir frente al mar, tu nuevo estilo de vida',
            'total_floors' => 4,
            'estimated_delivery' => '2027-06-01',
            'whatsapp_number' => '+59891234567',
            'contact_email' => 'ventas@salado.com',
        ]);

        // Create typologies
        $tipo1d = UnitTypology::create([
            'project_id' => $project->id,
            'name' => '1 Dormitorio',
            'bedrooms' => 1,
            'bathrooms' => 1,
            'area_m2' => 45.50,
            'description' => 'Unidad compacta ideal para inversores o parejas.',
        ]);

        $tipo2d = UnitTypology::create([
            'project_id' => $project->id,
            'name' => '2 Dormitorios Tipo A',
            'bedrooms' => 2,
            'bathrooms' => 1,
            'area_m2' => 68.00,
            'description' => 'Amplio living-comedor con balcon y vista al mar.',
        ]);

        // Create 12 units across 4 floors
        $statuses = ['available', 'available', 'available', 'reserved', 'available', 'sold',
                     'available', 'available', 'reserved', 'available', 'available', 'available'];

        $unitData = [
            // Floor 0 (PB)
            ['identifier' => 'PB-01', 'floor' => 0, 'typology' => $tipo1d, 'price' => 65000],
            ['identifier' => 'PB-02', 'floor' => 0, 'typology' => $tipo2d, 'price' => 85000],
            ['identifier' => 'PB-03', 'floor' => 0, 'typology' => $tipo2d, 'price' => 88000],
            // Floor 1
            ['identifier' => '1A', 'floor' => 1, 'typology' => $tipo1d, 'price' => 70000],
            ['identifier' => '1B', 'floor' => 1, 'typology' => $tipo2d, 'price' => 92000],
            ['identifier' => '1C', 'floor' => 1, 'typology' => $tipo2d, 'price' => 95000],
            // Floor 2
            ['identifier' => '2A', 'floor' => 2, 'typology' => $tipo1d, 'price' => 72000],
            ['identifier' => '2B', 'floor' => 2, 'typology' => $tipo2d, 'price' => 95000],
            ['identifier' => '2C', 'floor' => 2, 'typology' => $tipo2d, 'price' => 98000],
            // Floor 3
            ['identifier' => '3A', 'floor' => 3, 'typology' => $tipo1d, 'price' => 75000],
            ['identifier' => '3B', 'floor' => 3, 'typology' => $tipo2d, 'price' => 100000],
            ['identifier' => '3C', 'floor' => 3, 'typology' => $tipo2d, 'price' => 105000],
        ];

        foreach ($unitData as $i => $data) {
            Unit::create([
                'project_id' => $project->id,
                'typology_id' => $data['typology']->id,
                'identifier' => $data['identifier'],
                'floor' => $data['floor'],
                'bedrooms' => $data['typology']->bedrooms,
                'bathrooms' => $data['typology']->bathrooms,
                'area_m2' => $data['typology']->area_m2,
                'price' => $data['price'],
                'status' => $statuses[$i],
                'sort_order' => $i,
            ]);
        }

        $this->command->info('Demo data created: 2 typologies + 12 units for project "' . $project->name . '"');
    }
}
