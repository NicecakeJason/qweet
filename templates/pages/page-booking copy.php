<?php
/**
 * Шаблон страницы бронирования апартамента
 * 
 * 
 *
 * @package Sun_Apartament
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

get_header('flat'); // Подключаем header темы

// Получаем параметры из URL
$apartament_id = isset($_GET['apartament_id']) ? intval($_GET['apartament_id']) : 0;
$checkin_date = isset($_GET['checkin_date']) ? sanitize_text_field($_GET['checkin_date']) : '';
$checkout_date = isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '';
$guest_count = isset($_GET['guest_count']) ? intval($_GET['guest_count']) : 1;
$children_count = isset($_GET['children_count']) ? intval($_GET['children_count']) : 0;

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

// Инициализируем сервисы
$price_service = new \Sun\Apartament\Services\PriceService();

?>
<div class="booking-page">
    <h1 class="booking-title">Бронирование апартамента</h1>

    <?php
    // Проверка ошибок бронирования
    if (isset($_GET['booking_error'])) {
        $booking_error = sanitize_text_field($_GET['booking_error']);
        if ($booking_error === 'terms_not_accepted') {
            echo '<div class="booking-block" style="background-color: #fff3f3; border: 1px solid #ffcaca; margin-bottom: 20px;">
                <div class="block-content">
                    <p style="color: var(--danger-color); font-weight: 500; margin: 0;">Для продолжения необходимо подтвердить согласие с правилами бронирования и условиями проживания.</p>
                </div>
            </div>';
        } elseif ($booking_error === 'save_failed') {
            echo '<div class="booking-block" style="background-color: #fff3f3; border: 1px solid #ffcaca; margin-bottom: 20px;">
                <div class="block-content">
                    <p style="color: var(--danger-color); font-weight: 500; margin: 0;">Произошла ошибка при сохранении бронирования. Пожалуйста, попробуйте еще раз или свяжитесь с администратором.</p>
                </div>
            </div>';
        }
    }

    if ($apartament_id > 0 && $checkin_date && $checkout_date) {
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
        $period_prices_data = $price_service->get_prices_for_period($apartament_id, $checkin_date, $checkout_date);
        $nights_count = $period_prices_data['nights'];
        $total_price = $period_prices_data['total_price'];
        $daily_prices = $period_prices_data['daily_prices'];
        $final_price = $total_price;
        
        // Обработка отправки формы бронирования
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Форма обрабатывается через класс SearchResultsPage,
            // сюда код попадет только если была ошибка обработки
        } else {
            // Отображаем форму бронирования
            ?>
            <div class="booking-container">
                <div class="booking-left">
                    <div class="booking-block">
                        <div class="block-header">
                            <h2 class="block-title">Информация о бронировании</h2>
                        </div>

                        <div class="block-content">
                            <div class="property-info">
                                <img class="property-image" src="<?php echo esc_url($first_image_url); ?>"
                                    alt="<?php echo esc_attr($title); ?>">

                                <div class="property-details">
                                    <h3 class="property-name"><?php echo esc_html($title); ?></h3>

                                    <div class="property-features">
                                        <?php if ($square_footage): ?>
                                            <div class="feature-item">
                                                <?php if ($square_footage_icon): ?>
                                                    <img class="feature-icon" src="<?php echo esc_url($square_footage_icon); ?>"
                                                        alt="Площадь">
                                                <?php endif; ?>
                                                <?php echo esc_html($square_footage); ?> м²
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($floor_count): ?>
                                            <div class="feature-item">
                                                <?php if ($floor_count_icon): ?>
                                                    <img class="feature-icon" src="<?php echo esc_url($floor_count_icon); ?>"
                                                        alt="Этаж">
                                                <?php endif; ?>
                                                <?php echo esc_html($floor_count); ?> этаж
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($guest_count_max): ?>
                                            <div class="feature-item">
                                                <?php if ($guest_count_icon): ?>
                                                    <img class="feature-icon" src="<?php echo esc_url($guest_count_icon); ?>"
                                                        alt="Гости">
                                                <?php endif; ?>
                                                До <?php echo esc_html($guest_count_max); ?> гостей
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($checkin_date && $checkout_date): ?>
                                <div class="date-row">
                                    <div class="date-box">
                                        <div class="date-label">Заезд</div>
                                        <div class="date-value"><?php echo date('d.m.Y', strtotime($checkin_date)); ?></div>
                                    </div>

                                    <div class="date-box">
                                        <div class="date-label">Выезд</div>
                                        <div class="date-value"><?php echo date('d.m.Y', strtotime($checkout_date)); ?></div>
                                    </div>
                                </div>
                                
                                <div class="guest-row">
                                    <div class="guest-box">
                                        <div class="guest-label">Гости</div>
                                        <div class="guest-value">
                                            <?php echo $guest_count; ?> взрослых<?php echo $children_count > 0 ? ', ' . $children_count . ' детей' : ''; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="booking-block">
                        <div class="block-header">
                            <h2 class="block-title">Данные гостя</h2>
                        </div>

                        <div class="block-content">
                            <form id="booking-form" method="post">
                                <?php wp_nonce_field('booking_form', 'booking_nonce'); ?>
                                
                                <input type="hidden" name="apartament_id" value="<?php echo $apartament_id; ?>">
                                <input type="hidden" name="checkin_date" value="<?php echo esc_attr($checkin_date); ?>">
                                <input type="hidden" name="checkout_date" value="<?php echo esc_attr($checkout_date); ?>">
                                <input type="hidden" name="total_price"
                                    value="<?php echo isset($final_price) ? $final_price : 0; ?>">
                                <input type="hidden" name="guest_count" value="<?php echo esc_attr($guest_count); ?>">
                                <input type="hidden" name="children_count" value="<?php echo esc_attr($children_count); ?>">

                                <div class="form-block">
                                    <h3 class="form-title">Персональные данные</h3>

                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label class="form-label" for="last_name">Фамилия</label>
                                            <input type="text" id="last_name" name="last_name" class="form-control" required>
                                        </div>

                                        <div class="form-field">
                                            <label class="form-label" for="first_name">Имя</label>
                                            <input type="text" id="first_name" name="first_name" class="form-control" required>
                                        </div>

                                        <div class="form-field">
                                            <label class="form-label" for="middle_name">Отчество</label>
                                            <input type="text" id="middle_name" name="middle_name" class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-block">
                                    <h3 class="form-title">Контактная информация</h3>

                                    <div class="form-grid">
                                        <div class="form-field">
                                            <label class="form-label" for="email">Email</label>
                                            <input type="email" id="email" name="email" class="form-control" required>
                                        </div>

                                        <div class="form-field">
                                            <label class="form-label" for="phone">Телефон</label>
                                            <input type="tel" id="phone" name="phone" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-block">
                                    <h3 class="form-title">Способ оплаты</h3>

                                    <div class="form-field">
                                        <select id="payment_method" name="payment_method" class="form-control" required>
                                            <option value="card">Банковская карта</option>
                                            <option value="cash">Наличными при заезде</option>
                                            <option value="transfer">Банковский перевод</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Добавляем чекбокс для подтверждения бронирования -->
                                <div class="checkbox-field">
                                    <div class="checkbox-container">
                                        <input type="checkbox" id="terms_accepted" name="terms_accepted" class="checkbox-input" required>
                                        <label for="terms_accepted" class="checkbox-label">
                                            Я подтверждаю своё согласие с <a href="#">правилами бронирования</a> и <a href="#">условиями проживания</a>. 
                                            Я согласен на обработку моих персональных данных в соответствии с <a href="#">политикой конфиденциальности</a>.
                                        </label>
                                    </div>
                                </div>

                                <div class="form-field">
                                    <button type="submit" class="booking-btn">Забронировать</button>
                                </div>

                                <div class="booking-info">
                                    <p>После бронирования вам на почту будет отправлена вся необходимая информация. При возникновении вопросов, свяжитесь с нами по телефону: <strong>+7 (XXX) XXX-XX-XX</strong>.</p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="booking-right">
                    <div class="booking-block" style="position: sticky; top: 20px;">
                        <div class="block-header">
                            <h2 class="block-title">Детали бронирования</h2>
                        </div>

                        <div class="block-content">
                            <?php if ($checkin_date && $checkout_date): ?>
                                <div class="price-info">
                                    <div class="price-row">
                                        <div>Проживание</div>
                                        <div><?php echo number_format($total_price, 0, '.', ' '); ?> ₽</div>
                                    </div>

                                    <div class="price-breakdown">
                                        <?php echo $nights_count; ?> <?php echo pluralize_nights($nights_count); ?> ×
                                        <?php echo number_format($total_price / $nights_count, 0, '.', ' '); ?> ₽

                                        <?php if (isset($daily_prices) && count(array_unique($daily_prices)) > 1): ?>
                                            <span class="price-per-night">(цена меняется в зависимости от дат)</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="guest-info-row">
                                        <div>Гости</div>
                                        <div><?php echo $guest_count; ?> взрослых<?php echo $children_count > 0 ? ', ' . $children_count . ' детей' : ''; ?></div>
                                    </div>

                                    <div class="price-total">
                                        <div class="total-label">Итого</div>
                                        <div class="total-value"><?php echo number_format($final_price, 0, '.', ' '); ?> ₽</div>
                                    </div>
                                </div>

                                <div class="booking-info">
                                    <p>Бесплатная отмена бронирования за 48 часов до заезда. После этого срока удерживается
                                        стоимость первых суток проживания.</p>
                                </div>
                            <?php else: ?>
                                <p>Для расчета стоимости выберите даты заезда и выезда.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
    } else {
        // Если апартамент не выбран
        ?>
        <div class="booking-block">
            <div class="block-header">
                <h2 class="block-title">Выберите апартамент</h2>
            </div>

            <div class="block-content" style="text-align: center; padding: 40px 20px;">
                <p style="margin-bottom: 20px;">Для оформления бронирования необходимо выбрать апартамент.</p>

                <a href="<?php echo home_url('/apartaments/'); ?>"
                    style="display: inline-block; padding: 12px 24px; background-color: var(--primary-color); color: white; text-decoration: none; border-radius: var(--border-radius); font-weight: 500; transition: background-color 0.2s;">
                    Перейти к выбору апартаментов
                </a>
            </div>
        </div>
        <?php
    }
    ?>
</div>

<?php get_footer(); // Подключаем footer темы