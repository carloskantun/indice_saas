<?php
// Exportación básica a CSV
session_start();
require_once '../../config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/');
    exit;
}

// Headers para descarga CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="gastos_' . date('Y-m-d') . '.csv"');

// Abrir salida
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras CSV
fputcsv($output, [
    'Folio',
    'Proveedor', 
    'Monto',
    'Fecha de Pago',
    'Estado',
    'Tipo',
    'Método de Pago',
    'Concepto'
]);

try {
    // Consulta básica de gastos
    $company_id = $_SESSION['company_id'];
    $business_id = $_SESSION['business_id'];
    
    $stmt = $db->prepare("
        SELECT 
            e.folio,
            COALESCE(p.name, 'Sin proveedor') as provider_name,
            e.amount,
            e.payment_date,
            e.status,
            e.expense_type,
            e.payment_method,
            e.concept
        FROM expenses e
        LEFT JOIN providers p ON e.provider_id = p.id
        WHERE e.company_id = ? AND e.business_id = ?
        ORDER BY e.payment_date DESC
    ");
    
    $stmt->execute([$company_id, $business_id]);
    
    // Escribir datos
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['folio'],
            $row['provider_name'],
            '$' . number_format($row['amount'], 2),
            $row['payment_date'],
            $row['status'],
            $row['expense_type'],
            $row['payment_method'],
            $row['concept']
        ]);
    }
    
} catch (Exception $e) {
    fputcsv($output, ['Error', 'No se pudieron exportar los datos']);
}

fclose($output);
?>
