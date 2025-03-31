<?php
namespace Sun\Apartament\Services;

/**
 * Сервис для отображения информации о бронировании
 *
 * @since 1.0.0
 */
class BookingInfoService {

    /**
     * Сервис управления ценами
     *
     * @var PriceService
     */
    private $price_service;

    /**
     * Конструктор класса
     */
    public function __construct() {
        $this->price_service = new PriceService();
    }

    /**
     * Отображение информации о бронировании
     */
    public function display_booking_info($post_id, $start_date, $end_date) {
        $prices_data = $this->price_service->get_prices_for_period($post_id, $start_date, $end_date);
        
        $output = '<div class="booking-info">';
        $output .= '<p>Период: с ' . date_i18n('d.m.Y', strtotime($start_date)) . ' по ' . date_i18n('d.m.Y', strtotime($end_date)) . '</p>';
        $output .= '<p>Количество ночей: ' . $prices_data['nights'] . '</p>';
        $output .= '<p>Итоговая стоимость: ' . number_format($prices_data['total_price'], 0, '.', ' ') . ' руб.</p>';
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Отображение текущей цены
     */
    public function display_current_price($post_id) {
        return $this->price_service->get_price_for_date($post_id, current_time('Y-m-d'));
    }
}