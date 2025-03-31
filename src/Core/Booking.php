<?php
namespace Sun\Apartament\Core;

/**
 * Класс модели бронирования
 *
 * Представляет собой модель данных бронирования и методы для работы с ней.
 *
 * @since 1.0.0
 */
class Booking {
    /**
     * ID бронирования
     *
     * @var int
     */
    private $id;
    
    /**
     * Номер бронирования
     *
     * @var string
     */
    private $booking_id;
    
    /**
     * ID апартамента
     *
     * @var int
     */
    private $apartament_id;
    
    /**
     * Дата заезда
     *
     * @var string
     */
    private $checkin_date;
    
    /**
     * Дата выезда
     *
     * @var string
     */
    private $checkout_date;
    
    /**
     * Имя гостя
     *
     * @var string
     */
    private $first_name;
    
    /**
     * Фамилия гостя
     *
     * @var string
     */
    private $last_name;
    
    /**
     * Отчество гостя
     *
     * @var string
     */
    private $middle_name;
    
    /**
     * Email гостя
     *
     * @var string
     */
    private $email;
    
    /**
     * Телефон гостя
     *
     * @var string
     */
    private $phone;
    
    /**
     * Количество взрослых гостей
     *
     * @var int
     */
    private $guest_count;
    
    /**
     * Количество детей
     *
     * @var int
     */
    private $children_count;
    
    /**
     * Общая стоимость бронирования
     *
     * @var float
     */
    private $total_price;
    
    /**
     * Способ оплаты
     *
     * @var string
     */
    private $payment_method;
    
    /**
     * Статус бронирования
     *
     * @var string
     */
    private $status;
    
    /**
     * Дата создания бронирования
     *
     * @var string
     */
    private $created_at;
    
    /**
     * Флаг согласия с условиями
     *
     * @var bool
     */
    private $terms_accepted;
    
    /**
     * Конструктор класса
     *
     * @param int|WP_Post $booking_id ID или объект бронирования
     */
    public function __construct($booking_id = 0) {
        if ($booking_id instanceof \WP_Post) {
            $this->id = $booking_id->ID;
        } elseif (is_numeric($booking_id) && $booking_id > 0) {
            $this->id = $booking_id;
        } else {
            $this->id = 0;
        }
        
        if ($this->id > 0) {
            $this->load();
        }
    }
    
    /**
     * Загружает данные бронирования из базы
     *
     * @return bool Результат загрузки
     */
    public function load() {
        if ($this->id <= 0) {
            return false;
        }
        
        $post = get_post($this->id);
        
        if (!$post || $post->post_type !== 'sun_booking') {
            return false;
        }
        
        $this->booking_id = $post->post_title;
        $this->status = $post->post_status;
        
        // Загрузка мета-данных
        $this->apartament_id = get_post_meta($this->id, '_booking_apartament_id', true);
        $this->checkin_date = get_post_meta($this->id, '_booking_checkin_date', true);
        $this->checkout_date = get_post_meta($this->id, '_booking_checkout_date', true);
        
        $this->first_name = get_post_meta($this->id, '_booking_first_name', true);
        $this->last_name = get_post_meta($this->id, '_booking_last_name', true);
        $this->middle_name = get_post_meta($this->id, '_booking_middle_name', true);
        $this->email = get_post_meta($this->id, '_booking_email', true);
        $this->phone = get_post_meta($this->id, '_booking_phone', true);
        
        $this->guest_count = get_post_meta($this->id, '_booking_guest_count', true);
        $this->children_count = get_post_meta($this->id, '_booking_children_count', true);
        
        $this->total_price = get_post_meta($this->id, '_booking_total_price', true);
        $this->payment_method = get_post_meta($this->id, '_booking_payment_method', true);
        
        $this->created_at = get_post_meta($this->id, '_booking_created_at', true);
        $this->terms_accepted = get_post_meta($this->id, '_booking_terms_accepted', true);
        
        return true;
    }
    
    /**
     * Сохраняет данные бронирования в базу
     *
     * @return int|bool ID бронирования или false в случае ошибки
     */
    public function save() {
        // Проверяем, что апартамент доступен на выбранные даты
        if ($this->apartament_id > 0 && $this->checkin_date && $this->checkout_date) {
            $apartament = new Apartament($this->apartament_id);
            
            if (!$apartament->is_available($this->checkin_date, $this->checkout_date, $this->id)) {
                return false;
            }
        }
        
        // Если не указан номер бронирования, генерируем его
        if (empty($this->booking_id)) {
            $this->booking_id = $this->generate_booking_number();
        }
        
        // Если не указана дата создания, устанавливаем текущую
        if (empty($this->created_at)) {
            $this->created_at = current_time('mysql');
        }
        
        // Если не указан статус, устанавливаем "pending"
        if (empty($this->status)) {
            $this->status = 'pending';
        }
        
        $post_data = [
            'post_title'   => $this->booking_id,
            'post_type'    => 'sun_booking',
            'post_status'  => $this->status,
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
            update_post_meta($this->id, '_booking_apartament_id', $this->apartament_id);
            update_post_meta($this->id, '_booking_checkin_date', $this->checkin_date);
            update_post_meta($this->id, '_booking_checkout_date', $this->checkout_date);
            
            update_post_meta($this->id, '_booking_first_name', $this->first_name);
            update_post_meta($this->id, '_booking_last_name', $this->last_name);
            update_post_meta($this->id, '_booking_middle_name', $this->middle_name);
            update_post_meta($this->id, '_booking_email', $this->email);
            update_post_meta($this->id, '_booking_phone', $this->phone);
            
            update_post_meta($this->id, '_booking_guest_count', $this->guest_count);
            update_post_meta($this->id, '_booking_children_count', $this->children_count);
            
            update_post_meta($this->id, '_booking_total_price', $this->total_price);
            update_post_meta($this->id, '_booking_payment_method', $this->payment_method);
            
            update_post_meta($this->id, '_booking_created_at', $this->created_at);
            update_post_meta($this->id, '_booking_terms_accepted', $this->terms_accepted);
            
            // Обновляем занятые даты апартамента
            $this->update_apartament_booked_dates();
        }
        
        return $result;
    }
    
    /**
     * Обновляет занятые даты апартамента
     *
     * @return bool Результат обновления
     */
    private function update_apartament_booked_dates() {
        if ($this->apartament_id <= 0 || empty($this->checkin_date) || empty($this->checkout_date)) {
            return false;
        }
        
        // Получаем занятые даты апартамента
        $booked_dates = get_post_meta($this->apartament_id, '_apartament_booked_dates', true);
        
        if (!is_array($booked_dates)) {
            $booked_dates = [];
        }
        
        // Дата в формате Y-m-d
        $checkin_formatted = date('Y-m-d', strtotime($this->checkin_date));
        $checkout_formatted = date('Y-m-d', strtotime($this->checkout_date));
        
        // Генерируем диапазон дат
        $start = new \DateTime($checkin_formatted);
        $end = new \DateTime($checkout_formatted);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);
        
        // Добавляем даты с привязкой к ID бронирования
        foreach ($period as $date) {
            $date_string = $date->format('Y-m-d');
            $booked_dates[$date_string] = $this->id;
        }
        
        // Сохраняем обновленные даты
        update_post_meta($this->apartament_id, '_apartament_booked_dates', $booked_dates);
        
        // Для обратной совместимости также обновляем старое поле _apartament_availability
        $old_availability = get_post_meta($this->apartament_id, '_apartament_availability', true);
        $old_availability = $old_availability ? json_decode($old_availability, true) : [];
        
        foreach ($period as $date) {
            $date_string = $date->format('Y-m-d');
            if (!in_array($date_string, $old_availability)) {
                $old_availability[] = $date_string;
            }
        }
        
        update_post_meta($this->apartament_id, '_apartament_availability', json_encode(array_values($old_availability)));
        
        return true;
    }
    
    /**
     * Генерирует уникальный номер бронирования
     *
     * @return string Номер бронирования
     */
    private function generate_booking_number() {
        $prefix = 'DC-';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -4));
        
        return $prefix . $date . '-' . $random;
    }
    
    /**
     * Отменяет бронирование
     *
     * @return bool Результат отмены
     */
    public function cancel() {
        $this->status = 'cancelled';
        return $this->save();
    }
    
    /**
     * Подтверждает бронирование
     *
     * @return bool Результат подтверждения
     */
    public function confirm() {
        $this->status = 'confirmed';
        return $this->save();
    }
    
    /**
     * Отмечает бронирование как завершенное
     *
     * @return bool Результат
     */
    public function complete() {
        $this->status = 'completed';
        return $this->save();
    }
    
    /**
     * Получает объект апартамента, связанный с бронированием
     *
     * @return Apartament|null Объект апартамента или null
     */
    public function get_apartament() {
        if ($this->apartament_id > 0) {
            return new Apartament($this->apartament_id);
        }
        
        return null;
    }
    
    /**
     * Рассчитывает количество ночей
     *
     * @return int Количество ночей
     */
    public function get_nights_count() {
        if (empty($this->checkin_date) || empty($this->checkout_date)) {
            return 0;
        }
        
        $checkin = new \DateTime($this->checkin_date);
        $checkout = new \DateTime($this->checkout_date);
        
        return $checkout->diff($checkin)->days;
    }
    
    // Геттеры и сеттеры для всех свойств
    
    /**
     * Получает ID бронирования
     *
     * @return int
     */
    public function get_id() {
        return $this->id;
    }
    
    /**
     * Получает номер бронирования
     *
     * @return string
     */
    public function get_booking_id() {
        return $this->booking_id;
    }
    
    /**
     * Устанавливает номер бронирования
     *
     * @param string $booking_id Номер бронирования
     * @return void
     */
    public function set_booking_id($booking_id) {
        $this->booking_id = $booking_id;
    }
    
    /**
     * Получает ID апартамента
     *
     * @return int
     */
    public function get_apartament_id() {
        return $this->apartament_id;
    }
    
    /**
     * Устанавливает ID апартамента
     *
     * @param int $apartament_id ID апартамента
     * @return void
     */
    public function set_apartament_id($apartament_id) {
        $this->apartament_id = $apartament_id;
    }
    
    /**
     * Получает дату заезда
     *
     * @return string
     */
    public function get_checkin_date() {
        return $this->checkin_date;
    }
    
    /**
     * Устанавливает дату заезда
     *
     * @param string $checkin_date Дата заезда
     * @return void
     */
    public function set_checkin_date($checkin_date) {
        $this->checkin_date = $checkin_date;
    }
    
    /**
     * Получает дату выезда
     *
     * @return string
     */
    public function get_checkout_date() {
        return $this->checkout_date;
    }
    
    /**
     * Устанавливает дату выезда
     *
     * @param string $checkout_date Дата выезда
     * @return void
     */
    public function set_checkout_date($checkout_date) {
        $this->checkout_date = $checkout_date;
    }
    
    /**
     * Получает имя гостя
     *
     * @return string
     */
    public function get_first_name() {
        return $this->first_name;
    }
    
    /**
     * Устанавливает имя гостя
     *
     * @param string $first_name Имя
     * @return void
     */
    public function set_first_name($first_name) {
        $this->first_name = $first_name;
    }
    
    /**
     * Получает фамилию гостя
     *
     * @return string
     */
    public function get_last_name() {
        return $this->last_name;
    }
    
    /**
     * Устанавливает фамилию гостя
     *
     * @param string $last_name Фамилия
     * @return void
     */
    public function set_last_name($last_name) {
        $this->last_name = $last_name;
    }
    
    /**
     * Получает отчество гостя
     *
     * @return string
     */
    public function get_middle_name() {
        return $this->middle_name;
    }
    
    /**
     * Устанавливает отчество гостя
     *
     * @param string $middle_name Отчество
     * @return void
     */
    public function set_middle_name($middle_name) {
        $this->middle_name = $middle_name;
    }
    
   /**
     * Получает email гостя
     *
     * @return string
     */
    public function get_email() {
      return $this->email;
  }
  
  /**
   * Устанавливает email гостя
   *
   * @param string $email Email
   * @return void
   */
  public function set_email($email) {
      $this->email = $email;
  }
  
  /**
   * Получает телефон гостя
   *
   * @return string
   */
  public function get_phone() {
      return $this->phone;
  }
  
  /**
   * Устанавливает телефон гостя
   *
   * @param string $phone Телефон
   * @return void
   */
  public function set_phone($phone) {
      $this->phone = $phone;
  }
  
  /**
   * Получает количество взрослых гостей
   *
   * @return int
   */
  public function get_guest_count() {
      return $this->guest_count;
  }
  
  /**
   * Устанавливает количество взрослых гостей
   *
   * @param int $guest_count Количество гостей
   * @return void
   */
  public function set_guest_count($guest_count) {
      $this->guest_count = $guest_count;
  }
  
  /**
   * Получает количество детей
   *
   * @return int
   */
  public function get_children_count() {
      return $this->children_count;
  }
  
  /**
   * Устанавливает количество детей
   *
   * @param int $children_count Количество детей
   * @return void
   */
  public function set_children_count($children_count) {
      $this->children_count = $children_count;
  }
  
  /**
   * Получает общую стоимость
   *
   * @return float
   */
  public function get_total_price() {
      return $this->total_price;
  }
  
  /**
   * Устанавливает общую стоимость
   *
   * @param float $total_price Стоимость
   * @return void
   */
  public function set_total_price($total_price) {
      $this->total_price = $total_price;
  }
  
  /**
   * Получает способ оплаты
   *
   * @return string
   */
  public function get_payment_method() {
      return $this->payment_method;
  }
  
  /**
   * Устанавливает способ оплаты
   *
   * @param string $payment_method Способ оплаты
   * @return void
   */
  public function set_payment_method($payment_method) {
      $this->payment_method = $payment_method;
  }
  
  /**
   * Получает статус бронирования
   *
   * @return string
   */
  public function get_status() {
      return $this->status;
  }
  
  /**
   * Устанавливает статус бронирования
   *
   * @param string $status Статус
   * @return void
   */
  public function set_status($status) {
      $this->status = $status;
  }
  
  /**
   * Получает дату создания
   *
   * @return string
   */
  public function get_created_at() {
      return $this->created_at;
  }
  
  /**
   * Получает флаг согласия с условиями
   *
   * @return bool
   */
  public function get_terms_accepted() {
      return $this->terms_accepted;
  }
  
  /**
   * Устанавливает флаг согласия с условиями
   *
   * @param bool $terms_accepted Флаг
   * @return void
   */
  public function set_terms_accepted($terms_accepted) {
      $this->terms_accepted = $terms_accepted;
  }
}