<?php
namespace Sun\Apartament\Core;

/**
 * Основной класс плагина
 *
 * Этот класс является центральной точкой управления всем плагином.
 * Он отвечает за инициализацию, активацию, деактивацию и удаление плагина.
 *
 * @since 1.0.0
 */
class Plugin
{
    /**
     * Экземпляр класса базы данных
     *
     * @var Database
     */
    private $database;

    /**
     * Конструктор класса
     */
    public function __construct()
    {
        // Инициализация свойств
        $this->database = new Database();
    }

    /**
     * Запускает плагин
     *
     * Инициализирует все компоненты и регистрирует хуки
     *
     * @return void
     */
    public function run()
    {
        // Проверка и создание таблиц базы данных
        $this->database->check_and_create_tables();

        // Загрузка текстового домена для переводов
        $this->load_textdomain();

        // Инициализация компонентов
        $this->init_admin();
        $this->init_frontend();
        $this->register_post_types();
        $this->register_taxonomies();

        // Регистрация хуков
        $this->register_hooks();

        // Установка планировщика задач
        $this->setup_scheduler();
    }

    /**
     * Загружает текстовый домен для переводов
     *
     * @return void
     */
    private function load_textdomain()
    {
        load_plugin_textdomain(
            'sun-apartament',
            false,
            dirname(plugin_basename(SUN_APARTAMENT_PATH)) . '/languages/'
        );
    }

    /**
     * Инициализирует компоненты административной части
     *
     * @return void
     */
    private function init_admin()
    {
        if (!is_admin()) {
            return;
        }

        // Инициализация меню в админке
        $admin_menu = new \Sun\Apartament\Admin\Menu();
        $admin_menu->register();

        // Инициализация метабоксов
        // Инициализация метабоксов
        $amenities_metabox = new \Sun\Apartament\Admin\Metaboxes\AmenitiesMetabox();
        $amenities_metabox->register();

        $gallery_metabox = new \Sun\Apartament\Admin\Metaboxes\GalleryMetabox();
        $gallery_metabox->register();

        $price_metabox = new \Sun\Apartament\Admin\Metaboxes\PriceMetabox();
        $price_metabox->register();

        // Инициализация ассетов для админки
        $admin_assets = new \Sun\Apartament\Frontend\Assets();
        $admin_assets->register_admin_assets();

        // Инициализация страниц админки
        $dashboard = new \Sun\Apartament\Admin\Dashboard();
        $dashboard->register();

        $calendar = new \Sun\Apartament\Admin\Calendar();
        $calendar->register();

        $tools = new \Sun\Apartament\Admin\Tools();
        $tools->register();
    }

    /**
     * Инициализирует компоненты фронтенда
     *
     * @return void
     */
    private function init_frontend()
    {
        // Инициализация шорткодов
        $apartament_shortcode = new \Sun\Apartament\Frontend\Shortcodes\ApartamentShortcode();
        $apartament_shortcode->register();

        $booking_form_shortcode = new \Sun\Apartament\Frontend\Shortcodes\BookingFormShortcode();
        $booking_form_shortcode->register();

        // Инициализация ассетов для фронтенда
        $assets = new \Sun\Apartament\Frontend\Assets();
        $assets->register_frontend_assets();

        // Инициализация сервиса шаблонов
        $template_service = new \Sun\Apartament\Services\TemplateService();
        add_filter('template_include', [$template_service, 'template_include']);



        $search_results_page = new \Sun\Apartament\Frontend\Pages\SearchResultsPage();
        $search_results_page->register();
        $booking_page = new \Sun\Apartament\Frontend\Pages\BookingPage();
        $booking_page->register();

        $booking_info_service = new \Sun\Apartament\Services\BookingInfoService();

        // Инициализация сервиса шаблонов
        $template_service = new \Sun\Apartament\Services\TemplateService();
        add_filter('template_include', [$template_service, 'template_include']);

        // Инициализация AJAX-обработчиков
        // $ajax_handler = new \Sun\Apartament\Frontend\AjaxHandler();
        // $ajax_handler->register();
    }

    /**
     * Регистрирует пользовательские типы записей
     *
     * @return void
     */
    private function register_post_types()
    {
        // Регистрация типа записи "Апартамент"
        $apartament_post_type = new \Sun\Apartament\Core\PostTypes\ApartamentPostType();
        $apartament_post_type->register();

        // Регистрация типа записи "Бронирование"
        $booking_post_type = new \Sun\Apartament\Core\PostTypes\BookingPostType();
        $booking_post_type->register();
    }

    /**
     * Регистрирует таксономии
     *
     * @return void
     */
    private function register_taxonomies()
    {
        // Регистрация таксономии "Тип апартамента"
        $apartament_type_taxonomy = new \Sun\Apartament\Core\Taxonomies\ApartamentTypeTaxonomy();
        $apartament_type_taxonomy->register();
    }

    /**
     * Регистрирует хуки
     *
     * @return void
     */
    private function register_hooks()
    {
        // Регистрация общих хуков
        add_action('init', [$this, 'register_post_statuses']);

        // Добавление шорткодов в визуальный редактор
        add_action('admin_init', function () {
            if (current_user_can('edit_posts') && current_user_can('edit_pages')) {
                if (get_user_option('rich_editing') == 'true') {
                    add_filter('mce_buttons', function ($buttons) {
                        array_push($buttons, 'separator', 'sun_apartament_shortcodes');
                        return $buttons;
                    });

                    add_filter('mce_external_plugins', function ($plugin_array) {
                        $plugin_array['sun_apartament_shortcodes'] = SUN_APARTAMENT_URL . 'assets/js/admin/shortcodes-button.js';
                        return $plugin_array;
                    });
                }
            }
        });

        // Сохранение версии плагина в опциях
        if (get_option('sun_apartament_version') !== SUN_APARTAMENT_VERSION) {
            update_option('sun_apartament_version', SUN_APARTAMENT_VERSION);
        }
    }

    /**
     * Настраивает планировщик задач
     *
     * @return void
     */
    private function setup_scheduler()
    {
        // Добавляем ежедневную задачу очистки
        if (!wp_next_scheduled('sun_apartament_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'sun_apartament_daily_cleanup');
        }

        // Добавляем обработчик для ежедневной задачи
        add_action('sun_apartament_daily_cleanup', [$this, 'daily_cleanup']);

        // Задача проверки истекших бронирований
        if (!wp_next_scheduled('sun_apartament_check_expired_bookings')) {
            wp_schedule_event(time(), 'hourly', 'sun_apartament_check_expired_bookings');
        }

        // Обработчик для проверки истекших бронирований
        add_action('sun_apartament_check_expired_bookings', [$this, 'check_expired_bookings']);
    }

    /**
     * Ежедневная задача очистки
     *
     * @return void
     */
    public function daily_cleanup()
    {
        // Очистка логов старше 30 дней
        $log_service = new \Sun\Apartament\Services\LogService();
        $log_service->cleanup_old_logs(30);

        // Обновление статусов завершенных бронирований
        $booking_service = new \Sun\Apartament\Services\BookingService();
        $booking_service->update_completed_bookings();
    }

    /**
     * Проверка истекших бронирований
     *
     * @return void
     */
    public function check_expired_bookings()
    {
        $booking_service = new \Sun\Apartament\Services\BookingService();
        $booking_service->check_expired_pending_bookings();
    }

    /**
     * Регистрирует пользовательские статусы записей
     *
     * @return void
     */
    public function register_post_statuses()
    {
        // Регистрация статусов для бронирований
        register_post_status('pending', [
            'label' => _x('Ожидает подтверждения', 'Status for bookings', 'sun-apartament'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Ожидает подтверждения <span class="count">(%s)</span>', 'Ожидает подтверждения <span class="count">(%s)</span>', 'sun-apartament'),
        ]);

        register_post_status('confirmed', [
            'label' => _x('Подтверждено', 'Status for bookings', 'sun-apartament'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Подтверждено <span class="count">(%s)</span>', 'Подтверждено <span class="count">(%s)</span>', 'sun-apartament'),
        ]);

        register_post_status('cancelled', [
            'label' => _x('Отменено', 'Status for bookings', 'sun-apartament'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Отменено <span class="count">(%s)</span>', 'Отменено <span class="count">(%s)</span>', 'sun-apartament'),
        ]);

        register_post_status('completed', [
            'label' => _x('Завершено', 'Status for bookings', 'sun-apartament'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Завершено <span class="count">(%s)</span>', 'Завершено <span class="count">(%s)</span>', 'sun-apartament'),
        ]);
    }

    /**
     * Активация плагина
     * 
     * Метод вызывается при активации плагина.
     *
     * @static
     * @return void
     */
    public static function activate()
    {
        // Создание таблиц БД
        $database = new Database();
        $database->create_tables();

        // Создание необходимых страниц
        self::create_pages();

        // Настройка ролей и прав доступа
        self::setup_roles_and_capabilities();

        // Сохранение версии плагина
        update_option('sun_apartament_version', SUN_APARTAMENT_VERSION);

        // Очистка кэша перезаписи
        flush_rewrite_rules();
    }

    /**
     * Создает необходимые страницы для работы плагина
     *
     * @static
     * @return void
     */
    private static function create_pages()
    {
        $pages = [
            'booking' => [
                'title' => 'Бронирование',
                'content' => '<!-- wp:shortcode -->[sunapartament_booking_form]<!-- /wp:shortcode -->'
            ],
            'results' => [
                'title' => 'Результаты поиска',
                'content' => '<!-- wp:paragraph -->Результаты поиска свободных апартаментов<!-- /wp:paragraph -->'
            ]
        ];

        foreach ($pages as $slug => $page_data) {
            // Проверяем, существует ли уже страница с таким slug
            $page_exists = get_page_by_path($slug);

            if (!$page_exists) {
                // Создаем новую страницу
                wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'comment_status' => 'closed'
                ]);
            }
        }
    }

    /**
     * Настраивает роли и права доступа
     *
     * @static
     * @return void
     */
    private static function setup_roles_and_capabilities()
    {
        // Получаем роль администратора
        $admin_role = get_role('administrator');

        if ($admin_role) {
            // Добавляем специальные права для работы с апартаментами
            $admin_role->add_cap('manage_apartaments');
            $admin_role->add_cap('manage_bookings');
        }

        // Добавляем пользовательскую роль менеджера апартаментов
        add_role('apartament_manager', 'Менеджер апартаментов', [
            'read' => true,
            'edit_posts' => true,
            'manage_apartaments' => true,
            'manage_bookings' => true,
            'edit_apartament' => true,
            'edit_apartaments' => true,
            'publish_apartaments' => true,
            'read_apartament' => true,
            'delete_apartament' => true,
            'edit_published_apartaments' => true,
            'edit_booking' => true,
            'edit_bookings' => true,
            'read_booking' => true,
        ]);
    }

    /**
     * Деактивация плагина
     * 
     * Метод вызывается при деактивации плагина.
     *
     * @static
     * @return void
     */
    public static function deactivate()
    {
        // Очищаем расписание для отложенных задач
        wp_clear_scheduled_hook('sun_apartament_daily_cleanup');
        wp_clear_scheduled_hook('sun_apartament_check_expired_bookings');

        // Очистка кэша перезаписи
        flush_rewrite_rules();
    }

    /**
     * Удаление плагина
     * 
     * Метод вызывается при удалении плагина.
     *
     * @static
     * @return void
     */
    public static function uninstall()
    {
        // Проверяем, включена ли опция сохранения данных при удалении
        $keep_data = get_option('sun_apartament_keep_data', 'no');

        if ($keep_data !== 'yes') {
            // Удаление таблиц БД
            $database = new Database();
            $database->drop_tables();

            // Удаление пользовательских типов записей и их данных
            $apartaments = get_posts([
                'post_type' => 'apartament',
                'numberposts' => -1,
                'post_status' => 'any'
            ]);

            foreach ($apartaments as $apartament) {
                wp_delete_post($apartament->ID, true);
            }

            $bookings = get_posts([
                'post_type' => 'sun_booking',
                'numberposts' => -1,
                'post_status' => 'any'
            ]);

            foreach ($bookings as $booking) {
                wp_delete_post($booking->ID, true);
            }

            // Удаление страниц, созданных плагином
            $pages_to_delete = ['booking', 'results'];

            foreach ($pages_to_delete as $page_slug) {
                $page = get_page_by_path($page_slug);
                if ($page) {
                    wp_delete_post($page->ID, true);
                }
            }

            // Удаление опций плагина
            delete_option('sun_apartament_version');
            delete_option('sun_apartament_settings');
            delete_option('sun_apartament_keep_data');

            // Удаление всех опций с префиксом sun_booking_
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'sun_booking_%'");

            // Удаление ролей и прав доступа
            $admin_role = get_role('administrator');
            if ($admin_role) {
                $admin_role->remove_cap('manage_apartaments');
                $admin_role->remove_cap('manage_bookings');
            }

            // Удаление пользовательской роли
            remove_role('apartament_manager');
        }
    }
}