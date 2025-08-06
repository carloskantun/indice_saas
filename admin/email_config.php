<?php
// Email Configuration - Ultra Clean Version
// No functions, no comments, just constants

if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', 'noreply@indiceapp.com');
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', 'Indice Produccion');
}

if (!defined('MAIL_REPLY_TO')) {
    define('MAIL_REPLY_TO', 'soporte@indiceapp.com');
}

if (!defined('EMAIL_DEBUG')) {
    define('EMAIL_DEBUG', false);
}

if (!defined('EMAIL_USE_SMTP')) {
    define('EMAIL_USE_SMTP', false);
}
