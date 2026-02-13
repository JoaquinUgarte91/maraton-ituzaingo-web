<?php
// ========================================
// CONFIGURACIÓN DE EMAIL - EJEMPLO
// ========================================
// 
// ⚠️ INSTRUCCIONES:
// 1. Copia este archivo y renómbralo a "email_config.php"
//    cp email_config.example.php email_config.php
// 2. Edita email_config.php con los datos reales del municipio
// 3. ¡NUNCA subas email_config.php a GitHub!

// ========================================
// DATOS SMTP DEL MUNICIPIO (ejemplo)
// ========================================
define('EMAIL_HOST', 'smtp.gmail.com');  // Servidor SMTP (ej: mail.miituzaingo.gob.ar)
define('EMAIL_PORT', 587);                         // Puerto SMTP (587 para STARTTLS, 465 para SSL)
define('EMAIL_USERNAME', 'gugarte305@gmail.com');  // Email del municipio
define('EMAIL_PASSWORD', 'scnv zeka jxxt kqro');    // ⚠️ ¡Reemplaza con la contraseña real!
define('EMAIL_FROM', 'gugarte305@gmail.com');      // Email remitente
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');  // Nombre que aparece como remitente
define('EMAIL_REPLY_TO', 'gugarte305@gmail.com'); // Email para respuestas