<!-- Modal Cambiar Plan -->
<div class="modal fade" id="changePlanModal" tabindex="-1" aria-labelledby="changePlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePlanModalLabel">
                    <i class="fas fa-exchange-alt"></i>
                    <?= $lang['change_plan'] ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changePlanForm">
                <input type="hidden" id="change_plan_company_id" name="company_id">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Empresa:</strong> <span id="change_plan_company_name"></span>
                    </div>

                    <div class="mb-3">
                        <label for="new_plan_id" class="form-label">Seleccionar Nuevo Plan *</label>
                        <select class="form-select" id="new_plan_id" name="plan_id" required>
                            <option value="">-- Seleccionar Plan --</option>
                            <?php foreach ($plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" 
                                        data-price="<?= $plan['price_monthly'] ?>" 
                                        data-name="<?= htmlspecialchars($plan['name']) ?>">
                                    <?= htmlspecialchars($plan['name']) ?> - $<?= number_format($plan['price_monthly'], 2) ?>/mes
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Comparación de planes -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card border-secondary">
                                <div class="card-header bg-secondary text-white">
                                    <h6 class="mb-0">Plan Actual</h6>
                                </div>
                                <div class="card-body" id="current_plan_details">
                                    <!-- Se llena dinámicamente -->
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-primary">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">Nuevo Plan</h6>
                                </div>
                                <div class="card-body" id="new_plan_details">
                                    <p class="text-muted">Selecciona un plan para ver los detalles</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alertas de validación -->
                    <div id="plan_validation_alerts" class="mt-3">
                        <!-- Se llenan dinámicamente -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <?= $lang['cancel'] ?>
                    </button>
                    <button type="submit" class="btn btn-success" id="confirm_plan_change">
                        <i class="fas fa-check"></i>
                        Confirmar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Manejar cambio de plan seleccionado
document.addEventListener('DOMContentLoaded', function() {
    const planSelect = document.getElementById('new_plan_id');
    if (planSelect) {
        planSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const newPlanDetails = document.getElementById('new_plan_details');
            
            if (this.value) {
                // Obtener detalles del plan seleccionado
                fetch(`controller.php?action=get_plan&id=${this.value}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const plan = data.plan;
                            newPlanDetails.innerHTML = `
                                <h6>${plan.name}</h6>
                                <p class="mb-1"><strong>Precio:</strong> $${parseFloat(plan.price_monthly).toFixed(2)}/mes</p>
                                <p class="mb-1"><strong>Usuarios:</strong> ${plan.users_max == -1 ? 'Ilimitado' : plan.users_max}</p>
                                <p class="mb-1"><strong>Unidades:</strong> ${plan.units_max == -1 ? 'Ilimitado' : plan.units_max}</p>
                                <p class="mb-1"><strong>Negocios:</strong> ${plan.businesses_max == -1 ? 'Ilimitado' : plan.businesses_max}</p>
                                <p class="mb-0"><strong>Storage:</strong> ${plan.storage_max_mb == -1 ? 'Ilimitado' : plan.storage_max_mb + ' MB'}</p>
                            `;
                            
                            // Validar límites vs uso actual
                            validatePlanLimits(plan);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        newPlanDetails.innerHTML = '<p class="text-danger">Error al cargar detalles del plan</p>';
                    });
            } else {
                newPlanDetails.innerHTML = '<p class="text-muted">Selecciona un plan para ver los detalles</p>';
                document.getElementById('plan_validation_alerts').innerHTML = '';
            }
        });
    }
});

function validatePlanLimits(newPlan) {
    const companyId = document.getElementById('change_plan_company_id').value;
    
    // Obtener uso actual de la empresa
    fetch(`controller.php?action=get_company_usage&id=${companyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const usage = data.usage;
                const alertsDiv = document.getElementById('plan_validation_alerts');
                let alerts = [];
                
                // Validar cada límite
                if (newPlan.users_max != -1 && usage.users_count > newPlan.users_max) {
                    alerts.push(`⚠️ La empresa tiene ${usage.users_count} usuarios, pero el nuevo plan solo permite ${newPlan.users_max}`);
                }
                
                if (newPlan.units_max != -1 && usage.units_count > newPlan.units_max) {
                    alerts.push(`⚠️ La empresa tiene ${usage.units_count} unidades, pero el nuevo plan solo permite ${newPlan.units_max}`);
                }
                
                if (newPlan.businesses_max != -1 && usage.businesses_count > newPlan.businesses_max) {
                    alerts.push(`⚠️ La empresa tiene ${usage.businesses_count} negocios, pero el nuevo plan solo permite ${newPlan.businesses_max}`);
                }
                
                if (alerts.length > 0) {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <h6><i class="fas fa-exclamation-triangle"></i> Advertencias:</h6>
                            ${alerts.map(alert => `<p class="mb-1">${alert}</p>`).join('')}
                            <hr>
                            <small>El cambio de plan puede requerir que la empresa reduzca su uso actual.</small>
                        </div>
                    `;
                } else {
                    alertsDiv.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            El nuevo plan es compatible con el uso actual de la empresa.
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error validating limits:', error);
        });
}
</script>
