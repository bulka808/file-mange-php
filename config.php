<?php
session_start();

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', '/file-manager/uploads/'); // Относительно корня сайта
// --- Автоматическое создание папки uploads и .htaccess ---
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        die('Ошибка: не удалось создать папку для загрузок: ' . UPLOAD_DIR);
    }
}

// Создаём .htaccess в uploads/, если его нет
$htaccessPath = UPLOAD_DIR . '.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = <<<'HTACCESS'
# Запретить показ списка файлов
Options -Indexes

# Запретить выполнение опасных файлов
<Files "*.php">
    Require all denied
</Files>
<Files "*.phtml">
    Require all denied
</Files>
<Files "*.phar">
    Require all denied
</Files>
<Files "*.pl">
    Require all denied
</Files>
<Files "*.py">
    Require all denied
</Files>
<Files "*.exe">
    Require all denied
</Files>
<Files "*.bat">
    Require all denied
</Files>
<Files "*.sh">
    Require all denied
</Files>
<Files "*.js">
    Require all denied
</Files>
<Files "*.html">
    Require all denied
</Files>
<Files "*.htm">
    Require all denied
</Files>
HTACCESS;

    if (!file_put_contents($htaccessPath, $htaccessContent)) {
        // Не критично, но предупредим в лог
        error_log("Не удалось создать .htaccess в папке uploads. Убедитесь, что папка доступна для записи.");
    }
}

// Проверка прав на запись
if (!is_writable(UPLOAD_DIR)) {
    die('Ошибка: папка ' . UPLOAD_DIR . ' недоступна для записи.');
}

// ===== Чёрный список файлов и расширений ===== //
$forbiddenMimes = [
    'application/x-php',
    'application/x-sh',
    'application/x-shellscript',
    'text/x-php',
    'text/html',
    'text/javascript',
    'application/javascript',
    'application/x-javascript',
    'application/octet-stream',
];
$forbiddenExtensions = ['php', 'phtml', 'phar', 'sh', 'exe', 'bat', 'cmd', 'js', 'html', 'htm', 'asp', 'aspx'];
$maxSize = 500 * 1024 * 1024; // 500 МБ 


// ===== Настройки БД ===== //
// заменить если отличается + см. db.sql
$host = 'localhost';
$dbname = 'file_manager'; // задано в bd.sql
$dbuser = 'user';
$dbpass = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// Проверка входа
function require_login()
{
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}

// Защита от XSS
function h($str)
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
