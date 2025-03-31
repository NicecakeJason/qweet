<?php
namespace Sun\Apartament\Services;

/**
 * Абстрактный класс сервиса
 *
 * Базовый класс для всех сервисов плагина
 *
 * @since 1.0.0
 */
abstract class AbstractService {
    /**
     * Регистрирует сервис
     *
     * Должен быть реализован во всех дочерних классах
     *
     * @return void
     */
    abstract public function register();
    
    /**
     * Возвращает имя класса без пространства имен
     *
     * @return string
     */
    protected function get_class_name() {
        $class_name = get_class($this);
        $class_parts = explode('\\', $class_name);
        
        return end($class_parts);
    }
    
    /**
     * Проверяет, инициализирован ли сервис
     *
     * @return bool
     */
    protected function is_initialized() {
        return did_action('init');
    }
    
    /**
     * Журналирует сообщение
     *
     * @param string $message Сообщение для журнала
     * @param string $level Уровень сообщения (error, warning, info, debug)
     * @return void
     */
    protected function log($message, $level = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $class_name = $this->get_class_name();
        $log_message = "[{$class_name}] [{$level}] {$message}";
        
        if ($level === 'error') {
            error_log($log_message);
        } else {
            // Записывать в журнал только если включен расширенный режим отладки
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log($log_message);
            }
        }
    }
}