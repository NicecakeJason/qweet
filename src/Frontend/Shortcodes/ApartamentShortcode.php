<?php
namespace Sun\Apartament\Frontend\Shortcodes;

use Sun\Apartament\Core\Apartament;
use Sun\Apartament\Services\TemplateService;
use Sun\Apartament\Services\PriceService;

/**
 * Класс для шорткода отображения апартаментов
 *
 * @since 1.0.0
 */
class ApartamentShortcode {
    /**
     * Экземпляр сервиса шаблонов
     *
     * @var TemplateService
     */
    private $template_service;
    
    /**
     * Экземпляр сервиса цен
     *
     * @var PriceService
     */
    private $price_service;
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->template_service = new TemplateService();
        $this->price_service = new PriceService();
    }
    
    /**
     * Регистрирует шорткод
     *
     * @return void
     */
    public function register() {
        add_shortcode('sunapartament_properties', [$this, 'render_shortcode']);
    }
    
    /**
     * Отображает шорткод
     *
     * @param array $atts Атрибуты шорткода
     * @return string HTML код шорткода
     */
    public function render_shortcode($atts) {
        // Атрибуты шорткода
        $atts = shortcode_atts([
            'posts_per_page' => 5, // Количество постов для вывода
            'category' => '', // Слаг категории (термина таксономии)
            'orderby' => 'title', // Сортировка
            'order' => 'ASC' // Порядок сортировки
        ], $atts, 'sunapartament_properties');
        
        // Аргументы для WP_Query
        $args = [
            'post_type' => 'apartament', // Указываем custom post type
            'posts_per_page' => intval($atts['posts_per_page']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
        ];
        
        // Если указана категория, добавляем фильтрацию по термину таксономии
        if (!empty($atts['category'])) {
            $args['tax_query'] = [
                [
                    'taxonomy' => 'apartament-type', // Указываем таксономию
                    'field' => 'slug', // Используем слаг термина
                    'terms' => $atts['category'], // Слаг категории из шорткода
                ],
            ];
        }
        
        // Запрос постов
        $query = new \WP_Query($args);
        
        // Буферизация вывода
        ob_start();
        
        // Проверяем, есть ли посты
        if ($query->have_posts()) {
            // Группируем посты по рубрикам (терминам таксономии)
            $grouped_posts = [];
            
            // Получаем текущую дату в формате Y-m-d
            $current_date = date('Y-m-d');
            
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Получаем термины таксономии для текущего поста
                $terms = get_the_terms($post_id, 'apartament-type');
                
                if ($terms && !is_wp_error($terms)) {
                    // Если указана категория, фильтруем термины по этой категории
                    if (!empty($atts['category'])) {
                        $terms = array_filter($terms, function ($term) use ($atts) {
                            return $term->slug === $atts['category'];
                        });
                    }
                    
                    // Добавляем пост в каждую из его рубрик
                    foreach ($terms as $term) {
                        if (!isset($grouped_posts[$term->term_id])) {
                            $grouped_posts[$term->term_id] = [
                                'term' => $term,
                                'posts' => [],
                            ];
                        }
                        
                        // Проверяем, был ли уже добавлен этот пост в данную рубрику
                        if (!in_array($post_id, $grouped_posts[$term->term_id]['posts'])) {
                            $post_data = get_post($post_id);
                            
                            // Добавляем метаданные для поста
                            $post_data->meta = [
                                'gallery' => get_post_meta($post_id, 'sunapartament_gallery', true),
                                'square_footage' => get_post_meta($post_id, 'sunapartament_square_footage', true),
                                'guest_count' => get_post_meta($post_id, 'sunapartament_guest_count', true),
                                'floor_count' => get_post_meta($post_id, 'sunapartament_floor_count', true),
                                'square_footage_icon' => get_post_meta($post_id, 'sunapartament_square_footage_icon', true),
                                'guest_count_icon' => get_post_meta($post_id, 'sunapartament_guest_count_icon', true),
                                'floor_count_icon' => get_post_meta($post_id, 'sunapartament_floor_count_icon', true),
                                // Передаем текущую дату вторым параметром
                                'current_price' => $this->price_service->get_price_for_date($post_id, $current_date)
                            ];
                            
                            $grouped_posts[$term->term_id]['posts'][] = $post_data;
                        }
                    }
                }
            }
            
            // Сброс данных поста
            wp_reset_postdata();
            
            // Загружаем шаблон шорткода
            $this->template_service->get_template_part('shortcodes/apartament-list', ['grouped_posts' => $grouped_posts]);
        } else {
            // Если постов нет, выводим сообщение
            echo '<p>' . esc_html__('Нет доступных апартаментов.', 'sun-apartament') . '</p>';
        }
        
        return ob_get_clean();
    }
}