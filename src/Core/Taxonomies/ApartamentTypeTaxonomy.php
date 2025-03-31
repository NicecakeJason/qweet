<?php
namespace Sun\Apartament\Core\Taxonomies;

/**
 * Класс для регистрации таксономии "Тип апартамента"
 *
 * @since 1.0.0
 */
class ApartamentTypeTaxonomy {
    /**
     * Регистрирует таксономию
     *
     * @return void
     */
    public function register() {
        add_action('init', [$this, 'register_taxonomy']);
    }
    
    /**
     * Регистрирует таксономию "Тип апартамента"
     *
     * @return void
     */
    public function register_taxonomy() {
        $labels = [
            'name'              => esc_html_x('Типы апартаментов', 'taxonomy general name', 'sun-apartament'),
            'singular_name'     => esc_html_x('Тип апартамента', 'taxonomy singular name', 'sun-apartament'),
            'search_items'      => esc_html__('Поиск типов', 'sun-apartament'),
            'all_items'         => esc_html__('Все типы', 'sun-apartament'),
            'parent_item'       => esc_html__('Родительский тип', 'sun-apartament'),
            'parent_item_colon' => esc_html__('Родительский тип:', 'sun-apartament'),
            'edit_item'         => esc_html__('Редактировать тип', 'sun-apartament'),
            'update_item'       => esc_html__('Обновить тип', 'sun-apartament'),
            'add_new_item'      => esc_html__('Добавить новый тип', 'sun-apartament'),
            'new_item_name'     => esc_html__('Добавить новое имя', 'sun-apartament'),
            'menu_name'         => esc_html__('Тип апартамента', 'sun-apartament'),
        ];
        
        $args = [
            'hierarchical'       => true,
            'labels'             => $labels,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'query_var'          => true,
            'rewrite'            => ['slug' => 'apartaments/type'],
        ];
        
        register_taxonomy('apartament-type', ['apartament'], $args);
    }
}