<?php
namespace Sun\Apartament\Frontend\Pages;

use Sun\Apartament\Services\BookingService;
use Sun\Apartament\Services\PriceService;
use Sun\Apartament\Services\TemplateService;

/**
 * Класс страницы бронирования
 *
 * @since 1.0.0
 */
class BookingPage {
    
    /**
     * Сервис бронирования
     *
     * @var BookingService
     */
    private $booking_service;
    
    /**
     * Сервис цен
     *
     * @var PriceService
     */
    private $price_service;
    
    /**
     * Сервис шаблонов
     *
     * @var TemplateService
     */
    private $template_service;
    
    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->booking_service = new BookingService();
        $this->price_service = new PriceService();
        $this->template_service = new TemplateService();
    }
    
    /**
     * Регистрирует класс
     *
     * @return void
     */
    public function register() {
        add_action('template_redirect', [$this, 'process_booking_form']);
        add_filter('the_content', [$this, 'render_booking_page_content'], 20);
        add_filter('template_include', [$this, 'load_booking_template'], 99);
    }
    
 /**
 * Загружает шаблон страницы бронирования
 *
 * @param string $template Путь к текущему шаблону
 * @return string Путь к шаблону
 */
public function load_booking_template($template) {
   // Проверяем, что текущая страница - страница бронирования
   global $post;
   if (is_page() && $post->post_name === 'booking') {
       // Проверяем, есть ли необходимые параметры для формы бронирования
       $has_booking_params = isset($_GET['apartament_id']) && 
                            isset($_GET['checkin_date']) && 
                            isset($_GET['checkout_date']);
       
       if ($has_booking_params) {
           // Путь к файлу шаблона бронирования
           $template_file = SUN_APARTAMENT_PATH . 'templates/pages/page-booking.php';
           
           // Проверяем наличие шаблона
           if (file_exists($template_file)) {
               return $template_file;
           }
       } else {
           // Для страницы бронирования без параметров
           $default_template = SUN_APARTAMENT_PATH . 'templates/pages/booking-page.php';
           if (file_exists($default_template)) {
               return $default_template;
           }
       }
   }
   
   // Проверяем, если это страница подтверждения бронирования
   if (is_page() && $post->post_name === 'booking-confirmation') {
       if (isset($_GET['booking_id']) && isset($_GET['status'])) {
           $template_file = SUN_APARTAMENT_PATH . 'templates/pages/booking-confirmation.php';
           if (file_exists($template_file)) {
               return $template_file;
           }
       }
   }
   
   return $template;
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
        
        // Проверяем, что это страница бронирования
        global $post;
        if (!is_page() || !in_array($post->post_name, ['booking', 'results'])) {
            return;
        }
        
        // Проверяем nonce
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
        $booking_data = array(
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
        );
        
        // Генерация ID бронирования
        $booking_id = 'SA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
        $booking_data['booking_id'] = $booking_id;
        
        // Сохранение бронирования через сервис
        $result = $this->save_booking($booking_data);
        
        if ($result) {
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
    
    /**
     * Сохраняет бронирование 
     *
     * @param array $booking_data Данные бронирования
     * @return bool Результат операции
     */
    private function save_booking($booking_data) {
        global $wpdb;
        
        // Проверяем наличие таблиц
        $personal_data_table = $wpdb->prefix . 'sun_personal_data';
        $bookings_table = $wpdb->prefix . 'sun_bookings';
        
        $personal_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$personal_data_table'") === $personal_data_table;
        $bookings_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") === $bookings_table;
        
        // Если таблиц нет, создаем их
        if (!$personal_table_exists || !$bookings_table_exists) {
            $this->create_booking_tables();
            
            // Проверяем еще раз после создания
            $personal_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$personal_data_table'") === $personal_data_table;
            $bookings_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") === $bookings_table;
        }
        
        // Если таблицы существуют, сохраняем данные
        if ($personal_table_exists && $bookings_table_exists) {
            try {
                // Сохраняем данные в таблицу персональных данных
                $personal_result = $wpdb->insert(
                    $personal_data_table,
                    [
                        'first_name' => $booking_data['first_name'],
                        'last_name' => $booking_data['last_name'],
                        'middle_name' => $booking_data['middle_name'],
                        'email' => $booking_data['email'],
                        'phone' => $booking_data['phone'],
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%s', '%s', '%s', '%s', '%s']
                );
                
                if ($personal_result === false) {
                    error_log('Ошибка при добавлении персональных данных: ' . $wpdb->last_error);
                    return false;
                }
                
                $personal_data_id = $wpdb->insert_id;
                
                if (!$personal_data_id) {
                    error_log('Не удалось получить ID персональных данных');
                    return false;
                }
                
                // Сохраняем данные в таблицу бронирований
                $booking_result = $wpdb->insert(
                    $bookings_table,
                    [
                        'booking_id' => $booking_data['booking_id'],
                        'personal_data_id' => $personal_data_id,
                        'apartament_id' => $booking_data['apartament_id'],
                        'checkin_date' => date('Y-m-d', strtotime($booking_data['checkin_date'])),
                        'checkout_date' => date('Y-m-d', strtotime($booking_data['checkout_date'])),
                        'total_price' => $booking_data['total_price'],
                        'payment_method' => $booking_data['payment_method'],
                        'status' => $booking_data['status'],
                        'terms_accepted' => $booking_data['terms_accepted'],
                        'created_at' => current_time('mysql')
                    ],
                    ['%s', '%d', '%d', '%s', '%s', '%f', '%s', '%s', '%d', '%s']
                );
                
                if ($booking_result === false) {
                    error_log('Ошибка при добавлении бронирования: ' . $wpdb->last_error);
                    return false;
                }
                
                // Успешное сохранение в БД
                
                // Также сохраняем в пост для совместимости
                $this->save_booking_as_post($booking_data);
                
                // Отправляем уведомления
                $this->send_booking_notifications($booking_data);
                
                return true;
                
            } catch (\Exception $e) {
                error_log('Исключение при сохранении данных: ' . $e->getMessage());
                return false;
            }
        }
        
        // Сохраняем только в пост, если таблицы недоступны
        return $this->save_booking_as_post($booking_data);
    }
    
    /**
     * Сохраняет бронирование как запись WordPress
     *
     * @param array $booking_data Данные бронирования
     * @return bool Результат операции
     */
    private function save_booking_as_post($booking_data) {
        // Создаем запись с отключенными хуками
        $post_args = [
            'post_type' => 'sun_booking',
            'post_title' => $booking_data['booking_id'],
            'post_status' => $booking_data['status'],
            'post_author' => get_current_user_id(),
        ];
        
        // Отключаем запуск хука wp_insert_post
        add_filter('wp_insert_post_empty_content', '__return_false', 999);
        
        // Создаем запись
        $post_id = wp_insert_post($post_args, true);
        
        // Удаляем фильтр
        remove_filter('wp_insert_post_empty_content', '__return_false', 999);
        
        if (is_wp_error($post_id)) {
            error_log('Ошибка при создании бронирования в WordPress: ' . $post_id->get_error_message());
            return false;
        }
        
        // Устанавливаем метаполя вручную
        update_post_meta($post_id, '_booking_apartament_id', $booking_data['apartament_id']);
        update_post_meta($post_id, '_booking_first_name', $booking_data['first_name']);
        update_post_meta($post_id, '_booking_last_name', $booking_data['last_name']);
        update_post_meta($post_id, '_booking_middle_name', $booking_data['middle_name']);
        update_post_meta($post_id, '_booking_email', $booking_data['email']);
        update_post_meta($post_id, '_booking_phone', $booking_data['phone']);
        update_post_meta($post_id, '_booking_terms_accepted', $booking_data['terms_accepted']);
        update_post_meta($post_id, '_booking_guest_count', $booking_data['guest_count']);
        update_post_meta($post_id, '_booking_children_count', $booking_data['children_count']);
        
        // Форматируем даты в нужный формат d.m.Y
        update_post_meta($post_id, '_booking_checkin_date', date('d.m.Y', strtotime($booking_data['checkin_date'])));
        update_post_meta($post_id, '_booking_checkout_date', date('d.m.Y', strtotime($booking_data['checkout_date'])));
        
        update_post_meta($post_id, '_booking_total_price', $booking_data['total_price']);
        update_post_meta($post_id, '_booking_payment_method', $booking_data['payment_method']);
        update_post_meta($post_id, '_booking_created_at', current_time('mysql'));
        
        // Вручную добавляем даты недоступности
        $this->update_apartament_availability($booking_data['apartament_id'], $booking_data['checkin_date'], $booking_data['checkout_date'], $post_id);
        
        return true;
    }
    
    /**
     * Обновляет данные о доступности апартамента
     *
     * @param int $apartament_id ID апартамента
     * @param string $checkin_date Дата заезда
     * @param string $checkout_date Дата выезда
     * @param int $booking_id ID бронирования
     * @return void
     */
    private function update_apartament_availability($apartament_id, $checkin_date, $checkout_date, $booking_id) {
        // Для нового формата _apartament_booked_dates
        $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
        if (!is_array($booked_dates)) {
            $booked_dates = [];
        }
        
        $start = new \DateTime($checkin_date);
        $end = new \DateTime($checkout_date);
        $interval = new \DateInterval('P1D');
        $daterange = new \DatePeriod($start, $interval, $end);
        
        foreach ($daterange as $date) {
            $date_string = $date->format('Y-m-d');
            $booked_dates[$date_string] = $booking_id;
        }
        update_post_meta($apartament_id, '_apartament_booked_dates', $booked_dates);
        
        // Для старого формата _apartament_availability
        $existing_dates = get_post_meta($apartament_id, '_apartament_availability', true);
        $existing_dates = $existing_dates ? json_decode($existing_dates, true) : [];
        $dates_to_add = [];
        
        foreach ($daterange as $date) {
            $dates_to_add[] = $date->format('Y-m-d');
        }
        
        $updated_dates = array_unique(array_merge($existing_dates, $dates_to_add));
        update_post_meta($apartament_id, '_apartament_availability', json_encode(array_values($updated_dates)));
    }
    
    /**
     * Отправляет уведомления о бронировании
     *
     * @param array $booking_data Данные бронирования
     * @return array Результаты отправки
     */
    private function send_booking_notifications($booking_data) {
        $notification_results = array(
            'email_client' => false,
            'email_admin' => false
        );
        
        // Отправка письма клиенту
        $to = $booking_data['email'];
        $subject = 'Подтверждение бронирования №' . $booking_data['booking_id'];
        
        // Получаем шаблон письма
        $message = $this->get_client_email_template($booking_data);
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $notification_results['email_client'] = wp_mail($to, $subject, $message, $headers);
        
        // Отправка письма администратору
        $admin_email = get_option('admin_email');
        $admin_subject = 'Новое бронирование №' . $booking_data['booking_id'];
        
        $admin_message = $this->get_admin_email_template($booking_data);
        
        $notification_results['email_admin'] = wp_mail($admin_email, $admin_subject, $admin_message, $headers);
        
        return $notification_results;
    }
    
    /**
     * Возвращает шаблон письма для клиента
     *
     * @param array $booking_data Данные бронирования
     * @return string HTML-шаблон письма
     */
    private function get_client_email_template($booking_data) {
        // Подготовка данных для шаблона
        $apartament_title = get_the_title($booking_data['apartament_id']);
        $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
        $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
        $nights = (new \DateTime($booking_data['checkout_date']))->diff(new \DateTime($booking_data['checkin_date']))->days;
        
        // Получение шаблона
        ob_start();
        include SUN_APARTAMENT_PATH . 'templates/emails/booking-confirmation-client.php';
        return ob_get_clean();
    }
    
    /**
     * Возвращает шаблон письма для администратора
     *
     * @param array $booking_data Данные бронирования
     * @return string HTML-шаблон письма
     */
    private function get_admin_email_template($booking_data) {
        // Подготовка данных для шаблона
        $apartament_title = get_the_title($booking_data['apartament_id']);
        $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
        $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
        $nights = (new \DateTime($booking_data['checkout_date']))->diff(new \DateTime($booking_data['checkin_date']))->days;
        
        // Получение шаблона
        ob_start();
        include SUN_APARTAMENT_PATH . 'templates/emails/booking-confirmation-admin.php';
        return ob_get_clean();
    }
    
    /**
     * Создает таблицы базы данных для бронирований
     * 
     * @return void
     */
    private function create_booking_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Таблица персональных данных
        $personal_data_table = $wpdb->prefix . 'sun_personal_data';
        $sql_personal_data = "CREATE TABLE $personal_data_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            middle_name varchar(100) DEFAULT '',
            email varchar(100) NOT NULL,
            phone varchar(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // Таблица бронирований
        $bookings_table = $wpdb->prefix . 'sun_bookings';
        $sql_bookings = "CREATE TABLE $bookings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            booking_id varchar(20) NOT NULL,
            personal_data_id bigint(20) NOT NULL,
            apartament_id bigint(20) NOT NULL,
            checkin_date date NOT NULL,
            checkout_date date NOT NULL,
            total_price decimal(10,2) NOT NULL,
            payment_method varchar(20) NOT NULL,
            status varchar(20) NOT NULL,
            terms_accepted tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY booking_id (booking_id)
        ) $charset_collate;";
        
        // Необходимо для использования dbDelta()
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Создаем таблицы
        dbDelta($sql_personal_data);
        dbDelta($sql_bookings);
    }
    
    /**
     * Выводит содержимое страницы бронирования
     *
     * @param string $content Содержимое страницы
     * @return string Обновленное содержимое
     */
    public function render_booking_page_content($content) {
        global $post;
        
        // Проверяем, что это страница с шорткодом бронирования или результатов поиска
        if (!is_page() || !in_array($post->post_name, ['booking', 'results'])) {
            return $content;
        }
        
        // Проверяем наличие параметра booking_id в URL
        if (isset($_GET['booking_id']) && isset($_GET['status']) && $_GET['status'] === 'success') {
            // Получаем данные бронирования
            $booking_id = sanitize_text_field($_GET['booking_id']);
            
            // Рендерим страницу подтверждения
            ob_start();
            $this->render_booking_confirmation($booking_id);
            $booking_content = ob_get_clean();
            
            return $content . $booking_content;
        }
        
        // Проверяем наличие параметра apartament_id и дат в URL
        $apartament_id = isset($_GET['apartament_id']) ? intval($_GET['apartament_id']) : 0;
        $checkin_date = isset($_GET['checkin_date']) ? sanitize_text_field($_GET['checkin_date']) : '';
        $checkout_date = isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '';
        
        if ($apartament_id && $checkin_date && $checkout_date) {
            // Рендерим форму бронирования для выбранного апартамента
            ob_start();
            $this->render_booking_form($apartament_id, $checkin_date, $checkout_date);
            $booking_content = ob_get_clean();
            
            return $content . $booking_content;
        }
        
        return $content;
    }
    
    /**
     * Рендерит форму бронирования для выбранного апартамента
     *
     * @param int $apartament_id ID апартамента
     * @param string $checkin_date Дата заезда
     * @param string $checkout_date Дата выезда
     * @return void
     */
    public function render_booking_form($apartament_id, $checkin_date, $checkout_date) {
        // Получаем параметры из URL
        $guest_count = isset($_GET['guest_count']) ? intval($_GET['guest_count']) : 1;
        $children_count = isset($_GET['children_count']) ? intval($_GET['children_count']) : 0;
        
        // Получаем информацию об апартаменте
        $gallery_images = get_post_meta($apartament_id, 'sunapartament_gallery', true);
        $first_image_url = $gallery_images && is_array($gallery_images) && !empty($gallery_images) ? 
                   wp_get_attachment_image_src($gallery_images[0], 'large')[0] : '';
        $title = get_the_title($apartament_id);
        $square_footage = get_post_meta($apartament_id, 'sunapartament_square_footage', true);
        $guest_count_max = get_post_meta($apartament_id, 'sunapartament_guest_count', true);
        $floor_count = get_post_meta($apartament_id, 'sunapartament_floor_count', true);
        
        // Получаем пользовательские иконки для удобств
        $square_footage_icon = get_post_meta($apartament_id, 'sunapartament_square_footage_icon', true);
        $guest_count_icon = get_post_meta($apartament_id, 'sunapartament_guest_count_icon', true);
        $floor_count_icon = get_post_meta($apartament_id, 'sunapartament_floor_count_icon', true);
        
        // Расчет стоимости бронирования
        $period_prices_data = $this->price_service->get_prices_for_period($apartament_id, $checkin_date, $checkout_date);
        $nights_count = $period_prices_data['nights'];
        $total_price = $period_prices_data['total_price'];
        $daily_prices = $period_prices_data['daily_prices'];
        $final_price = $total_price;
        
        // Функция для склонения слова "ночь"
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
        
        // Проверяем наличие ошибки бронирования
        $booking_error = isset($_GET['booking_error']) ? sanitize_text_field($_GET['booking_error']) : '';
        
        // Подготовка данных для шаблона
        $template_data = [
            'apartament_id' => $apartament_id,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'guest_count' => $guest_count,
            'children_count' => $children_count,
            'first_image_url' => $first_image_url,
            'title' => $title,
            'square_footage' => $square_footage,
            'guest_count_max' => $guest_count_max,
            'floor_count' => $floor_count,
            'square_footage_icon' => $square_footage_icon,
            'guest_count_icon' => $guest_count_icon,
            'floor_count_icon' => $floor_count_icon,
            'nights_count' => $nights_count,
            'total_price' => $total_price,
            'daily_prices' => $daily_prices,
            'final_price' => $final_price,
            'booking_error' => $booking_error
        ];
        
        // Загружаем шаблон формы бронирования
        $this->template_service->get_template_part('booking-form', $template_data);
    }
    
    /**
     * Рендерит страницу подтверждения бронирования
     *
     * @param string $booking_id ID бронирования
     * @return void
     */
    private function render_booking_confirmation($booking_id) {
        // Получаем информацию о бронировании из БД или постов WordPress
        $booking_data = $this->get_booking_info($booking_id);
        
        if (!$booking_data) {
            echo '<div class="booking-error">';
            echo '<h2>Ошибка</h2>';
            echo '<p>Бронирование не найдено. Пожалуйста, проверьте номер бронирования.</p>';
            echo '</div>';
            return;
        }
        
        // Загружаем шаблон подтверждения бронирования
        $this->template_service->get_template_part('booking-confirmation', $booking_data);
    }
    
    /**
     * Получает информацию о бронировании
     *
     * @param string $booking_id ID бронирования
     * @return array|bool Данные бронирования или false
     */
    private function get_booking_info($booking_id) {
        global $wpdb;
        
        // Сначала проверяем в таблице бронирований
        $bookings_table = $wpdb->prefix . 'sun_bookings';
        $personal_data_table = $wpdb->prefix . 'sun_personal_data';
        
        $query = "SELECT b.*, p.first_name, p.last_name, p.middle_name, p.email, p.phone
                 FROM $bookings_table b
                 JOIN $personal_data_table p ON b.personal_data_id = p.id
                 WHERE b.booking_id = %s
                 LIMIT 1";
        
        $booking = $wpdb->get_row($wpdb->prepare($query, $booking_id), ARRAY_A);
        
        if ($booking) {
            // Преобразуем даты в нужный формат
            $booking['apartament_title'] = get_the_title($booking['apartament_id']);
            return $booking;
        }
        
        // Если в таблице не найдено, ищем в постах WordPress
        $bookings = get_posts([
            'post_type' => 'sun_booking',
            'post_title' => $booking_id,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);
        
        if (empty($bookings)) {
            return false;
        }
        
        $post_id = $bookings[0]->ID;
        
        // Собираем данные из метаполей
        $booking_data = [
            'booking_id' => $booking_id,
            'apartament_id' => get_post_meta($post_id, '_booking_apartament_id', true),
            'apartament_title' => get_the_title(get_post_meta($post_id, '_booking_apartament_id', true)),
            'first_name' => get_post_meta($post_id, '_booking_first_name', true),
            'last_name' => get_post_meta($post_id, '_booking_last_name', true),
            'middle_name' => get_post_meta($post_id, '_booking_middle_name', true),
            'email' => get_post_meta($post_id, '_booking_email', true),
            'phone' => get_post_meta($post_id, '_booking_phone', true),
            'checkin_date' => get_post_meta($post_id, '_booking_checkin_date', true),
            'checkout_date' => get_post_meta($post_id, '_booking_checkout_date', true),
            'total_price' => get_post_meta($post_id, '_booking_total_price', true),
            'payment_method' => get_post_meta($post_id, '_booking_payment_method', true),
            'status' => $bookings[0]->post_status,
            'guest_count' => get_post_meta($post_id, '_booking_guest_count', true),
            'children_count' => get_post_meta($post_id, '_booking_children_count', true),
            'created_at' => get_post_meta($post_id, '_booking_created_at', true) ?: $bookings[0]->post_date
        ];
        
        return $booking_data;
    }
}