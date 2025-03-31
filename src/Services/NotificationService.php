<?php
namespace Sun\Apartament\Services;

/**
 * Сервис для отправки уведомлений
 *
 * @since 1.0.0
 */
class NotificationService extends AbstractService {
    /**
     * Регистрирует сервис
     *
     * @return void
     */
    public function register() {
        // Регистрация настроек
        add_action('admin_init', [$this, 'register_notification_settings']);
    }
    
    /**
     * Регистрирует настройки уведомлений
     *
     * @return void
     */
    public function register_notification_settings() {
        // Регистрируем настройки
        register_setting('general', 'sun_booking_telegram_bot_token');
        register_setting('general', 'sun_booking_telegram_chat_id');
        register_setting('general', 'sun_booking_telegram_enabled');
        register_setting('general', 'sun_booking_admin_emails');
        register_setting('general', 'sun_booking_contact_phone');
        register_setting('general', 'sun_booking_contact_email');
        
        // Добавляем секцию настроек
        add_settings_section(
            'sun_booking_notification_settings',
            'Настройки уведомлений о бронированиях',
            [$this, 'render_notification_settings_info'],
            'general'
        );
        
        // Добавляем поля настроек
        add_settings_field(
            'sun_booking_admin_emails',
            'Дополнительные Email для уведомлений',
            [$this, 'render_admin_emails_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        add_settings_field(
            'sun_booking_telegram_enabled',
            'Включить уведомления в Telegram',
            [$this, 'render_telegram_enabled_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        add_settings_field(
            'sun_booking_telegram_bot_token',
            'Токен Telegram-бота',
            [$this, 'render_telegram_bot_token_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        add_settings_field(
            'sun_booking_telegram_chat_id',
            'ID чата Telegram',
            [$this, 'render_telegram_chat_id_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        add_settings_field(
            'sun_booking_contact_phone',
            'Телефон для связи (в письмах)',
            [$this, 'render_contact_phone_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        add_settings_field(
            'sun_booking_contact_email',
            'Email для связи (в письмах)',
            [$this, 'render_contact_email_field'],
            'general',
            'sun_booking_notification_settings'
        );
        
        // Настройка AJAX для тестирования уведомлений
        add_action('wp_ajax_test_telegram_notification', [$this, 'ajax_test_telegram_notification']);
    }
    
    /**
     * Выводит описание секции настроек
     *
     * @return void
     */
    public function render_notification_settings_info() {
        echo '<p>Настройки для отправки уведомлений о бронированиях по Email и в Telegram</p>';
    }
    
    /**
     * Выводит поле для дополнительных email
     *
     * @return void
     */
    public function render_admin_emails_field() {
        $emails = get_option('sun_booking_admin_emails', '');
        echo '<input type="text" name="sun_booking_admin_emails" value="' . esc_attr($emails) . '" style="width: 300px;" />';
        echo '<p class="description">Укажите дополнительные email-адреса через запятую, которые будут получать уведомления о бронированиях</p>';
    }
    
    /**
     * Выводит поле для включения Telegram
     *
     * @return void
     */
    public function render_telegram_enabled_field() {
        $enabled = get_option('sun_booking_telegram_enabled', 'yes');
        echo '<select name="sun_booking_telegram_enabled">
            <option value="yes" ' . selected($enabled, 'yes', false) . '>Включено</option>
            <option value="no" ' . selected($enabled, 'no', false) . '>Отключено</option>
        </select>';
    }
    
    /**
     * Выводит поле для токена бота
     *
     * @return void
     */
    public function render_telegram_bot_token_field() {
        $token = get_option('sun_booking_telegram_bot_token', '');
        echo '<input type="text" name="sun_booking_telegram_bot_token" value="' . esc_attr($token) . '" style="width: 300px;" />';
        echo '<p class="description">Токен, полученный от @BotFather при создании бота</p>';
    }
    
    /**
     * Выводит поле для ID чата
     *
     * @return void
     */
    public function render_telegram_chat_id_field() {
        $chat_id = get_option('sun_booking_telegram_chat_id', '');
        echo '<input type="text" name="sun_booking_telegram_chat_id" value="' . esc_attr($chat_id) . '" style="width: 300px;" />';
        echo '<p class="description">ID чата, куда будут отправляться уведомления (можно узнать через @userinfobot)</p>';
        
        // Кнопка для тестовой отправки
        echo '<p><button type="button" class="button" id="test_telegram_notification">Отправить тестовое уведомление</button></p>';
        
        // JavaScript для отправки тестового уведомления
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#test_telegram_notification').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('Отправка...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_telegram_notification',
                        security: '<?php echo wp_create_nonce('test_telegram_notification'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Тестовое уведомление успешно отправлено!');
                        } else {
                            alert('Ошибка: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('Произошла ошибка при отправке запроса');
                    },
                    complete: function() {
                        button.prop('disabled', false).text('Отправить тестовое уведомление');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Выводит поле для телефона
     *
     * @return void
     */
    public function render_contact_phone_field() {
        $phone = get_option('sun_booking_contact_phone', '+7 (XXX) XXX-XX-XX');
        echo '<input type="text" name="sun_booking_contact_phone" value="' . esc_attr($phone) . '" style="width: 300px;" />';
        echo '<p class="description">Телефон для связи, который будет указан в письме клиенту</p>';
    }
    
    /**
     * Выводит поле для email
     *
     * @return void
     */
    public function render_contact_email_field() {
        $email = get_option('sun_booking_contact_email', get_option('admin_email'));
        echo '<input type="email" name="sun_booking_contact_email" value="' . esc_attr($email) . '" style="width: 300px;" />';
        echo '<p class="description">Email для связи, который будет указан в письме клиенту</p>';
    }
    
    /**
     * AJAX-обработчик отправки тестового уведомления
     *
     * @return void
     */
    public function ajax_test_telegram_notification() {
        check_ajax_referer('test_telegram_notification', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Недостаточно прав для выполнения операции']);
            return;
        }
        
        // Получаем токен бота и ID чата из настроек
        $telegram_bot_token = get_option('sun_booking_telegram_bot_token', '');
        $telegram_chat_id = get_option('sun_booking_telegram_chat_id', '');
        
        // Проверяем, что настройки указаны
        if (empty($telegram_bot_token) || empty($telegram_chat_id)) {
            wp_send_json_error(['message' => 'Не указаны токен Telegram-бота или ID чата в настройках']);
            return;
        }
        
        // Формируем тестовое сообщение
        $message = "<b>🔔 Тестовое уведомление</b>\n\n";
        $message .= "Это тестовое уведомление от системы бронирования апартаментов.\n";
        $message .= "Время отправки: " . current_time('d.m.Y H:i:s') . "\n";
        $message .= "Сайт: " . get_bloginfo('name') . " (" . get_bloginfo('url') . ")\n";
        
        // Формируем URL для запроса к API Telegram
        $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
        
        // Подготавливаем данные для запроса
        $params = [
            'chat_id' => $telegram_chat_id,
            'text' => $message,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        // Выполняем запрос к API Telegram
        $response = wp_remote_post($url, [
            'body' => $params,
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ]
        ]);
        
        // Проверяем результат запроса
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Ошибка: ' . $response->get_error_message()]);
            return;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);
        
        if ($response_code === 200 && isset($response_data['ok']) && $response_data['ok'] === true) {
            wp_send_json_success(['message' => 'Уведомление успешно отправлено']);
        } else {
            $error_msg = isset($response_data['description']) ? $response_data['description'] : 'Неизвестная ошибка';
            wp_send_json_error(['message' => 'Ошибка отправки: ' . $error_msg]);
        }
    }
    
    /**
     * Отправляет уведомления о новом бронировании
     *
     * @param array $booking_data Данные бронирования
     * @return array Результаты отправки
     */
    public function send_booking_notifications($booking_data) {
        $results = [
            'email_client' => false,
            'email_admin' => false,
            'telegram' => false,
            'sms' => false
        ];
        
        // Отправка уведомления клиенту
        $results['email_client'] = $this->send_client_email($booking_data);
        
        // Отправка уведомления администратору
        $results['email_admin'] = $this->send_admin_email($booking_data);
        
        // Отправка уведомления в Telegram
        $results['telegram'] = $this->send_telegram_notification($booking_data);
        
        // Логируем результаты отправки
        error_log('Результаты отправки уведомлений: ' . json_encode($results, JSON_UNESCAPED_UNICODE));
        
        return $results;
    }
    
    /**
     * Отправляет уведомление об отмене бронирования
     *
     * @param array $booking_data Данные бронирования
     * @return array Результаты отправки
     */
    public function send_cancellation_notification($booking_data) {
        $results = [
            'email_client' => false,
            'email_admin' => false,
            'telegram' => false
        ];
        
        // Отправка уведомления клиенту
        $results['email_client'] = $this->send_client_cancellation_email($booking_data);
        
        // Отправка уведомления администратору
        $results['email_admin'] = $this->send_admin_cancellation_email($booking_data);
        
        // Отправка уведомления в Telegram
        $results['telegram'] = $this->send_telegram_cancellation($booking_data);
        
        return $results;
    }
    
    /**
     * Отправляет email о бронировании клиенту
     *
     * @param array $booking_data Данные бронирования
     * @return bool Результат отправки
     */
    private function send_client_email($booking_data) {
        if (empty($booking_data['email'])) {
            error_log('Ошибка отправки email клиенту: не указан email');
            return false;
        }
        
        $to = $booking_data['email'];
        $subject = 'Подтверждение бронирования #' . $booking_data['booking_id'];
        
        // Формируем HTML для письма
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $message = $this->get_client_email_template($booking_data);
        
        // Пробуем отправить письмо с повторными попытками (максимум 3)
        $attempt = 1;
        $max_attempts = 3;
        $success = false;
        
        while ($attempt <= $max_attempts && !$success) {
            $result = wp_mail($to, $subject, $message, $headers);
            
            if ($result) {
                $success = true;
                error_log("Email клиенту {$booking_data['email']} успешно отправлен с попытки #{$attempt}");
            } else {
                error_log("Ошибка отправки email клиенту {$booking_data['email']} (попытка #{$attempt})");
                $attempt++;
                if ($attempt <= $max_attempts) {
                    sleep(2); // Пауза 2 секунды перед следующей попыткой
                }
            }
        }
        
        // Сохраняем письмо в лог, если отправка не удалась
        if (!$success) {
            error_log("Не удалось отправить email клиенту после {$max_attempts} попыток. Email: {$booking_data['email']}");
            
            // Сохраняем копию письма в файл для диагностики
            $log_dir = WP_CONTENT_DIR . '/uploads/email-logs';
            if (!file_exists($log_dir)) {
                wp_mkdir_p($log_dir);
            }
            
            $filename = $log_dir . '/client-' . $booking_data['booking_id'] . '-' . date('YmdHis') . '.html';
            file_put_contents($filename, $message);
        }
        
        return $success;
    }
    
    /**
     * Отправляет email о бронировании администратору
     *
     * @param array $booking_data Данные бронирования
     * @return bool Результат отправки
     */
    private function send_admin_email($booking_data) {
        // Email администратора из настроек
        $admin_email = get_option('admin_email');
        
        // Дополнительные email для уведомлений
        $additional_emails = get_option('sun_booking_admin_emails', '');
        $admin_emails = [$admin_email];
        
        if (!empty($additional_emails)) {
            $additional_emails_array = explode(',', $additional_emails);
            foreach ($additional_emails_array as $email) {
                $email = trim($email);
                if (is_email($email)) {
                    $admin_emails[] = $email;
                }
            }
        }
        
        $subject = 'Новое бронирование #' . $booking_data['booking_id'];
        
        // Формируем HTML для письма
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $message = $this->get_admin_email_template($booking_data);
        
        // Отправляем письмо всем администраторам
        $success = true;
        
        foreach ($admin_emails as $email) {
            $attempt = 1;
            $max_attempts = 3;
            $email_sent = false;
            
            while ($attempt <= $max_attempts && !$email_sent) {
                $result = wp_mail($email, $subject, $message, $headers);
                
                if ($result) {
                    $email_sent = true;
                    error_log("Email администратору {$email} успешно отправлен с попытки #{$attempt}");
                } else {
                    error_log("Ошибка отправки email администратору {$email} (попытка #{$attempt})");
                    $attempt++;
                    if ($attempt <= $max_attempts) {
                        sleep(2); // Пауза 2 секунды перед следующей попыткой
                    }
                }
            }
            
            // Если хотя бы одно письмо не удалось отправить, считаем что была ошибка
            if (!$email_sent) {
               $success = false;
           }
       }
       
       // Сохраняем письмо в лог, если отправка не удалась
       if (!$success) {
           error_log("Не удалось отправить email администраторам. Бронирование: {$booking_data['booking_id']}");
           
           // Сохраняем копию письма в файл для диагностики
           $log_dir = WP_CONTENT_DIR . '/uploads/email-logs';
           if (!file_exists($log_dir)) {
               wp_mkdir_p($log_dir);
           }
           
           $filename = $log_dir . '/admin-' . $booking_data['booking_id'] . '-' . date('YmdHis') . '.html';
           file_put_contents($filename, $message);
       }
       
       return $success;
   }
   
   /**
    * Отправляет уведомление о бронировании в Telegram
    *
    * @param array $booking_data Данные бронирования
    * @return bool Результат отправки
    */
   private function send_telegram_notification($booking_data) {
       // Получаем токен бота и ID чата из настроек
       $telegram_bot_token = get_option('sun_booking_telegram_bot_token', '');
       $telegram_chat_id = get_option('sun_booking_telegram_chat_id', '');
       
       // Проверяем, включены ли Telegram-уведомления
       $telegram_enabled = get_option('sun_booking_telegram_enabled', 'yes');
       
       // Если уведомления отключены или настройки отсутствуют, прекращаем выполнение
       if ($telegram_enabled !== 'yes' || empty($telegram_bot_token) || empty($telegram_chat_id)) {
           error_log('Telegram-уведомления отключены или не указаны необходимые настройки');
           return false;
       }
       
       // Формируем текст сообщения
       $message = $this->get_telegram_message($booking_data);
       
       // Формируем URL для запроса к API Telegram
       $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
       
       // Подготавливаем данные для запроса
       $params = [
           'chat_id' => $telegram_chat_id,
           'text' => $message,
           'parse_mode' => 'HTML',
           'disable_web_page_preview' => true
       ];
       
       // Пробуем отправить сообщение с повторными попытками (максимум 3)
       $attempt = 1;
       $max_attempts = 3;
       $success = false;
       
       while ($attempt <= $max_attempts && !$success) {
           // Выполняем запрос к API Telegram
           $response = wp_remote_post($url, [
               'body' => $params,
               'timeout' => 15,
               'headers' => [
                   'Content-Type' => 'application/x-www-form-urlencoded'
               ]
           ]);
           
           if (!is_wp_error($response)) {
               $response_code = wp_remote_retrieve_response_code($response);
               $response_body = wp_remote_retrieve_body($response);
               $response_data = json_decode($response_body, true);
               
               // Проверяем успешность запроса
               if ($response_code === 200 && isset($response_data['ok']) && $response_data['ok'] === true) {
                   $success = true;
                   error_log("Telegram-уведомление успешно отправлено с попытки #{$attempt}");
               } else {
                   $error_msg = isset($response_data['description']) ? $response_data['description'] : 'Неизвестная ошибка';
                   error_log("Ошибка отправки в Telegram (попытка #{$attempt}): {$error_msg}");
                   $attempt++;
                   if ($attempt <= $max_attempts) {
                       sleep(2); // Пауза 2 секунды перед следующей попыткой
                   }
               }
           } else {
               error_log("Ошибка соединения с Telegram API (попытка #{$attempt}): " . $response->get_error_message());
               $attempt++;
               if ($attempt <= $max_attempts) {
                   sleep(2); // Пауза 2 секунды перед следующей попыткой
               }
           }
       }
       
       // Логируем финальный результат
       if (!$success) {
           error_log("Не удалось отправить Telegram-уведомление после {$max_attempts} попыток.");
       }
       
       return $success;
   }
   
   /**
    * Отправляет клиенту уведомление об отмене бронирования
    *
    * @param array $booking_data Данные бронирования
    * @return bool Результат отправки
    */
   private function send_client_cancellation_email($booking_data) {
       if (empty($booking_data['email'])) {
           return false;
       }
       
       $to = $booking_data['email'];
       $subject = 'Отмена бронирования #' . $booking_data['booking_id'];
       
       // Формируем HTML для письма
       $headers = [
           'Content-Type: text/html; charset=UTF-8',
           'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
       ];
       
       $message = $this->get_client_cancellation_email_template($booking_data);
       
       // Отправляем письмо
       return wp_mail($to, $subject, $message, $headers);
   }
   
   /**
    * Отправляет администратору уведомление об отмене бронирования
    *
    * @param array $booking_data Данные бронирования
    * @return bool Результат отправки
    */
   private function send_admin_cancellation_email($booking_data) {
       // Email администратора из настроек
       $admin_email = get_option('admin_email');
       
       // Дополнительные email для уведомлений
       $additional_emails = get_option('sun_booking_admin_emails', '');
       $admin_emails = [$admin_email];
       
       if (!empty($additional_emails)) {
           $additional_emails_array = explode(',', $additional_emails);
           foreach ($additional_emails_array as $email) {
               $email = trim($email);
               if (is_email($email)) {
                   $admin_emails[] = $email;
               }
           }
       }
       
       $subject = 'Отмена бронирования #' . $booking_data['booking_id'];
       
       // Формируем HTML для письма
       $headers = [
           'Content-Type: text/html; charset=UTF-8',
           'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
       ];
       
       $message = $this->get_admin_cancellation_email_template($booking_data);
       
       // Отправляем письмо всем администраторам
       $success = true;
       
       foreach ($admin_emails as $email) {
           $result = wp_mail($email, $subject, $message, $headers);
           if (!$result) {
               $success = false;
           }
       }
       
       return $success;
   }
   
   /**
    * Отправляет в Telegram уведомление об отмене бронирования
    *
    * @param array $booking_data Данные бронирования
    * @return bool Результат отправки
    */
   private function send_telegram_cancellation($booking_data) {
       // Получаем токен бота и ID чата из настроек
       $telegram_bot_token = get_option('sun_booking_telegram_bot_token', '');
       $telegram_chat_id = get_option('sun_booking_telegram_chat_id', '');
       
       // Проверяем, включены ли Telegram-уведомления
       $telegram_enabled = get_option('sun_booking_telegram_enabled', 'yes');
       
       // Если уведомления отключены или настройки отсутствуют, прекращаем выполнение
       if ($telegram_enabled !== 'yes' || empty($telegram_bot_token) || empty($telegram_chat_id)) {
           return false;
       }
       
       // Формируем текст сообщения
       $message = $this->get_telegram_cancellation_message($booking_data);
       
       // Формируем URL для запроса к API Telegram
       $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
       
       // Подготавливаем данные для запроса
       $params = [
           'chat_id' => $telegram_chat_id,
           'text' => $message,
           'parse_mode' => 'HTML',
           'disable_web_page_preview' => true
       ];
       
       // Выполняем запрос к API Telegram
       $response = wp_remote_post($url, [
           'body' => $params,
           'timeout' => 15,
           'headers' => [
               'Content-Type' => 'application/x-www-form-urlencoded'
           ]
       ]);
       
       if (!is_wp_error($response)) {
           $response_code = wp_remote_retrieve_response_code($response);
           $response_body = wp_remote_retrieve_body($response);
           $response_data = json_decode($response_body, true);
           
           // Проверяем успешность запроса
           return ($response_code === 200 && isset($response_data['ok']) && $response_data['ok'] === true);
       }
       
       return false;
   }
   
   /**
    * Формирует HTML-шаблон письма для клиента
    *
    * @param array $booking_data Данные бронирования
    * @return string HTML-шаблон
    */
   private function get_client_email_template($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       // Рассчитываем количество ночей
       $checkin = new \DateTime($booking_data['checkin_date']);
       $checkout = new \DateTime($booking_data['checkout_date']);
       $interval = $checkin->diff($checkout);
       $nights = $interval->days;
       
       // Получаем информацию о гостях
       $guest_count = isset($booking_data['guest_count']) ? intval($booking_data['guest_count']) : 1;
       $children_count = isset($booking_data['children_count']) ? intval($booking_data['children_count']) : 0;
       $guests_text = $guest_count . ' взрослых' . ($children_count > 0 ? ', ' . $children_count . ' детей' : '');
       
       // Функция для склонения слова "ночь"
       $nights_text = $this->pluralize_nights($nights);
       
       // Получаем способ оплаты
       $payment_methods = [
           'card' => 'Банковская карта',
           'cash' => 'Наличными при заселении',
           'transfer' => 'Банковский перевод'
       ];
       
       $payment_method = isset($payment_methods[$booking_data['payment_method']]) 
           ? $payment_methods[$booking_data['payment_method']] 
           : $booking_data['payment_method'];
       
       // Получаем контактные данные из настроек сайта
       $contact_phone = get_option('sun_booking_contact_phone', '+7 (XXX) XXX-XX-XX');
       $contact_email = get_option('sun_booking_contact_email', get_option('admin_email'));
       
       ob_start();
       ?>
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Подтверждение бронирования</title>
           <style>
               body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; margin: 0; padding: 0; }
               .container { max-width: 600px; margin: 0 auto; padding: 20px; }
               .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
               .booking-info { margin: 20px 0; }
               .booking-row { margin-bottom: 10px; }
               .label { font-weight: bold; }
               .highlight { color: #0066cc; font-weight: bold; }
               .total-price { font-size: 18px; color: #0066cc; font-weight: bold; }
               .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
               .button { display: inline-block; padding: 10px 20px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; }
               
               /* Responsive styles */
               @media only screen and (max-width: 480px) {
                   .container { padding: 10px; }
                   .header h1 { font-size: 24px; }
               }
           </style>
       </head>
       <body>
           <div class="container">
               <div class="header">
                   <h1>Ваше бронирование подтверждено</h1>
               </div>
               
               <p>Уважаемый(ая) <?php echo esc_html($booking_data['first_name']) . ' ' . esc_html($booking_data['last_name']); ?>!</p>
               
               <p>Благодарим Вас за бронирование. Ниже представлены детали Вашего бронирования:</p>
               
               <div class="booking-info">
                   <div class="booking-row">
                       <span class="label">Номер бронирования:</span> 
                       <span class="highlight"><?php echo esc_html($booking_data['booking_id']); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Апартамент:</span> 
                       <span><?php echo esc_html($apartament_title); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Дата заезда:</span> 
                       <span><?php echo esc_html($checkin_date); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Дата выезда:</span> 
                       <span><?php echo esc_html($checkout_date); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Длительность:</span> 
                       <span><?php echo esc_html($nights) . ' ' . esc_html($nights_text); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Количество гостей:</span> 
                       <span><?php echo esc_html($guests_text); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Способ оплаты:</span> 
                       <span><?php echo esc_html($payment_method); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Итоговая стоимость:</span> 
                       <span class="total-price"><?php echo number_format($booking_data['total_price'], 0, '.', ' '); ?> ₽</span>
                   </div>
               </div>
               
               <p>Если у Вас возникнут вопросы, пожалуйста, свяжитесь с нами:</p>
               <ul>
                   <li>Телефон: <strong><?php echo esc_html($contact_phone); ?></strong></li>
                   <li>Email: <strong><?php echo esc_html($contact_email); ?></strong></li>
               </ul>
               
               <p>Мы желаем Вам приятного пребывания!</p>
               
               <div class="footer">
                   <p>Это автоматическое письмо, пожалуйста, не отвечайте на него.</p>
                   <p>&copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. Все права защищены.</p>
               </div>
           </div>
       </body>
       </html>
       <?php
       return ob_get_clean();
   }
   
   /**
    * Формирует HTML-шаблон письма для администратора
    *
    * @param array $booking_data Данные бронирования
    * @return string HTML-шаблон
    */
   private function get_admin_email_template($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       $apartament_url = get_permalink($apartament_id);
       $apartament_edit_url = admin_url('post.php?post=' . $apartament_id . '&action=edit');
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       // Рассчитываем количество ночей
       $checkin = new \DateTime($booking_data['checkin_date']);
       $checkout = new \DateTime($booking_data['checkout_date']);
       $interval = $checkin->diff($checkout);
       $nights = $interval->days;
       
       // Получаем информацию о гостях
       $guest_count = isset($booking_data['guest_count']) ? intval($booking_data['guest_count']) : 1;
       $children_count = isset($booking_data['children_count']) ? intval($booking_data['children_count']) : 0;
       $guests_text = $guest_count . ' взрослых' . ($children_count > 0 ? ', ' . $children_count . ' детей' : '');
       
       // Получаем способ оплаты
       $payment_methods = [
           'card' => 'Банковская карта',
           'cash' => 'Наличными при заселении',
           'transfer' => 'Банковский перевод'
       ];
       
       $payment_method = isset($payment_methods[$booking_data['payment_method']]) 
           ? $payment_methods[$booking_data['payment_method']] 
           : $booking_data['payment_method'];
       
       ob_start();
       ?>
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Новое бронирование</title>
           <style>
               body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; margin: 0; padding: 0; }
               .container { max-width: 600px; margin: 0 auto; padding: 20px; }
               .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
               .booking-info, .guest-info { margin: 20px 0; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
               .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
               .booking-row { margin-bottom: 10px; }
               .label { font-weight: bold; }
               .highlight { color: #0066cc; font-weight: bold; }
               .total-price { font-size: 18px; color: #0066cc; font-weight: bold; }
               .button { display: inline-block; padding: 10px 15px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; margin-bottom: 10px; }
               .button.secondary { background-color: #666; }
               .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
               
               /* Responsive styles */
               @media only screen and (max-width: 480px) {
                   .container { padding: 10px; }
                   .header h1 { font-size: 24px; }
                   .button { display: block; margin-bottom: 10px; text-align: center; }
               }
           </style>
       </head>
       <body>
           <div class="container">
               <div class="header">
                   <h1>Новое бронирование</h1>
                   <p style="color: #666;">Бронирование #<?php echo esc_html($booking_data['booking_id']); ?> от <?php echo date('d.m.Y H:i', current_time('timestamp')); ?></p>
               </div>
               
               <p>Поступило новое бронирование. Информация о бронировании:</p>
               
               <div class="booking-info">
                   <div class="section-title">Детали бронирования:</div>
                   
                   <div class="booking-row">
                       <span class="label">Номер бронирования:</span> 
                       <span class="highlight"><?php echo esc_html($booking_data['booking_id']); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Апартамент:</span> 
                       <span><?php echo esc_html($apartament_title); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Дата заезда:</span> 
                       <span><?php echo esc_html($checkin_date); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Дата выезда:</span> 
                       <span><?php echo esc_html($checkout_date); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Длительность:</span> 
                       <span><?php echo esc_html($nights) . ' ' . $this->pluralize_nights($nights); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Количество гостей:</span> 
                       <span><?php echo esc_html($guests_text); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Способ оплаты:</span> 
                       <span><?php echo esc_html($payment_method); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Итоговая стоимость:</span> 
                       <span class="total-price"><?php echo number_format($booking_data['total_price'], 0, '.', ' '); ?> ₽</span>
                   </div>
               </div>
               
               <div class="guest-info">
                   <div class="section-title">Информация о госте:</div>
                   
                   <div class="booking-row">
                       <span class="label">ФИО:</span> 
                       <span><?php echo esc_html($booking_data['last_name']) . ' ' . esc_html($booking_data['first_name']) . 
                       (empty($booking_data['middle_name']) ? '' : ' ' . esc_html($booking_data['middle_name'])); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Email:</span> 
                       <span><a href="mailto:<?php echo esc_attr($booking_data['email']); ?>"><?php echo esc_html($booking_data['email']); ?></a></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Телефон:</span> 
                       <span><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $booking_data['phone'])); ?>"><?php echo esc_html($booking_data['phone']); ?></a></span>
                   </div>
               </div>
               
               <div style="margin: 25px 0;">
                   <a href="<?php echo esc_url(admin_url('edit.php?post_type=sun_booking')); ?>" class="button">
                       Управление бронированиями
                   </a>
                   
                   <a href="<?php echo esc_url($apartament_edit_url); ?>" class="button secondary">
                       Редактировать апартамент
                   </a>
                   
                   <a href="<?php echo esc_url($apartament_url); ?>" class="button secondary">
                       Просмотреть апартамент
                   </a>
               </div>
               
               <div class="footer">
                   <p>Это автоматическое уведомление, отправленное с сайта <?php echo get_bloginfo('name'); ?>.</p>
                   <p>Для управления настройками уведомлений перейдите в <a href="<?php echo esc_url(admin_url('options-general.php')); ?>">настройки сайта</a>.</p>
               </div>
           </div>
       </body>
       </html>
       <?php
       return ob_get_clean();
   }
   
   /**
    * Формирует текст сообщения для Telegram
    *
    * @param array $booking_data Данные бронирования
    * @return string Текст сообщения
    */
   private function get_telegram_message($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       // Рассчитываем количество ночей
       $checkin = new \DateTime($booking_data['checkin_date']);
       $checkout = new \DateTime($booking_data['checkout_date']);
       $interval = $checkin->diff($checkout);
       $nights = $interval->days;
       
       // Получаем информацию о гостях
       $guest_count = isset($booking_data['guest_count']) ? intval($booking_data['guest_count']) : 1;
       $children_count = isset($booking_data['children_count']) ? intval($booking_data['children_count']) : 0;
       $guests_text = $guest_count . ' взрослых' . ($children_count > 0 ? ', ' . $children_count . ' детей' : '');
       
       // Форматируем стоимость
       $total_price = number_format($booking_data['total_price'], 0, '.', ' ');
       
       // Получаем способ оплаты
       $payment_methods = [
           'card' => 'Банковская карта',
           'cash' => 'Наличными при заселении',
           'transfer' => 'Банковский перевод'
       ];
       
       $payment_method = isset($payment_methods[$booking_data['payment_method']]) 
           ? $payment_methods[$booking_data['payment_method']] 
           : $booking_data['payment_method'];
       
       // Формируем текст сообщения (используем HTML-разметку, которую поддерживает Telegram)
       $message = "<b>📋 Новое бронирование #{$booking_data['booking_id']}</b>\n\n";
       
       $message .= "<b>🏡 Апартамент:</b> {$apartament_title}\n";
       $message .= "<b>📅 Даты:</b> {$checkin_date} — {$checkout_date} ({$nights} " . $this->pluralize_nights($nights) . ")\n";
       $message .= "<b>👥 Гости:</b> {$guests_text}\n";
       $message .= "<b>💰 Стоимость:</b> {$total_price} ₽\n";
       $message .= "<b>💳 Оплата:</b> {$payment_method}\n\n";
       
       $message .= "<b>👤 Гость:</b> {$booking_data['last_name']} {$booking_data['first_name']}\n";
       
       // Добавляем отчество, если есть
       if (!empty($booking_data['middle_name'])) {
           $message .= "<b>Отчество:</b> {$booking_data['middle_name']}\n";
       }
       
       $message .= "<b>📧 Email:</b> {$booking_data['email']}\n";
       $message .= "<b>📞 Телефон:</b> {$booking_data['phone']}\n\n";
       
       $message .= "<b>⏱ Время бронирования:</b> " . date('d.m.Y H:i:s') . "\n";
       
       return $message;
   }
   
   /**
    * Формирует текст сообщения об отмене для Telegram
    *
    * @param array $booking_data Данные бронирования
    * @return string Текст сообщения
    */
   private function get_telegram_cancellation_message($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       // Формируем текст сообщения
       $message = "<b>🚫 Отмена бронирования #{$booking_data['booking_id']}</b>\n\n";
       
       $message .= "<b>🏡 Апартамент:</b> {$apartament_title}\n";
       $message .= "<b>📅 Отмененные даты:</b> {$checkin_date} — {$checkout_date}\n\n";
       
       $message .= "<b>👤 Гость:</b> {$booking_data['last_name']} {$booking_data['first_name']}\n";
       $message .= "<b>📧 Email:</b> {$booking_data['email']}\n";
       
       if (isset($booking_data['phone'])) {
           $message .= "<b>📞 Телефон:</b> {$booking_data['phone']}\n";
       }
       
       $message .= "\n<b>⏱ Время отмены:</b> " . date('d.m.Y H:i:s') . "\n";
       
       return $message;
   }
   
   /**
    * Формирует HTML-шаблон письма об отмене для клиента
    *
    * @param array $booking_data Данные бронирования
    * @return string HTML-шаблон
    */
   private function get_client_cancellation_email_template($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       // Получаем контактные данные из настроек сайта
       $contact_phone = get_option('sun_booking_contact_phone', '+7 (XXX) XXX-XX-XX');
       $contact_email = get_option('sun_booking_contact_email', get_option('admin_email'));
       
       ob_start();
       ?>
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Отмена бронирования</title>
           <style>
               body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; margin: 0; padding: 0; }
               .container { max-width: 600px; margin: 0 auto; padding: 20px; }
               .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
               .booking-info { margin: 20px 0; }
               .booking-row { margin-bottom: 10px; }
               .label { font-weight: bold; }
               .highlight { color: #cc0000; font-weight: bold; }
               .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
               
               /* Responsive styles */
               @media only screen and (max-width: 480px) {
                   .container { padding: 10px; }
                   .header h1 { font-size: 24px; }
               }
           </style>
       </head>
       <body>
           <div class="container">
               <div class="header">
                   <h1>Отмена бронирования</h1>
               </div>
               
               <p>Уважаемый(ая) <?php echo esc_html($booking_data['first_name']) . ' ' . esc_html($booking_data['last_name']); ?>!</p>
               
               <p>Информируем Вас, что бронирование #<?php echo esc_html($booking_data['booking_id']); ?> отменено.</p>
               
               <div class="booking-info">
                   <div class="booking-row">
                       <span class="label">Номер бронирования:</span> 
                       <span class="highlight"><?php echo esc_html($booking_data['booking_id']); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Апартамент:</span> 
                       <span><?php echo esc_html($apartament_title); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Даты бронирования:</span> 
                       <span><?php echo esc_html($checkin_date) . ' — ' . esc_html($checkout_date); ?></span>
                   </div>
               </div>
               
               <p>Если у Вас возникнут вопросы, пожалуйста, свяжитесь с нами:</p>
               <ul>
                   <li>Телефон: <strong><?php echo esc_html($contact_phone); ?></strong></li>
                   <li>Email: <strong><?php echo esc_html($contact_email); ?></strong></li>
               </ul>
               
               <p>Надеемся увидеть Вас снова в будущем!</p>
               
               <div class="footer">
                   <p>Это автоматическое письмо, пожалуйста, не отвечайте на него.</p>
                   <p>&copy; <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>. Все права защищены.</p>
               </div>
           </div>
       </body>
       </html>
       <?php
       return ob_get_clean();
   }
   
   /**
    * Формирует HTML-шаблон письма об отмене для администратора
    *
    * @param array $booking_data Данные бронирования
    * @return string HTML-шаблон
    */
   private function get_admin_cancellation_email_template($booking_data) {
       // Получаем информацию об апартаменте
       $apartament_id = $booking_data['apartament_id'];
       $apartament_title = get_the_title($apartament_id);
       $apartament_url = get_permalink($apartament_id);
       $apartament_edit_url = admin_url('post.php?post=' . $apartament_id . '&action=edit');
       
       // Форматируем даты
       $checkin_date = date('d.m.Y', strtotime($booking_data['checkin_date']));
       $checkout_date = date('d.m.Y', strtotime($booking_data['checkout_date']));
       
       ob_start();
       ?>
       <!DOCTYPE html>
       <html>
       <head>
           <meta charset="UTF-8">
           <meta name="viewport" content="width=device-width, initial-scale=1.0">
           <title>Отмена бронирования</title>
           <style>
               body { font-family: Arial, sans-serif; line-height: 1.5; color: #333; margin: 0; padding: 0; }
               .container { max-width: 600px; margin: 0 auto; padding: 20px; }
               .header { text-align: center; padding-bottom: 20px; border-bottom: 1px solid #eee; }
               .booking-info, .guest-info { margin: 20px 0; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
               .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
               .booking-row { margin-bottom: 10px; }
               .label { font-weight: bold; }
               .highlight { color: #cc0000; font-weight: bold; }
               .button { display: inline-block; padding: 10px 15px; background-color: #0066cc; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; margin-bottom: 10px; }
               .button.secondary { background-color: #666; }
               .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
               
               /* Responsive styles */
               @media only screen and (max-width: 480px) {
                   .container { padding: 10px; }
                   .header h1 { font-size: 24px; }
                   .button { display: block; margin-bottom: 10px; text-align: center; }
               }
           </style>
       </head>
       <body>
           <div class="container">
               <div class="header">
                   <h1>Отмена бронирования</h1>
                   <p style="color: #666;">Бронирование #<?php echo esc_html($booking_data['booking_id']); ?> отменено <?php echo date('d.m.Y H:i', current_time('timestamp')); ?></p>
               </div>
               
               <p>Информация об отмененном бронировании:</p>
               
               <div class="booking-info">
                   <div class="section-title">Детали бронирования:</div>
                   
                   <div class="booking-row">
                       <span class="label">Номер бронирования:</span> 
                       <span class="highlight"><?php echo esc_html($booking_data['booking_id']); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Апартамент:</span> 
                       <span><?php echo esc_html($apartament_title); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Даты бронирования:</span> 
                       <span><?php echo esc_html($checkin_date) . ' — ' . esc_html($checkout_date); ?></span>
                   </div>
               </div>
               
               <div class="guest-info">
                   <div class="section-title">Информация о госте:</div>
                   
                   <div class="booking-row">
                       <span class="label">ФИО:</span> 
                       <span><?php echo esc_html($booking_data['last_name']) . ' ' . esc_html($booking_data['first_name']) . 
                       (empty($booking_data['middle_name']) ? '' : ' ' . esc_html($booking_data['middle_name'])); ?></span>
                   </div>
                   
                   <div class="booking-row">
                       <span class="label">Email:</span> 
                       <span><a href="mailto:<?php echo esc_attr($booking_data['email']); ?>"><?php echo esc_html($booking_data['email']); ?></a></span>
                   </div>
                   
                   <?php if (isset($booking_data['phone'])): ?>
                   <div class="booking-row">
                       <span class="label">Телефон:</span> 
                       <span><a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $booking_data['phone'])); ?>"><?php echo esc_html($booking_data['phone']); ?></a></span>
                   </div>
                   <?php endif; ?>
               </div>
               
               <div style="margin: 25px 0;">
                   <a href="<?php echo esc_url(admin_url('edit.php?post_type=sun_booking')); ?>" class="button">
                       Управление бронированиями
                   </a>
                   
                   <a href="<?php echo esc_url($apartament_url); ?>" class="button secondary">
                       Просмотреть апартамент
                   </a>
               </div>
               
               <div class="footer">
                   <p>Это автоматическое уведомление, отправленное с сайта <?php echo get_bloginfo('name'); ?>.</p>
               </div>
           </div>
       </body>
       </html>
       <?php
       return ob_get_clean();
   }
   
   /**
    * Склоняет слово "ночь" в зависимости от количества
    *
    * @param int $number Количество ночей
    * @return string Склонение слова "ночь"
    */
   private function pluralize_nights($number) {
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