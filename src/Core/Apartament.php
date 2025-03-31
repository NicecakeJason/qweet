<?php
namespace Sun\Apartament\Core;

/**
 * Класс модели апартамента
 *
 * Представляет собой модель данных апартамента и методы для работы с ней.
 *
 * @since 1.0.0
 */
class Apartament {
    /**
     * ID апартамента
     *
     * @var int
     */
    private $id;
    
    /**
     * Название апартамента
     *
     * @var string
     */
    private $title;
    
    /**
     * Описание апартамента
     *
     * @var string
     */
    private $description;
    
    /**
     * Площадь апартамента
     *
     * @var float
     */
    private $square_footage;
    
    /**
     * Максимальное количество гостей
     *
     * @var int
     */
    private $guest_count;
    
    /**
     * Этаж, на котором находится апартамент
     *
     * @var int
     */
    private $floor_count;
    
    /**
     * Массив изображений апартамента
     *
     * @var array
     */
    private $gallery;
    
    /**
     * Массив удобств апартамента
     *
     * @var array
     */
    private $amenities;
    
    /**
     * Конструктор класса
     *
     * @param int|WP_Post $apartament_id ID или объект апартамента
     */
    public function __construct($apartament_id = 0) {
        if ($apartament_id instanceof \WP_Post) {
            $this->id = $apartament_id->ID;
        } elseif (is_numeric($apartament_id) && $apartament_id > 0) {
            $this->id = $apartament_id;
        } else {
            $this->id = 0;
        }
        
        if ($this->id > 0) {
            $this->load();
        }
    }
    
    /**
     * Загружает данные апартамента из базы
     *
     * @return bool Результат загрузки
     */
    public function load() {
        if ($this->id <= 0) {
            return false;
        }
        
        $post = get_post($this->id);
        
        if (!$post || $post->post_type !== 'apartament') {
            return false;
        }
        
        $this->title = $post->post_title;
        $this->description = $post->post_content;
        
        // Загрузка мета-данных
        $this->square_footage = get_post_meta($this->id, 'sunapartament_square_footage', true);
        $this->guest_count = get_post_meta($this->id, 'sunapartament_guest_count', true);
        $this->floor_count = get_post_meta($this->id, 'sunapartament_floor_count', true);
        
        // Загрузка галереи
        $this->gallery = get_post_meta($this->id, 'sunapartament_gallery', true);
        if (!is_array($this->gallery)) {
            $this->gallery = [];
        }
        
        // Загрузка удобств
        $this->amenities = get_post_meta($this->id, 'sunapartament_additional_amenities', true);
        if (!is_array($this->amenities)) {
            $this->amenities = [];
        }
        
        return true;
    }
    
    /**
     * Сохраняет данные апартамента в базу
     *
     * @return int|bool ID апартамента или false в случае ошибки
     */
    public function save() {
        $post_data = [
            'post_title'   => $this->title,
            'post_content' => $this->description,
            'post_type'    => 'apartament',
            'post_status'  => 'publish',
        ];
        
        if ($this->id > 0) {
            $post_data['ID'] = $this->id;
            $result = wp_update_post($post_data);
        } else {
            $result = wp_insert_post($post_data);
            if ($result) {
                $this->id = $result;
            }
        }
        
        if ($result) {
            // Сохраняем мета-данные
            update_post_meta($this->id, 'sunapartament_square_footage', $this->square_footage);
            update_post_meta($this->id, 'sunapartament_guest_count', $this->guest_count);
            update_post_meta($this->id, 'sunapartament_floor_count', $this->floor_count);
            
            // Сохраняем галерею
            update_post_meta($this->id, 'sunapartament_gallery', $this->gallery);
            
            // Сохраняем удобства
            update_post_meta($this->id, 'sunapartament_additional_amenities', $this->amenities);
        }
        
        return $result;
    }
    
    /**
     * Проверяет доступность апартамента на указанные даты
     *
     * @param string $checkin_date Дата заезда (Y-m-d)
     * @param string $checkout_date Дата выезда (Y-m-d)
     * @param int $exclude_booking_id ID бронирования для исключения (опционально)
     * @return bool Доступен ли апартамент
     */
    public function is_available($checkin_date, $checkout_date, $exclude_booking_id = null) {
        // Получаем занятые даты
        $booked_dates = get_post_meta($this->id, '_apartament_booked_dates', true);
        
        if (!is_array($booked_dates)) {
            $booked_dates = [];
        }
        
        // Проверяем каждую дату в диапазоне
        $start = new \DateTime($checkin_date);
        $end = new \DateTime($checkout_date);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            $date_string = $date->format('Y-m-d');
            
            // Проверяем, занята ли дата другим бронированием
            if (isset($booked_dates[$date_string]) && 
                ($exclude_booking_id === null || $booked_dates[$date_string] != $exclude_booking_id)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Получает цену апартамента на указанную дату
     *
     * @param string $date Дата (Y-m-d)
     * @return float Цена
     */
    public function get_price_for_date($date) {
        $date_obj = new \DateTime($date);
        $year = $date_obj->format('Y');
        $month = $date_obj->format('n');
        $day = $date_obj->format('j');
        
        // Получаем сохраненные цены
        $prices_json = get_post_meta($this->id, 'sunapartament_daily_prices', true);
        $prices = $prices_json ? json_decode($prices_json, true) : [];
        
        // Проверяем наличие цены для указанной даты
        if (isset($prices[$year][$month][$day]) && is_numeric($prices[$year][$month][$day])) {
            return (float)$prices[$year][$month][$day];
        }
        
        // Если цена не найдена, возвращаем 0
        return 0;
    }
    
    /**
     * Получает цены апартамента на период
     *
     * @param string $start_date Дата начала (Y-m-d)
     * @param string $end_date Дата окончания (Y-m-d)
     * @return array Информация о ценах на период
     */
    public function get_prices_for_period($start_date, $end_date) {
        // Преобразование строковых дат
        $start = new \DateTime($start_date);
        $end = new \DateTime($end_date);
        
        // Установка времени в 00:00:00
        $start->setTime(0, 0, 0);
        $end->setTime(0, 0, 0);
        
        // Рассчитываем количество ночей
        $nights = $end->diff($start)->days;
        
        // Рассчитываем цены
        $total_price = 0;
        $daily_prices = [];
        
        $current = clone $start;
        for ($i = 0; $i < $nights; $i++) {
            $date_str = $current->format('Y-m-d');
            $price = $this->get_price_for_date($date_str);
            
            $daily_prices[$date_str] = $price;
            $total_price += $price;
            
            $current->modify('+1 day');
        }
        
        return [
            'nights' => $nights,
            'total_price' => $total_price,
            'daily_prices' => $daily_prices
        ];
    }
    
    // Геттеры и сеттеры
    
    /**
     * Получает ID апартамента
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Получает название апартамента
     *
     * @return string
     */
    public function get_title() {
        return $this->title;
    }
    
    /**
     * Устанавливает название апартамента
     *
     * @param string $title Название
     * @return void
     */
    public function set_title($title) {
        $this->title = $title;
    }
    
    /**
     * Получает описание апартамента
     *
     * @return string
     */
    public function get_description() {
        return $this->description;
    }
    
    /**
     * Устанавливает описание апартамента
     *
     * @param string $description Описание
     * @return void
     */
    public function set_description($description) {
        $this->description = $description;
    }
    
    /**
     * Получает площадь апартамента
     *
     * @return float
     */
    public function get_square_footage() {
        return $this->square_footage;
    }
    
    /**
     * Устанавливает площадь апартамента
     *
     * @param float $square_footage Площадь
     * @return void
     */
    public function set_square_footage($square_footage) {
        $this->square_footage = $square_footage;
    }
    
    /**
     * Получает максимальное количество гостей
     *
     * @return int
     */
    public function get_guest_count() {
        return $this->guest_count;
    }
    
    /**
     * Устанавливает максимальное количество гостей
     *
     * @param int $guest_count Количество гостей
     * @return void
     */
    public function set_guest_count($guest_count) {
        $this->guest_count = $guest_count;
    }
    
    /**
     * Получает этаж
     *
     * @return int
     */
    public function get_floor_count() {
        return $this->floor_count;
    }
    
    /**
     * Устанавливает этаж
     *
     * @param int $floor_count Этаж
     * @return void
     */
    public function set_floor_count($floor_count) {
        $this->floor_count = $floor_count;
    }
    
    /**
     * Получает галерею изображений
     *
     * @return array
     */
    public function get_gallery() {
        return $this->gallery;
    }
    
    /**
     * Устанавливает галерею изображений
     *
     * @param array $gallery Массив ID изображений
     * @return void
     */
    public function set_gallery($gallery) {
        if (is_array($gallery)) {
            $this->gallery = $gallery;
        }
    }
    
    /**
     * Получает удобства апартамента
     *
     * @return array
     */
    public function get_amenities() {
        return $this->amenities;
    }
    
    /**
     * Устанавливает удобства апартамента
     *
     * @param array $amenities Массив удобств
     * @return void
     */
    public function set_amenities($amenities) {
        if (is_array($amenities)) {
            $this->amenities = $amenities;
        }
    }
}