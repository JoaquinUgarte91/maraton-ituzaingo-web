<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Importante para evitar bloqueos

require_once __DIR__ . '/../includes/db.php';

try {
    // Consultamos DIRECTAMENTE la tabla de stock físico
    // Asumimos que la tabla tiene columnas: 'carrera' y 'stock_disponible'
    $query = "SELECT carrera, stock_disponible FROM stock_remeras";
    $res = $conexion->query($query);

    $stock = [];
    
    // Inicializamos en 0 por seguridad
    $stock['10km'] = 0;
    $stock['3km'] = 0;
    $stock['Kids'] = 0;

    while ($row = $res->fetch_assoc()) {
        // Guardamos el stock real que pusiste en la base de datos
        // Ejemplo: $stock['10km'] = 0;
        $stock[$row['carrera']] = (int)$row['stock_disponible'];
    }

    echo json_encode(['success' => true, 'stock' => $stock]);

} catch (Throwable $e) {
    error_log("Error en stock.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener stock']);
}
?>