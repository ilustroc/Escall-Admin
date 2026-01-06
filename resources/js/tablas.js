// resources/js/tablas.js

document.addEventListener('DOMContentLoaded', function () {
    const inputFiltro = document.getElementById('filtroAgente');
    
    if (inputFiltro) {
        const tableBody = document.querySelector('#tabla tbody');
        
        inputFiltro.addEventListener('input', function (e) {
            const query = e.target.value.toLowerCase().trim();
            const rows = tableBody.querySelectorAll('tr');

            rows.forEach(row => {
                // Buscamos en la primera celda (Sticky left) que contiene el nombre
                const agentNameCell = row.querySelector('td.sticky-left');
                
                if (agentNameCell) {
                    const agentName = agentNameCell.textContent.toLowerCase();
                    // Mostrar u ocultar según coincidencia
                    row.style.display = agentName.includes(query) ? '' : 'none';
                }
            });
        });
    }
});