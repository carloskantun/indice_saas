<!-- MODALES PARA EL MÓDULO DE GASTOS -->

<?php
// Debug: Verificar variables de sesión
error_log("MODALS DEBUG - Company ID: " . ($_SESSION['company_id'] ?? 'NULL'));
error_log("MODALS DEBUG - Business ID: " . ($_SESSION['business_id'] ?? 'NULL'));

// Obtener proveedores para los modales
$db = getDB();

// Usar las variables explícitas si están disponibles, sino usar sesión
$company_id = $company_id_for_modals ?? $_SESSION['company_id'] ?? null;

// Debug adicional
error_log("MODALS DEBUG - Using company_id: " . ($company_id ?? 'NULL'));
error_log("MODALS DEBUG - company_id_for_modals: " . ($company_id_for_modals ?? 'NULL'));
error_log("MODALS DEBUG - SESSION company_id: " . ($_SESSION['company_id'] ?? 'NULL'));

if (!$company_id) {
    error_log("MODALS ERROR - No company_id found in session or variables");
    $modal_providers = [];
} else {
    $providers_sql = "SELECT id, name FROM providers WHERE company_id = ? AND status = 'active' ORDER BY name";
    $stmt = $db->prepare($providers_sql);
    $stmt->execute([$company_id]);
    $modal_providers = $stmt->fetchAll();
    
    error_log("MODALS DEBUG - Found " . count($modal_providers) . " providers for company " . $company_id);
    
    // Debug adicional: mostrar proveedores encontrados
    foreach ($modal_providers as $p) {
        error_log("MODALS DEBUG - Provider: ID={$p['id']}, Name={$p['name']}");
    }
}
?>

<!-- Modal Nuevo/Editar Gasto -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="expenseModalLabel">Nuevo Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="expenseForm">
                <div class="modal-body">
                    <input type="hidden" id="expense_id_hidden" name="expense_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="provider_id" class="form-label">Proveedor</label>
                                <select class="form-select select2" id="provider_id" name="provider_id">
                                    <option value="">Sin proveedor</option>
                                    <?php if (empty($modal_providers)): ?>
                                    <option value="" disabled style="color: red;">❌ No hay proveedores (Company ID: <?php echo $company_id; ?>)</option>
                                    <?php else: ?>
                                    <?php foreach ($modal_providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>">
                                        <?php echo htmlspecialchars($provider['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    <?php if (empty($modal_providers)): ?>
                                    <span style="color: red;">⚠️ No se encontraron proveedores activos</span>
                                    <?php else: ?>
                                    <span style="color: green;">✅ <?php echo count($modal_providers); ?> proveedores disponibles</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expense_type" class="form-label">Tipo de Gasto</label>
                                <select class="form-select" id="expense_type" name="expense_type">
                                    <option value="Unico">Único</option>
                                    <option value="Recurrente">Recurrente</option>
                                    <option value="Credito">Crédito</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="purchase_type" class="form-label">Tipo de Compra</label>
                                <select class="form-select" id="purchase_type" name="purchase_type">
                                    <option value="">Seleccionar...</option>
                                    <option value="Contado">Contado</option>
                                    <option value="Credito">Crédito</option>
                                    <option value="Anticipado">Anticipado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Método de Pago</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="bank_account" class="form-label">Cuenta Bancaria</label>
                                <input type="text" class="form-control" id="bank_account" name="bank_account" placeholder="Número de cuenta o referencia">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="origin" class="form-label">Origen</label>
                                <select class="form-select" id="origin" name="origin">
                                    <option value="Directo">Directo</option>
                                    <option value="Orden">Orden de Compra</option>
                                    <option value="Requisicion">Requisición</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="concept" class="form-label">Concepto <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="concept" name="concept" rows="3" required placeholder="Descripción del gasto..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_folio" class="form-label">Folio de Orden</label>
                        <input type="text" class="form-control" id="order_folio" name="order_folio" placeholder="Folio de orden de compra (opcional)">
                    </div>
                    
                    <!-- CAMPO DE ARCHIVOS - FACTURAS/TICKETS -->
                    <div class="mb-3">
                        <label for="expense_files" class="form-label">Facturas/Tickets <span class="text-muted">(Opcional)</span></label>
                        <input type="file" class="form-control" id="expense_files" name="archivos[]" accept="image/jpeg,image/png,image/jpg,application/pdf" multiple>
                        <div class="form-text">
                            <i class="fas fa-upload me-1"></i>
                            Suba facturas, tickets o comprobantes del gasto
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Gasto -->
<div class="modal fade" id="editExpenseModal" tabindex="-1" aria-labelledby="editExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExpenseModalLabel">Editar Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editExpenseForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_expense_id" name="expense_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_provider_id" class="form-label">Proveedor</label>
                                <select class="form-select select2" id="edit_provider_id" name="provider_id">
                                    <option value="">Sin proveedor</option>
                                    <?php if (empty($modal_providers)): ?>
                                    <option value="" disabled style="color: red;">❌ No hay proveedores (Company ID: <?php echo $company_id; ?>)</option>
                                    <?php else: ?>
                                    <?php foreach ($modal_providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>">
                                        <?php echo htmlspecialchars($provider['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    <?php if (empty($modal_providers)): ?>
                                    <span style="color: red;">⚠️ No se encontraron proveedores activos</span>
                                    <?php else: ?>
                                    <span style="color: green;">✅ <?php echo count($modal_providers); ?> proveedores disponibles</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_amount" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="edit_amount" name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_payment_date" class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="edit_payment_date" name="payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_expense_type" class="form-label">Tipo de Gasto</label>
                                <select class="form-select" id="edit_expense_type" name="expense_type">
                                    <option value="Unico">Único</option>
                                    <option value="Recurrente">Recurrente</option>
                                    <option value="Credito">Crédito</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_purchase_type" class="form-label">Tipo de Compra</label>
                                <select class="form-select" id="edit_purchase_type" name="purchase_type">
                                    <option value="">Seleccionar...</option>
                                    <option value="Contado">Contado</option>
                                    <option value="Credito">Crédito</option>
                                    <option value="Anticipado">Anticipado</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_payment_method" class="form-label">Método de Pago</label>
                                <select class="form-select" id="edit_payment_method" name="payment_method">
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_bank_account" class="form-label">Cuenta Bancaria</label>
                                <input type="text" class="form-control" id="edit_bank_account" name="bank_account" placeholder="Número de cuenta o referencia">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_origin" class="form-label">Origen</label>
                                <select class="form-select" id="edit_origin" name="origin">
                                    <option value="Directo">Directo</option>
                                    <option value="Orden">Orden de Compra</option>
                                    <option value="Requisicion">Requisición</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_concept" class="form-label">Concepto <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="edit_concept" name="concept" rows="3" required placeholder="Descripción del gasto..."></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_order_folio" class="form-label">Folio de Orden</label>
                        <input type="text" class="form-control" id="edit_order_folio" name="order_folio" placeholder="Folio de orden de compra (opcional)">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-submit">Actualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentModalLabel">Registrar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <input type="hidden" id="payment_expense_id" name="expense_id">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Monto pendiente:</strong> <span id="pendingAmountSpan">$0.00</span>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Monto del Pago <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="payment_amount" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="form-text">Puede ser un pago parcial o total</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_date" class="form-label">Fecha del Pago <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="payment_date" name="payment_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_comment" class="form-label">Comentario</label>
                        <textarea class="form-control" id="payment_comment" name="comment" rows="3" placeholder="Observaciones del pago (opcional)"></textarea>
                    </div>
                    
                    <!-- CAMPO DE ARCHIVOS - COMPROBANTES -->
                    <div class="mb-3">
                        <label for="payment_files" class="form-label">Comprobantes <span class="text-muted">(Opcional)</span></label>
                        <input type="file" class="form-control" id="payment_files" name="comprobantes[]" accept="image/jpeg,image/png,image/jpg,application/pdf" multiple>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Puede subir múltiples archivos (imágenes JPG, PNG o documentos PDF). 
                            También puede arrastrar y soltar archivos aquí.
                        </div>
                        <div id="file-preview" class="mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-submit">Registrar Pago</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Gasto -->
<div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewExpenseModalLabel">Detalle del Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido generado dinámicamente por JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nuevo/Editar Proveedor -->
<div class="modal fade" id="providerModal" tabindex="-1" aria-labelledby="providerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="providerModalLabel">Nuevo Proveedor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="providerForm">
                <div class="modal-body">
                    <input type="hidden" id="provider_id_hidden" name="provider_id">
                    
                    <div class="mb-3">
                        <label for="provider_name" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="provider_name" name="name" required placeholder="Nombre del proveedor">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="provider_phone" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="provider_phone" name="phone" placeholder="Número de teléfono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="provider_email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="provider_email" name="email" placeholder="Correo electrónico">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="provider_address" class="form-label">Dirección</label>
                        <textarea class="form-control" id="provider_address" name="address" rows="2" placeholder="Dirección completa"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="provider_rfc" class="form-label">RFC</label>
                        <input type="text" class="form-control" id="provider_rfc" name="rfc" placeholder="RFC del proveedor" maxlength="13">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nueva Orden de Compra -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Nueva Orden de Compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="orderForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_provider_id" class="form-label">Proveedor</label>
                                <select class="form-select select2" id="order_provider_id" name="provider_id">
                                    <option value="">Seleccionar proveedor</option>
                                    <?php if (empty($modal_providers)): ?>
                                    <option value="" disabled style="color: red;">❌ No hay proveedores (Company ID: <?php echo $company_id; ?>)</option>
                                    <?php else: ?>
                                    <?php foreach ($modal_providers as $provider): ?>
                                    <option value="<?php echo $provider['id']; ?>">
                                        <?php echo htmlspecialchars($provider['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">
                                    <?php if (empty($modal_providers)): ?>
                                    <span style="color: red;">⚠️ No se encontraron proveedores activos</span>
                                    <?php else: ?>
                                    <span style="color: green;">✅ <?php echo count($modal_providers); ?> proveedores disponibles</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_amount" class="form-label">Monto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="order_amount" name="amount" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_payment_date" class="form-label">Fecha de Pago <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="order_payment_date" name="payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_expense_type" class="form-label">Tipo de Orden</label>
                                <select class="form-select" id="order_expense_type" name="expense_type">
                                    <option value="Unico">Orden (Única)</option>
                                    <option value="Recurrente">Orden (Recurrente)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Campos para órdenes recurrentes -->
                    <div id="campos_recurrente" style="display: none;">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_periodicidad" class="form-label">Periodicidad</label>
                                    <select class="form-select" id="order_periodicidad" name="periodicidad">
                                        <option value="">Seleccione</option>
                                        <option value="Mensual">Mensual</option>
                                        <option value="Quincenal">Quincenal</option>
                                        <option value="Semanal">Semanal</option>
                                        <option value="Diario">Diario</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_plazo" class="form-label">Plazo</label>
                                    <select class="form-select" id="order_plazo" name="plazo">
                                        <option value="">Seleccione</option>
                                        <option value="Trimestral">3 meses</option>
                                        <option value="Semestral">6 meses</option>
                                        <option value="Anual">12 meses</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_purchase_type" class="form-label">Tipo de Compra</label>
                                <select class="form-select" id="order_purchase_type" name="purchase_type">
                                    <option value="">Seleccionar...</option>
                                    <option value="Venta">Venta</option>
                                    <option value="Administrativa">Administrativa</option>
                                    <option value="Operativo">Operativo</option>
                                    <option value="Impuestos">Impuestos</option>
                                    <option value="Intereses/Créditos">Intereses/Créditos</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="order_payment_method" class="form-label">Método de Pago</label>
                                <select class="form-select" id="order_payment_method" name="payment_method">
                                    <option value="Transferencia">Transferencia</option>
                                    <option value="Efectivo">Efectivo</option>
                                    <option value="Tarjeta">Tarjeta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_bank_account" class="form-label">Cuenta Bancaria</label>
                        <input type="text" class="form-control" id="order_bank_account" name="bank_account" placeholder="Número de cuenta o referencia">
                    </div>
                    
                    <div class="mb-3">
                        <label for="order_concept" class="form-label">Concepto <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="order_concept" name="concept" rows="3" required placeholder="Descripción de la orden de compra..."></textarea>
                    </div>
                    
                    <!-- Campos para órdenes recurrentes -->
                    <div id="camposRecurrente" class="d-none">
                        <hr>
                        <h6>Configuración de Recurrencia</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_frequency" class="form-label">Frecuencia</label>
                                    <select class="form-select" id="order_frequency" name="frequency">
                                        <option value="monthly">Mensual</option>
                                        <option value="biweekly">Quincenal</option>
                                        <option value="weekly">Semanal</option>
                                        <option value="yearly">Anual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="order_end_date" class="form-label">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="order_end_date" name="end_date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-submit">Crear Orden</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal KPIs -->
<div class="modal fade" id="kpisModal" tabindex="-1" aria-labelledby="kpisModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="kpisModalLabel">
                    <i class="fas fa-chart-pie me-2"></i>
                    Indicadores y Estadísticas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Contenido generado dinámicamente por JavaScript -->
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando estadísticas...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
