<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

class StrategicAnalysisController extends Controller
{
    public function index()
    {
        Gate::authorize('create-project');

        $html = $this->markdownToHtml();

        return view('admin.strategic-analysis.index', compact('html'));
    }

    public function downloadPdf()
    {
        Gate::authorize('create-project');

        $html = $this->markdownToHtml();

        $pdf = Pdf::loadView('admin.strategic-analysis.pdf', compact('html'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'sans-serif');

        return $pdf->download('analisis-estrategico-realestate3d.pdf');
    }

    private function markdownToHtml(): string
    {
        $mdPath = storage_path('app/ANALISIS-ESTRATEGICO.md');

        if (! file_exists($mdPath)) {
            abort(404, 'Documento de analisis no encontrado.');
        }

        $markdown = file_get_contents($mdPath);

        $config = [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ];

        $environment = new Environment($config);
        $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new \League\CommonMark\Extension\Autolink\AutolinkExtension);

        $converter = new MarkdownConverter($environment);

        return $converter->convert($markdown)->getContent();
    }
}
