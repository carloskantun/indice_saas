<?php
// Exportación básica a PDF - Placeholder
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="gastos_' . date('Y-m-d') . '.pdf"');

// Por ahora, mostrar mensaje
echo "PDF de exportación en desarrollo";
// TODO: Implementar librería PDF (TCPDF, FPDF, etc.)
?>
