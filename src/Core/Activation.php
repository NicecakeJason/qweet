<?php
namespace Sun\Apartament\Core;

/**
 * Класс для обработки активации плагина
 *
 * @since 1.0.0
 */
class Activation {
    /**
     * Обрабатывает активацию плагина
     *
     * @return void
     */
    public static function activate() {
        // Создаем таблицы базы данных
        $database = new Database();
        $database->create_tables();
        
        // Создаем необходимые страницы, если их нет
        self::create_pages();
        
        // Устанавливаем роли и права доступа
        self::setup_roles_and_capabilities();
        
        // Сохраняем версию плагина
        update_option('sun_apartament_version', SUN_APARTAMENT_VERSION);
        
        // Очищаем кэш перезаписи
        flush_rewrite_rules();
    }
    
    /**
     * Создает необходимые страницы для работы плагина
     *
     * @return void
     */
    private static function create_pages() {
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
     * @return void
     */
    private static function setup_roles_and_capabilities() {
        // Получаем роль администратора
        $admin_role = get_role('administrator');
        
        if ($admin_role) {
            // Добавляем специальные права для работы с апартаментами
            $admin_role->add_cap('manage_apartaments');
            $admin_role->add_cap('manage_bookings');
        }
        
        // При необходимости можно добавить пользовательскую роль
        // add_role('apartament_manager', 'Менеджер апартаментов', [
        //     'read' => true,
        //     'edit_posts' => true,
        //     'manage_apartaments' => true,
        //     'manage_bookings' => true,
        // ]);
    }
}