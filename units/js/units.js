/**
 * JavaScript para gestión de unidades
 * Indice SaaS - Sistema modular
 */

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createUnitForm');
    
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createUnit();
        });
    }
});

// Crear nueva unidad
async function createUnit() {
    const form = document.getElementById('createUnitForm');
    const formData = new FormData(form);
    const data = {
        name: formData.get('name'),
        description: formData.get('description'),
        company_id: formData.get('company_id')
    };
    
    try {
        showLoading(true);
        
        const response = await fetch('controller.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('createUnitModal')).hide();
            // Recargar página después de 1 segundo
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.error || 'Error al crear la unidad');
        }
    } catch (error) {
        showAlert('danger', 'Error de conexión');
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
}

// Editar unidad
async function editUnit(unitId) {
    // Por implementar - mostrar modal de edición
    console.log('Editar unidad:', unitId);
}

// Eliminar unidad
async function deleteUnit(unitId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta unidad? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch(`controller.php?id=${unitId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            // Recargar página después de 1 segundo
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.error || 'Error al eliminar la unidad');
        }
    } catch (error) {
        showAlert('danger', 'Error de conexión');
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
}

// Mostrar alertas
function showAlert(type, message) {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertContainer.style.top = '20px';
    alertContainer.style.right = '20px';
    alertContainer.style.zIndex = '9999';
    alertContainer.style.minWidth = '300px';
    
    alertContainer.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertContainer);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        if (alertContainer.parentNode) {
            alertContainer.remove();
        }
    }, 5000);
}

// Mostrar/ocultar loading
function showLoading(show) {
    const buttons = document.querySelectorAll('button[type="submit"]');
    buttons.forEach(button => {
        if (show) {
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        } else {
            button.disabled = false;
            // Restaurar texto original basado en el contexto
            if (button.closest('#createUnitModal')) {
                button.innerHTML = 'Crear';
            }
        }
    });
}
