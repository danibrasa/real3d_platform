<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;

class UnitPdfController extends Controller
{
    public function generate(Project $project, Unit $unit)
    {
        // Ensure unit belongs to project
        if ($unit->project_id !== $project->id) {
            abort(404);
        }

        $unit->load('typology');
        $project->load(['galleryImages', 'paymentPlans.milestones']);

        // Build QR code as SVG
        $unitUrl = route('viewer.landing', $project->slug) . '?unit=' . $unit->id;
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(120)
            ->margin(0)
            ->generate($unitUrl);

        // Floor plan as base64 (if exists)
        $floorPlanBase64 = null;
        $floorPlanPath = $unit->floor_plan;
        if ($floorPlanPath) {
            $absolutePath = $this->resolveStoragePath($floorPlanPath);
            if ($absolutePath) {
                $floorPlanBase64 = $this->imageToBase64($absolutePath, 600);
            }
        }

        // Gallery images as base64 (first 4, compressed)
        $galleryBase64 = [];
        foreach ($project->galleryImages->take(4) as $img) {
            $imgPath = $this->resolveStoragePath($img->image_path);
            if ($imgPath) {
                $galleryBase64[] = $this->imageToBase64($imgPath, 300);
            }
        }

        // Thumbnail as base64
        $thumbnailBase64 = null;
        if ($project->thumbnail_path) {
            $thumbPath = $this->resolveStoragePath($project->thumbnail_path);
            if (!$thumbPath) {
                $thumbPath = public_path('storage/' . $project->thumbnail_path);
                if (!file_exists($thumbPath)) {
                    $thumbPath = null;
                }
            }
            if ($thumbPath) {
                $thumbnailBase64 = $this->imageToBase64($thumbPath, 500);
            }
        }

        $pdf = Pdf::loadView('pdf.unit-brochure', compact(
            'project',
            'unit',
            'qrSvg',
            'floorPlanBase64',
            'galleryBase64',
            'thumbnailBase64',
            'unitUrl',
        ))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'sans-serif');

        $filename = 'ficha-' . $project->slug . '-' . $unit->identifier . '.pdf';

        return $pdf->download($filename);
    }

    private function resolveStoragePath(string $relativePath): ?string
    {
        $paths = [
            storage_path('app/private/' . $relativePath),
            storage_path('app/' . $relativePath),
        ];

        foreach ($paths as $path) {
            if (file_exists($path) && !is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    private function imageToBase64(string $path, int $maxWidth): string
    {
        $info = getimagesize($path);
        if (!$info) {
            $mime = mime_content_type($path);
            return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
        }

        [$origW, $origH, $type] = $info;

        // If already small enough, just encode directly
        if ($origW <= $maxWidth) {
            return 'data:' . $info['mime'] . ';base64,' . base64_encode(file_get_contents($path));
        }

        // Resize
        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
        $newH = (int) round($origH * $ratio);

        $source = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => null,
        };

        if (!$source) {
            return 'data:' . $info['mime'] . ';base64,' . base64_encode(file_get_contents($path));
        }

        $dest = imagecreatetruecolor($newW, $newH);

        // Preserve transparency for PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }

        imagecopyresampled($dest, $source, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        imagejpeg($dest, null, 75);
        $data = ob_get_clean();

        imagedestroy($source);
        imagedestroy($dest);

        return 'data:image/jpeg;base64,' . base64_encode($data);
    }
}
