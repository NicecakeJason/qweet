<?php
/**
 * Шаблон полной формы бронирования для главной и архивных страниц
 *
 * @var array $atts Атрибуты шорткода
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="full-booking-form">
    <form class="form-group" id="sunapartament-booking-form" method="post">
    <?php wp_nonce_field('sunapartament_booking_nonce', 'booking_nonce'); ?>
    <input type="hidden" name="sunapartament_booking_submit" value="1">
        <div class="row">
            <div class="col-xl-10">
                <div class="row row-cols-1 row-cols-xl-4 row-cols-lg-4 row-cols-md-2 section-grid">
                    <!-- Скрытые поля для хранения реальных значений -->
                    <input type="hidden" id="checkin_date" name="checkin_date" value="">
                    <input type="hidden" id="checkout_date" name="checkout_date" value="">
                    <input type="hidden" id="guest_count" name="guest_count" value="1">
                    <input type="hidden" id="children_count" name="children_count" value="0">
                    
                    <div class="col mb-3">
                        <label class="detail-info__text mb-1" for="checkin_date_display">Заезд</label>
                        <div id="checkin_date_display" class="form-input date-display" data-target="checkin_date">
                            <span class="date-placeholder">Дата заезда</span>
                        </div>
                    </div>
                    
                    <div class="col mb-3">
                        <label class="detail-info__text mb-1" for="checkout_date_display">Выезд</label>
                        <div id="checkout_date_display" class="form-input date-display" data-target="checkout_date">
                            <span class="date-placeholder">Дата выезда</span>
                        </div>
                    </div>
                    
                    <div class="col mb-3">
                        <label class="detail-info__text mb-1">Взрослые</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-minus" data-target="guest_count">-</button>
                            <div id="guest_count_display" class="form-input counter-display text-center">1</div>
                            <button type="button" class="btn btn-plus" data-target="guest_count">+</button>
                        </div>
                    </div>
                    
                    <div class="col mb-3">
                        <label class="detail-info__text mb-1">Дети</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-minus" data-target="children_count">-</button>
                            <div id="children_count_display" class="form-input counter-display text-center">0</div>
                            <button type="button" class="btn btn-plus" data-target="children_count">+</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-2 mt-2 align-self-center d-flex justify-content-center">
                <button class="col-12 card-btn" type="submit" name="sunapartament_booking_submit">Показать</button>  
            </div>
        </div>
    </form>
</div>