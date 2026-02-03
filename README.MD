# 🏃‍♀️ Maratón Ituzaingó 2026

Sitio web oficial de la **13° Edición de la Maratón “Corremos por Más Derechos y Más Igualdad 2026”**, organizada por el **Consejo de Mujeres, Géneros, Diversidad, Niñeces y Adolescencias del Municipio de Ituzaingó**.

---

## 📋 Características principales

- ✅ Formulario de inscripción con validación en tiempo real  
- ✅ Selección dinámica de talles según tipo de carrera (Kids / Adultos)  
- ✅ Validación automática de edad  
- ✅ Sistema anti-bots con **Google reCAPTCHA v2**  
- ✅ Generación de **QR único** para retiro de kit  
- ✅ Envío de email de confirmación al inscribirse  
- ✅ Pestañas interactivas para recorridos:
  - 10K  
  - 3K  
  - 1K Kids  
- ✅ Diseño **responsive** (móvil, tablet y desktop)  
- ✅ Integración con **Google Calendar**

---

## ⚙️ Requisitos previos

Antes de desplegar el proyecto, asegurarse de contar con:

- Servidor web con **PHP 7.4 o superior** (Apache / Nginx)  
- Base de datos **MySQL o MariaDB**  
- Dominio configurado (ejemplo: `miituzaingo.gov.ar`)  
- Acceso a **Google reCAPTCHA**  
- Acceso a un servidor **SMTP** para envío de emails

---

## 🚀 Instalación y despliegue

### 1️⃣ Clonar el repositorio

```bash
git clone https://github.com/municipalidad-ituzaingo/maraton-2026.git
cd maraton-2026
```

---

## 🔐 Configuración de Google reCAPTCHA (OBLIGATORIO)

### 2️⃣ Obtener claves

1. Ingresar a https://www.google.com/recaptcha/admin/create  
2. Completar el formulario:
   - **Label**: Maratón Ituzaingó 2026  
   - **Tipo**: reCAPTCHA v2 → *I'm not a robot*  
   - **Dominios**:
     - `localhost`  
     - `miituzaingo.gov.ar`  
   - Activar **Send alerts to owners**
3. Guardar:
   - **Site Key**
   - **Secret Key**

---

### 3️⃣ Configurar reCAPTCHA en el proyecto

Copiar el archivo de ejemplo:

```bash
cp includes/recaptcha_config.example.php includes/recaptcha_config.php
```

Editar `includes/recaptcha_config.php`:

```php
<?php
define('RECAPTCHA_SITE_KEY', 'TU_SITE_KEY_REAL_AQUI');
define('RECAPTCHA_SECRET_KEY', 'TU_SECRET_KEY_REAL_AQUI');
```

Editar `index.html` y reemplazar la Site Key:

```html
<div class="g-recaptcha" data-sitekey="TU_SITE_KEY_REAL_AQUI"></div>
```

---

## 📧 Configuración de Email

### Paso 1: Obtener credenciales SMTP

El municipio debe contar con acceso a un servidor SMTP. Existen tres alternativas:

#### **Opción A: Servidor SMTP del municipio (RECOMENDADO)** ✅
- Utilizar el servidor oficial de correo del municipio  
- Ejemplo: `smtp.miituzaingo.gov.ar`  
- Solicitar credenciales al área de IT

#### **Opción B: Gmail (alternativa temporal)** ⚠️
- Más simple, pero menos profesional  
- Requiere una **Contraseña de aplicación**  
- Generarla desde: https://myaccount.google.com/apppasswords

#### **Opción C: SendGrid (servicio profesional)** 🚀
- Servicio de email transaccional confiable  
- Plan gratuito: hasta **100 emails diarios**  
- Registro en: https://sendgrid.com/

---

### Paso 2: Configurar el archivo de Email

Copiar el archivo de ejemplo:

```bash
cp includes/email_config.example.php includes/email_config.php
```

Editar `includes/email_config.php` y completar con los datos reales:

```php
<?php
define('EMAIL_HOST', 'smtp.miituzaingo.gov.ar');                 // Servidor SMTP
define('EMAIL_PORT', 587);                                      // Puerto SMTP
define('EMAIL_USERNAME', 'maraton@miituzaingo.gov.ar');         // Usuario
define('EMAIL_PASSWORD', 'TU_CONTRASEÑA');                      // Contraseña
define('EMAIL_FROM', 'maraton@miituzaingo.gov.ar');             // Remitente
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');            // Nombre remitente
define('EMAIL_REPLY_TO', 'Consejomujeresdeituzaingo@hotmail.com'); // Responder a
```

---

### Paso 3: Probar el envío de Email

1. Completar el formulario de inscripción  
2. Verificar que llegue el email de confirmación  
3. Confirmar que los caracteres especiales (tildes, ñ, acentos) se visualicen correctamente  

---

## 🗄️ Configuración de Base de Datos

1. Editar los archivos:
   - `includes/config.php`
   - `includes/db.php`
2. Configurar los datos de conexión a la base de datos del municipio.
3. Estos archivos **no deben subirse al repositorio** (usar `.gitignore`).

---

## 🔧 Permisos de archivos (recomendado)

```bash
chmod 755 api/
chmod 644 api/*.php
chmod 755 includes/
```

---

## 🌐 Acceso al sitio

Una vez configurado, el sitio estará disponible en:

https://www.miituzaingo.gov.ar/maraton-2026/

---

## ✅ Checklist final

- reCAPTCHA funcionando correctamente  
- Email de confirmación enviado correctamente  
- Base de datos creada y conectada  
- Archivos sensibles fuera de GitHub  
- Formulario operativo y generando QR  
- Recorridos 10K / 3K / 1K Kids funcionales  
- Diseño responsive validado  
- Enlaces y redes funcionando  
- SSL activo (HTTPS)  
- Dominio correctamente configurado  

---

## 🏛️ Municipio de Ituzaingó

Proyecto desarrollado para el **Municipio de Ituzaingó**  
Consejo de Mujeres, Géneros, Diversidad, Niñeces y Adolescencias.