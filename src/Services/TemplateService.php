<?php
namespace Sun\Apartament\Services;

/**
 * Сервис для работы с шаблонами плагина
 *
 * @since 1.0.0
 */
class TemplateService extends AbstractService {
    /**
     * Регистрирует сервис
     *
     * @return void
     */
    public function register() {
        add_filter('template_include', [$this, 'template_include']);
    }

    /**
     * Загружает шаблон из плагина или темы
     *
     * @param string $template_name Имя шаблона
     * @param array $args Аргументы для шаблона
     * @param string $template_path Путь к шаблонам в теме
     * @param string $default_path Путь к шаблонам по умолчанию
     * @return void
     */
    public function get_template_part($template_name, $args = [], $template_path = '', $default_path = '') {
        if (!empty($args) && is_array($args)) {
            extract($args);
        }
        
        $template_path = $template_path ? $template_path : 'sun-apartament';
        $default_path = $default_path ? $default_path : SUN_APARTAMENT_PATH . 'templates/';
        
        // Смотрим сначала в директории темы
        $template = locate_template([
            trailingslashit($template_path) . $template_name . '.php',
        ]);
        
        // Если шаблон не найден в теме, используем шаблон из плагина
        if (!$template) {
            $template = $default_path . $template_name . '.php';
        }
        
        // Проверяем существование файла
        if (file_exists($template)) {
            include $template;
        }
    }
    
    /**
     * Подключает шаблон с возвратом результата как строки
     *
     * @param string $template_name Имя шаблона
     * @param array $args Аргументы для шаблона
     * @return string Результат обработки шаблона
     */
    public function get_template_html($template_name, $args = []) {
        ob_start();
        $this->get_template_part($template_name, $args);
        return ob_get_clean();
    }
    
    /**
     * Проверяет и загружает шаблон страницы
     *
     * @param string $template Путь к файлу шаблона
     * @return string Путь к файлу шаблона
     */
    public function template_include($template) {
        global $post;
        
        // Проверяем тип записи
        if (is_singular('apartament')) {
            // Определяем какой шаблон использовать
            $template_files = [
                'single-apartament.php',
                'sun-apartament/single-apartament.php',
            ];
            
            // Проверяем, является ли это запросом с результатами поиска
            if (isset($_GET['source']) && $_GET['source'] === 'results') {
                $template_files = [
                    'single-apartament-results.php',
                    'sun-apartament/single-apartament-results.php',
                ];
            }
            
            // Ищем шаблон в теме
            $theme_template = locate_template($template_files);
            
            if ($theme_template) {
                return $theme_template;
            }
            
            // Используем шаблон из плагина
            $plugin_template = SUN_APARTAMENT_PATH . 'templates/' . $template_files[0];
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_post_type_archive('apartament')) {
            // Шаблон для архива апартаментов
            $template_files = [
                'archive-apartament.php',
                'sun-apartament/archive-apartament.php',
            ];
            
            $theme_template = locate_template($template_files);
            
            if ($theme_template) {
                return $theme_template;
            }
            
            $plugin_template = SUN_APARTAMENT_PATH . 'templates/' . $template_files[0];
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        } elseif (is_page()) {
            // Проверяем специальные страницы
            $page_slug = $post->post_name;
            
            if ($page_slug === 'booking' || $page_slug === 'results') {
                $template_file = 'page-' . $page_slug . '.php';
                $theme_template = locate_template([
                    $template_file,
                    'sun-apartament/' . $template_file,
                ]);
                
                if ($theme_template) {
                    return $theme_template;
                }
                
                $plugin_template = SUN_APARTAMENT_PATH . 'templates/' . $template_file;
                if (file_exists($plugin_template)) {
                    return $plugin_template;
                }
            }
        }
        
        return $template;
    }

    /**
     * Проверяет, находится ли дата в высоком сезоне
     * 
     * @param string $date Дата в формате Y-m-d
     * @return bool Результат проверки
     */
    public function is_high_season_date($date) {
        $price_service = ServiceManager::get_instance()->get_service(PriceService::class);
        if (!$price_service) {
            $price_service = new PriceService();
        }
        
        if (method_exists($price_service, 'is_high_season_date')) {
            return $price_service->is_high_season_date($date);
        }
        
        $this->log('Метод is_high_season_date не найден в PriceService', 'warning');
        return false;
    }
}