/**
 * JavaScript para módulos del sistema
 * Indice SaaS - Sistema modular
 */

document.addEventListener('DOMContentLoaded', function() {
    // Agregar efectos hover a las tarjetas de módulos
    const moduleCards = document.querySelectorAll('.module-card');
    
    moduleCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            if (!this.classList.contains('module-disabled')) {
                this.style.transform = 'translateY(-5px)';
            }
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Tooltip para módulos deshabilitados
    const disabledCards = document.querySelectorAll('.module-disabled');
    disabledCards.forEach(card => {
        card.setAttribute('title', 'Este módulo estará disponible próximamente');
    });
});

// Función para mostrar información del módulo
function showModuleInfo(moduleId) {
    // Por implementar - modal con información detallada del módulo
    console.log('Mostrar información del módulo:', moduleId);
}
