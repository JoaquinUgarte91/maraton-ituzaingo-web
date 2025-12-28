<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 🔐 Solo admin logueado
if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'No autorizado']);
  exit;
}

require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Solo se acepta POST']);
  exit;
}

// Debe venir como multipart/form-data con name="csv"
if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Falta el archivo CSV (campo: csv)']);
  exit;
}

$tmp  = $_FILES['csv']['tmp_name'];
$size = (int)($_FILES['csv']['size'] ?? 0);

if ($size <= 0) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'El archivo está vacío']);
  exit;
}
if ($size > 8_000_000) { // 8MB
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Archivo demasiado grande (máx 8MB)']);
  exit;
}

$fh = fopen($tmp, 'r');
if (!$fh) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo']);
  exit;
}

// Leer header
$header = fgetcsv($fh);
$expected = ['edicion','nombre','dni','email','carrera','fecha_inscripcion'];

$norm = function ($s) {
  $s = (string)$s;
  $s = trim($s);
  // eliminar BOM si existiera
  $s = preg_replace('/^\xEF\xBB\xBF/', '', $s);
  return strtolower($s);
};

$headerNorm = array_map($norm, $header ?? []);

if ($headerNorm !== $expected) {
  fclose($fh);
  http_response_code(400);
  echo json_encode([
    'success' => false,
    'message' => 'Header inválido. Se esperaba: ' . implode(',', $expected),
    'recibido' => $header
  ]);
  exit;
}

$inserted = 0;
$errors = [];

// Preparar insert
$sql = "INSERT INTO inscripciones_historicas
        (edicion, nombre, dni, email, carrera, fecha_inscripcion)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);

$line = 1; // header
while (($row = fgetcsv($fh)) !== false) {
  $line++;

  // Si viene una fila vacía
  if (count($row) === 1 && trim((string)$row[0]) === '') continue;

  // asegurar 6 columnas
  $row = array_pad($row, 6, '');

  $edicion = (int)trim($row[0]);
  $nombre  = trim($row[1]);
  $dni     = trim($row[2]);
  $email   = trim($row[3]);
  $carrera = trim($row[4]);
  $fecha   = trim($row[5]);

  // Validaciones mínimas
  if ($edicion < 1900 || $edicion > 2100) { $errors[] = "Línea $line: edición inválida"; continue; }
  if ($nombre === '') { $errors[] = "Línea $line: nombre vacío"; continue; }
  if ($dni === '') { $errors[] = "Línea $line: dni vacío"; continue; }
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = "Línea $line: email inválido"; continue; }
  if ($carrera === '') { $errors[] = "Línea $line: carrera vacía"; continue; }

  // normalizar Kids
  if (strtolower($carrera) === 'kids') $carrera = 'Kids';

  // fecha opcional: si viene vacía, guardamos NULL
  $fechaDb = null;
  if ($fecha !== '') {
    // Acepta: YYYY-MM-DD HH:MM:SS
    if (!preg_match('/^\d{4}-\d{2}-\d{2}(\s+\d{2}:\d{2}:\d{2})$/', $fecha)) {
      $errors[] = "Línea $line: fecha_inscripcion inválida (usar YYYY-MM-DD HH:MM:SS)";
      continue;
    }
    $fechaDb = $fecha;
  }

  try {
    $stmt->bind_param("isssss", $edicion, $nombre, $dni, $email, $carrera, $fechaDb);
    $stmt->execute();
    $inserted++;
  } catch (Throwable $e) {
    // Ej: duplicados si luego agregás unique keys
    $errors[] = "Línea $line: error al insertar ({$e->getMessage()})";
  }
}

fclose($fh);

echo json_encode([
  'success' => true,
  'inserted' => $inserted,
  'errors' => $errors
]);
