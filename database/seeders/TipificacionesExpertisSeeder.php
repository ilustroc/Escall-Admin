<?php

namespace Database\Seeders;

use App\Models\TipificacionExpertis;
use Illuminate\Database\Seeder;

class TipificacionesExpertisSeeder extends Seeder
{
    public function run(): void
    {
        $tipificaciones = [
            ['PPC', 'CEF', 1],
            ['PPM', 'CEF', 2],
            ['PAR', 'CEF', 3],
            ['VLL', 'CEF', 4],
            ['RPP', 'CEF', 5],
            ['CAN', 'CEF', 6],
            ['TAT', 'CEF', 7],
            ['REN', 'CEF', 8],
            ['REC', 'CEF', 9],
            ['FAL', 'CNE', 10],
            ['MCT', 'CNE', 11],
            ['DES', 'NOC', 12],
            ['GRB', 'NOC', 13],
            ['HOM', 'NOC', 14],
            ['ILC', 'NOC', 15],
            ['NOC', 'NOC', 16],
            ['NTT', 'NOC', 17],
        ];

        foreach ($tipificaciones as [$tipificacion, $gestion, $peso]) {
            TipificacionExpertis::updateOrCreate(
                ['tipificacion' => $tipificacion],
                [
                    'gestion' => $gestion,
                    'peso' => $peso,
                    'activo' => true,
                ],
            );
        }
    }
}
