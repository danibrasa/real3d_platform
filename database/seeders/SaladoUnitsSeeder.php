<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class SaladoUnitsSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::where('slug', 'salado')->firstOrFail();

        // Clear inquiry unit references
        $project->units()->each(function ($unit) {
            $unit->inquiries()->update(['unit_id' => null]);
        });

        // Delete existing units
        $project->units()->delete();

        $units = $this->getUnits();
        $sortOrder = 0;

        foreach ($units as $u) {
            Unit::create([
                'project_id' => $project->id,
                'identifier' => $u['id'],
                'floor' => $u['floor'],
                'bedrooms' => $u['bedrooms'],
                'bathrooms' => $u['bathrooms'],
                'area_m2' => $u['area'],
                'price' => $u['price'],
                'status' => $u['status'],
                'notes' => trim(($u['bloque'] ?? '').' | '.($u['tipologia'] ?? '').($u['buyer'] ? ' | '.$u['buyer'] : '')),
                'sort_order' => $sortOrder++,
            ]);
        }

        $this->command->info('Created '.count($units)." units for project: {$project->name}");
    }

    private function getUnits(): array
    {
        return [
            // ==================== BLOQUE BAVARO ====================
            // Floor 0
            ['id' => 'E101', 'floor' => 0, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 144.91, 'price' => 290250, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina y picuzzi', 'buyer' => 'David/Manuel'],
            ['id' => 'B102', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Felipe'],
            ['id' => 'B103', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 135700, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Juan Salgueiriño/Alberto'],
            ['id' => 'B104', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 135700, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Miguel Ca'],
            ['id' => 'B105', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Alberto Pintos'],
            ['id' => 'E106', 'floor' => 0, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 141.48, 'price' => 286820, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza a piscina y picuzzi', 'buyer' => ''],
            // Floor 1
            ['id' => 'E201', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 112.00, 'price' => 262340, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => ''],
            ['id' => 'B202', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Dani'],
            ['id' => 'B203', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 140700, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Ignacio'],
            ['id' => 'B204', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 140700, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Alberto F'],
            ['id' => 'B205', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Alberto F'],
            ['id' => 'E206', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 112.00, 'price' => 262340, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Terraza vista piscina', 'buyer' => ''],
            // Floor 2 (Penthouses)
            ['id' => 'E301', 'floor' => 2, 'bedrooms' => 3, 'bathrooms' => 3, 'area' => 206.00, 'price' => 380044, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B302', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.00, 'price' => 216919, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => 'Dani'],
            ['id' => 'B303', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 111.00, 'price' => 207836, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => 'Jorge Cun'],
            ['id' => 'B304', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 111.00, 'price' => 207824, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B305', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.00, 'price' => 216919, 'status' => 'sold', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => 'Miguel'],
            ['id' => 'E306', 'floor' => 2, 'bedrooms' => 3, 'bathrooms' => 3, 'area' => 202.00, 'price' => 375196, 'status' => 'available', 'bloque' => 'Bloque Bavaro', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],

            // ==================== BLOQUE SALADO ====================
            // Floor 1
            ['id' => 'C221', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 68.56, 'price' => 161560, 'status' => 'sold', 'bloque' => 'Bloque Salado', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Jorge Cun'],
            ['id' => 'C220', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 70.71, 'price' => 164785, 'status' => 'sold', 'bloque' => 'Bloque Salado', 'tipologia' => 'Terraza vista piscina', 'buyer' => 'Marcos C'],
            // Floor 2 (Penthouses)
            ['id' => 'C321', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 129.04, 'price' => 257280, 'status' => 'sold', 'bloque' => 'Bloque Salado', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'C320', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 133.15, 'price' => 263445, 'status' => 'sold', 'bloque' => 'Bloque Salado', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],

            // ==================== BLOQUE PUNTA CANA ====================
            // Floor 0
            ['id' => 'D107', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 93.88, 'price' => 173995, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina y picuzzi', 'buyer' => 'Marcos C'],
            ['id' => 'D108', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 93.91, 'price' => 174025, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza parking picuzzi', 'buyer' => 'Marcos C'],
            ['id' => 'B109', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza salida piscina', 'buyer' => 'Cesar'],
            ['id' => 'B110', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza salida parking', 'buyer' => 'Marcos C'],
            ['id' => 'B111', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 135700, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza salida piscina', 'buyer' => 'David'],
            ['id' => 'B112', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Fede Battane'],
            ['id' => 'B113', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 135700, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Jose Lorenzo'],
            ['id' => 'B114', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'David'],
            ['id' => 'B115', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza salida piscina', 'buyer' => 'Benito'],
            ['id' => 'B116', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'David'],
            ['id' => 'A117', 'floor' => 0, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 151.98, 'price' => 284540, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina y picuzzi', 'buyer' => ''],
            ['id' => 'B118', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 141735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Benito'],
            ['id' => 'D119', 'floor' => 0, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 101.82, 'price' => 181935, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Enrique C'],
            // Floor 1
            ['id' => 'D207', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.67, 'price' => 147785, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Giorgio'],
            ['id' => 'D208', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.67, 'price' => 147785, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Enrique C'],
            ['id' => 'B209', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Alberto F'],
            ['id' => 'B210', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Marcos C'],
            ['id' => 'B211', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 140700, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Jesus La'],
            ['id' => 'B212', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 143735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Javi'],
            ['id' => 'B213', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 59.50, 'price' => 140700, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Miguel Ca'],
            ['id' => 'B214', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Enrique C'],
            ['id' => 'B215', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => 'Jose Puerta'],
            ['id' => 'B216', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'reserved', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Maria'],
            ['id' => 'A217', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 2, 'area' => 103.74, 'price' => 241300, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a piscina', 'buyer' => ''],
            ['id' => 'B218', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.15, 'price' => 146735, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Jazmin Montesino'],
            ['id' => 'D219', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 2, 'area' => 62.67, 'price' => 147785, 'status' => 'reserved', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Terraza a parking', 'buyer' => 'Katty'],
            // Floor 2 (Penthouses)
            ['id' => 'D307', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 120.39, 'price' => 239365, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => 'Raul Rodriguez'],
            ['id' => 'D308', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 120.28, 'price' => 239200, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'B309', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.42, 'price' => 233215, 'status' => 'reserved', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B310', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.47, 'price' => 233215, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'B311', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 111.35, 'price' => 223475, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B312', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.47, 'price' => 233215, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'B313', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 111.35, 'price' => 223475, 'status' => 'sold', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => 'Marcos C'],
            ['id' => 'B314', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.47, 'price' => 233215, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'B315', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.61, 'price' => 233215, 'status' => 'reserved', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B316', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.47, 'price' => 233215, 'status' => 'reserved', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'A317', 'floor' => 2, 'bedrooms' => 3, 'bathrooms' => 3, 'area' => 198.65, 'price' => 388665, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista piscina', 'buyer' => ''],
            ['id' => 'B318', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 116.47, 'price' => 233215, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
            ['id' => 'D319', 'floor' => 2, 'bedrooms' => 2, 'bathrooms' => 3, 'area' => 117.93, 'price' => 235675, 'status' => 'available', 'bloque' => 'Bloque Punta Cana', 'tipologia' => 'Penthouse vista parking', 'buyer' => ''],
        ];
    }
}
