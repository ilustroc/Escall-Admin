<div x-data="{ open: false, item: {}, type: '' }" 
     @open-edit-modal.window="open = true; item = $event.detail.item; type = $event.detail.type"
     x-show="open" x-cloak class="relative z-50">
    
    <div class="fixed inset-0 bg-black/40 backdrop-blur-sm transition-opacity" @click="open = false"></div>
    
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl transform rounded-xl bg-white p-6 shadow-2xl transition-all max-h-[90vh] overflow-y-auto custom-scrollbar">
            
            <div class="flex justify-between items-center mb-5 border-b border-slate-100 pb-3">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <span x-text="type == 'pago' ? '💰 Editar Pago' : (type == 'gestion' ? '📞 Editar Gestión' : '📂 Editar Cartera')"></span>
                </h3>
                <button @click="open = false" class="text-slate-400 hover:text-red-500 transition">
                    <x-heroicon-o-x-mark class="h-6 w-6"/>
                </button>
            </div>
            
            <form method="POST" action="{{ route('listas.update') }}">
                @csrf @method('PUT')
                <input type="hidden" name="id" :value="type === 'data' ? item.codigo : item.id">
                <input type="hidden" name="dni_original" :value="item.dni">
                <input type="hidden" name="type" :value="type">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    
                    {{-- DNI (Siempre visible) --}}
                    <div class="md:col-span-2">
                        <label class="block font-bold text-slate-700 mb-1">Documento (DNI)</label>
                        <input type="text" class="w-full rounded-lg border-slate-200 bg-slate-100 text-slate-500 font-bold px-3 py-2" :value="item.dni" readonly>
                    </div>

                    {{-- PAGOS --}}
                    <template x-if="type == 'pago'">
                        <div class="contents">
                            <div><label class="block font-bold mb-1">Fecha</label><input name="fecha" type="date" class="w-full rounded border-slate-300" :value="item.fecha ? item.fecha.split(' ')[0] : ''"></div>
                            <div><label class="block font-bold mb-1">Monto (S/)</label><input name="monto" type="number" step="0.01" class="w-full rounded border-slate-300 font-bold text-emerald-600" :value="item.monto"></div>
                            <div><label class="block font-bold mb-1">Cosecha</label><select name="cosecha" class="w-full rounded border-slate-300"><option :value="item.cosecha" x-text="item.cosecha"></option>@foreach($listas['cosechas'] ?? [] as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach</select></div>
                            <div><label class="block font-bold mb-1">Asesor</label><select name="asesor" class="w-full rounded border-slate-300"><option :value="item.asesor" x-text="item.asesor"></option>@foreach($listas['asesores'] ?? [] as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach</select></div>
                            <div class="md:col-span-2"><label class="block font-bold mb-1">Operación</label><input name="operacion" type="text" class="w-full rounded border-slate-300" :value="item.operacion"></div>
                        </div>
                    </template>

                    {{-- GESTIONES --}}
                    <template x-if="type == 'gestion'">
                        <div class="contents">
                            <div><label class="block font-bold mb-1">Fecha/Hora</label><input name="fecha_gestion" type="datetime-local" class="w-full rounded border-slate-300" :value="item.fecha_gestion ? item.fecha_gestion.replace(' ', 'T') : ''"></div>
                            <div><label class="block font-bold mb-1">Resultado</label><select name="resultado" class="w-full rounded border-slate-300 font-bold text-blue-600"><option :value="item.tipificacion" x-text="item.tipificacion"></option>@foreach($listas['resultados'] ?? [] as $r) <option value="{{ $r }}">{{ $r }}</option> @endforeach</select></div>
                            <div class="md:col-span-2"><label class="block font-bold mb-1">Asesor Responsable</label><select name="asesor" class="w-full rounded border-slate-300"><option :value="item.nombre" x-text="item.nombre"></option>@foreach($listas['asesores'] ?? [] as $a) <option value="{{ $a }}">{{ $a }}</option> @endforeach</select></div>
                            <div class="md:col-span-2"><label class="block font-bold mb-1">Observación</label><textarea name="observacion" rows="4" class="w-full rounded border-slate-300 text-slate-700" x-text="item.observacion"></textarea></div>
                        </div>
                    </template>

                    {{-- DATA --}}
                    <template x-if="type == 'data'">
                        <div class="contents">
                            <div class="md:col-span-2"><label class="block font-bold mb-1">Nombre</label><input name="titular" class="w-full rounded border-slate-300" :value="item.titular"></div>
                            <div><label class="block font-bold mb-1">Cartera</label><select name="cartera" class="w-full rounded border-slate-300"><option :value="item.cartera" x-text="item.cartera"></option>@foreach($listas['carteras'] ?? [] as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach</select></div>
                            <div><label class="block font-bold mb-1">Cosecha</label><select name="cosecha" class="w-full rounded border-slate-300"><option :value="item.cosecha" x-text="item.cosecha"></option>@foreach($listas['cosechas'] ?? [] as $c) <option value="{{ $c }}">{{ $c }}</option> @endforeach</select></div>
                            <div><label class="block font-bold mb-1">Deuda</label><input name="deuda_capital" type="number" step="0.01" class="w-full rounded border-slate-300" :value="item.deuda_capital"></div>
                        </div>
                    </template>
                </div>

                <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="open = false" class="px-4 py-2 rounded-lg bg-white border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-slate-800 text-white text-xs font-bold hover:bg-slate-700 shadow-md transition flex items-center gap-2"><x-heroicon-o-check class="w-4 h-4"/> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>