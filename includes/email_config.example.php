<?php
// ========================================
// CONFIGURACIÓN DE EMAIL - EJEMPLO
// ========================================
// 
// 1. Renombra este archivo a "email_config.php"
// 2. Reemplaza los datos con los del municipio


// ========================================
// OPCIÓN A: Usar servidor SMTP del municipio (RECOMENDADO)
// ========================================
define('EMAIL_HOST', 'smtp.miituzaingo.gov.ar');         // Servidor SMTP del municipio
define('EMAIL_PORT', 587);                               // Puerto SMTP (25, 465 o 587)
define('EMAIL_USERNAME', 'maraton@miituzaingo.gov.ar');  // Email del municipio
define('EMAIL_PASSWORD', 'contraseña_del_municipio');    // Contraseña del municipio
define('EMAIL_FROM', 'maraton@miituzaingo.gov.ar');      // Email remitente
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');     // Nombre remitente
define('EMAIL_REPLY_TO', 'Consejomujeresdeituzaingo@hotmail.com'); // Email de respuesta

// ========================================
// OPCIÓN B: Usar Gmail (alternativa temporal)
// ========================================
/*
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'tucorreo@gmail.com');
define('EMAIL_PASSWORD', 'tu_contraseña_de_aplicacion');
define('EMAIL_FROM', 'maraton@ituzaingo.gov.ar');
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');
define('EMAIL_REPLY_TO', 'Consejomujeresdeituzaingo@hotmail.com');
*/

// ========================================
// OPCIÓN C: Usar SendGrid (servicio profesional)
// ========================================
/*
define('EMAIL_HOST', 'smtp.sendgrid.net');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'apikey');                      // Siempre "apikey" para SendGrid
define('EMAIL_PASSWORD', 'TU_API_KEY_DE_SENDGRID');      // API Key de SendGrid
define('EMAIL_FROM', 'maraton@ituzaingo.gov.ar');
define('EMAIL_FROM_NAME', 'Maratón Ituzaingó 2026');
define('EMAIL_REPLY_TO', 'Consejomujeresdeituzaingo@hotmail.com');
*/