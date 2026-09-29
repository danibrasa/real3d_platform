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
        $avisados = [];
        $sinNadie = [];
        foreach ($pendientes as $lead) {
            $destinatarios = AvisoDeConsulta::destinatarios($lead->project);

            // Sin nadie a quien avisar no se da por avisado: quedaria marcado
            // para siempre y nadie sabria que nunca salio. Se dice, y se
            // vuelve a intentar manana.
            if ($destinatarios->isEmpty()) {
                $sinNadie[] = $lead;

                continue;
            }

            foreach ($destinatarios as $correo) {
                $porDestinatario[$correo][] = $lead;
            }
            $avisados[] = $lead->id;
        }

        foreach ($porDestinatario as $correo => $leads) {
            Mail::to($correo)->queue(new LeadsSinAtender(collect($leads), $horas));
        }

        Inquiry::whereIn('id', $avisados)->update(['avisado_sin_atender_en' => now()]);

        foreach ($sinNadie as $lead) {
            $this->warn("sin nadie a quien avisar del lead #{$lead->id} ({$lead->project->name}): el proyecto no tiene correo de contacto ni promotora");
        }

        $this->info(sprintf('leads sin atender: %d lead(s), %d aviso(s), %d sin nadie a quien avisar',
            $pendientes->count(), count($porDestinatario), count($sinNadie)));

        return self::SUCCESS;
    }
}
