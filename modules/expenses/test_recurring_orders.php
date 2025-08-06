<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Órdenes Recurrentes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>🧪 Prueba de Órdenes Recurrentes</h2>
        
        <div class="alert alert-info">
            <strong>Funcionalidad:</strong> Esta prueba simula la creación de órdenes recurrentes<br>
            <strong>Ejemplo:</strong> $2000 cada quincena por 3 meses = 6 órdenes de $2000
        </div>
        
        <form id="testOrderForm">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Monto</label>
                    <input type="number" class="form-control" name="amount" value="2000" step="0.01" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Fecha de Inicio</label>
                    <input type="date" class="form-control" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-md-6">
                    <label class="form-label">Tipo de Orden</label>
                    <select class="form-select" name="expense_type" id="expense_type">
                        <option value="Unico">Orden (Única)</option>
                        <option value="Recurrente" selected>Orden (Recurrente)</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Proveedor</label>
                    <select class="form-select" name="provider_id">
                        <option value="">Sin proveedor</option>
                        <option value="1">Proveedor de Prueba</option>
                    </select>
                </div>
            </div>
            
            <div id="campos_recurrente" class="mt-3">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Periodicidad</label>
                        <select class="form-select" name="periodicidad">
                            <option value="Mensual">Mensual</option>
                            <option value="Quincenal" selected>Quincenal</option>
                            <option value="Semanal">Semanal</option>
                            <option value="Diario">Diario</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Plazo</label>
                        <select class="form-select" name="plazo">
                            <option value="Trimestral" selected>3 meses</option>
                            <option value="Semestral">6 meses</option>
                            <option value="Anual">12 meses</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label">Concepto</label>
                <textarea class="form-control" name="concept" required>Orden de compra recurrente - Prueba</textarea>
            </div>
            
            <div class="mt-4">
                <button type="button" class="btn btn-primary" onclick="calculateOrders()">
                    📊 Calcular Órdenes
                </button>
                <button type="submit" class="btn btn-success">
                    💾 Crear Órdenes
                </button>
            </div>
        </form>
        
        <div id="calculation-result" class="mt-4"></div>
        <div id="creation-result" class="mt-4"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        // Mostrar/ocultar campos recurrentes
        document.getElementById('expense_type').addEventListener('change', function() {
            const camposRecurrente = document.getElementById('campos_recurrente');
            if (this.value === 'Recurrente') {
                camposRecurrente.style.display = 'block';
            } else {
                camposRecurrente.style.display = 'none';
            }
        });
        
        // Calcular cuántas órdenes se crearían
        function calculateOrders() {
            const periodicidad = document.querySelector('[name="periodicidad"]').value;
            const plazo = document.querySelector('[name="plazo"]').value;
            const amount = parseFloat(document.querySelector('[name="amount"]').value);
            const startDate = new Date(document.querySelector('[name="payment_date"]').value);
            
            const plazoMonths = {
                'Trimestral': 3,
                'Semestral': 6,
                'Anual': 12
            };
            
            let iterations;
            const totalMonths = plazoMonths[plazo];
            
            if (periodicidad === 'Mensual') {
                iterations = totalMonths;
            } else if (periodicidad === 'Quincenal') {
                iterations = totalMonths * 2;
            } else if (periodicidad === 'Semanal') {
                iterations = totalMonths * 4;
            } else if (periodicidad === 'Diario') {
                iterations = totalMonths * 30;
            }
            
            const totalAmount = amount * iterations;
            
            // Calcular fechas
            const dates = [];
            let currentDate = new Date(startDate);
            
            for (let i = 0; i < Math.min(iterations, 10); i++) { // Mostrar solo primeras 10
                dates.push(new Date(currentDate));
                
                if (periodicidad === 'Mensual') {
                    currentDate.setMonth(currentDate.getMonth() + 1);
                } else if (periodicidad === 'Quincenal') {
                    currentDate.setDate(currentDate.getDate() + 15);
                } else if (periodicidad === 'Semanal') {
                    currentDate.setDate(currentDate.getDate() + 7);
                } else if (periodicidad === 'Diario') {
                    currentDate.setDate(currentDate.getDate() + 1);
                }
            }
            
            const resultDiv = document.getElementById('calculation-result');
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <h5>📊 Resultado del Cálculo:</h5>
                    <ul>
                        <li><strong>Número de órdenes:</strong> ${iterations}</li>
                        <li><strong>Monto por orden:</strong> $${amount.toLocaleString('es-MX')}</li>
                        <li><strong>Monto total:</strong> $${totalAmount.toLocaleString('es-MX')}</li>
                        <li><strong>Periodicidad:</strong> ${periodicidad}</li>
                        <li><strong>Duración:</strong> ${plazo} (${totalMonths} meses)</li>
                    </ul>
                    
                    <h6>📅 Primeras fechas (muestra):</h6>
                    <ul>
                        ${dates.map((date, index) => 
                            `<li>Orden ${index + 1}: ${date.toLocaleDateString('es-MX')}</li>`
                        ).join('')}
                        ${iterations > 10 ? '<li><em>... y ' + (iterations - 10) + ' más</em></li>' : ''}
                    </ul>
                </div>
            `;
        }
        
        // Enviar formulario
        document.getElementById('testOrderForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'create_order');
            
            const resultDiv = document.getElementById('creation-result');
            resultDiv.innerHTML = '<div class="alert alert-info">⏳ Creando órdenes...</div>';
            
            fetch('controller.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const orders = data.orders || [];
                    resultDiv.innerHTML = `
                        <div class="alert alert-success">
                            <h5>✅ Órdenes creadas exitosamente!</h5>
                            <p><strong>Total creadas:</strong> ${orders.length}</p>
                            ${orders.length > 0 ? `
                                <h6>📋 Folios generados:</h6>
                                <ul>
                                    ${orders.map(order => 
                                        `<li>${order.order_folio} - ${order.payment_date}</li>`
                                    ).join('')}
                                </ul>
                            ` : ''}
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <h5>❌ Error al crear órdenes</h5>
                            <p>${data.error}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>❌ Error de conexión</h5>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        });
        
        // Calcular automáticamente al cargar
        calculateOrders();
    </script>
</body>
</html>
