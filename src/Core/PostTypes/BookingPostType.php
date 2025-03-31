<?php
namespace Sun\Apartament\Core\PostTypes;

/**
 * Класс для регистрации типа записи "Бронирование"
 *
 * @since 1.0.0
 */
class BookingPostType {
    /**
     * Регистрирует тип записи
     *
     * @return void
     */
    public function register() {
        add_action('init', [$this, 'register_post_type']);
    }
    
    /**
     * Регистрирует пользовательский тип записи "Бронирование"
     *
     * @return void
     */
    public function register_post_type() {
        $labels = [
            'name'                  => esc_html__('Бронирования', 'sun-apartament'),
            'singular_name'         => esc_html__('Бронирование', 'sun-apartament'),
            'menu_name'             => esc_html__('Бронирования', 'sun-apartament'),
            'name_admin_bar'        => esc_html__('Бронирование', 'sun-apartament'),
            'add_new'               => esc_html__('Добавить новое', 'sun-apartament'),
            'add_new_item'          => esc_html__('Добавить новое бронирование', 'sun-apartament'),
            'new_item'              => esc_html__('Новое бронирование', 'sun-apartament'),
            'edit_item'             => esc_html__('Редактировать бронирование', 'sun-apartament'),
            'view_item'             => esc_html__('Просмотреть бронирование', 'sun-apartament'),
            'all_items'             => esc_html__('Все бронирования', 'sun-apartament'),
            'search_items'          => esc_html__('Поиск бронирований', 'sun-apartament'),
            'not_found'             => esc_html__('Бронирования не найдены', 'sun-apartament'),
            'not_found_in_trash'    => esc_html__('В корзине бронирования не найдены', 'sun-apartament'),
        ];

        $args = [
            'labels'                => $labels,
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => true,
            'show_in_menu'          => false, // Будет отображаться в подменю плагина
            'query_var'             => true,
            'rewrite'               => ['slug' => 'bookings'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => ['title', 'custom-fields'],
            'menu_icon'             => 'dashicons-calendar-alt',
        ];

        register_post_type('sun_booking', $args);
    }
}