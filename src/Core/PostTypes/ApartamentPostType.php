<?php
namespace Sun\Apartament\Core\PostTypes;

/**
 * Класс для регистрации типа записи "Апартамент"
 *
 * @since 1.0.0
 */
class ApartamentPostType {
    /**
     * Регистрирует тип записи
     *
     * @return void
     */
    public function register() {
        add_action('init', [$this, 'register_post_type']);
    }
    
    /**
     * Регистрирует пользовательский тип записи "Апартамент"
     *
     * @return void
     */
    public function register_post_type() {
        $labels = [
            'name'               => __('Апартаменты', 'sun-apartament'),
            'singular_name'      => __('Апартамент', 'sun-apartament'),
            'menu_name'          => __('Апартаменты', 'sun-apartament'),
            'name_admin_bar'     => __('Апартамент', 'sun-apartament'),
            'add_new'            => __('Добавить новый', 'sun-apartament'),
            'add_new_item'       => __('Добавить новый апартамент', 'sun-apartament'),
            'new_item'           => __('Новый апартамент', 'sun-apartament'),
            'edit_item'          => __('Редактировать апартамент', 'sun-apartament'),
            'view_item'          => __('Просмотреть апартамент', 'sun-apartament'),
            'all_items'          => __('Все апартаменты', 'sun-apartament'),
            'search_items'       => __('Искать апартаменты', 'sun-apartament'),
            'parent_item_colon'  => __('Родительский апартамент:', 'sun-apartament'),
            'not_found'          => __('Апартаменты не найдены.', 'sun-apartament'),
            'not_found_in_trash' => __('Апартаменты не найдены в корзине.', 'sun-apartament'),
        ];
    
        $args = [
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => [
                'slug' => 'apartaments', // ИЗМЕНЕНО: с 'apartament' на 'apartaments'
                'with_front' => true
            ],
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-building',
            'supports'           => ['title', 'editor', 'thumbnail', 'excerpt'],
        ];
    
        register_post_type('apartament', $args);
    
    }
}