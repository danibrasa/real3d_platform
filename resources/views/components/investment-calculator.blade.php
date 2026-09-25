@props(['project', 'priceMin' => null])

@php
    $yield = $project->rental_yield_annual ?? 10;
    $occupancy = $project->average_occupancy ?? 70;
    $appreciation = $project->appreciation_rate_annual ?? 6;
    $mgmtFee = $project->management_fee ?? 20;
    $taxRate = $project->property_tax_rate ?? 1;
    $nightlyRate = $project->avg_nightly_rate ?? 120;
    $defaultPrice = $priceMin ?? 150000;
@endphp

<section x-data="investCalc({{ $defaultPrice }}, {{ $nightlyRate }}, {{ $occupancy }}, {{ $appreciation }}, {{ $mgmtFee }}, {{ $taxRate }})"
         @unit-selected-price.window="price = $event.detail.price"
         class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 md:p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-2">Calculadora de Inversion</h2>
    <p class="text-sm text-gray-500 mb-6">Proyecta el rendimiento de tu inversion inmobiliaria</p>

    <div class="grid md:grid-cols-2 gap-8">
        {{-- Inputs --}}
        <div class="space-y-5">
            {{-- Unit price --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Precio de la unidad (USD)</label>
                <input type="number" x-model.number="price" min="10000" step="1000"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            {{-- Down payment --}}
            <div>
                <div class="flex justify-between mb-1">
                    <label class="text-xs font-medium text-gray-600">Enganche</label>
                    <span class="text-xs text-gray-500" x-text="downPaymentPct + '% — USD ' + formatN(Math.round(price * downPaymentPct / 100))"></span>
                </div>
                <input type="range" x-model.number="downPaymentPct" min="10" max="100" step="5"
                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
            </div>

            {{-- Nightly rate --}}
            <div>
                <div class="flex justify-between mb-1">
                    <label class="text-xs font-medium text-gray-600">Tarifa noche (USD)</label>
                    <span class="text-xs text-gray-500" x-text="'USD ' + nightlyRate"></span>
                </div>
                <input type="range" x-model.number="nightlyRate" min="30" max="500" step="5"
                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
            </div>

            {{-- Occupancy --}}
            <div>
                <div class="flex justify-between mb-1">
                    <label class="text-xs font-medium text-gray-600">Ocupacion estimada</label>
                    <span class="text-xs text-gray-500" x-text="occupancy + '%'"></span>
                </div>
                <input type="range" x-model.number="occupancy" min="20" max="95" step="5"
                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
            </div>

            {{-- Investment horizon --}}
            <div>
                <div class="flex justify-between mb-1">
                    <label class="text-xs font-medium text-gray-600">Horizonte de inversion</label>
                    <span class="text-xs text-gray-500" x-text="years + ' anos'"></span>
                </div>
                <input type="range" x-model.number="years" min="1" max="10" step="1"
                       class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-blue-600">
            </div>
        </div>

        {{-- Results --}}
        <div>
            {{-- KPI cards --}}
            <div class="grid grid-cols-2 gap-3 mb-5">
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <div class="text-xs text-gray-500 mb-1">Ingreso neto mensual</div>
                    <div class="text-xl font-bold text-green-600" x-text="'USD ' + formatN(monthlyNet())"></div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <div class="text-xs text-gray-500 mb-1">ROI anual</div>
                    <div class="text-xl font-bold text-blue-600" x-text="roiAnnual() + '%'"></div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <div class="text-xs text-gray-500 mb-1">Payback</div>
                    <div class="text-xl font-bold text-indigo-600" x-text="payback() + ' anos'"></div>
                </div>
                <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100">
                    <div class="text-xs text-gray-500 mb-1">Valor en <span x-text="years"></span> anos</div>
                    <div class="text-xl font-bold text-purple-600" x-text="'USD ' + formatN(futureValue())"></div>
                </div>
            </div>

            {{-- Breakdown --}}
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-100 text-sm space-y-2">
                <div class="flex justify-between">
                    <span class="text-gray-500">Ingreso bruto anual</span>
                    <span class="font-medium" x-text="'USD ' + formatN(grossAnnual())"></span>
                </div>
                <div class="flex justify-between text-red-500">
                    <span>Administracion ({{ $mgmtFee }}%)</span>
                    <span x-text="'-USD ' + formatN(mgmtCost())"></span>
                </div>
                <div class="flex justify-between text-red-500">
                    <span>Impuestos ({{ $taxRate }}%)</span>
                    <span x-text="'-USD ' + formatN(taxCost())"></span>
                </div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-semibold">
                    <span class="text-gray-700">Ingreso neto anual</span>
                    <span class="text-green-600" x-text="'USD ' + formatN(netAnnual())"></span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-gray-500">Ganancia total en <span x-text="years"></span> anos</span>
                    <span class="font-medium text-green-700" x-text="'USD ' + formatN(totalReturn())"></span>
                </div>
            </div>

            <p class="text-[10px] text-gray-400 mt-3 leading-tight">Proyeccion estimativa basada en datos de mercado de la zona. Rendimientos pasados no garantizan resultados futuros. Consulte con un asesor financiero antes de invertir.</p>
        </div>
    </div>
</section>

<script>
function investCalc(defaultPrice, defaultNightly, defaultOccupancy, appreciation, mgmtFee, taxRate) {
    return {
        price: defaultPrice,
        downPaymentPct: 30,
        nightlyRate: defaultNightly,
        occupancy: defaultOccupancy,
        years: 5,
        _appreciation: appreciation,
        _mgmtFee: mgmtFee,
        _taxRate: taxRate,

        formatN(n) { return Math.round(n).toLocaleString('en-US'); },

        grossAnnual() {
            return this.nightlyRate * 365 * (this.occupancy / 100);
        },
        mgmtCost() {
            return this.grossAnnual() * (this._mgmtFee / 100);
        },
        taxCost() {
            return this.price * (this._taxRate / 100);
        },
        netAnnual() {
            return this.grossAnnual() - this.mgmtCost() - this.taxCost();
        },
        monthlyNet() {
            return Math.round(this.netAnnual() / 12);
        },
        roiAnnual() {
            if (this.price <= 0) return '0.0';
            return (this.netAnnual() / this.price * 100).toFixed(1);
        },
        payback() {
            const net = this.netAnnual();
            if (net <= 0) return '∞';
            return (this.price / net).toFixed(1);
        },
        futureValue() {
            return Math.round(this.price * Math.pow(1 + this._appreciation / 100, this.years));
        },
        totalReturn() {
            return Math.round(this.netAnnual() * this.years + (this.futureValue() - this.price));
        },
    };
}
</script>
