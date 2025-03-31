<?php
/**
 * Шаблон формы поиска апартаментов
 *
 * @var array $atts Атрибуты шорткода
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="sun-booking-form search-form theme-<?php echo esc_attr($atts['theme']); ?>">
    <h3 class="form-title"><?php esc_html_e('Найти апартаменты', 'sun-apartament'); ?></h3>
    
    <form method="post" action="">
        <?php wp_nonce_field('sunapartament_booking_nonce', 'booking_nonce'); ?>
        <input type="hidden" name="sunapartament_booking_submit" value="1">
        
        <div class="form-row">
            <label for="checkin_date"><?php esc_html_e('Дата заезда', 'sun-apartament'); ?></label>
            <input type="text" id="checkin_date" name="checkin_date" class="datepicker" required placeholder="дд.мм.гггг">
        </div>

        <div class="form-row">
            <label for="checkout_date"><?php esc_html_e('Дата выезда', 'sun-apartament'); ?></label>
            <input type="text" id="checkout_date" name="checkout_date" class="datepicker" required placeholder="дд.мм.гггг">
        </div>

        <div class="form-row">
            <label for="guest_count"><?php esc_html_e('Количество взрослых', 'sun-apartament'); ?></label>
            <input type="number" id="guest_count" name="guest_count" min="1" value="1">
        </div>

        <div class="form-row">
            <label for="children_count"><?php esc_html_e('Количество детей', 'sun-apartament'); ?></label>
            <input type="number" id="children_count" name="children_count" min="0" value="0">
        </div>

        <div class="form-row">
            <button type="submit" class="sun-book-btn"><?php esc_html_e('Найти апартаменты', 'sun-apartament'); ?></button>
        </div>
    </form>
</div>