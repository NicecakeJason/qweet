<?php
namespace Sun\Apartament\Admin;

use Sun\Apartament\Core\Apartament;
use Sun\Apartament\Core\Booking;
use Sun\Apartament\Services\BookingService;

/**
 * Класс для отображения календаря доступности
 *
 * @since 1.0.0
 */
class Calendar {
    /**
     * Регистрирует хуки
     *
     * @return void
     */
    public function register() {
        // Этот метод не используется напрямую, так как класс Calendar инициализируется через Menu
        
        // Добавляем AJAX-обработчики для календаря
        add_action('wp_ajax_sun_get_calendar_events', [$this, 'ajax_get_calendar_events']);
        add_action('wp_ajax_sun_create_calendar_booking', [$this, 'ajax_create_calendar_booking']);
        add_action('wp_ajax_sun_update_calendar_booking', [$this, 'ajax_update_calendar_booking']);
    }
    
    /**
     * Отображает страницу календаря
     *
     * @return void
     */
    public function render() {
        // Получаем список апартаментов
        $apartaments = get_posts([
            'post_type' => 'apartament',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        // Заголовок страницы
        echo '<div class="wrap sun-calendar-page">';
        echo '<h1 class="wp-heading-inline">' . __('Календарь доступности апартаментов', 'sun-apartament') . '</h1>';
        
        // Фильтры календаря
        echo '<div class="sun-calendar-filters">';
        echo '<select id="apartment-filter">';
        echo '<option value="0">' . __('Все апартаменты', 'sun-apartament') . '</option>';
        
        foreach ($apartaments as $apartament) {
            echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
        
        // Контейнер для календаря
        echo '<div id="sun-calendar" class="sun-calendar-container"></div>';
        
        // Модальное окно для создания/редактирования бронирования
        $this->render_booking_modal();
        
        // Инициализация календаря через JavaScript
        $this->init_calendar_script();
        
        echo '</div>'; // Конец wrap
    }
    
    /**
     * Отображает модальное окно для создания/редактирования бронирования
     *
     * @return void
     */
    private function render_booking_modal() {
        // Получаем список апартаментов
        $apartaments = get_posts([
            'post_type' => 'apartament',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<div id="booking-modal" class="sun-booking-modal" style="display: none;">';
        echo '<div class="sun-booking-modal-content">';
        echo '<span class="sun-booking-modal-close">&times;</span>';
        
        echo '<h2 id="booking-modal-title">' . __('Новое бронирование', 'sun-apartament') . '</h2>';
        
        echo '<form id="booking-form">';
        echo '<input type="hidden" id="booking_id" name="booking_id" value="">';
        
        // Апартамент
        echo '<div class="form-row">';
        echo '<label for="apartament_id">' . __('Апартамент', 'sun-apartament') . ':</label>';
        echo '<select id="apartament_id" name="apartament_id" required>';
        echo '<option value="">' . __('- Выберите апартамент -', 'sun-apartament') . '</option>';
        
        foreach ($apartaments as $apartament) {
            echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
        
        // Даты бронирования
        // Даты бронирования
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="checkin_date">' . __('Дата заезда', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="checkin_date" name="checkin_date" class="datepicker" required>';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="checkout_date">' . __('Дата выезда', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="checkout_date" name="checkout_date" class="datepicker" required>';
        echo '</div>';
        echo '</div>';
        
        // Информация о госте
        echo '<h3>' . __('Информация о госте', 'sun-apartament') . '</h3>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="last_name">' . __('Фамилия', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="last_name" name="last_name" required>';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="first_name">' . __('Имя', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="first_name" name="first_name" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="middle_name">' . __('Отчество', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="middle_name" name="middle_name">';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="email">' . __('Email', 'sun-apartament') . ':</label>';
        echo '<input type="email" id="email" name="email" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="phone">' . __('Телефон', 'sun-apartament') . ':</label>';
        echo '<input type="text" id="phone" name="phone" required>';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="payment_method">' . __('Способ оплаты', 'sun-apartament') . ':</label>';
        echo '<select id="payment_method" name="payment_method">';
        echo '<option value="card">' . __('Банковская карта', 'sun-apartament') . '</option>';
        echo '<option value="cash">' . __('Наличными при заезде', 'sun-apartament') . '</option>';
        echo '<option value="transfer">' . __('Банковский перевод', 'sun-apartament') . '</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="guest_count">' . __('Взрослые', 'sun-apartament') . ':</label>';
        echo '<input type="number" id="guest_count" name="guest_count" min="1" value="1" required>';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="children_count">' . __('Дети', 'sun-apartament') . ':</label>';
        echo '<input type="number" id="children_count" name="children_count" min="0" value="0">';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-row">';
        echo '<div class="form-group half">';
        echo '<label for="total_price">' . __('Сумма, руб.', 'sun-apartament') . ':</label>';
        echo '<input type="number" id="total_price" name="total_price" min="0" required>';
        echo '</div>';
        
        echo '<div class="form-group half">';
        echo '<label for="status">' . __('Статус', 'sun-apartament') . ':</label>';
        echo '<select id="status" name="status">';
        echo '<option value="pending">' . __('Ожидает подтверждения', 'sun-apartament') . '</option>';
        echo '<option value="confirmed">' . __('Подтверждено', 'sun-apartament') . '</option>';
        echo '<option value="cancelled">' . __('Отменено', 'sun-apartament') . '</option>';
        echo '<option value="completed">' . __('Завершено', 'sun-apartament') . '</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        // Кнопки действий
        echo '<div class="form-actions">';
        echo '<button type="button" id="booking-delete" class="button button-link-delete" style="display: none;">' . __('Удалить', 'sun-apartament') . '</button>';
        echo '<button type="submit" id="booking-save" class="button button-primary">' . __('Сохранить', 'sun-apartament') . '</button>';
        echo '<button type="button" id="booking-cancel" class="button">' . __('Отмена', 'sun-apartament') . '</button>';
        echo '</div>';
        
        echo '</form>';
        
        echo '</div>'; // Конец modal-content
        echo '</div>'; // Конец modal
    }
    
    /**
     * Инициализирует скрипт для календаря
     *
     * @return void
     */
    private function init_calendar_script() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Локализация календаря
            const calendarEl = document.getElementById('sun-calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                initialView: 'dayGridMonth',
                locale: 'ru',
                selectable: true,
                selectMirror: true,
                editable: true,
                dayMaxEvents: true,
                events: function(info, successCallback, failureCallback) {
                    // Получаем события из AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'sun_get_calendar_events',
                            nonce: sunCalendarData.nonce,
                            start: info.startStr,
                            end: info.endStr,
                            apartament_id: $('#apartment-filter').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                successCallback(response.data.events);
                            } else {
                                failureCallback(response.data.message);
                            }
                        },
                        error: function() {
                            failureCallback('Ошибка загрузки данных календаря');
                        }
                    });
                },
                // Обработчик клика на событие (бронирование)
                eventClick: function(info) {
                    const bookingId = info.event.id;
                    
                    // Загружаем данные бронирования через AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'sun_get_booking_data',
                            nonce: sunCalendarData.nonce,
                            booking_id: bookingId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Заполняем форму модального окна
                                fillBookingForm(response.data.booking);
                                
                                // Отображаем модальное окно для редактирования
                                $('#booking-modal-title').text('Редактирование бронирования');
                                $('#booking-delete').show();
                                $('#booking-modal').show();
                            } else {
                                alert(response.data.message);
                            }
                        },
                        error: function() {
                            alert('Ошибка загрузки данных бронирования');
                        }
                    });
                },
                // Обработчик выбора диапазона дат
                select: function(info) {
                    // Очищаем форму
                    clearBookingForm();
                    
                    // Устанавливаем выбранные даты
                    $('#checkin_date').val(formatDate(info.start));
                    $('#checkout_date').val(formatDate(info.end));
                    
                    // Отображаем модальное окно для создания
                    $('#booking-modal-title').text('Новое бронирование');
                    $('#booking-delete').hide();
                    $('#booking-modal').show();
                }
            });
            
            calendar.render();
            
            // Фильтр по апартаментам
            $('#apartment-filter').change(function() {
                calendar.refetchEvents();
            });
            
            // Обработчики для модального окна
            $('.sun-booking-modal-close, #booking-cancel').click(function() {
                $('#booking-modal').hide();
            });
            
            // Обработчик отправки формы
            $('#booking-form').submit(function(e) {
                e.preventDefault();
                
                const bookingId = $('#booking_id').val();
                const isNew = !bookingId;
                
                // Собираем данные формы
                const formData = {
                    booking_id: bookingId,
                    apartament_id: $('#apartament_id').val(),
                    checkin_date: $('#checkin_date').val(),
                    checkout_date: $('#checkout_date').val(),
                    first_name: $('#first_name').val(),
                    last_name: $('#last_name').val(),
                    middle_name: $('#middle_name').val(),
                    email: $('#email').val(),
                    phone: $('#phone').val(),
                    guest_count: $('#guest_count').val(),
                    children_count: $('#children_count').val(),
                    total_price: $('#total_price').val(),
                    payment_method: $('#payment_method').val(),
                    status: $('#status').val()
                };
                
                // Отправляем данные через AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: isNew ? 'sun_create_calendar_booking' : 'sun_update_calendar_booking',
                        nonce: sunCalendarData.nonce,
                        booking: formData
                    },
                    success: function(response) {
                        if (response.success) {
                            // Скрываем модальное окно
                            $('#booking-modal').hide();
                            
                            // Обновляем события календаря
                            calendar.refetchEvents();
                            
                            // Выводим сообщение об успехе
                            alert(isNew ? 'Бронирование успешно создано' : 'Бронирование успешно обновлено');
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('Ошибка при сохранении бронирования');
                    }
                });
            });
            
            // Обработчик удаления бронирования
            $('#booking-delete').click(function() {
                if (confirm('Вы уверены, что хотите удалить это бронирование?')) {
                    const bookingId = $('#booking_id').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'sun_delete_calendar_booking',
                            nonce: sunCalendarData.nonce,
                            booking_id: bookingId
                        },
                        success: function(response) {
                            if (response.success) {
                                // Скрываем модальное окно
                                $('#booking-modal').hide();
                                
                                // Обновляем события календаря
                                calendar.refetchEvents();
                                
                                // Выводим сообщение об успехе
                                alert('Бронирование успешно удалено');
                            } else {
                                alert(response.data.message);
                            }
                        },
                        error: function() {
                            alert('Ошибка при удалении бронирования');
                        }
                    });
                }
            });
            
            // Инициализация выбора дат
            $('.datepicker').datepicker({
                dateFormat: 'dd.mm.yy',
                changeMonth: true,
                changeYear: true,
                firstDay: 1,
                dayNames: ['Воскресенье', 'Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота'],
                dayNamesMin: ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                monthNames: ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                monthNamesShort: ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек']
            });
            
            // Функция форматирования даты
            function formatDate(date) {
                const d = new Date(date);
                let day = d.getDate();
                let month = d.getMonth() + 1;
                const year = d.getFullYear();
                
                if (day < 10) day = '0' + day;
                if (month < 10) month = '0' + month;
                
                return day + '.' + month + '.' + year;
            }
            
            // Функция заполнения формы данными бронирования
            function fillBookingForm(booking) {
                $('#booking_id').val(booking.id);
                $('#apartament_id').val(booking.apartament_id);
                $('#checkin_date').val(booking.checkin_date);
                $('#checkout_date').val(booking.checkout_date);
                $('#first_name').val(booking.first_name);
                $('#last_name').val(booking.last_name);
                $('#middle_name').val(booking.middle_name || '');
                $('#email').val(booking.email);
                $('#phone').val(booking.phone);
                $('#guest_count').val(booking.guest_count);
                $('#children_count').val(booking.children_count);
                $('#total_price').val(booking.total_price);
                $('#payment_method').val(booking.payment_method);
                $('#status').val(booking.status);
            }
            
            // Функция очистки формы
            function clearBookingForm() {
                $('#booking_id').val('');
                $('#apartament_id').val('');
                $('#checkin_date').val('');
                $('#checkout_date').val('');
                $('#first_name').val('');
                $('#last_name').val('');
                $('#middle_name').val('');
                $('#email').val('');
                $('#phone').val('');
                $('#guest_count').val(1);
                $('#children_count').val(0);
                $('#total_price').val('');
                $('#payment_method').val('card');
                $('#status').val('pending');
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX-обработчик для получения событий календаря
     *
     * @return void
     */
    public function ajax_get_calendar_events() {
        // Проверяем nonce для безопасности
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-calendar-nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности']);
        }
        
        // Получаем параметры запроса
        $start = isset($_POST['start']) ? sanitize_text_field($_POST['start']) : '';
        $end = isset($_POST['end']) ? sanitize_text_field($_POST['end']) : '';
        $apartament_id = isset($_POST['apartament_id']) ? intval($_POST['apartament_id']) : 0;
        
        // Подготавливаем аргументы для получения бронирований
        $args = [
            'post_type' => 'sun_booking',
            'posts_per_page' => -1,
            'post_status' => ['pending', 'confirmed'],
            'meta_query' => []
        ];
        
        // Если указан конкретный апартамент, фильтруем по нему
        if ($apartament_id > 0) {
            $args['meta_query'][] = [
                'key' => '_booking_apartament_id',
                'value' => $apartament_id,
                'compare' => '='
            ];
        }
        
        // Получаем бронирования
        $bookings_query = new \WP_Query($args);
        $events = [];
        
        if ($bookings_query->have_posts()) {
            while ($bookings_query->have_posts()) {
                $bookings_query->the_post();
                $booking_id = get_the_ID();
                $apartament_id = get_post_meta($booking_id, '_booking_apartament_id', true);
                $checkin_date = get_post_meta($booking_id, '_booking_checkin_date', true);
                $checkout_date = get_post_meta($booking_id, '_booking_checkout_date', true);
                $first_name = get_post_meta($booking_id, '_booking_first_name', true);
                $last_name = get_post_meta($booking_id, '_booking_last_name', true);
                $status = get_post_status();
                
                // Форматируем даты для FullCalendar
                $checkin_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkin_date)));
                $checkout_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkout_date)));
                
                // Определяем цвет события в зависимости от статуса
                $color = ($status === 'confirmed') ? '#4CAF50' : '#FF9800';
                
                // Создаем событие для календаря
                $events[] = [
                    'id' => $booking_id,
                    'title' => $last_name . ' ' . $first_name,
                    'start' => $checkin_formatted,
                    'end' => $checkout_formatted,
                    'allDay' => true,
                    'color' => $color,
                    'extendedProps' => [
                        'apartament_id' => $apartament_id,
                        'apartament_title' => get_the_title($apartament_id),
                        'status' => $status
                    ]
                ];
            }
            
            wp_reset_postdata();
        }
        
        // Возвращаем события
        wp_send_json_success(['events' => $events]);
    }
    
    /**
     * AJAX-обработчик для создания бронирования из календаря
     *
     * @return void
     */
    public function ajax_create_calendar_booking() {
        // Проверяем nonce для безопасности
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-calendar-nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности']);
        }
        
        // Получаем данные бронирования
        $booking_data = isset($_POST['booking']) ? $_POST['booking'] : [];
        
        if (empty($booking_data)) {
            wp_send_json_error(['message' => 'Отсутствуют данные бронирования']);
        }
        
        // Проверяем обязательные поля
        $required_fields = ['apartament_id', 'checkin_date', 'checkout_date', 'first_name', 'last_name', 'email', 'phone'];
        
        foreach ($required_fields as $field) {
            if (empty($booking_data[$field])) {
                wp_send_json_error(['message' => 'Не заполнены обязательные поля']);
            }
        }
        
        // Создаем бронирование
        $booking_service = new BookingService();
        $result = $booking_service->create_booking($booking_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'Бронирование успешно создано', 'booking_id' => $result]);
        } else {
            wp_send_json_error(['message' => 'Ошибка при создании бронирования']);
        }
    }
    
    /**
     * AJAX-обработчик для обновления бронирования из календаря
     *
     * @return void
     */
    public function ajax_update_calendar_booking() {
        // Проверяем nonce для безопасности
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-calendar-nonce')) {
            wp_send_json_error(['message' => 'Ошибка безопасности']);
        }
        
        // Получаем данные бронирования
        $booking_data = isset($_POST['booking']) ? $_POST['booking'] : [];
        
        if (empty($booking_data) || empty($booking_data['booking_id'])) {
            wp_send_json_error(['message' => 'Отсутствуют данные бронирования']);
        }
        
        // Обновляем бронирование
        $booking_service = new BookingService();
        $result = $booking_service->update_booking($booking_data['booking_id'], $booking_data);
        
        if ($result) {
            wp_send_json_success(['message' => 'Бронирование успешно обновлено']);
        } else {
            wp_send_json_error(['message' => 'Ошибка при обновлении бронирования']);
        }
    }
}