<?php
declare(strict_types=1);

define('APP_BASE_PATH', dirname(__DIR__, 2));

define('DB_HOST', getenv('BD_CINE_HOST') ?: 'localhost');
define('DB_NAME', getenv('BD_CINE_NAME') ?: 'bd_cine');
define('DB_USER', getenv('BD_CINE_USER') ?: 'root');
define('DB_PASS', getenv('BD_CINE_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

define('APP_SESSION_MINUTOS', 30);
define('APP_DEBUG', (getenv('APP_DEBUG') ?: '0') === '1');

// ─── OMDb API ────────────────────────────────────────────────────────────────
// Obtén tu API key gratuita en: https://www.omdbapi.com/apikey.aspx
// Puedes sobreescribirla con la variable de entorno OMDB_API_KEY
define('OMDB_API_KEY', getenv('OMDB_API_KEY') ?: 'TU_API_KEY_AQUI');
