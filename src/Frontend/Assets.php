<?php
namespace Sun\Apartament\Frontend;

/**
 * Класс для управления ассетами плагина
 *
 * @since 1.0.0
 */
class Assets {
    /**
     * Регистрирует ассеты для фронтенда
     *
     * @return void
     */
    public function register_frontend_assets() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Регистрирует ассеты для админки
     *
     * @return void
     */
    public function register_admin_assets() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    /**
     * Подключает стили для фронтенда
     *
     * @return void
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'sun-apartament-style',
            SUN_APARTAMENT_URL . 'assets/css/front/style.css',
            [],
            SUN_APARTAMENT_VERSION
        );

        wp_enqueue_style(
            'sun-apartament-booking-confirmation-styles',
            SUN_APARTAMENT_URL . 'assets/css/front/booking-confirmation-styles.css',
            [],
            SUN_APARTAMENT_VERSION
        );
        
        wp_enqueue_style(
            'sun-apartament-booking-form',
            SUN_APARTAMENT_URL . 'assets/css/front/booking-form.css',
            [],
            SUN_APARTAMENT_VERSION
        );
        
        // Подключаем flatpickr для выбора дат
        wp_enqueue_style(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
            [],
            SUN_APARTAMENT_VERSION
        );
    }
    
    /**
     * Подключает скрипты для фронтенда
     *
     * @return void
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'sun-apartament-script',
            SUN_APARTAMENT_URL . 'assets/js/front/scripts.js',
            ['jquery'],
            SUN_APARTAMENT_VERSION,
            true
        );
        
        wp_enqueue_script(
            'sun-apartament-booking-form',
            SUN_APARTAMENT_URL . 'assets/js/front/booking-form.js',
            ['jquery'],
            SUN_APARTAMENT_VERSION,
            true
        );
        
        // Подключаем flatpickr для выбора дат
        wp_enqueue_script(
            'flatpickr-js',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js',
            [],
            null,
            true
        );
        
        wp_enqueue_script(
            'flatpickr-l10n-ru',
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ru.js',
            ['flatpickr-js'],
            null,
            true
        );
        
        // Передаем переменные в JS
        wp_localize_script('sun-apartament-booking-form', 'sunApartamentData', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sun-apartament-nonce'),
            'messages' => [
                'min_stay_error' => __('Минимальный срок бронирования - 7 ночей.', 'sun-apartament'),
                'select_dates_error' => __('Пожалуйста, выберите даты заезда и выезда.', 'sun-apartament'),
                'min_guests_error' => __('Должен быть выбран хотя бы один взрослый.', 'sun-apartament'),
                'calendar_not_loaded' => __('Календарь не загружен. Пожалуйста, обновите страницу.', 'sun-apartament')
            ]
        ]);
    }
    
    /**
     * Подключает стили для админки
     *
     * @param string $hook Текущая страница админки
     * @return void
     */
    public function enqueue_admin_styles($hook) {
        global $post;
        
        // Подключаем стили только на страницах редактирования апартаментов и бронирований
        if (($hook == 'post.php' || $hook == 'post-new.php') && 
            (isset($post) && ($post->post_type == 'apartament' || $post->post_type == 'sun_booking') || 
             isset($_GET['post_type']) && ($_GET['post_type'] == 'apartament' || $_GET['post_type'] == 'sun_booking'))) {
            
            wp_enqueue_style(
                'sun-apartament-admin-style',
                SUN_APARTAMENT_URL . 'assets/css/admin/style.css',
                [],
                SUN_APARTAMENT_VERSION
            );
            
            // Подключаем jQuery UI
            wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
            
            // Подключаем Font Awesome для иконок
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
                [],
                '5.15.4'
            );
        }
        
        // Подключаем стили для страницы календаря
        if ($hook == 'sun_booking_page_sun-booking-calendar') {
            wp_enqueue_style(
                'sun-apartament-calendar',
                SUN_APARTAMENT_URL . 'assets/css/admin/calendar.css',
                [],
                SUN_APARTAMENT_VERSION
            );
            
            wp_enqueue_style(
                'fullcalendar',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css',
                [],
                '5.10.1'
            );
        }
        
        // Подключаем стили для дашборда плагина
        if ($hook == 'toplevel_page_sun-apartament') {
            wp_enqueue_style(
                'sun-dashboard-style',
                SUN_APARTAMENT_URL . 'assets/css/admin/dashboard.css',
                [],
                SUN_APARTAMENT_VERSION
            );
            
            wp_enqueue_style(
                'font-awesome',
                'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
                [],
                '5.15.4'
            );
        }
    }
    
    /**
     * Подключает скрипты для админки
     *
     * @param string $hook Текущая страница админки
     * @return void
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        // Подключаем скрипты только на страницах редактирования апартаментов и бронирований
        if (($hook == 'post.php' || $hook == 'post-new.php') && 
            (isset($post) && ($post->post_type == 'apartament' || $post->post_type == 'sun_booking') || 
             isset($_GET['post_type']) && ($_GET['post_type'] == 'apartament' || $_GET['post_type'] == 'sun_booking'))) {
            
            wp_enqueue_media(); // Подключаем медиа библиотеку WordPress
            
            wp_enqueue_script(
                'sun-apartament-admin-script',
                SUN_APARTAMENT_URL . 'assets/js/admin/scripts.js',
                ['jquery', 'jquery-ui-datepicker'],
                SUN_APARTAMENT_VERSION,
                true
            );
            
            // Передаем переменные в JS
            wp_localize_script('sun-apartament-admin-script', 'sunApartamentAdmin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sun-apartament-admin-nonce'),
                'messages' => [
                    'confirm_delete' => __('Вы уверены, что хотите удалить это?', 'sun-apartament'),
                    'image_select' => __('Выберите изображение', 'sun-apartament'),
                    'select' => __('Выбрать', 'sun-apartament')
                ]
            ]);
        }
        
        // Подключаем скрипты для страницы календаря
        if ($hook == 'sun_booking_page_sun-booking-calendar') {
            wp_enqueue_script(
                'fullcalendar',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js',
                [],
                '5.10.1',
                true
            );
            
            wp_enqueue_script(
                'fullcalendar-locales',
                'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales-all.min.js',
                ['fullcalendar'],
                '5.10.1',
                true
            );
            
            wp_enqueue_script(
                'sun-apartament-calendar',
                SUN_APARTAMENT_URL . 'assets/js/admin/calendar.js',
                ['jquery', 'fullcalendar'],
                SUN_APARTAMENT_VERSION,
                true
            );
            
            // Передаем данные в скрипт календаря
            wp_localize_script('sun-apartament-calendar', 'sunCalendarData', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sun-calendar-nonce'),
                'locale' => 'ru',
                'bookings_url' => admin_url('edit.php?post_type=sun_booking')
            ]);
        }
        
        // Подключаем скрипты для дашборда
        if ($hook == 'toplevel_page_sun-apartament') {
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                [],
                '3.7.0',
                true
            );
            
            wp_enqueue_script(
                'sun-dashboard-script',
                SUN_APARTAMENT_URL . 'assets/js/admin/dashboard.js',
                ['jquery', 'jquery-ui-datepicker', 'chart-js'],
                SUN_APARTAMENT_VERSION,
                true
            );
        }
    }
}