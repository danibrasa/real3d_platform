<x-app-layout>
    <x-slot name="title">Planes de pago: {{ $project->name }}</x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Planes de pago: {{ $project->name }}</h2>
            <a href="{{ route('admin.projects.edit', $project) }}" class="text-sm text-gray-600 hover:underline">&larr; Volver al proyecto</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8" x-data="paymentPlans()">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-lg">{{ session('success') }}</div>
            @endif

            <!-- Add new plan -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-semibold mb-4">Crear nuevo plan</h3>
                <form method="POST" action="{{ route('admin.projects.payment-plans.store', $project) }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del plan</label>
                        <input type="text" name="name" required placeholder="Ej: Plan 30/70, Plan financiado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="is_default" value="1" class="rounded border-gray-300 text-blue-600">
                        Por defecto
                    </label>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">Crear plan</button>
                </form>
            </div>

            <!-- Existing plans -->
            @forelse($plans as $plan)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6" x-data="planEditor({{ $plan->toJson() }})">
                <form method="POST" action="{{ route('admin.projects.payment-plans.update', [$project, $plan]) }}">
                    @csrf @method('PUT')

                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <input type="text" name="name" :value="plan.name" required class="text-lg font-semibold rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="is_default" value="1" :checked="plan.is_default" class="rounded border-gray-300 text-blue-600">
                                Por defecto
                            </label>
                            @if($plan->is_default)
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">Activo</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium" :class="totalPct() === 100 ? 'text-green-600' : 'text-red-600'" x-text="totalPct().toFixed(0) + '%'"></span>
                            <span class="text-xs text-gray-400">del total</span>
                        </div>
                    </div>

                    <!-- Milestones -->
                    <div class="space-y-3 mb-4">
                        <template x-for="(ms, idx) in milestones" :key="idx">
                            <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 w-8 h-8 rounded-full text-white flex items-center justify-center text-sm font-bold"
                                     :style="'background-color: ' + typeColor(ms.milestone_type, idx)"
                                     x-text="idx + 1"></div>
                                <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-3">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Nombre *</label>
                                        <input type="text" :name="'milestones['+idx+'][name]'" x-model="ms.name" required placeholder="Ej: Reserva, Firma" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                                        <select :name="'milestones['+idx+'][milestone_type]'" x-model="ms.milestone_type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                            <option value="reservation">Reserva</option>
                                            <option value="signing">Firma</option>
                                            <option value="construction">Construccion</option>
                                            <option value="delivery">Entrega</option>
                                            <option value="other">Otro</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Porcentaje *</label>
                                        <div class="relative">
                                            <input type="number" :name="'milestones['+idx+'][percentage]'" x-model.number="ms.percentage" required min="0" max="100" step="0.5" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm pr-8">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">Descripcion</label>
                                        <input type="text" :name="'milestones['+idx+'][description]'" x-model="ms.description" placeholder="Detalle opcional" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    </div>
                                    <div class="flex items-end gap-2">
                                        <div class="flex-1">
                                            <label class="block text-xs text-gray-500 mb-1">Cuando</label>
                                            <input type="text" :name="'milestones['+idx+'][due_description]'" x-model="ms.due_description" placeholder="Ej: A la firma" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                        </div>
                                        <button type="button" @click="removeMilestone(idx)" class="mb-0.5 p-2 text-red-400 hover:text-red-600 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Progress bar preview -->
                    <div class="mb-4">
                        <div class="text-xs text-gray-500 mb-2">Vista previa:</div>
                        <div class="flex h-6 rounded-full overflow-hidden bg-gray-200">
                            <template x-for="(ms, idx) in milestones" :key="idx">
                                <div class="flex items-center justify-center text-[10px] font-bold text-white transition-all"
                                     :style="'width: ' + ms.percentage + '%; background-color: ' + typeColor(ms.milestone_type, idx)"
                                     x-text="ms.percentage > 5 ? ms.percentage + '%' : ''">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="button" @click="addMilestone()" class="text-sm text-blue-600 hover:underline">+ Agregar hito</button>
                        <div class="flex items-center gap-3">
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold hover:bg-green-700 transition">Guardar plan</button>
                        </div>
                    </div>
                </form>

                <!-- Delete button (separate form) -->
                <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                    <form method="POST" action="{{ route('admin.projects.payment-plans.destroy', [$project, $plan]) }}" onsubmit="return confirm('Eliminar este plan de pago?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Eliminar plan</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500">
                <p>No hay planes de pago configurados para este proyecto.</p>
                <p class="text-sm mt-2">Crea un plan arriba para empezar a configurar los hitos de pago.</p>
            </div>
            @endforelse
        </div>
    </div>

    <script>
        function paymentPlans() {
            return {};
        }

        function planEditor(planData) {
            return {
                plan: planData,
                milestones: planData.milestones && planData.milestones.length
                    ? planData.milestones.map(m => ({
                        name: m.name,
                        percentage: parseFloat(m.percentage),
                        description: m.description || '',
                        due_description: m.due_description || '',
                        milestone_type: m.milestone_type || 'other',
                    }))
                    : [{ name: '', percentage: 0, description: '', due_description: '', milestone_type: 'other' }],

                typeColorMap: {
                    reservation: '#2563eb',
                    signing: '#3b82f6',
                    construction: '#d97706',
                    delivery: '#059669',
                    other: '#6b7280',
                },

                typeColor(type, idx) {
                    if (type && type !== 'other') {
                        return this.typeColorMap[type] || '#6b7280';
                    }
                    // Heuristic fallback
                    const total = this.milestones.length;
                    if (total <= 1) return '#6b7280';
                    if (idx === 0) return '#2563eb';
                    if (idx === total - 1) return '#059669';
                    return '#d97706';
                },

                addMilestone() {
                    this.milestones.push({ name: '', percentage: 0, description: '', due_description: '', milestone_type: 'other' });
                },

                removeMilestone(idx) {
                    if (this.milestones.length > 1) {
                        this.milestones.splice(idx, 1);
                    }
                },

                totalPct() {
                    return this.milestones.reduce((sum, ms) => sum + (parseFloat(ms.percentage) || 0), 0);
                },
            };
        }
    </script>
</x-app-layout>
