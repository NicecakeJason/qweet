<?php
namespace Sun\Apartament\Core;

/**
 * Класс для обработки деактивации плагина
 *
 * @since 1.0.0
 */
class Deactivation {
    /**
     * Обрабатывает деактивацию плагина
     *
     * @return void
     */
    public static function deactivate() {
        // Очищаем расписание для отложенных задач
        self::clear_scheduled_hooks();
        
        // Очищаем кэш перезаписи
        flush_rewrite_rules();
    }
    
    /**
     * Очищает расписание для отложенных задач
     *
     * @return void
     */
    private static function clear_scheduled_hooks() {
        // Очищаем все хуки, которые мы запланировали при активации
        wp_clear_scheduled_hook('sun_apartament_daily_cleanup');
        wp_clear_scheduled_hook('sun_apartament_check_expired_bookings');
    }
}