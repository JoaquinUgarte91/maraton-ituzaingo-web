<?php
// Incluir configuración (aunque no se use mucho, mantiene consistencia)
include '../includes/config.php';

// ✅ Conexión centralizada
require_once __DIR__ . '/../includes/db.php';

$id = $_GET['id'] ?? null;

if ($id && is_numeric($id)) {
    $stmt = $conexion->prepare("SELECT * FROM inscripciones WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($fila = $resultado->fetch_assoc()) {
        echo "<!DOCTYPE html>
        <html lang='es'>
        <head>
            <meta charset='UTF-8'>
            <title>Ficha del Corredor - Maratón Ituzaingó 2026</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                h1 { color: #2c3e50; }
                p { margin: 8px 0; }
                strong { color: #3498db; }
            </style>
        </head>
        <body>
            <h1>Ficha del Corredor</h1>
            <p><strong>Nombre:</strong> " . htmlspecialchars($fila['nombre']) . "</p>
            <p><strong>DNI:</strong> " . htmlspecialchars($fila['dni']) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($fila['email']) . "</p>
            <p><strong>Carrera:</strong> " . htmlspecialchars($fila['carrera']) . "</p>
            <p><strong>Fecha de inscripción:</strong> " . htmlspecialchars($fila['fecha_inscripcion']) . "</p>
            <br>
            <a href='javascript:history.back()'>← Volver</a>
        </body>
        </html>";
    } else {
        echo "Inscripción no encontrada.";
    }
    $stmt->close();
} else {
    echo "ID no válido.";
}

$conexion->close();
