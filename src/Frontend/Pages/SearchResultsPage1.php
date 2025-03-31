<?php
namespace Sun\Apartament\Frontend\Pages;

use Sun\Apartament\Services\TemplateService;

/**
 * Класс для страницы результатов поиска
 *
 * @since 1.0.0
 */
class SearchResultsPage {
    /**
     * Экземпляр сервиса шаблонов
     *
     * @var TemplateService
     */
    private $template_service;
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->template_service = new TemplateService();
    }
    
    /**
     * Регистрация страницы результатов
     *
     * @return void
     */
    public function register() {
        // Добавляем фильтр для перехвата шаблона WordPress
        add_filter('template_include', [$this, 'load_results_template']);
    }
    
    /**
     * Загрузка шаблона страницы результатов
     *
     * @param string $template Путь к файлу шаблона
     * @return string Путь к файлу шаблона
     */
    public function load_results_template($template) {
        // Проверяем, присутствует ли параметр 'sunapartament_results' в URL 
        if (isset($_GET['sunapartament_results'])) {
            // Путь к файлу шаблона результатов
            $template_file = SUN_APARTAMENT_PATH . 'templates/pages/search-results.php';
            
            // Проверяем наличие шаблона
            if (file_exists($template_file)) {
                return $template_file;
            }
        }
        
        return $template;
    }
}