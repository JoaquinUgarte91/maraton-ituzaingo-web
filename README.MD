# Maratón Ituzaingó 2026 - Sistema de Inscripción

## Descripción
Sistema web para la inscripción a la 13° edición de la Maratón "Corremos Por Más Derechos y Más Igualdad" 2026, organizada por la Municipalidad de Ituzaingó.

## Características
- Formulario de inscripción con validación en tiempo real.
- Generación de QR para retiro de kit.
- Visualización del stock disponible por categoría.
- Ficha de corredor por ID.

## Estructura del Proyecto
maraton-ituzaingo-2026/
├── public/ # Frontend
├── api/ # Backend
├── includes/ # Configuración global
├── db/ # Script de base de datos
└── README.md # Documentación


## Requisitos
- Servidor web con PHP 7.4+ y MySQL/MariaDB.
- Extensión PDO habilitada.

## Instalación
1. Importa `db/maraton_db.sql` en tu base de datos.
2. Configura `includes/config.php` según tus necesidades.
3. Coloca el proyecto en el directorio raíz de tu servidor web.
4. Accede a `http://localhost/maraton-ituzaingo-2026/public/index.html`.

## Autor
[Nombre del desarrollador]