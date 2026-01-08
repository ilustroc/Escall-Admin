import './bootstrap'; // Importación por defecto de Laravel

document.addEventListener('DOMContentLoaded', () => {
    
    // Elementos del DOM
    const btn = document.getElementById('mobile-menu-btn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');

    // Solo ejecutamos si los elementos existen
    if (btn && sidebar && overlay) {
        
        function toggleMenu() {
            const isClosed = sidebar.classList.contains('-translate-x-full');
            
            if (isClosed) {
                // Abrir menú
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('opacity-0', 'pointer-events-none');
            } else {
                // Cerrar menú
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('opacity-0', 'pointer-events-none');
            }
        }

        // Event Listeners
        btn.addEventListener('click', toggleMenu);
        overlay.addEventListener('click', toggleMenu);
    }
});