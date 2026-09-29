<?php

namespace App\Console\Commands;

use App\Mail\LeadsSinAtender;
use App\Models\Inquiry;
use App\Support\Leads\AvisoDeConsulta;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Los leads que llevan un dia sin que nadie les conteste, a su promotora.
 *
 * Un lead contestado al dia siguiente vale la mitad; a los tres dias, nada.
 * El aviso de "tienes una consulta nueva" ya sale al momento; esto es el
 * segundo aviso, el de "sigue ahi". Uno por lead, no uno cada dia: un
 * recordatorio que se repite se deja de leer, y entonces tampoco se lee el
 * que importa.
 */
class AvisarLeadsSinAtender extends Command
{
    protected $signature = 'leads:sin-atender
                            {--horas=24 : Horas sin atender a partir de las que se avisa}';

    protected $description = 'Avisa a cada promotora de los leads que llevan demasiado sin atender';

    public function handle(): int
    {
        $horas = (int) $this->option('horas');

        $pendientes = Inquiry::with('project')
            ->where('estado', Inquiry::NUEVO)
            ->whereNull('avisado_sin_atender_en')
            ->where('created_at', '<=', now()->subHours($horas))
            ->get();

        // Un correo por promotora con todos los suyos, no uno por lead.
        $porDestinatario = [];
        foreach ($pendientes as $lead) {
            foreach (AvisoDeConsulta::destinatarios($lead->project) as $correo) {
                $porDestinatario[$correo][] = $lead;
            }
        }

        foreach ($porDestinatario as $correo => $leads) {
            Mail::to($correo)->queue(new LeadsSinAtender(collect($leads), $horas));
        }

        Inquiry::whereIn('id', $pendientes->pluck('id'))->update(['avisado_sin_atender_en' => now()]);

        $this->info(sprintf('leads sin atender: %d lead(s), %d aviso(s)', $pendientes->count(), count($porDestinatario)));

        return self::SUCCESS;
    }
}
