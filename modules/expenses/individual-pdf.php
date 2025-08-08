<?php
// PDF individual de un gasto específico
session_start();
require_once '../../config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/');
    exit;
}

$expense_id = intval($_GET['expense_id'] ?? 0);
if (!$expense_id) {
    die('ID de gasto requerido');
}

// Obtener datos del gasto
$company_id = $_SESSION['company_id'];
$business_id = $_SESSION['business_id'];

$stmt = $db->prepare("
    SELECT 
        e.*,
        COALESCE(p.name, 'Sin proveedor') as provider_name,
        u.name as unit_name,
        b.name as business_name,
        c.name as company_name,
        COALESCE((SELECT SUM(ep.amount) FROM expense_payments ep WHERE ep.expense_id = e.id), 0) AS paid_amount
    FROM expenses e
    LEFT JOIN providers p ON e.provider_id = p.id
    LEFT JOIN units u ON e.unit_id = u.id
    LEFT JOIN businesses b ON e.business_id = b.id
    LEFT JOIN companies c ON e.company_id = c.id
    WHERE e.id = ? AND e.company_id = ? AND e.business_id = ?
");

$stmt->execute([$expense_id, $company_id, $business_id]);
$expense = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$expense) {
    die('Gasto no encontrado');
}

// Obtener pagos del gasto
$stmt = $db->prepare("
    SELECT ep.*, u.name as created_by_name
    FROM expense_payments ep
    LEFT JOIN users u ON ep.created_by = u.id
    WHERE ep.expense_id = ?
    ORDER BY ep.payment_date DESC
");
$stmt->execute([$expense_id]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Headers para PDF (por ahora HTML)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Gasto <?php echo htmlspecialchars($expense['folio']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .section { margin: 20px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; }
        .table th { background-color: #f5f5f5; }
        .amount { font-weight: bold; color: #28a745; }
        .pending { color: #dc3545; }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($expense['company_name']); ?></h1>
        <h2>Detalle de Gasto - <?php echo htmlspecialchars($expense['folio']); ?></h2>
        <p>Fecha de generación: <?php echo date('d/m/Y H:i'); ?></p>
    </div>
    
    <div class="section">
        <h3>Información General</h3>
        <table class="table">
            <tr><td><strong>Folio:</strong></td><td><?php echo htmlspecialchars($expense['folio']); ?></td></tr>
            <tr><td><strong>Proveedor:</strong></td><td><?php echo htmlspecialchars($expense['provider_name']); ?></td></tr>
            <tr><td><strong>Monto Total:</strong></td><td class="amount">$<?php echo number_format($expense['amount'], 2); ?></td></tr>
            <tr><td><strong>Pagado:</strong></td><td class="amount">$<?php echo number_format($expense['paid_amount'], 2); ?></td></tr>
            <tr><td><strong>Pendiente:</strong></td><td class="pending">$<?php echo number_format($expense['amount'] - $expense['paid_amount'], 2); ?></td></tr>
            <tr><td><strong>Fecha de Pago:</strong></td><td><?php echo $expense['payment_date']; ?></td></tr>
            <tr><td><strong>Estado:</strong></td><td><?php echo htmlspecialchars($expense['status']); ?></td></tr>
            <tr><td><strong>Tipo:</strong></td><td><?php echo htmlspecialchars($expense['expense_type']); ?></td></tr>
            <tr><td><strong>Método de Pago:</strong></td><td><?php echo htmlspecialchars($expense['payment_method']); ?></td></tr>
        </table>
    </div>
    
    <div class="section">
        <h3>Concepto</h3>
        <p style="border: 1px solid #ddd; padding: 10px; background-color: #f9f9f9;">
            <?php echo nl2br(htmlspecialchars($expense['concept'])); ?>
        </p>
    </div>
    
    <?php if (count($payments) > 0): ?>
    <div class="section">
        <h3>Historial de Pagos</h3>
        <table class="table">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Comentario</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td><?php echo $payment['payment_date']; ?></td>
                    <td class="amount">$<?php echo number_format($payment['amount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($payment['comment'] ?: 'Sin comentario'); ?></td>
                    <td><?php echo htmlspecialchars($payment['created_by_name'] ?: 'N/A'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="section" style="margin-top: 50px; text-align: center; color: #666;">
        <small>Documento generado el <?php echo date('d/m/Y H:i:s'); ?> por <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Sistema'); ?></small>
    </div>
</body>
</html>
