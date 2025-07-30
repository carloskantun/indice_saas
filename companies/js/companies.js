/**
 * JavaScript para gestión de empresas
 * Indice SaaS - Sistema modular
 */

document.addEventListener('DOMContentLoaded', function() {
    const createForm = document.getElementById('createCompanyForm');
    
    if (createForm) {
        createForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createCompany();
        });
    }
});

// Crear nueva empresa
async function createCompany() {
    const form = document.getElementById('createCompanyForm');
    const formData = new FormData(form);
    const data = {
        name: formData.get('name'),
        description: formData.get('description')
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
            bootstrap.Modal.getInstance(document.getElementById('createCompanyModal')).hide();
            // Recargar página después de 1 segundo
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.error || 'Error al crear la empresa');
        }
    } catch (error) {
        showAlert('danger', 'Error de conexión');
        console.error('Error:', error);
    } finally {
        showLoading(false);
    }
}

// Editar empresa
async function editCompany(companyId) {
    // Por implementar - mostrar modal de edición
    console.log('Editar empresa:', companyId);
}

// Eliminar empresa
async function deleteCompany(companyId) {
    if (!confirm('¿Estás seguro de que quieres eliminar esta empresa? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        showLoading(true);
        
        const response = await fetch(`controller.php?id=${companyId}`, {
            method: 'DELETE'
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', result.message);
            // Recargar página después de 1 segundo
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert('danger', result.error || 'Error al eliminar la empresa');
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
            if (button.closest('#createCompanyModal')) {
                button.innerHTML = 'Crear';
            }
        }
    });
}
