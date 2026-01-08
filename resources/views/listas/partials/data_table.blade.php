<div class="overflow-x-auto">
    <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-500 font-bold uppercase border-b border-slate-200">
            <tr>
                <th class="px-4 py-3">Documento</th>
                <th class="px-4 py-3">Nombre</th>
                <th class="px-4 py-3">Cartera</th>
                <th class="px-4 py-3">Cosecha</th>
                <th class="px-4 py-3">Deuda</th>
                <th class="px-4 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($data as $item)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-4 py-3 font-bold text-slate-700">{{ $item->dni }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $item->titular }}</td>
                    <td class="px-4 py-3 font-bold">{{ $item->cartera }}</td>
                    <td class="px-4 py-3"><span class="bg-purple-50 text-purple-700 px-2 py-0.5 rounded">{{ $item->cosecha }}</span></td>
                    <td class="px-4 py-3 font-mono">S/ {{ $item->deuda_capital }}</td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex justify-end gap-2">
                            {{-- Botón Editar --}}
                            <button onclick="openModal(@js($item), 'data')" 
                                    class="p-1 rounded text-blue-600 hover:bg-blue-50 hover:text-blue-800 transition-colors" 
                                    title="Editar">
                                <x-heroicon-o-pencil class="h-4 w-4" />
                            </button>
                            
                            {{-- Botón Eliminar --}}
                            <form action="{{ route('listas.destroy') }}" method="POST" onsubmit="return confirm('¿Eliminar de cartera?')">
                                @csrf @method('DELETE')
                                <input type="hidden" name="id" value="{{ $item->id ?? $item->dni }}">
                                <input type="hidden" name="dni_ref" value="{{ $item->dni }}">
                                <input type="hidden" name="type" value="data">
                                <button type="submit" class="p-1 rounded text-red-500 hover:bg-red-50 hover:text-red-700 transition-colors" title="Eliminar">
                                    <x-heroicon-o-trash class="h-4 w-4" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-8 text-center text-slate-400 italic">No hay registros en cartera.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>