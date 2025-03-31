<?php
/**
 * Plugin Name: Sun Apartament
 * Plugin URI: 
 * Description: Плагин для управления апартаментами и бронированиями
 * Version: 1.0.0
 * Author: Ваше имя
 * Author URI: 
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: sun-apartament
 * Domain Path: /languages
 */

// Запрет прямого доступа к файлу
if (!defined('ABSPATH')) {
    exit;
}

// Определение констант плагина
define('SUN_APARTAMENT_VERSION', '1.0.0');
define('SUN_APARTAMENT_PATH', plugin_dir_path(__FILE__));
define('SUN_APARTAMENT_URL', plugin_dir_url(__FILE__));
define('SUN_APARTAMENT_BASENAME', plugin_basename(__FILE__));

// Подключение автозагрузчика Composer
if (file_exists(SUN_APARTAMENT_PATH . 'vendor/autoload.php')) {
    require_once SUN_APARTAMENT_PATH . 'vendor/autoload.php';
} else {
    // Fallback для случая, когда composer не установлен
    require_once SUN_APARTAMENT_PATH . 'src/Core/Autoloader.php';
    $autoloader = new \Sun\Apartament\Core\Autoloader();
    $autoloader->register();
}

// Инициализация плагина
function sun_apartament_init() {
    // Подключаем главный класс плагина
    $plugin = new \Sun\Apartament\Core\Plugin();
    $plugin->run();
}

// Регистрация хуков активации и деактивации
register_activation_hook(__FILE__, ['\Sun\Apartament\Core\Activation', 'activate']);
register_deactivation_hook(__FILE__, ['\Sun\Apartament\Core\Deactivation', 'deactivate']);

// Запуск плагина
add_action('plugins_loaded', 'sun_apartament_init');