<?php

namespace App\Console\Commands;

use App\Support\Pilotos\Embudo;
use Illuminate\Console\Command;

/**
 * El embudo de cada piloto, en texto: para el ciclo semanal (se lee, se
 * apunta lo que digan y lo que hagan, y se decide la tanda de arreglos).
 */
class InformeDePilotos extends Command
{
    protected $signature = 'pilotos:informe';

    protected $description = 'Por cada promotora: dias de alta a visor pedido y a publicado, leads por semana, contestados y en que paso esta';

    public function handle(): int
    {
        $embudos = Embudo::deTodas();
        if (! $embudos) {
            $this->info('pilotos: todavia no hay promotoras');

            return self::SUCCESS;
        }

        $d = fn ($n) => $n === null ? '-' : $n.'d';
        $this->table(
            ['Promotora', 'Alta', '-> pedido', '-> publicado', 'Leads/sem', 'Contestados (en el dia)', 'Visitas 30d', 'Etapa (desde)'],
            array_map(fn ($e) => [
                ($e['promotora']->companyProfile?->company_name ?? $e['promotora']->name).' ('.($e['plan'] ?? 'sin plan').')',
                $e['alta']->format('d/m/Y'),
                $d($e['dias_alta_a_pedido']),
                $d($e['dias_pedido_a_publicado']).($e['publicado_aproximado'] ? ' ~' : ''),
                $e['leads'].'/'.$e['leads_semana'],
                $e['leads_contestados'].' ('.$e['leads_en_el_dia'].')',
                $e['visitas_30d'],
                $e['etapa'].($e['dias_en_etapa'] !== null ? ' ('.$e['dias_en_etapa'].'d)' : ''),
            ], $embudos),
        );

        return self::SUCCESS;
    }
}
