/**
 * Sistema de alertas auto-ocultables
 * Maneja la funcionalidad de ocultar automáticamente mensajes después de 3 segundos(modificable)
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-ocultar mensajes después de 3 segundos
    const alerts = document.querySelectorAll('.auto-hide-alert');
    
    alerts.forEach(function(alert) {
        // Añadir ícono de cierre manual
        const closeButton = document.createElement('button');
        closeButton.innerHTML = '&times;';
        closeButton.className = 'alert-close-btn';
        closeButton.style.cssText = `
            background: none;
            border: none;
            font-size: 1.2rem;
            font-weight: bold;
            color: inherit;
            cursor: pointer;
            float: right;
            line-height: 1;
            opacity: 0.7;
            margin-left: 10px;
        `;
        
        closeButton.addEventListener('click', function() {
            hideAlert(alert);
        });
        
        alert.appendChild(closeButton);
        
        // Auto-ocultar después de 3 segundos
        setTimeout(function() {
            hideAlert(alert);
        }, 3000);
    });
});

function hideAlert(alert) {
    if (!alert || !alert.parentNode) return;
    
    // Añadir clase para animación de desvanecimiento
    alert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
    alert.style.opacity = '0';
    alert.style.transform = 'translateY(-10px)';
    
    // Remover el elemento después de la animación
    setTimeout(function() {
        if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
        }
    }, 500);
}