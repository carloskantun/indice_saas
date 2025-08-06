-- Script para verificar qué órdenes existen
SELECT 
    folio,
    amount,
    concept,
    origin,
    order_folio,
    status,
    payment_date
FROM expenses 
WHERE company_id = 1 
ORDER BY origin, folio;
