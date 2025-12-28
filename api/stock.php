<?php
header('Content-Type: application/json');

// Configuración global
include '../includes/config.php';
// Conexión centralizada
require_once __DIR__ . '/../includes/db.php';

try {
    $query = "SELECT carrera, COUNT(*) as total FROM inscripciones GROUP BY carrera";
    $res = $conexion->query($query);

    $inscripciones = [];
    while ($row = $res->fetch_assoc()) {
        $inscripciones[$row['carrera']] = (int)$row['total'];
    }

    // Límites desde config.php
    $limites = MARATON_2026_CONFIG['limites_inscripciones'];

    $stock = [
        '10km'  => ($limites['10km'] ?? 0) - ($inscripciones['10km'] ?? 0),
        '3km'   => ($limites['3km'] ?? 0) - ($inscripciones['3km'] ?? 0),
        'Kids'  => ($limites['Kids'] ?? 0) - ($inscripciones['Kids'] ?? 0),
        // total inscriptos (no stock total). Si querés stock total, te lo cambio.
        'total' => array_sum($inscripciones),
    ];

    echo json_encode(['success' => true, 'stock' => $stock]);

} catch (Throwable $e) {
    error_log("Error en stock.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al obtener el stock']);
}
