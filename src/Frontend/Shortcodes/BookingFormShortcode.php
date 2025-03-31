<?php
namespace Sun\Apartament\Frontend\Shortcodes;

use Sun\Apartament\Services\TemplateService;

/**
 * Класс для шорткода формы бронирования
 *
 * @since 1.0.0
 */
class BookingFormShortcode {
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
     * Регистрирует шорткод
     *
     * @return void
     */
    public function register() {
        // Удаляем любые существующие регистрации шорткода
        remove_shortcode('sunapartament_booking_form');
        
        // Регистрируем наш шорткод
        add_shortcode('sunapartament_booking_form', [$this, 'render_shortcode']);
        
        // Обработка отправки формы
        add_action('init', [$this, 'handle_booking_form']);
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
            'apartament_id' => 0, // ID апартамента (если 0, то форма поиска)
            'theme' => 'default', // Тема оформления
        ], $atts, 'sunapartament_booking_form');
        
        // Буферизация вывода
        ob_start();
        
        // Проверяем наличие директории шаблонов
        $templates_dir = SUN_APARTAMENT_PATH . 'templates/shortcodes/';
        if (!is_dir($templates_dir)) {
            // Создаем директорию, если она не существует
            wp_mkdir_p($templates_dir);
        }
        
        // Определяем тип страницы
        $is_singular = is_singular('apartament');
        $is_front_page = is_front_page() || is_home();
        $is_archive = is_archive() && !$is_singular;
        
        // Определяем какой шаблон загружать
        $template = 'booking-form';
        
        if ($is_singular && !empty($atts['apartament_id'])) {
            $template = 'booking-form-single';
        } elseif ($is_front_page || $is_archive) {
            $template = 'booking-form';  // Изменили на booking-form
        }
        
        // Проверяем наличие файла шаблона
        $template_file = $templates_dir . $template . '.php';
        
        
        
        // Загружаем шаблон формы
        $this->template_service->get_template_part('shortcodes/' . $template, ['atts' => $atts]);
        
        return ob_get_clean();
    }
    
    /**
     * Создает шаблон формы по умолчанию
     *
     * @param string $template_file Путь к файлу шаблона
     * @param string $template_type Тип шаблона
     */

     public function handle_booking_form() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sunapartament_booking_submit'])) {
            // Очищаем заголовки
            header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
            header("Pragma: no-cache");
            header("Expires: 0");
            
            // Проверяем nonce для безопасности
            if (!isset($_POST['booking_nonce']) || !wp_verify_nonce($_POST['booking_nonce'], 'sunapartament_booking_nonce')) {
                wp_die('Ошибка безопасности! Пожалуйста, обновите страницу и попробуйте снова.');
                return;
            }
            
            // Получаем данные формы
            $checkin_date = isset($_POST['checkin_date']) ? sanitize_text_field($_POST['checkin_date']) : '';
            $checkout_date = isset($_POST['checkout_date']) ? sanitize_text_field($_POST['checkout_date']) : '';
            $guest_count = isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 0;
            $children_count = isset($_POST['children_count']) ? intval($_POST['children_count']) : 0;
            
            // Проверка данных
            if (empty($checkin_date) || empty($checkout_date)) {
                wp_die('Пожалуйста, выберите даты заезда и выезда.');
                return;
            }
            
            // Проверка формата дат
            $checkin = \DateTime::createFromFormat('d.m.Y', $checkin_date);
            $checkout = \DateTime::createFromFormat('d.m.Y', $checkout_date);
            
            if ($checkin === false || $checkout === false) {
                wp_die('Неверный формат даты. Пожалуйста, используйте формат дд.мм.гггг');
                return;
            }
            
            // Проверка минимального срока
            $interval = $checkin->diff($checkout);
            if ($interval->days < 1) {
                wp_die('Минимальный срок бронирования - 1 ночь.');
                return;
            }
            
            // Проверка даты заезда
            if ($checkin < new \DateTime(date('d.m.Y'))) {
                wp_die('Дата заезда не может быть раньше текущей даты.');
                return;
            }
            
            // Если это поиск, перенаправляем на страницу результатов
            if (!isset($_POST['apartament_id']) || empty($_POST['apartament_id'])) {
                $results_page_url = add_query_arg([
                    'checkin_date' => $checkin_date,
                    'checkout_date' => $checkout_date,
                    'guest_count' => $guest_count,
                    'children_count' => $children_count,
                    'sunapartament_results' => 1, // Добавляем флаг для определения страницы результатов
                ], home_url('/'));
                
                wp_redirect($results_page_url);
                exit;
            }
            
            // Если это бронирование конкретного апартамента
            $apartament_id = intval($_POST['apartament_id']);
            
            // Перенаправляем на страницу оформления бронирования
            $booking_page_url = add_query_arg([
                'apartament_id' => $apartament_id,
                'checkin_date' => $checkin_date,
                'checkout_date' => $checkout_date,
                'guest_count' => $guest_count,
                'children_count' => $children_count,
            ], home_url('/booking'));
            
            wp_redirect($booking_page_url);
            exit;
        }
    }
}