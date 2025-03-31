<?php
namespace Sun\Apartament\Core;

/**
 * Класс автозагрузчика для плагина
 *
 * Используется как резервный вариант, если Composer не установлен
 *
 * @since 1.0.0
 */
class Autoloader {
    /**
     * Регистрирует автозагрузчик
     *
     * @return void
     */
    public function register() {
        spl_autoload_register([$this, 'autoload']);
    }
    
    /**
     * Загружает класс
     *
     * @param string $class_name Полное имя класса с пространством имен
     * @return void
     */
    public function autoload($class_name) {
        // Проверяем, относится ли класс к нашему плагину
        $namespace = 'Sun\\Apartament\\';
        
        if (strpos($class_name, $namespace) !== 0) {
            return;
        }
        
        // Получаем относительный путь к файлу класса
        $class_path = str_replace($namespace, '', $class_name);
        $class_path = str_replace('\\', DIRECTORY_SEPARATOR, $class_path);
        
        // Формируем полный путь к файлу
        $file_path = SUN_APARTAMENT_PATH . 'src' . DIRECTORY_SEPARATOR . $class_path . '.php';
        
        // Проверяем существование файла и подключаем его
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
}