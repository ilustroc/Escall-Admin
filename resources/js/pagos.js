document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('btnRellenar');
    const inputCodigo = document.getElementById('iCodigo');
    const inputHidden = document.getElementById('iCodigoHidden');
    const msg = document.getElementById('fillMsg');

    // Mapeo: Campo JSON -> Selector ID
    const fieldMap = {
        dni: 'sDni',
        nombre: 'sNombre',
        cartera: 'sCartera',
        entidad: 'sEntidad',
        cosecha: 'sCosecha',
        departamento: 'sDpto',
        rango: 'sRango',
        capital: 'sCapital',
        producto: 'sProducto'
    };

    if (btn) {
        btn.addEventListener('click', async () => {
            // Limpiar estado previo
            msg.textContent = '';
            msg.className = 'text-xs mt-2';
            const codigo = (inputCodigo.value || '').trim();

            if (!codigo) {
                msg.textContent = '⚠️ Por favor, ingresa un código.';
                msg.classList.add('text-amber-600');
                inputCodigo.focus();
                return;
            }

            try {
                // Estado Loading
                btn.disabled = true;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';

                // Consulta AJAX
                // Nota: Asegúrate de que window.routes.lookup esté definido en el Blade o pasa la URL como data-attribute
                const url = btn.dataset.url + '?codigo=' + encodeURIComponent(codigo);
                const response = await fetch(url);
                const json = await response.json();

                if (!json.ok) {
                    throw new Error(json.msg || 'No se encontraron datos.');
                }

                // Rellenar campos
                Object.entries(fieldMap).forEach(([key, id]) => {
                    const el = document.getElementById(id);
                    if (el) {
                        el.value = (json.data && json.data[key]) !== null ? json.data[key] : '';
                        // Efecto visual de "campo actualizado"
                        el.classList.add('bg-green-50', 'transition-colors', 'duration-500');
                        setTimeout(() => el.classList.remove('bg-green-50'), 1000);
                    }
                });

                // Guardar código validado
                if (inputHidden) inputHidden.value = codigo;

                msg.innerHTML = '<span class="flex items-center gap-1">✓ Datos cargados correctamente.</span>';
                msg.classList.add('text-emerald-600', 'font-medium');

            } catch (error) {
                console.error(error);
                msg.textContent = `✕ ${error.message}`;
                msg.classList.add('text-red-600');
            } finally {
                // Restaurar botón
                btn.disabled = false;
                btn.innerHTML = 'Buscar y Rellenar';
            }
        });
    }
});