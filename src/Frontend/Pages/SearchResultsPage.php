<?php
namespace Sun\Apartament\Frontend\Pages;

use Sun\Apartament\Services\TemplateService;
use Sun\Apartament\Services\BookingService;
use Sun\Apartament\Services\PriceService;

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
     * Экземпляр сервиса бронирований
     *
     * @var BookingService
     */
    private $booking_service;
    
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
        $this->booking_service = new BookingService();
        $this->price_service = new PriceService();
    }
    
    /**
     * Регистрация страницы результатов
     *
     * @return void
     */
    public function register() {
        // Добавляем фильтр для перехвата шаблона WordPress
        add_filter('template_include', [$this, 'load_results_template']);
        
        // Обработка отправки формы бронирования
        add_action('template_redirect', [$this, 'process_booking_form']);
        
        // Добавление переменных в шаблон результатов
        add_action('sun_apartament_before_search_results', [$this, 'add_template_vars']);
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
        
        // Проверяем, что это страница результатов и параметры бронирования есть
        global $post;
        if (is_page() && $post->post_name === 'results') {
            $has_search_params = isset($_GET['checkin_date']) && 
                                isset($_GET['checkout_date']) && 
                                (isset($_GET['guest_count']) || isset($_GET['children_count']));
            
            if ($has_search_params) {
                // Если есть параметр apartament_id, значит это страница бронирования конкретного апартамента
                if (isset($_GET['apartament_id'])) {
                    // Пытаемся найти шаблон в теме
                    $theme_template = locate_template([
                        'templates/page-booking.php',
                        'sun-apartament/page-booking.php',
                    ]);
                    
                    if ($theme_template) {
                        return $theme_template;
                    }
                    
                    // Если в теме нет шаблона, используем шаблон из плагина
                    $plugin_template = SUN_APARTAMENT_PATH . 'templates/page-booking.php';
                    if (file_exists($plugin_template)) {
                        return $plugin_template;
                    }
                }
                
                // Путь к файлу шаблона результатов поиска
                $template_file = SUN_APARTAMENT_PATH . 'templates/search-results.php';
                
                // Проверяем наличие шаблона
                if (file_exists($template_file)) {
                    return $template_file;
                }
            }
        }
        
        return $template;
    }
    
    /**
     * Добавляет переменные в шаблон перед рендерингом результатов
     *
     * @return void
     */
    public function add_template_vars() {
        global $sun_apartament_template_vars;
        
        if (!is_array($sun_apartament_template_vars)) {
            $sun_apartament_template_vars = [];
        }
        
        // Добавляем функцию для склонения слова "ночь"
        if (!function_exists('pluralize_nights')) {
            function pluralize_nights($number) {
                $forms = array('ночь', 'ночи', 'ночей');
                $mod10 = $number % 10;
                $mod100 = $number % 100;

                if ($mod10 == 1 && $mod100 != 11) {
                    return $forms[0];
                } elseif ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
                    return $forms[1];
                } else {
                    return $forms[2];
                }
            }
        }
        
        // Добавляем экземпляр сервиса цен
        $sun_apartament_template_vars['price_service'] = $this->price_service;
    }
    
    /**
     * Обрабатывает отправку формы бронирования
     *
     * @return void
     */
    public function process_booking_form() {
        // Проверяем, является ли это отправкой формы бронирования
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['apartament_id'])) {
            return;
        }
        
        // Проверяем, что это страница бронирования или результатов поиска
        global $post;
        if (!is_page() || !in_array($post->post_name, ['booking', 'results'])) {
            return;
        }
        
        // Проверяем наличие nonce
        if (!isset($_POST['booking_nonce']) || !wp_verify_nonce($_POST['booking_nonce'], 'booking_form')) {
            wp_die('Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.');
        }
        
        // Проверяем, что пользователь подтвердил условия
        if (!isset($_POST['terms_accepted'])) {
            // Редирект обратно с параметром ошибки
            wp_redirect(add_query_arg('booking_error', 'terms_not_accepted', $_SERVER['HTTP_REFERER']));
            exit;
        }
        
        // Подготовка данных бронирования
        $booking_data = [
            'apartament_id' => intval($_POST['apartament_id']),
            'checkin_date' => sanitize_text_field($_POST['checkin_date']),
            'checkout_date' => sanitize_text_field($_POST['checkout_date']),
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'middle_name' => isset($_POST['middle_name']) ? sanitize_text_field($_POST['middle_name']) : '',
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'guest_count' => intval($_POST['guest_count'] ?? 1),
            'children_count' => intval($_POST['children_count'] ?? 0),
            'total_price' => floatval($_POST['total_price']),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? 'card'),
            'terms_accepted' => 1,
            'status' => 'confirmed'
        ];
        
        // Передаем данные в сервис бронирований для сохранения
        $booking_id = $this->booking_service->create_booking($booking_data);
        
        if ($booking_id) {
            // Редирект на страницу подтверждения с параметрами
            wp_redirect(add_query_arg([
                'booking_id' => $booking_id,
                'status' => 'success'
            ], get_permalink(get_page_by_path('booking-confirmation'))));
            exit;
        } else {
            // Редирект обратно с параметром ошибки
            wp_redirect(add_query_arg('booking_error', 'save_failed', $_SERVER['HTTP_REFERER']));
            exit;
        }
    }
}