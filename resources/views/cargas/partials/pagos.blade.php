{{-- resources/views/cargas/partials/pagos.blade.php --}}

<section class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
    
    {{-- ENCABEZADO --}}
    <div class="border-b border-slate-200 bg-slate-50/50 px-5 py-4 flex items-center gap-3">
        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
        </div>
        <div>
            <h2 class="text-sm font-bold text-slate-800">Cargar Pagos Individuales</h2>
            <p class="text-xs text-slate-500">Registra un pago y guarda una "foto" (snapshot) de la cuenta.</p>
        </div>
    </div>

    <div class="p-5 md:p-8">

        {{-- MENSAJES DE ÉXITO/ERROR --}}
        @if(session('ok'))
            <div class="mb-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <svg class="h-5 w-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                {{ session('ok') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <p class="font-bold mb-1">Por favor corrige los siguientes errores:</p>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        {{-- BUSCADOR DE CUENTA --}}
        <div class="mb-8 rounded-lg bg-slate-50 p-4 border border-slate-100">
            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">
                Paso 1: Buscar Cuenta
            </label>
            <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                <input id="iCodigo" 
                       type="text" 
                       placeholder="Ingresa el N° de Cuenta / Código"
                       class="block w-full sm:w-64 rounded-lg border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                
                <button id="btnRellenar" 
                        type="button"
                        data-url="{{ route('cargas.pagos.lookup') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all">
                    Buscar y Rellenar
                </button>
            </div>
            <div id="fillMsg" class="mt-2 text-xs h-5"></div>
        </div>

        <form method="POST" action="{{ route('cargas.pagos.store') }}">
            @csrf
            {{-- Input oculto para el código validado --}}
            <input id="iCodigoHidden" type="hidden" name="codigo">

            {{-- DETALLES DEL PAGO --}}
            <div class="mb-8">
                <h3 class="mb-4 text-sm font-bold text-slate-800 border-b border-slate-100 pb-2">
                    Paso 2: Detalles del Pago
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Fecha de Pago</label>
                        <input name="fecha" type="date" required 
                               class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Monto (S/.)</label>
                        <div class="relative rounded-md shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <span class="text-slate-500 sm:text-sm">S/</span>
                            </div>
                            <input name="monto" type="number" step="0.01" min="0" required placeholder="0.00"
                                   class="block w-full rounded-lg border-slate-300 pl-8 focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Tipo de Operación</label>
                        <select name="operacion" required 
                                class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                            <option value="CANCELACION">CANCELACION</option>
                            <option value="PAGO PARCIAL">PAGO PARCIAL</option>
                            <option value="CUOTA">CUOTA</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">Asesor Responsable</label>
                        <input name="asesor" type="text" placeholder="Opcional"
                               class="block w-full rounded-lg border-slate-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm">
                    </div>
                </div>
            </div>

            {{-- SNAPSHOT (READONLY) --}}
            <div class="mb-8 rounded-xl border border-dashed border-slate-300 bg-slate-50/50 p-5">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-700">Paso 3: Snapshot de Cliente</h3>
                    <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Solo Lectura</span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    {{-- Generar inputs readonly con un loop para código más limpio --}}
                    @foreach([
                        'dni' => 'DNI', 
                        'nombre' => 'Nombre Cliente', 
                        'cartera' => 'Cartera', 
                        'entidad' => 'Entidad', 
                        'cosecha' => 'Cosecha', 
                        'departamento' => 'Departamento', 
                        'rango' => 'Rango Deuda', 
                        'capital' => 'Capital', 
                        'producto' => 'Producto'
                    ] as $key => $label)
                        <div>
                            <label class="block mb-1 text-slate-500">{{ $label }}</label>
                            <input id="s{{ ucfirst($key == 'departamento' ? 'Dpto' : $key) }}" 
                                   name="{{ $key }}" 
                                   readonly 
                                   class="block w-full rounded-md border-slate-200 bg-slate-100 text-slate-600 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-xs cursor-not-allowed">
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- BOTÓN SUBMIT --}}
            <div class="flex justify-end border-slate-100">
                <button type="submit" 
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-transform active:scale-95">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                    </svg>
                    Guardar Pago
                </button>
            </div>

        </form>
    </div>
</section>

{{-- Inyectar Scripts --}}
@vite(['resources/js/pagos.js'])