<?php
// Добавьте этот метод в класс BookingService

/**
 * Создает новое бронирование
 *
 * @param array $booking_data Данные бронирования
 * @return string|bool ID бронирования или false в случае ошибки
 */
public function create_booking($booking_data) {
    // Генерация ID бронирования
    $booking_id = 'SA-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    $booking_data['booking_id'] = $booking_id;
    
    // Проверка обязательных полей
    $required_fields = ['apartament_id', 'checkin_date', 'checkout_date', 'first_name', 'last_name', 'email', 'phone'];
    
    foreach ($required_fields as $field) {
        if (empty($booking_data[$field])) {
            $this->log('Ошибка создания бронирования: отсутствует поле ' . $field, 'error');
            return false;
        }
    }
    
    // Преобразуем даты в формат Y-m-d для корректной работы
    $checkin_formatted = date('Y-m-d', strtotime($booking_data['checkin_date']));
    $checkout_formatted = date('Y-m-d', strtotime($booking_data['checkout_date']));
    
    // Проверка доступности апартамента
    $apartament = new \Sun\Apartament\Core\Apartament($booking_data['apartament_id']);
    if (!$apartament || !$apartament->get_id()) {
        $this->log('Ошибка создания бронирования: апартамент не найден', 'error');
        return false;
    }
    
    if (!$apartament->is_available($checkin_formatted, $checkout_formatted)) {
        $this->log('Ошибка создания бронирования: апартамент недоступен на указанные даты', 'error');
        return false;
    }
    
    // Сохраняем сначала в базу данных, если таблицы существуют
    $db_result = $this->save_booking_to_db($booking_data);
    
    // Затем сохраняем как пост для обратной совместимости
    $post_result = $this->save_booking_as_post($booking_data);
    
    // Если одно из сохранений успешно, считаем бронирование созданным
    if ($db_result || $post_result) {
        // Отправляем уведомления
        $this->send_booking_notifications($booking_data);
        
        // Обновляем доступность апартамента
        $this->update_apartament_availability(
            $booking_data['apartament_id'], 
            $checkin_formatted, 
            $checkout_formatted, 
            $booking_id
        );
        
        return $booking_id;
    }
    
    return false;
}

/**
 * Сохраняет бронирование в базу данных
 *
 * @param array $booking_data Данные бронирования
 * @return bool Результат операции
 */
private function save_booking_to_db($booking_data) {
    global $wpdb;
    
    // Проверяем наличие таблиц
    $personal_data_table = $wpdb->prefix . 'sun_personal_data';
    $bookings_table = $wpdb->prefix . 'sun_bookings';
    
    $personal_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$personal_data_table'") === $personal_data_table;
    $bookings_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") === $bookings_table;
    
    // Если таблиц нет, пытаемся создать их
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
                    'middle_name' => $booking_data['middle_name'] ?? '',
                    'email' => $booking_data['email'],
                    'phone' => $booking_data['phone'],
                    'created_at' => current_time('mysql')
                ],
                ['%s', '%s', '%s', '%s', '%s', '%s']
            );
            
            if ($personal_result === false) {
                $this->log('Ошибка при добавлении персональных данных: ' . $wpdb->last_error, 'error');
                return false;
            }
            
            $personal_data_id = $wpdb->insert_id;
            
            if (!$personal_data_id) {
                $this->log('Не удалось получить ID персональных данных', 'error');
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
                $this->log('Ошибка при добавлении бронирования: ' . $wpdb->last_error, 'error');
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            $this->log('Исключение при сохранении данных: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    return false;
}

/**
 * Сохраняет бронирование как запись WordPress
 *
 * @param array $booking_data Данные бронирования
 * @return bool Результат операции
 */
private function save_booking_as_post($booking_data) {
    // Проверка: не существует ли уже бронирования с такими же данными
    $existing_booking = get_posts([
        'post_type' => 'sun_booking',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => '_booking_apartament_id',
                'value' => $booking_data['apartament_id']
            ],
            [
                'key' => '_booking_email',
                'value' => $booking_data['email']
            ],
            [
                'key' => '_booking_checkin_date',
                'value' => date('d.m.Y', strtotime($booking_data['checkin_date']))
            ],
            [
                'key' => '_booking_checkout_date',
                'value' => date('d.m.Y', strtotime($booking_data['checkout_date']))
            ]
        ],
        'posts_per_page' => 1,
        'post_status' => 'any'
    ]);

    // Если бронирование с такими данными уже существует, используем его
    if (!empty($existing_booking)) {
        $post_id = $existing_booking[0]->ID;
        $booking_id = $existing_booking[0]->post_title;
        $this->log('Найдено существующее бронирование: ' . $post_id, 'info');
        return true;
    }
    
    // ОЧЕНЬ ВАЖНО: Полностью отключаем хуки save_post
    global $wp_filter;
    $save_post_actions = isset($wp_filter['save_post']) ? $wp_filter['save_post'] : null;

    // Очищаем все действия хука save_post
    $wp_filter['save_post'] = new \WP_Hook();

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

    // Восстанавливаем действия хука save_post
    if ($save_post_actions) {
        $wp_filter['save_post'] = $save_post_actions;
    }

    if (is_wp_error($post_id)) {
        $this->log('Ошибка при создании бронирования в WordPress: ' . $post_id->get_error_message(), 'error');
        return false;
    }

    // Устанавливаем метаполя вручную
    update_post_meta($post_id, '_booking_apartament_id', $booking_data['apartament_id']);
    update_post_meta($post_id, '_booking_first_name', $booking_data['first_name']);
    update_post_meta($post_id, '_booking_last_name', $booking_data['last_name']);
    update_post_meta($post_id, '_booking_middle_name', $booking_data['middle_name'] ?? '');
    update_post_meta($post_id, '_booking_email', $booking_data['email']);
    update_post_meta($post_id, '_booking_phone', $booking_data['phone']);
    update_post_meta($post_id, '_booking_terms_accepted', $booking_data['terms_accepted']);
    update_post_meta($post_id, '_booking_guest_count', $booking_data['guest_count'] ?? 1);
    update_post_meta($post_id, '_booking_children_count', $booking_data['children_count'] ?? 0);

    // Форматируем даты в нужный формат d.m.Y
    update_post_meta($post_id, '_booking_checkin_date', date('d.m.Y', strtotime($booking_data['checkin_date'])));
    update_post_meta($post_id, '_booking_checkout_date', date('d.m.Y', strtotime($booking_data['checkout_date'])));

    update_post_meta($post_id, '_booking_total_price', $booking_data['total_price']);
    update_post_meta($post_id, '_booking_payment_method', $booking_data['payment_method']);
    update_post_meta($post_id, '_booking_created_at', current_time('mysql'));
    
    return true;
}

/**
 * Обновляет данные о доступности апартамента
 *
 * @param int $apartament_id ID апартамента
 * @param string $checkin_date Дата заезда
 * @param string $checkout_date Дата выезда
 * @param string $booking_id ID бронирования
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
    
    // Обновляем старые метаданные для совместимости
    $bookings = get_post_meta($apartament_id, '_apartament_bookings', true);
    $bookings = $bookings ? json_decode($bookings, true) : [];
    
    // Добавляем в старую структуру только если еще нет
    if (!isset($bookings[$booking_id])) {
        $bookings[$booking_id] = [
            'first_name' => $booking_data['first_name'],
            'last_name' => $booking_data['last_name'],
            'middle_name' => $booking_data['middle_name'] ?? '',
            'email' => $booking_data['email'],
            'phone' => $booking_data['phone'],
            'checkin_date' => date('d.m.Y', strtotime($booking_data['checkin_date'])),
            'checkout_date' => date('d.m.Y', strtotime($booking_data['checkout_date'])),
            'payment_method' => $booking_data['payment_method'],
            'booking_date' => current_time('mysql'),
            'total_price' => $booking_data['total_price'],
            'status' => $booking_data['status'],
            'booking_id' => $booking_id,
            'guest_count' => $booking_data['guest_count'] ?? 1,
            'children_count' => $booking_data['children_count'] ?? 0,
            'terms_accepted' => $booking_data['terms_accepted']
        ];
        update_post_meta($apartament_id, '_apartament_bookings', json_encode($bookings));
    }
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