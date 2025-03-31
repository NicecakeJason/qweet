<?php
namespace Sun\Apartament\Admin;

/**
 * Класс для управления меню в админке
 *
 * @since 1.0.0
 */
class Menu {
    /**
     * Регистрирует меню
     *
     * @return void
     */
    public function register() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    /**
     * Добавляет пункты меню в админку
     *
     * @return void
     */
    public function add_admin_menu() {
        // Главное меню
        add_menu_page(
            __('Sun Apartament', 'sun-apartament'),
            __('Sun Apartament', 'sun-apartament'),
            'manage_options',
            'sun-apartament',
            [$this, 'render_dashboard_page'],
            'dashicons-building',
            30
        );
        
        // Подпункт "Панель управления"
        add_submenu_page(
            'sun-apartament',
            __('Панель управления', 'sun-apartament'),
            __('Панель управления', 'sun-apartament'),
            'manage_options',
            'sun-apartament',
            [$this, 'render_dashboard_page']
        );
        
        // Подпункт "Апартаменты"
        add_submenu_page(
            'sun-apartament',
            __('Апартаменты', 'sun-apartament'),
            __('Апартаменты', 'sun-apartament'),
            'manage_options',
            'edit.php?post_type=apartament',
            null
        );
        
        // Подпункт "Типы апартаментов"
        add_submenu_page(
            'sun-apartament',
            __('Типы апартаментов', 'sun-apartament'),
            __('Типы', 'sun-apartament'),
            'manage_options',
            'edit-tags.php?taxonomy=apartament-type&post_type=apartament',
            null
        );
        
        // Подпункт "Бронирования"
        add_submenu_page(
            'sun-apartament',
            __('Бронирования', 'sun-apartament'),
            __('Бронирования', 'sun-apartament'),
            'manage_options',
            'edit.php?post_type=sun_booking',
            null
        );
        
        // Подпункт "Календарь"
        add_submenu_page(
            'sun-apartament',
            __('Календарь доступности', 'sun-apartament'),
            __('Календарь', 'sun-apartament'),
            'manage_options',
            'sun-booking-calendar',
            [$this, 'render_calendar_page']
        );
        
        // Подпункт "Настройки"
        add_submenu_page(
            'sun-apartament',
            __('Настройки', 'sun-apartament'),
            __('Настройки', 'sun-apartament'),
            'manage_options',
            'sun-apartament-settings',
            [$this, 'render_settings_page']
        );
        
        // Подпункт "Инструменты"
        add_submenu_page(
            'sun-apartament',
            __('Инструменты', 'sun-apartament'),
            __('Инструменты', 'sun-apartament'),
            'manage_options',
            'sun-apartament-tools',
            [$this, 'render_tools_page']
        );
    }
    
    /**
     * Отображает страницу дашборда
     *
     * @return void
     */
    public function render_dashboard_page() {
        // Страница дашборда отображается через класс Dashboard
        $dashboard = new Dashboard();
        $dashboard->render();
    }
    
    /**
     * Отображает страницу календаря
     *
     * @return void
     */
    public function render_calendar_page() {
        // Страница календаря отображается через класс Calendar
        $calendar = new Calendar();
        $calendar->render();
    }
    
    /**
     * Отображает страницу настроек
     *
     * @return void
     */
    public function render_settings_page() {
        // Страница настроек отображается через класс Settings
        $settings = new Settings();
        $settings->render();
    }
    
    /**
     * Отображает страницу инструментов
     *
     * @return void
     */
    public function render_tools_page() {
        // Страница инструментов отображается через класс Tools
        $tools = new Tools();
        $tools->render();
    }
}