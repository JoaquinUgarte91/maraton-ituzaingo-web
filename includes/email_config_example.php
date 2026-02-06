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
define('EMAIL_HOST', 'mail.miituzaingo.gob.ar');  // Servidor SMTP (ej: mail.miituzaingo.gob.ar)
define('EMAIL_PORT', 587);                         // Puerto SMTP (587 para STARTTLS, 465 para SSL)
define('EMAIL_USERNAME', 'mujeres_mimaraton@miituzaingo.gob.ar');  // Email del municipio
define('EMAIL_PASSWORD', 'TU_CONTRASEÑA_AQUI');    // ⚠️ ¡Reemplaza con la contraseña real!
define('EMAIL_FROM', 'mujeres_mimaraton@miituzaingo.gob.ar');      // Email remitente
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');  // Nombre que aparece como remitente
define('EMAIL_REPLY_TO', 'Consejomujeresdeituzaingo@hotmail.com'); // Email para respuestas