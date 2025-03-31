<?php
namespace Sun\Apartament\Services;

use Sun\Apartament\Core\Booking;
use Sun\Apartament\Core\Apartament;

/**
 * Сервис для управления бронированиями
 *
 * @since 1.0.0
 */
class BookingService extends AbstractService {
    /**
     * Регистрирует сервис
     *
     * @return void
     */
    public function register() {
        add_action('init', [$this, 'register_booking_hooks']);
    }
    
    /**
     * Регистрирует хуки для обработки бронирований
     * 
     * @return void
     */
    public function register_booking_hooks() {
        // Обработчик формы бронирования
        add_action('wp_ajax_create_booking', [$this, 'ajax_create_booking']);
        add_action('wp_ajax_nopriv_create_booking', [$this, 'ajax_create_booking']);
        
        // Обработчик отмены бронирования
        add_action('wp_ajax_cancel_booking', [$this, 'ajax_cancel_booking']);
        add_action('wp_ajax_nopriv_cancel_booking', [$this, 'ajax_cancel_booking']);
    }
    
    /**
     * Создает новое бронирование
     *
     * @param array $booking_data Данные бронирования
     * @return int|false ID созданного бронирования или false в случае ошибки
     */
    public function create_booking($booking_data) {
        if (empty($booking_data['apartament_id']) || 
            empty($booking_data['checkin_date']) || 
            empty($booking_data['checkout_date'])) {
            error_log('Ошибка создания бронирования: отсутствуют обязательные данные');
            return false;
        }
        
        // Проверяем тип поста апартамента
        $apartament_id = intval($booking_data['apartament_id']);
        $expected_post_types = array('apartament', 'flat', 'room');
        $post_type = get_post_type($apartament_id);
        
        if (!in_array($post_type, $expected_post_types)) {
            error_log('Ошибка создания бронирования: неверный тип поста апартамента');
            return false;
        }
        
        // Преобразуем даты в формат Y-m-d для корректной работы
        $checkin_formatted = date('Y-m-d', strtotime($booking_data['checkin_date']));
        $checkout_formatted = date('Y-m-d', strtotime($booking_data['checkout_date']));
        
        // Проверка наличия апартамента
        $apartament = new Apartament($booking_data['apartament_id']);
        if (!$apartament->get_id()) {
            error_log('Ошибка создания бронирования: апартамент не найден');
            return false;
        }
        
        // Проверка доступности апартамента на выбранные даты
        if (!$apartament->is_available($checkin_formatted, $checkout_formatted)) {
            error_log('Ошибка создания бронирования: апартамент недоступен на выбранные даты');
            return false;
        }
        
        // Если цена не указана в данных, рассчитываем её
        if (empty($booking_data['total_price'])) {
            $prices_data = $apartament->get_prices_for_period($checkin_formatted, $checkout_formatted);
            $total_price = $prices_data['total_price'];
        } else {
            $total_price = $booking_data['total_price'];
        }
        
        // Создание нового бронирования
        $booking = new Booking();
        $booking->set_apartament_id($booking_data['apartament_id']);
        $booking->set_checkin_date($booking_data['checkin_date']);
        $booking->set_checkout_date($booking_data['checkout_date']);
        $booking->set_first_name($booking_data['first_name']);
        $booking->set_last_name($booking_data['last_name']);
        $booking->set_middle_name($booking_data['middle_name'] ?? '');
        $booking->set_email($booking_data['email']);
        $booking->set_phone($booking_data['phone']);
        $booking->set_guest_count($booking_data['guest_count'] ?? 1);
        $booking->set_children_count($booking_data['children_count'] ?? 0);
        $booking->set_total_price($total_price);
        $booking->set_payment_method($booking_data['payment_method'] ?? 'card');
        $booking->set_terms_accepted($booking_data['terms_accepted'] ?? 0);
        $booking->set_status($booking_data['status'] ?? 'pending');
        
        // Сохранение бронирования
        $booking_id = $booking->save();
        
        if (!$booking_id) {
            error_log('Ошибка при сохранении бронирования');
            return false;
        }
        
        // Получаем сгенерированный номер бронирования
        $booking_number = $booking->get_booking_id();
        error_log('Создано бронирование с ID: ' . $booking_id . ', номер: ' . $booking_number);
        
        // Отправка уведомлений, если статус не черновик
        if ($booking_data['status'] !== 'draft') {
            $this->send_booking_notifications($booking);
        }
        
        return $booking_id;
    }
    
    /**
     * AJAX-обработчик создания бронирования
     * 
     * @return void
     */
    public function ajax_create_booking() {
        // Проверка nonce для безопасности
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'booking_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
            return;
        }

        // Проверка обязательных полей
        $required_fields = ['apartament_id', 'checkin_date', 'checkout_date', 'first_name', 'last_name', 'email', 'phone'];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(['message' => 'Пожалуйста, заполните все обязательные поля.']);
                return;
            }
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
            'guest_count' => isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 1,
            'children_count' => isset($_POST['children_count']) ? intval($_POST['children_count']) : 0,
            'payment_method' => isset($_POST['payment_method']) ? sanitize_text_field($_POST['payment_method']) : 'card',
            'terms_accepted' => isset($_POST['terms_accepted']) ? 1 : 0,
            'status' => 'pending'
        ];
        
        // Создание бронирования
        $booking_id = $this->create_booking($booking_data);
        
        if (!$booking_id) {
            wp_send_json_error(['message' => 'Ошибка при создании бронирования. Пожалуйста, попробуйте снова.']);
            return;
        }
        
        // Получаем созданный объект бронирования для получения номера
        $booking = new \Sun\Apartament\Core\Booking($booking_id);
        $booking_number = $booking->get_booking_id(); // Получаем номер бронирования (например, DC-20250330-ABCD)
        
        // Возвращаем успешный результат
        wp_send_json_success([
            'message' => 'Бронирование успешно создано.',
            'booking_id' => $booking_number, // Используем номер бронирования, а не ID записи
            'redirect' => add_query_arg([
                'booking_id' => $booking_number, // Используем номер бронирования для редиректа
                'status' => 'success'
            ], get_permalink(get_page_by_path('booking-confirmation')))
        ]);
    }
    
    /**
     * AJAX-обработчик отмены бронирования
     * 
     * @return void
     */
    public function ajax_cancel_booking() {
        // Проверка nonce для безопасности
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'cancel_booking_nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
            return;
        }
        
        // Проверка параметров
        if (empty($_POST['booking_id']) || empty($_POST['email'])) {
            wp_send_json_error(['message' => 'Недостаточно данных для отмены бронирования.']);
            return;
        }
        
        $booking_id = sanitize_text_field($_POST['booking_id']);
        $email = sanitize_email($_POST['email']);
        
        // Поиск бронирования
        $bookings = get_posts([
            'post_type' => 'sun_booking',
            'post_title' => $booking_id,
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_booking_email',
                    'value' => $email,
                    'compare' => '='
                ]
            ]
        ]);
        
        if (empty($bookings)) {
            wp_send_json_error(['message' => 'Бронирование не найдено или данные не совпадают.']);
            return;
        }
        
        $booking = new Booking($bookings[0]);
        $result = $booking->cancel();
        
        if ($result) {
            // Отправка уведомления об отмене
            $this->send_cancellation_notification($booking);
            
            wp_send_json_success(['message' => 'Бронирование успешно отменено.']);
        } else {
            wp_send_json_error(['message' => 'Не удалось отменить бронирование. Пожалуйста, попробуйте снова.']);
        }
    }
    
    /**
     * Отправляет уведомления о новом бронировании
     * 
     * @param Booking $booking Объект бронирования
     * @return void
     */
    private function send_booking_notifications($booking) {
        // Вызов сервиса отправки уведомлений
        $notification_service = new NotificationService();
        
        // Данные для уведомления
        $notification_data = [
            'booking_id' => $booking->get_booking_id(),
            'apartament_id' => $booking->get_apartament_id(),
            'first_name' => $booking->get_first_name(),
            'last_name' => $booking->get_last_name(),
            'middle_name' => $booking->get_middle_name(),
            'email' => $booking->get_email(),
            'phone' => $booking->get_phone(),
            'checkin_date' => $booking->get_checkin_date(),
            'checkout_date' => $booking->get_checkout_date(),
            'total_price' => $booking->get_total_price(),
            'payment_method' => $booking->get_payment_method(),
            'guest_count' => $booking->get_guest_count(),
            'children_count' => $booking->get_children_count()
        ];
        
        // Отправляем уведомления
        $notification_service->send_booking_notifications($notification_data);
    }
    
    /**
     * Отправляет уведомление об отмене бронирования
     * 
     * @param Booking $booking Объект бронирования
     * @return void
     */
    private function send_cancellation_notification($booking) {
        $notification_service = new NotificationService();
        
        $notification_data = [
            'booking_id' => $booking->get_booking_id(),
            'apartament_id' => $booking->get_apartament_id(),
            'first_name' => $booking->get_first_name(),
            'last_name' => $booking->get_last_name(),
            'email' => $booking->get_email(),
            'checkin_date' => $booking->get_checkin_date(),
            'checkout_date' => $booking->get_checkout_date()
        ];
        
        $notification_service->send_cancellation_notification($notification_data);
    }
    
    /**
     * Получает информацию о бронировании по ID
     * 
     * @param string|int $booking_id ID бронирования (может быть числовым или строковым)
     * @return array|false Данные бронирования или false в случае ошибки
     */
    public function get_booking_by_id($booking_id) {
        error_log('[Sun Apartament] Запрос бронирования с ID: ' . $booking_id);
        
        // Проверяем, является ли ID числом
        if (is_numeric($booking_id)) {
            // Если числовое - это скорее всего ID записи в WordPress
            $booking = new Booking(intval($booking_id));
            
            if ($booking->get_id() > 0) {
                error_log('[Sun Apartament] Найдено бронирование по числовому ID: ' . $booking_id);
                // Возвращаем данные из объекта Booking
                return [
                    'booking_id' => $booking->get_booking_id(),
                    'apartament_id' => $booking->get_apartament_id(),
                    'checkin_date' => $booking->get_checkin_date(),
                    'checkout_date' => $booking->get_checkout_date(),
                    'first_name' => $booking->get_first_name(),
                    'last_name' => $booking->get_last_name(),
                    'middle_name' => $booking->get_middle_name(),
                    'email' => $booking->get_email(),
                    'phone' => $booking->get_phone(),
                    'guest_count' => $booking->get_guest_count(),
                    'children_count' => $booking->get_children_count(),
                    'total_price' => $booking->get_total_price(),
                    'payment_method' => $booking->get_payment_method(),
                    'status' => $booking->get_status(),
                    'created_at' => $booking->get_created_at()
                ];
            }
        }
        
        // Если это строковой ID (например, DC-20250330-ABCD)
        // Ищем бронирование по значению post_title
        $bookings = get_posts([
            'post_type' => 'sun_booking',
            'post_title' => $booking_id,
            'posts_per_page' => 1
        ]);
        
        if (!empty($bookings)) {
            error_log('[Sun Apartament] Найдено бронирование по строковому ID: ' . $booking_id);
            $booking = new Booking($bookings[0]->ID);
            
            return [
                'booking_id' => $booking->get_booking_id(),
                'apartament_id' => $booking->get_apartament_id(),
                'checkin_date' => $booking->get_checkin_date(),
                'checkout_date' => $booking->get_checkout_date(),
                'first_name' => $booking->get_first_name(),
                'last_name' => $booking->get_last_name(),
                'middle_name' => $booking->get_middle_name(),
                'email' => $booking->get_email(),
                'phone' => $booking->get_phone(),
                'guest_count' => $booking->get_guest_count(),
                'children_count' => $booking->get_children_count(),
                'total_price' => $booking->get_total_price(),
                'payment_method' => $booking->get_payment_method(),
                'status' => $booking->get_status(),
                'created_at' => $booking->get_created_at()
            ];
        }
        
        // Также проверяем в пользовательских полях других записей
        $meta_bookings = get_posts([
            'post_type' => 'sun_booking',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_booking_id',
                    'value' => $booking_id,
                    'compare' => '='
                ]
            ]
        ]);
        
        if (!empty($meta_bookings)) {
            error_log('[Sun Apartament] Найдено бронирование по мета-полю: ' . $booking_id);
            $booking = new Booking($meta_bookings[0]->ID);
            
            return [
                'booking_id' => $booking->get_booking_id(),
                'apartament_id' => $booking->get_apartament_id(),
                'checkin_date' => $booking->get_checkin_date(),
                'checkout_date' => $booking->get_checkout_date(),
                'first_name' => $booking->get_first_name(),
                'last_name' => $booking->get_last_name(),
                'middle_name' => $booking->get_middle_name(),
                'email' => $booking->get_email(),
                'phone' => $booking->get_phone(),
                'guest_count' => $booking->get_guest_count(),
                'children_count' => $booking->get_children_count(),
                'total_price' => $booking->get_total_price(),
                'payment_method' => $booking->get_payment_method(),
                'status' => $booking->get_status(),
                'created_at' => $booking->get_created_at()
            ];
        }
        
        // Для отладки выводим временные данные только в режиме разработки
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Sun Apartament] Бронирование с ID {$booking_id} не найдено, используем временные данные");
            
            // Находим первый апартамент правильного типа для отображения
            $apartment_posts = get_posts([
                'post_type' => ['apartament', 'flat', 'room'],
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            
            $apartament_id = !empty($apartment_posts) ? $apartment_posts[0]->ID : 0;
            
            return [
                'booking_id' => $booking_id, // Сохраняем оригинальный booking_id
                'apartament_id' => $apartament_id,
                'checkin_date' => date('Y-m-d'),
                'checkout_date' => date('Y-m-d', strtotime('+3 days')),
                'first_name' => 'Иван',
                'last_name' => 'Иванов',
                'middle_name' => 'Иванович',
                'email' => 'test@example.com',
                'phone' => '+7 (999) 123-45-67',
                'guest_count' => 2,
                'children_count' => 1,
                'total_price' => 15000,
                'payment_method' => 'card',
                'status' => 'confirmed',
                'created_at' => date('Y-m-d H:i:s'),
                'formatted_id' => $booking_id // Используем оригинальный ID как форматированный
            ];
        }
        
        // В продакшн-режиме возвращаем false, если бронирование не найдено
        error_log('[Sun Apartament] Бронирование не найдено. Режим отладки отключен. Возвращаем false.');
        return false;
    }
}