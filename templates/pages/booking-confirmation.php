<?php
// Отладочная функция - используйте только при разработке
function debug_booking_data($data, $title = 'Debug Info') {
    if (current_user_can('administrator')) {
        echo '<div style="background:#f5f5f5;padding:15px;margin:15px;border:1px solid #ddd;border-radius:5px;font-size:13px;">';
        echo "<h3>$title</h3><pre>";
        print_r($data);
        echo '</pre></div>';
    }
}

/**
 * Шаблон страницы подтверждения бронирования
 *
 * @package Sun_Apartament
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

get_header('flat'); // Подключаем header темы

// Инициализируем переменные
$booking_id = '';
$booking_created = false;
$apartament_id = 0;
$nights_count = 0;
$guest_count = 0;
$children_count = 0;
$total_price = 0;
$booking_data = [];
$apartament_title = '';
$checkin_date = '';
$checkout_date = '';
$notification_results = ['email_client' => false];

// Получаем параметры из URL
$booking_id = isset($_GET['booking_id']) ? sanitize_text_field($_GET['booking_id']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Инициализируем сервис бронирования
$booking_service = new \Sun\Apartament\Services\BookingService();

// Отладочная информация для администраторов
if (current_user_can('administrator') && isset($_GET['debug']) && $_GET['debug'] == 1) {
    debug_booking_data(get_defined_vars(), 'Все доступные переменные');
}

// Получаем данные бронирования, если есть ID и статус успешный
try {
    if ($status === 'success' && !empty($booking_id)) {
        $booking_data = $booking_service->get_booking_by_id($booking_id);
        
        // Проверяем успешность создания бронирования
        $booking_created = !empty($booking_data) && is_array($booking_data);
        
        // Получаем данные из бронирования, если оно существует
        if ($booking_created) {
            // Для отображения используем booking_id из данных бронирования, если есть
            if (isset($booking_data['booking_id']) && !empty($booking_data['booking_id'])) {
                $display_booking_id = $booking_data['booking_id'];
            } 
            // Или поле booking_number, если оно есть
            elseif (isset($booking_data['booking_number']) && !empty($booking_data['booking_number'])) {
                $display_booking_id = $booking_data['booking_number'];
            }
            // Или ID из URL
            else {
                $display_booking_id = $booking_id;
            }
            
            $apartament_id = isset($booking_data['apartament_id']) ? intval($booking_data['apartament_id']) : 0;
            
            // Проверяем наличие поля flat_id, если apartament_id пустой
            if (empty($apartament_id) && isset($booking_data['flat_id'])) {
                $apartament_id = intval($booking_data['flat_id']);
            }
            
            // Рассчитываем количество ночей, если есть даты
            if (isset($booking_data['checkin_date']) && isset($booking_data['checkout_date'])) {
                $start_date = new DateTime($booking_data['checkin_date']);
                $end_date = new DateTime($booking_data['checkout_date']);
                $nights_count = $end_date->diff($start_date)->days;
            }
            
            // Получаем количество гостей и детей
            $guest_count = isset($booking_data['adults']) ? intval($booking_data['adults']) : 
                          (isset($booking_data['guest_count']) ? intval($booking_data['guest_count']) : 0);
            $children_count = isset($booking_data['children']) ? intval($booking_data['children']) : 
                             (isset($booking_data['children_count']) ? intval($booking_data['children_count']) : 0);
            
            // Получаем общую стоимость
            $total_price = isset($booking_data['total_price']) ? floatval($booking_data['total_price']) : 0;
            
            // Отладочная информация для администраторов
            if (current_user_can('administrator') && isset($_GET['debug']) && $_GET['debug'] == 1) {
                debug_booking_data($booking_data, 'Данные бронирования');
            }
        } else {
            // Если данные бронирования не найдены, используем ID из URL
            $display_booking_id = $booking_id;
        }
    } else {
        // Если ID бронирования пуст или статус не success, используем ID из URL
        $display_booking_id = $booking_id;
    }
} catch (Exception $e) {
    if (current_user_can('administrator')) {
        debug_booking_data($e->getMessage(), 'Ошибка получения данных бронирования');
    }
    $booking_created = false;
    $booking_data = [];
    $display_booking_id = $booking_id;
}

// Функция для склонения слова "ночь"
if (!function_exists('pluralize_nights')) {
   function pluralize_nights($number)
   {
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
?>

<main>
    <section class="section">
        <div class="container">
            <!-- <?php if (function_exists('custom_breadcrumbs')) custom_breadcrumbs(); ?> -->
            
            <div class="booking-confirmation">
                <?php if ($status === 'success' && !empty($booking_id)): ?>
                    <?php if ($booking_created && !empty($booking_data)): ?>
                        <?php
                        // Получаем ID апартамента
                        if ($apartament_id > 0) {
                            $apartament_post = get_post($apartament_id);
                            $apartament_post_type = ($apartament_post instanceof WP_Post) ? $apartament_post->post_type : 'unknown';
                            
                            // Определяем ожидаемые типы постов для апартаментов
                            $expected_post_types = array('apartament', 'flat', 'room');
                            
                            // Проверяем соответствие типа поста
                            if ($apartament_post instanceof WP_Post && in_array($apartament_post_type, $expected_post_types)) {
                                $apartament_title = $apartament_post->post_title;
                            } else {
                                $apartament_title = 'Апартамент не найден';
                            }
                        } else {
                            $apartament_title = 'Апартамент не найден';
                        }
                        
                        // Форматируем даты
                        $checkin_date = isset($booking_data['checkin_date']) ? date('d.m.Y', strtotime($booking_data['checkin_date'])) : 'не указана';
                        $checkout_date = isset($booking_data['checkout_date']) ? date('d.m.Y', strtotime($booking_data['checkout_date'])) : 'не указана';
                        
                        // Получаем данные об изображении
                        $first_image_url = '';

                        // Вариант 1: Проверяем миниатюру поста
                        if ($apartament_id > 0 && has_post_thumbnail($apartament_id)) {
                            $first_image_url = get_the_post_thumbnail_url($apartament_id, 'large');
                        }

                        // Вариант 2: Проверяем метаполе галереи
                        if (empty($first_image_url) && $apartament_id > 0) {
                            $gallery_images = get_post_meta($apartament_id, 'sunapartament_gallery', true);
                            if ($gallery_images && is_array($gallery_images) && !empty($gallery_images)) {
                                $image_data = wp_get_attachment_image_src($gallery_images[0], 'large');
                                $first_image_url = is_array($image_data) ? $image_data[0] : '';
                            }
                        }

                        // Вариант 3: Проверяем ACF поле (если используется)
                        if (empty($first_image_url) && function_exists('get_field') && $apartament_id > 0) {
                            $acf_gallery = get_field('gallery', $apartament_id);
                            if ($acf_gallery && is_array($acf_gallery) && !empty($acf_gallery)) {
                                $first_image_url = isset($acf_gallery[0]['url']) ? $acf_gallery[0]['url'] : 
                                                 (isset($acf_gallery[0]['sizes']['large']) ? $acf_gallery[0]['sizes']['large'] : '');
                            }
                        }

                        // Вариант 4: Проверяем другие возможные поля
                        if (empty($first_image_url) && $apartament_id > 0) {
                            $possible_fields = ['sunapartament_thumbnail', 'main_image', 'featured_image'];
                            foreach ($possible_fields as $field) {
                                $image_id = get_post_meta($apartament_id, $field, true);
                                if ($image_id) {
                                    $image_data = wp_get_attachment_image_src($image_id, 'large');
                                    $first_image_url = is_array($image_data) ? $image_data[0] : '';
                                    if (!empty($first_image_url)) break;
                                }
                            }
                        }

                        // Если ничего не найдено, используем заглушку
                        if (empty($first_image_url)) {
                            $first_image_url = get_template_directory_uri() . '/assets/images/no-image.jpg';
                        }
                        
                        // Получаем метод оплаты из данных бронирования
                        $payment_method = isset($booking_data['payment_method']) ? $booking_data['payment_method'] : '';
                        if (empty($payment_method) && isset($_POST['payment_method'])) {
                            $payment_method = $_POST['payment_method'];
                        }
                        $payment_methods = [
                            'card' => 'Банковская карта',
                            'cash' => 'Наличными при заселении',
                            'transfer' => 'Банковский перевод'
                        ];
                        $payment_method_text = isset($payment_methods[$payment_method]) ? $payment_methods[$payment_method] : $payment_method;
                        
                        // Определяем email клиента
                        $client_email = isset($booking_data['email']) ? $booking_data['email'] : (isset($_POST['email']) ? $_POST['email'] : 'не указан');
                        
                        // Определяем статус уведомлений
                        $notification_sent = isset($notification_results) && isset($notification_results['email_client']) ? $notification_results['email_client'] : false;
                        ?>
                    
                    <h1 class="section-title">Бронирование подтверждено</h1>
                    
                    <section class="booking-confirmation">
                        <div class="confirmation-card">
                            <!-- Заголовок с иконкой -->
                            <header class="confirmation-header">
                                <svg class="success-icon" xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h1 class="confirmation-title">Ваше бронирование подтверждено</h1>
                            </header>

                            <!-- Основная информация -->
                            <div class="confirmation-content">
                                <p class="confirmation-message">
                                    Бронирование апартамента "<?php echo esc_html($apartament_title); ?>" успешно оформлено.
                                    <?php if ($notification_sent): ?>
                                        Информация о бронировании отправлена на указанный email: <strong><?php echo esc_html($client_email); ?></strong>
                                    <?php endif; ?>
                                </p>

                                <!-- Детали бронирования -->
                                <div class="booking-details">
                                    <h2 class="details-title">Информация о бронировании</h2>
                                    
                                    <dl class="details-list">
                                        <div class="detail-item">
                                            <dt class="detail-label">Номер бронирования:</dt>
                                            <dd class="detail-value booking-id"><?php echo esc_html($display_booking_id); ?></dd>
                                        </div>

                                        <div class="detail-item">
                                            <dt class="detail-label">Даты проживания:</dt>
                                            <dd class="detail-value">
                                                <span class="check-dates">
                                                    <?php echo $checkin_date; ?> — <?php echo $checkout_date; ?>
                                                </span>
                                                <span class="nights-count">(<?php echo $nights_count; ?> <?php echo pluralize_nights($nights_count); ?>)</span>
                                            </dd>
                                        </div>

                                        <div class="detail-item">
                                            <dt class="detail-label">Количество гостей:</dt>
                                            <dd class="detail-value">
                                                <?php echo $guest_count; ?> взрослых<?php echo $children_count > 0 ? ', ' . $children_count . ' детей' : ''; ?>
                                            </dd>
                                        </div>

                                        <div class="detail-item">
                                            <dt class="detail-label">Способ оплаты:</dt>
                                            <dd class="detail-value">
                                                <?php echo $payment_method_text; ?>
                                            </dd>
                                        </div>

                                        <div class="detail-item total-price">
                                            <dt class="detail-label">Итоговая стоимость:</dt>
                                            <dd class="detail-value price-highlight"><?php echo number_format($total_price, 0, '.', ' '); ?> ₽</dd>
                                        </div>
                                    </dl>
                                </div>
                                
                                <!-- Статус уведомлений -->
                                <?php if (isset($notification_results)): ?>
                                <div class="notification-status">
                                    <?php if ($notification_results['email_client']): ?>
                                        <div class="status-item success">
                                            <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                            </svg>
                                            <span>Письмо с деталями бронирования отправлено на ваш email</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-item error">
                                            <svg class="status-icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                            </svg>
                                            <span>Возникла проблема при отправке письма. Пожалуйста, сохраните номер бронирования.</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Предупреждение о технических проблемах -->
                                <?php if (!$booking_created): ?>
                                <div class="error-message">
                                    <svg class="error-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <p>Внимание: Возникли технические проблемы при сохранении данных в базу. Пожалуйста, свяжитесь с администратором сайта.</p>
                                </div>
                                <?php endif; ?>

                                <!-- Полезная информация -->
                                <div class="booking-info">
                                    <h2 class="info-title">Полезная информация</h2>
                                    <div class="info-content">
                                        <p>Заселение с 14:00, выселение до 12:00. При возникновении вопросов, пожалуйста, свяжитесь с нами.</p>
                                        
                                        <div class="contact-info">
                                            <div class="contact-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                                </svg>
                                                <span>+7 (XXX) XXX-XX-XX</span>
                                            </div>
                                            <div class="contact-item">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                                    <polyline points="22,6 12,13 2,6"></polyline>
                                                </svg>
                                                <span>booking@example.com</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Кнопки действий -->
                            <div class="confirmation-actions">
                                <a href="<?php echo home_url(); ?>" class="btn btn-secondary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    На главную
                                </a>
                                <a href="javascript:window.print();" class="btn btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                        <rect x="6" y="14" width="12" height="8"></rect>
                                    </svg>
                                    Распечатать
                                </a>
                            </div>
                        </div>
                    </section>
                    
                    <?php else: ?>
                    <div class="booking-error">
                        <h2 class="error-title">Бронирование не найдено</h2>
                        <p>К сожалению, информация о бронировании не найдена. Проверьте правильность номера бронирования или свяжитесь с нами.</p>
                        <div class="booking-debug" style="margin-top: 20px; padding: 15px; background: #f7f7f7; border: 1px solid #ddd; border-radius: 5px;">
                            <p>Информация для отладки (только для администраторов):</p>
                            <pre><?php 
                            if (current_user_can('administrator')) {
                                echo "Booking ID: " . esc_html($booking_id) . "\n";
                                echo "Status: " . esc_html($status) . "\n";
                                if (isset($booking_service)) {
                                    echo "Response: ";
                                    print_r($booking_service->get_booking_by_id($booking_id));
                                }
                            }
                            ?></pre>
                        </div>
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">Вернуться на главную</a>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="booking-error">
                    <h2 class="error-title">Ошибка бронирования</h2>
                    <p>Произошла ошибка при обработке запроса. Пожалуйста, попробуйте снова или свяжитесь с нами.</p>
                    <div class="booking-debug" style="margin-top: 20px; padding: 15px; background: #f7f7f7; border: 1px solid #ddd; border-radius: 5px;">
                        <p>Информация для отладки (только для администраторов):</p>
                        <pre><?php 
                        if (current_user_can('administrator')) {
                            echo "Booking ID: " . esc_html($booking_id) . "\n";
                            echo "Status: " . esc_html($status) . "\n";
                        }
                        ?></pre>
                    </div>
                    <a href="<?php echo home_url(); ?>" class="btn btn-primary">Вернуться на главную</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>