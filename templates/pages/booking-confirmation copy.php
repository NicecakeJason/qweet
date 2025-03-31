<?php
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

// Получаем параметры из URL
$booking_id = isset($_GET['booking_id']) ? sanitize_text_field($_GET['booking_id']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

// Инициализируем сервис бронирования
$booking_service = new \Sun\Apartament\Services\BookingService();

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
                    <?php
                    // Получаем информацию о бронировании
                    $booking_data = $booking_service->get_booking_by_id($booking_id);
                    
                    // Проверяем структуру данных бронирования
                    if (!is_array($booking_data) || empty($booking_data)) {
                        // Выводим сообщение об ошибке, если данные отсутствуют
                        echo '<div class="error-message">Ошибка: Данные бронирования не получены или имеют неправильный формат</div>';
                        // Добавляем отладочную информацию
                        if (current_user_can('administrator')) {
                            echo '<pre>Booking ID: ' . esc_html($booking_id) . '</pre>';
                            echo '<pre>Booking Service: ' . print_r($booking_service, true) . '</pre>';
                        }
                    }
                    
                    if ($booking_data): 
                        // Расширенная отладочная информация для администраторов
                        if (current_user_can('administrator')): ?>
                        
                        <?php endif; ?>

                        <?php
                        // Получаем ID апартамента
                        $apartament_id = isset($booking_data['apartament_id']) ? intval($booking_data['apartament_id']) : 0;

                        // Проверяем тип поста
                        $apartament_post = get_post($apartament_id);
                        $apartament_post_type = ($apartament_post instanceof WP_Post) ? $apartament_post->post_type : 'unknown';

                        // Определяем ожидаемые типы постов для апартаментов
                        $expected_post_types = array('apartament', 'flat', 'room'); // Добавьте все возможные типы

                        // Дополнительная отладка для поста апартамента
                        if (current_user_can('administrator')): ?>
                        
                        <?php endif; ?>

                        <?php
                        // Проверяем соответствие типа поста
                        if ($apartament_post instanceof WP_Post && in_array($apartament_post_type, $expected_post_types)) {
                            // Получаем заголовок напрямую из объекта поста
                            $apartament_title = $apartament_post->post_title;
                        } else {
                            // Проверяем, может быть у нас другое поле для ID апартамента
                            $alternative_field = isset($booking_data['flat_id']) ? intval($booking_data['flat_id']) : 0;
                            if ($alternative_field > 0) {
                                $alt_post = get_post($alternative_field);
                                $alt_post_type = ($alt_post instanceof WP_Post) ? $alt_post->post_type : 'unknown';
                                if ($alt_post instanceof WP_Post && in_array($alt_post_type, $expected_post_types)) {
                                    $apartament_id = $alternative_field;
                                    $apartament_post = $alt_post;
                                    $apartament_title = $alt_post->post_title;
                                } else {
                                    $apartament_title = 'Апартамент не найден';
                                }
                            } else {
                                // Находим первый апартамент правильного типа как запасной вариант
                                $apartment_posts = get_posts([
                                    'post_type' => $expected_post_types,
                                    'posts_per_page' => 1,
                                    'orderby' => 'date',
                                    'order' => 'DESC'
                                ]);
                                
                                if (!empty($apartment_posts)) {
                                    $apartament_id = $apartment_posts[0]->ID;
                                    $apartament_post = $apartment_posts[0];
                                    $apartament_title = $apartment_posts[0]->post_title;
                                    
                                    // Информация для администратора
                                    if (current_user_can('administrator')) {
                                        
                                    }
                                } else {
                                    $apartament_title = 'Апартамент не найден';
                                }
                            }
                        }
                        
                        $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
                        $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
                        
                        // Рассчитываем количество ночей
                        $start_date = new DateTime($booking_data['checkin_date']);
                        $end_date = new DateTime($booking_data['checkout_date']);
                        $nights = $end_date->diff($start_date)->days;
                        
                        // Получаем данные об изображении разными способами
                        $first_image_url = '';

                        // Вариант 1: Проверяем миниатюру поста
                        if (has_post_thumbnail($apartament_id)) {
                            $first_image_url = get_the_post_thumbnail_url($apartament_id, 'large');
                        }

                        // Вариант 2: Проверяем метаполе галереи
                        if (empty($first_image_url)) {
                            $gallery_images = get_post_meta($apartament_id, 'sunapartament_gallery', true);
                            if ($gallery_images && is_array($gallery_images) && !empty($gallery_images)) {
                                $image_data = wp_get_attachment_image_src($gallery_images[0], 'large');
                                $first_image_url = is_array($image_data) ? $image_data[0] : '';
                            }
                        }

                        // Вариант 3: Проверяем ACF поле (если используется)
                        if (empty($first_image_url) && function_exists('get_field')) {
                            $acf_gallery = get_field('gallery', $apartament_id);
                            if ($acf_gallery && is_array($acf_gallery) && !empty($acf_gallery)) {
                                $first_image_url = isset($acf_gallery[0]['url']) ? $acf_gallery[0]['url'] : 
                                                 (isset($acf_gallery[0]['sizes']['large']) ? $acf_gallery[0]['sizes']['large'] : '');
                            }
                        }

                        // Вариант 4: Проверяем другие возможные поля
                        if (empty($first_image_url)) {
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
                        
                        // Отладка изображения
                        if (current_user_can('administrator')): ?>
                        
                        <?php endif; ?>
                    
                    <h1 class="section-title">Бронирование подтверждено</h1>
                    
                    <div class="confirmation-message">
                        <p class="success-message">Ваше бронирование успешно оформлено! Мы отправили детали на указанный email.</p>
                    </div>
                    
                    <div class="booking-details">
                        <div class="booking-id">
                            <span class="label">Номер бронирования:</span>
                            <span class="value"><?php 
                                // Проверяем, есть ли форматированный ID в данных
                                if (!empty($booking_data['formatted_id'])) {
                                    echo esc_html($booking_data['formatted_id']);
                                } else {
                                    echo esc_html($booking_data['booking_id']); 
                                }
                            ?></span>
                        </div>
                        
                        <div class="booking-block">
                            <div class="block-header">
                                <h2 class="block-title">Информация о бронировании</h2>
                            </div>
                            
                            <div class="block-content">
                                <div class="property-info">
                                    <img class="property-image" src="<?php echo esc_url($first_image_url); ?>"
                                         alt="<?php echo esc_attr($apartament_title); ?>">
                                    
                                    <div class="property-details">
                                        <h3 class="property-name"><?php echo esc_html($apartament_title); ?></h3>
                                    </div>
                                </div>
                                
                                <div class="date-row">
                                    <div class="date-box">
                                        <div class="date-label">Заезд</div>
                                        <div class="date-value"><?php echo esc_html($checkin_date); ?></div>
                                    </div>
                                    
                                    <div class="date-box">
                                        <div class="date-label">Выезд</div>
                                        <div class="date-value"><?php echo esc_html($checkout_date); ?></div>
                                    </div>
                                </div>
                                
                                <div class="guest-row">
                                    <div class="guest-box">
                                        <div class="guest-label">Гости</div>
                                        <div class="guest-value">
                                            <?php echo esc_html($booking_data['guest_count']); ?> взрослых
                                            <?php echo $booking_data['children_count'] > 0 ? ', ' . esc_html($booking_data['children_count']) . ' детей' : ''; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="price-info">
                                    <div class="price-row">
                                        <div>Проживание</div>
                                        <div><?php echo number_format($booking_data['total_price'], 0, '.', ' '); ?> ₽</div>
                                    </div>
                                    
                                    <div class="price-breakdown">
                                        <?php echo esc_html($nights); ?> <?php echo pluralize_nights($nights); ?>
                                    </div>
                                    
                                    <div class="price-total">
                                        <div class="total-label">Итого</div>
                                        <div class="total-value"><?php echo number_format($booking_data['total_price'], 0, '.', ' '); ?> ₽</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-block">
                            <div class="block-header">
                                <h2 class="block-title">Данные гостя</h2>
                            </div>
                            
                            <div class="block-content">
                                <div class="guest-info">
                                    <div class="info-row">
                                        <div class="info-label">ФИО:</div>
                                        <div class="info-value">
                                            <?php echo esc_html($booking_data['last_name']); ?> 
                                            <?php echo esc_html($booking_data['first_name']); ?> 
                                            <?php echo esc_html($booking_data['middle_name']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <div class="info-label">Email:</div>
                                        <div class="info-value"><?php echo esc_html($booking_data['email']); ?></div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <div class="info-label">Телефон:</div>
                                        <div class="info-value"><?php echo esc_html($booking_data['phone']); ?></div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <div class="info-label">Способ оплаты:</div>
                                        <div class="info-value">
                                            <?php 
                                            $payment_methods = [
                                                'card' => 'Банковская карта',
                                                'cash' => 'Наличными при заезде',
                                                'transfer' => 'Банковский перевод'
                                            ];
                                            echo isset($payment_methods[$booking_data['payment_method']]) ? 
                                                 esc_html($payment_methods[$booking_data['payment_method']]) : 
                                                 esc_html($booking_data['payment_method']);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="booking-info">
                            <h3>Что дальше?</h3>
                            <p>Мы отправили подтверждение бронирования на указанный вами email. Пожалуйста, сохраните его.</p>
                            <p>При возникновении вопросов, свяжитесь с нами по телефону: <strong>+7 (XXX) XXX-XX-XX</strong>.</p>
                            
                            <div class="actions">
                                <a href="<?php echo home_url(); ?>" class="btn btn-primary">Вернуться на главную</a>
                            </div>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="booking-error">
                        <h2 class="error-title">Бронирование не найдено</h2>
                        <p>К сожалению, информация о бронировании не найдена. Проверьте правильность номера бронирования или свяжитесь с нами.</p>
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">Вернуться на главную</a>
                    </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                <div class="booking-error">
                    <h2 class="error-title">Ошибка бронирования</h2>
                    <p>Произошла ошибка при обработке запроса. Пожалуйста, попробуйте снова или свяжитесь с нами.</p>
                    <a href="<?php echo home_url(); ?>" class="btn btn-primary">Вернуться на главную</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<?php get_footer(); ?>