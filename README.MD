# 🏃‍♀️ Maratón Ituzaingó 2026 - Plataforma de Inscripción

![Estado](https://img.shields.io/badge/Estado-Producci%C3%B3n-green)
![PHP](https://img.shields.io/badge/PHP-7.4%20%7C%208.x-blue)
![DB](https://img.shields.io/badge/MySQL-MariaDB-orange)

Sitio web oficial y API de gestión para la **13° Edición de la Maratón “Corremos por Más Derechos y Más Igualdad 2026”**, organizada por el **Consejo de Mujeres, Géneros, Diversidad, Niñeces y Adolescencias del Municipio de Ituzaingó**.

El sistema gestiona el alta de participantes, validación de reglas de negocio (edades/cupos), generación de credenciales QR y notificaciones vía correo electrónico transaccional.

---

## 📋 Características Técnicas

- **Arquitectura:** Frontend desacoplado (HTML / JS) consumiendo API REST (PHP)
- **Seguridad:**
  - Protección Anti-Bots mediante **Google reCAPTCHA v2**
  - Sanitización de inputs y **Prepared Statements** (SQL Injection safe)
  - Detección automática de entorno (Local vs Producción) para gestión de SSL
- **Funcionalidades:**
  - Validación de edad server-side según categoría (Kids / Adultos)
  - **Generación de QR local** (no depende de APIs externas)
  - **Email con QR incrustado (CID)** para evitar bloqueos en Outlook y Gmail
- **Infraestructura:** Compatible con Linux (Ubuntu / CentOS) y Windows (XAMPP / IIS)

---

## 📂 Estructura del Proyecto

~~~text
/
├── api/                  # Endpoints del Backend
│   └── inscripcion.php   # Controlador principal del formulario
├── includes/             # Lógica de negocio y configuraciones
│   ├── PHPMailer/        # Librería de envíos SMTP
│   ├── PHPQRCode/        # Librería gráfica de QR
│   ├── config.php        # Constantes generales
│   ├── db.php            # Conexión a Base de Datos (ignorado en git)
│   ├── email_config.php  # Credenciales SMTP (ignorado en git)
│   └── ...
├── public/               # Frontend (acceso público)
│   ├── documentacion/    # PDFs (Deslindes, Reglamentos)
│   ├── imagenes/         # Assets gráficos
│   ├── temp/             # ⚠️ Carpeta crítica para QRs temporales
│   ├── index.html        # SPA del formulario
│   └── script.js         # Lógica cliente
└── docs/
    └── database_schema.sql # Script de instalación DB
~~~

---

## ⚙️ Requisitos del Servidor

### Software
- PHP **7.4 o superior**
- MySQL **5.7+** o MariaDB **10.x**

### Extensiones PHP obligatorias
- `php-gd` → Generación de imágenes QR
- `php-curl` → Conexión con Google reCAPTCHA
- `php-mysqli` → Base de datos
- `php-mbstring` → Manejo UTF-8

### Red
- Salida permitida a:
  - Puerto SMTP **587 o 465**
  - `google.com` (reCAPTCHA)

---

## 🚀 Guía de Instalación y Despliegue

### 1. Despliegue de Código

~~~bash
git clone https://github.com/municipalidad-ituzaingo/maraton-2026.git .
~~~

---

### 2. Base de Datos

Archivo: `docs/database_schema.sql`

~~~bash
mysql -u usuario -p nombre_base_datos < docs/database_schema.sql
~~~

---

### 3. Configuración de Credenciales (CRUCIAL)

El repositorio no incluye contraseñas reales por seguridad.

### A. Base de Datos

Renombrar:

    includes/db.example.php → includes/db.php

~~~php
$conexion = new mysqli(
  "localhost",
  "USUARIO_DB",
  "PASSWORD_DB",
  "NOMBRE_DB"
);
~~~

---

### B. Configuración de Email SMTP

Renombrar:

    includes/email_config.example.php → includes/email_config.php

~~~php
define('EMAIL_HOST', 'smtp.miituzaingo.gov.ar');
define('EMAIL_USERNAME', 'cuenta@miituzaingo.gov.ar');
define('EMAIL_PASSWORD', 'contraseña_segura');
define('EMAIL_PORT', 587);
~~~

---

### C. Google reCAPTCHA

Renombrar:

    includes/recaptcha_config.example.php → includes/recaptcha_config.php

Colocar la **Secret Key (server-side)**.

---

### 4. Permisos de Escritura (Linux)

~~~bash
mkdir -p public/temp
chown -R www-data:www-data public/temp
chmod -R 775 public/temp
~~~

---

## 🛡️ Seguridad y Entorno Híbrido

- **Modo Local (Dev):**
  - SSL relajado
  - Permite pruebas con SMTP y reCAPTCHA locales

- **Modo Producción:**
  - `CURLOPT_SSL_VERIFYPEER = true`
  - `display_errors = 0`
  - No se exponen rutas ni errores del servidor

---

## ✅ Checklist de Puesta en Producción

- [ ] `includes/db.php` creado con credenciales reales  
- [ ] `includes/recaptcha_config.php` con Secret Key válida  
- [ ] Dominio autorizado en Google reCAPTCHA  
- [ ] Carpeta `public/temp` con permisos de escritura  
- [ ] Extensión `php-gd` instalada  
- [ ] PDFs cargados en `public/documentacion/`  
- [ ] Inscripción de prueba con recepción de QR por email  

---

## 📞 Soporte Técnico

**Desarrollo y Mantenimiento**  
Área: Informática Municipal / Desarrollo Web  

---

© 2026 Municipalidad de Ituzaingó — Todos los derechos reservados.
