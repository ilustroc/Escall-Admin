<div class="overflow-x-auto">
    <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200">
            <tr>
                <th class="px-4 py-3">Documento</th>
                <th class="px-4 py-3">Cosecha</th>
                <th class="px-4 py-3">Fecha Pago</th>
                <th class="px-4 py-3">Monto</th>
                <th class="px-4 py-3">Asesor</th>
                <th class="px-4 py-3">Operación</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($data as $item)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-bold text-slate-700">{{ $item->dni }}</td>
                    <td class="px-4 py-3"><span class="bg-slate-100 px-2 py-0.5 rounded">{{ $item->cosecha }}</span></td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->fecha)->format('d/m/Y') }}</td>
                    <td class="px-4 py-3 font-mono font-bold text-emerald-600">S/ {{ $item->monto }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $item->asesor }}</td>
                    <td class="px-4 py-3 text-slate-500">{{ $item->operacion }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            {{-- Botón Editar --}}
                            <button onclick="openModal(@js($item), 'pago')" 
                                    class="p-1 rounded text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition-colors" 
                                    title="Editar">
                                <x-heroicon-o-pencil class="h-4 w-4" />
                            </button>

                            </button>
                            {{-- Botón Eliminar --}}
                            <form action="{{ route('listas.destroy') }}" method="POST" onsubmit="return confirm('¿Eliminar pago permanentemente?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="id" value="{{ $item->id }}">
                                <input type="hidden" name="type" value="pago">
                                <button type="submit" 
                                        class="p-1 rounded text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors" 
                                        title="Eliminar">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-400 italic">No hay pagos registrados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>