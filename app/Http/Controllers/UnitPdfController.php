<?php

namespace App\Http\Controllers;

use App\Models\PaymentMilestone;
use App\Models\Project;
use App\Models\Unit;
use App\Services\CurrencyService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class UnitPdfController extends Controller
{
    public function generate(Project $project, Unit $unit)
    {
        // SECURITY FIX: Verify project is publicly accessible before generating PDF
        if (!in_array($project->status, ['public', 'unlisted'])) {
            abort(404);
        }

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

        // All payment plans with discounts
        $allPlansData = [];
        foreach ($project->paymentPlans as $plan) {
            if ($plan->milestones->isEmpty()) continue;

            $effectivePrice = $plan->effectivePrice($unit->price ?? 0);
            $hasDiscount = $plan->hasDiscount() && $unit->price;

            $milestones = $plan->milestones->sortBy('sort_order')->values();
            $cumPct = 0;
            $msData = [];
            foreach ($milestones as $i => $ms) {
                $cumPct += $ms->percentage;
                $msData[] = [
                    'name' => $ms->name,
                    'pct' => $ms->percentage,
                    'cumPct' => $cumPct,
                    'due_description' => $ms->due_description,
                    'color' => $ms->phaseColor($i, $milestones->count()),
                    'amount' => $effectivePrice > 0 ? $effectivePrice * $ms->percentage / 100 : 0,
                    'cumAmount' => $effectivePrice > 0 ? $effectivePrice * $cumPct / 100 : 0,
                ];
            }

            $allPlansData[] = [
                'plan' => $plan,
                'milestones' => $msData,
                'effectivePrice' => $effectivePrice,
                'hasDiscount' => $hasDiscount,
                'discountAmount' => $hasDiscount ? ($unit->price - $effectivePrice) : 0,
                'discountLabel' => $hasDiscount ? $plan->discountDisplayLabel() : null,
            ];
        }

        // Investment calculator data
        $investmentData = null;
        if ($unit->price) {
            $yield = $project->rental_yield_annual ?? 10;
            $occupancy = $project->average_occupancy ?? 70;
            $appreciation = $project->appreciation_rate_annual ?? 6;
            $mgmtFee = $project->management_fee ?? 20;
            $taxRate = $project->property_tax_rate ?? 1;
            $nightlyRate = $project->avg_nightly_rate ?? 120;
            $years = 5;
            $price = $unit->price;

            $grossAnnual = $nightlyRate * 365 * ($occupancy / 100);
            $mgmtCost = $grossAnnual * ($mgmtFee / 100);
            $taxCost = $price * ($taxRate / 100);
            $netAnnual = $grossAnnual - $mgmtCost - $taxCost;
            $monthlyNet = round($netAnnual / 12);
            $roiAnnual = $price > 0 ? round($netAnnual / $price * 100, 1) : 0;
            $paybackYears = $netAnnual > 0 ? round($price / $netAnnual, 1) : null;
            $futureValue = round($price * pow(1 + $appreciation / 100, $years));
            $totalReturn = round($netAnnual * $years + ($futureValue - $price));

            $investmentData = compact(
                'grossAnnual', 'mgmtCost', 'taxCost', 'netAnnual', 'monthlyNet',
                'roiAnnual', 'paybackYears', 'futureValue', 'totalReturn',
                'nightlyRate', 'occupancy', 'appreciation', 'mgmtFee', 'taxRate', 'years'
            );
        }

        $pdf = Pdf::loadView('pdf.unit-brochure', compact(
            'project',
            'unit',
            'qrSvg',
            'floorPlanBase64',
            'galleryBase64',
            'thumbnailBase64',
            'unitUrl',
            'allPlansData',
            'investmentData',
        ))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'sans-serif');

        $filename = 'ficha-' . $project->slug . '-' . $unit->identifier . '.pdf';

        return $pdf->download($filename);
    }

    public function paymentSchedule(Request $request, Project $project, Unit $unit)
    {
        // SECURITY FIX: Verify project is publicly accessible
        if (!in_array($project->status, ['public', 'unlisted'])) {
            abort(404);
        }

        if ($unit->project_id !== $project->id) {
            abort(404);
        }

        $unit->load('typology');
        $project->load('paymentPlans.milestones');

        // Select plan
        $planId = $request->query('plan');
        if ($planId) {
            $plan = $project->paymentPlans->firstWhere('id', $planId);
        }
        if (!isset($plan) || !$plan) {
            $plan = $project->paymentPlans->firstWhere('is_default', true) ?? $project->paymentPlans->first();
        }

        if (!$plan || $plan->milestones->isEmpty()) {
            abort(404, 'No payment plan available');
        }

        // Currency
        $currencyCode = $request->query('currency', CurrencyService::getCurrentCode());
        $currencyService = app(CurrencyService::class);
        $formattedPrice = $unit->price ? $currencyService->format($unit->price, $currencyCode) : '-';

        // Discount
        $effectivePrice = $plan->effectivePrice($unit->price ?? 0);
        $hasDiscount = $plan->hasDiscount() && $unit->price;
        $formattedEffectivePrice = $hasDiscount
            ? $currencyService->format($effectivePrice, $currencyCode)
            : $formattedPrice;
        $discountAmount = $hasDiscount
            ? $currencyService->format($unit->price - $effectivePrice, $currencyCode)
            : null;
        $discountLabel = $hasDiscount ? $plan->discountDisplayLabel() : null;

        // Build milestones data with cumulative
        $milestones = $plan->milestones->sortBy('sort_order')->values();
        $total = $milestones->count();
        $cumPct = 0;
        $milestonesData = [];

        foreach ($milestones as $i => $ms) {
            $cumPct += $ms->percentage;
            $amount = $effectivePrice > 0 ? $effectivePrice * $ms->percentage / 100 : 0;
            $cumAmount = $effectivePrice > 0 ? $effectivePrice * $cumPct / 100 : 0;

            $computedDate = null;
            if ($project->estimated_delivery && $cumPct > 0) {
                $totalMonths = now()->diffInMonths($project->estimated_delivery);
                $computedDate = now()->addMonths(round($totalMonths * $cumPct / 100))->translatedFormat('M Y');
            }

            $milestonesData[] = [
                'name' => $ms->name,
                'pct' => $ms->percentage,
                'cumPct' => $cumPct,
                'due_description' => $ms->due_description,
                'color' => $ms->phaseColor($i, $total),
                'formattedAmount' => $unit->price ? $currencyService->format($amount, $currencyCode) : '-',
                'formattedCumAmount' => $unit->price ? $currencyService->format($cumAmount, $currencyCode) : '-',
                'computedDate' => $computedDate,
            ];
        }

        // QR pointing to landing#planes-de-pago
        $landingUrl = route('viewer.landing', $project->slug) . '#planes-de-pago';
        $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(120)
            ->margin(0)
            ->generate($landingUrl);

        $pdf = Pdf::loadView('pdf.payment-schedule', compact(
            'project',
            'unit',
            'plan',
            'milestonesData',
            'formattedPrice',
            'formattedEffectivePrice',
            'hasDiscount',
            'discountAmount',
            'discountLabel',
            'qrSvg',
            'landingUrl',
        ))
            ->setPaper('a4')
            ->setOption('defaultFont', 'sans-serif');

        $filename = 'plan-pagos-' . $project->slug . '-' . $unit->identifier . '.pdf';

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
