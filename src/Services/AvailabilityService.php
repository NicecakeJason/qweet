<?php
namespace Sun\Apartament\Services;

use Sun\Apartament\Core\Apartament;

/**
 * Сервис для управления доступностью апартаментов
 *
 * @since 1.0.0
 */
class AvailabilityService extends AbstractService {
    /**
     * Регистрирует сервис
     *
     * @return void
     */
    public function register() {
        add_action('admin_menu', [$this, 'add_availability_tools_page']);
        add_action('wp_ajax_reset_apartament_availability', [$this, 'ajax_reset_apartament_availability']);
        add_action('wp_ajax_cleanup_orphaned_bookings', [$this, 'ajax_cleanup_orphaned_bookings']);
    }
    
    /**
     * Добавляет страницу инструментов доступности
     * 
     * @return void
     */
    public function add_availability_tools_page() {
        add_submenu_page(
            'edit.php?post_type=sun_booking',
            'Инструменты доступности',
            'Инструменты доступности',
            'manage_options',
            'sun-reset-availability',
            [$this, 'render_availability_tools_page']
        );
    }
    
    /**
     * Отображает страницу инструментов доступности
     * 
     * @return void
     */
    public function render_availability_tools_page() {
        ?>
        <div class="wrap">
            <h1>Инструменты для управления доступностью апартаментов</h1>
            
            <div class="postbox">
                <div class="inside">
                    <h2>Сброс данных о доступности апартамента</h2>
                    
                    <div class="notice notice-warning" style="margin: 10px 0;">
                        <p><strong>Внимание!</strong> Используйте этот инструмент только если у вас возникли проблемы с доступностью апартаментов после удаления бронирований.</p>
                        <p>Сброс доступности пометит все даты апартамента как свободные.</p>
                    </div>
                    
                    <form id="reset-availability-form">
                        <?php wp_nonce_field('sun_reset_availability', 'security'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="apartament_id">Выберите апартамент:</label></th>
                                <td>
                                    <select name="apartament_id" id="apartament_id" required>
                                        <option value="">- Выберите апартамент -</option>
                                        <?php
                                        $apartaments = get_posts([
                                            'post_type' => 'apartament',
                                            'posts_per_page' => -1,
                                            'orderby' => 'title',
                                            'order' => 'ASC'
                                        ]);
                                        
                                        foreach ($apartaments as $apartament) {
                                            echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="reset_availability_btn">Сбросить доступность</button>
                        </p>
                    </form>
                </div>
            </div>
            
            <div class="postbox" style="margin-top: 20px;">
                <div class="inside">
                    <h2>Очистка осиротевших бронирований</h2>
                    
                    <div class="notice notice-info" style="margin: 10px 0;">
                        <p>Этот инструмент проверит все апартаменты и удалит "осиротевшие" данные о бронированиях - записи, которые остались в метаданных, но соответствующие бронирования уже не существуют.</p>
                        <p>Рекомендуется выполнять эту операцию после массового удаления бронирований или при проблемах с отображением доступности.</p>
                    </div>
                    
                    <form id="cleanup-orphaned-form">
                        <?php wp_nonce_field('sun_cleanup_orphaned', 'security_cleanup'); ?>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary" id="cleanup_orphaned_btn">Очистить осиротевшие бронирования</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Обработка формы сброса доступности
            $('#reset-availability-form').on('submit', function(e) {
                e.preventDefault();
                
                var apartament_id = $('#apartament_id').val();
                
                if (!apartament_id) {
                    alert('Пожалуйста, выберите апартамент.');
                    return;
                }
                
                var button = $('#reset_availability_btn');
                button.prop('disabled', true).text('Выполняется сброс...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'reset_apartament_availability',
                        security: $('#security').val(),
                        apartament_id: apartament_id
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Данные о доступности апартамента успешно сброшены!');
                        } else {
                            alert('Ошибка: ' + response.data.message);
                        }
                        
                        button.prop('disabled', false).text('Сбросить доступность');
                    },
                    error: function() {
                        alert('Произошла ошибка при выполнении запроса.');
                        button.prop('disabled', false).text('Сбросить доступность');
                    }
                });
            });
            
            // Обработка формы очистки осиротевших бронирований
            $('#cleanup-orphaned-form').on('submit', function(e) {
                e.preventDefault();
                
                var button = $('#cleanup_orphaned_btn');
                button.prop('disabled', true).text('Выполняется очистка...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'cleanup_orphaned_bookings',
                        security_cleanup: $('#security_cleanup').val()
                    },
                    success: function(response) {
                        if (response.success) {
                            if (Object.keys(response.data.results).length > 0) {
                                var message = 'Очистка осиротевших бронирований завершена!\n\n';
                                
                                $.each(response.data.results, function(apartament_id, info) {
                                    message += 'Апартамент: ' + info.title + '\n';
                                    message += 'Удалено дат: ' + info.removed_dates + '\n';
                                    message += 'Удалено бронирований: ' + info.removed_bookings + '\n\n';
                                });
                                
                                alert(message);
                            } else {
                                alert('Осиротевших бронирований не найдено.');
                            }
                        } else {
                            alert('Ошибка: ' + response.data.message);
                        }
                        
                        button.prop('disabled', false).text('Очистить осиротевшие бронирования');
                    },
                    error: function() {
                        alert('Произошла ошибка при выполнении запроса.');
                        button.prop('disabled', false).text('Очистить осиротевшие бронирования');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX-обработчик сброса доступности апартамента
     * 
     * @return void
     */
    public function ajax_reset_apartament_availability() {
        // Проверка nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'sun_reset_availability')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
            return;
        }
        
        // Проверка прав
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'У вас недостаточно прав для выполнения этой операции.']);
            return;
        }
        
        // Проверка параметров
        if (empty($_POST['apartament_id'])) {
            wp_send_json_error(['message' => 'Не указан ID апартамента.']);
            return;
        }
        
        $apartament_id = intval($_POST['apartament_id']);
        
        // Сбрасываем данные о доступности
        $result = $this->reset_apartament_availability($apartament_id);
        
        if ($result) {
            wp_send_json_success(['message' => 'Данные о доступности успешно сброшены.']);
        } else {
            wp_send_json_error(['message' => 'Не удалось сбросить данные о доступности.']);
        }
    }
    
   /**
     * AJAX-обработчик очистки осиротевших бронирований
     * 
     * @return void
     */
    public function ajax_cleanup_orphaned_bookings() {
      // Проверка nonce
      if (!isset($_POST['security_cleanup']) || !wp_verify_nonce($_POST['security_cleanup'], 'sun_cleanup_orphaned')) {
          wp_send_json_error(['message' => 'Ошибка безопасности. Пожалуйста, обновите страницу и попробуйте снова.']);
          return;
      }
      
      // Проверка прав
      if (!current_user_can('manage_options')) {
          wp_send_json_error(['message' => 'У вас недостаточно прав для выполнения этой операции.']);
          return;
      }
      
      // Выполняем очистку осиротевших бронирований
      $results = $this->cleanup_orphaned_bookings();
      
      if ($results !== false) {
          wp_send_json_success(['message' => 'Очистка осиротевших бронирований завершена.', 'results' => $results]);
      } else {
          wp_send_json_error(['message' => 'Не удалось выполнить очистку осиротевших бронирований.']);
      }
  }
  
  /**
   * Сбрасывает данные о доступности апартамента
   * 
   * @param int $apartament_id ID апартамента
   * @return bool Результат операции
   */
  public function reset_apartament_availability($apartament_id) {
      if (!$apartament_id) {
          return false;
      }
      
      // Очищаем новые данные о бронированиях (ассоциативный массив)
      delete_post_meta($apartament_id, '_apartament_booked_dates');
      update_post_meta($apartament_id, '_apartament_booked_dates', []);
      
      // Очищаем старые данные о бронированиях (для обратной совместимости)
      delete_post_meta($apartament_id, '_apartament_availability');
      update_post_meta($apartament_id, '_apartament_availability', json_encode([]));
      
      return true;
  }
  
  /**
   * Очищает осиротевшие бронирования
   * 
   * @return array|bool Результаты очистки или false в случае ошибки
   */
  public function cleanup_orphaned_bookings() {
      // Получаем все апартаменты
      $apartaments = get_posts([
          'post_type' => 'apartament',
          'posts_per_page' => -1,
      ]);
      
      if (empty($apartaments)) {
          return [];
      }
      
      // Получаем все существующие бронирования
      $existing_bookings = [];
      $bookings = get_posts([
          'post_type' => 'sun_booking',
          'posts_per_page' => -1,
          'post_status' => ['pending', 'confirmed', 'completed', 'cancelled', 'publish', 'draft'],
      ]);
      
      foreach ($bookings as $booking) {
          $existing_bookings[$booking->ID] = $booking->post_title;
      }
      
      $results = [];
      
      // Проверяем каждый апартамент
      foreach ($apartaments as $apartament) {
          $apartament_id = $apartament->ID;
          $changes_made = false;
          $removed_dates = 0;
          $removed_bookings = 0;
          
          // 1. Проверяем и очищаем _apartament_booked_dates
          $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
          if (is_array($booked_dates) && !empty($booked_dates)) {
              $cleaned_dates = [];
              
              foreach ($booked_dates as $date => $booking_id) {
                  if (isset($existing_bookings[$booking_id])) {
                      // Бронирование существует, оставляем запись
                      $cleaned_dates[$date] = $booking_id;
                  } else {
                      // Бронирование не существует, удаляем запись
                      $removed_dates++;
                      $changes_made = true;
                  }
              }
              
              if ($changes_made) {
                  update_post_meta($apartament_id, '_apartament_booked_dates', $cleaned_dates);
              }
          }
          
          // 2. Проверяем и очищаем _apartament_bookings (старые данные)
          $old_bookings_json = get_post_meta($apartament_id, '_apartament_bookings', true);
          if ($old_bookings_json) {
              $old_bookings = json_decode($old_bookings_json, true);
              
              if (is_array($old_bookings) && !empty($old_bookings)) {
                  $cleaned_bookings = [];
                  
                  foreach ($old_bookings as $booking_number => $booking_data) {
                      $keep_booking = false;
                      
                      // Проверяем, существует ли связанное бронирование
                      if (isset($booking_data['booking_id']) && $booking_data['booking_id'] > 0) {
                          if (isset($existing_bookings[$booking_data['booking_id']])) {
                              $keep_booking = true;
                          }
                      } else {
                          // Для старых бронирований без ID проверяем по номеру
                          foreach ($existing_bookings as $id => $title) {
                              if ($title === $booking_number) {
                                  $keep_booking = true;
                                  // Обновляем ID бронирования
                                  $booking_data['booking_id'] = $id;
                                  break;
                              }
                          }
                      }
                      
                      if ($keep_booking) {
                          $cleaned_bookings[$booking_number] = $booking_data;
                      } else {
                          $removed_bookings++;
                          $changes_made = true;
                      }
                  }
                  
                  if ($changes_made) {
                      update_post_meta($apartament_id, '_apartament_bookings', json_encode($cleaned_bookings));
                  }
              }
          }
          
          // 3. Обновляем старое поле _apartament_availability на основе очищенных _apartament_booked_dates
          if ($changes_made) {
              $cleaned_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
              $dates_array = [];
              
              if (is_array($cleaned_dates)) {
                  foreach ($cleaned_dates as $date => $booking_id) {
                      $dates_array[] = $date;
                  }
              }
              
              update_post_meta($apartament_id, '_apartament_availability', json_encode($dates_array));
              
              // Записываем результаты только если были изменения
              $results[$apartament_id] = [
                  'title' => $apartament->post_title,
                  'removed_dates' => $removed_dates,
                  'removed_bookings' => $removed_bookings
              ];
          }
      }
      
      return $results;
  }
  
  /**
   * Проверяет доступность апартамента на указанные даты
   * 
   * @param int $apartament_id ID апартамента
   * @param string $checkin_date Дата заезда (Y-m-d)
   * @param string $checkout_date Дата выезда (Y-m-d)
   * @param int|null $exclude_booking_id ID бронирования для исключения
   * @return bool Результат проверки
   */
  public function check_apartament_availability($apartament_id, $checkin_date, $checkout_date, $exclude_booking_id = null) {
      // Проверяем входные данные
      if (!$apartament_id || !$checkin_date || !$checkout_date) {
          return false;
      }
      
      // Преобразуем даты в объекты DateTime
      $checkin = new \DateTime($checkin_date);
      $checkout = new \DateTime($checkout_date);
      
      // Проверяем, что дата заезда раньше даты выезда
      if ($checkin >= $checkout) {
          return false;
      }
      
      // Получаем занятые даты апартамента
      $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
      
      if (!is_array($booked_dates)) {
          $booked_dates = [];
      }
      
      // Проверяем каждую дату в диапазоне
      $current = clone $checkin;
      
      while ($current < $checkout) {
          $date_string = $current->format('Y-m-d');
          
          // Проверяем, занята ли дата другим бронированием
          if (isset($booked_dates[$date_string]) && 
              ($exclude_booking_id === null || $booked_dates[$date_string] != $exclude_booking_id)) {
              return false;
          }
          
          // Переходим к следующей дате
          $current->modify('+1 day');
      }
      
      return true;
  }
  
  /**
   * Получает доступные апартаменты на указанные даты
   * 
   * @param string $checkin_date Дата заезда (Y-m-d)
   * @param string $checkout_date Дата выезда (Y-m-d)
   * @param int $guest_count Количество гостей
   * @param array $filters Дополнительные фильтры
   * @return array Массив доступных апартаментов
   */
  public function get_available_apartaments($checkin_date, $checkout_date, $guest_count = 1, $filters = []) {
      // Проверяем входные данные
      if (!$checkin_date || !$checkout_date) {
          return [];
      }
      
      // Получаем все апартаменты
      $args = [
          'post_type' => 'apartament',
          'posts_per_page' => -1,
          'orderby' => 'title',
          'order' => 'ASC'
      ];
      
      // Добавляем фильтр по типу апартамента, если указан
      if (!empty($filters['type'])) {
          $args['tax_query'] = [
              [
                  'taxonomy' => 'apartament-type',
                  'field' => 'slug',
                  'terms' => $filters['type']
              ]
          ];
      }
      
      $apartaments = get_posts($args);
      $available_apartaments = [];
      
      // Проверяем доступность каждого апартамента
      foreach ($apartaments as $apartament) {
          $apartament_id = $apartament->ID;
          
          // Проверяем количество гостей
          $max_guests = get_post_meta($apartament_id, 'sunapartament_guest_count', true);
          
          if ($max_guests < $guest_count) {
              continue;
          }
          
          // Проверяем доступность на указанные даты
          if ($this->check_apartament_availability($apartament_id, $checkin_date, $checkout_date)) {
              // Если апартамент доступен, добавляем его в результаты
              $apartament_obj = new Apartament($apartament_id);
              
              // Получаем цены на период
              $price_service = new PriceService();
              $price_data = $price_service->get_prices_for_period($apartament_id, $checkin_date, $checkout_date);
              
              $available_apartaments[] = [
                  'id' => $apartament_id,
                  'title' => $apartament->post_title,
                  'description' => $apartament->post_content,
                  'square_footage' => $apartament_obj->get_square_footage(),
                  'guest_count' => $apartament_obj->get_guest_count(),
                  'floor_count' => $apartament_obj->get_floor_count(),
                  'gallery' => $apartament_obj->get_gallery(),
                  'amenities' => $apartament_obj->get_amenities(),
                  'total_price' => $price_data['total_price'],
                  'nights_count' => $price_data['nights'],
                  'daily_prices' => $price_data['daily_prices'],
                  'permalink' => get_permalink($apartament_id)
              ];
          }
      }
      
      return $available_apartaments;
  }
  
  /**
   * Добавляет даты бронирования в список занятых дат апартамента
   * 
   * @param int $apartament_id ID апартамента
   * @param string $checkin_date Дата заезда (d.m.Y)
   * @param string $checkout_date Дата выезда (d.m.Y)
   * @param int $booking_id ID бронирования
   * @return bool Результат операции
   */
  public function add_booking_dates($apartament_id, $checkin_date, $checkout_date, $booking_id) {
      if (!$apartament_id || !$checkin_date || !$checkout_date || !$booking_id) {
          return false;
      }
      
      // Преобразуем даты в формат Y-m-d для работы с DateTime
      $checkin_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkin_date)));
      $checkout_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkout_date)));
      
      // Генерируем диапазон дат
      $start = new \DateTime($checkin_formatted);
      $end = new \DateTime($checkout_formatted);
      $interval = new \DateInterval('P1D');
      $daterange = new \DatePeriod($start, $interval, $end);
      
      // Получаем существующие занятые даты
      $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
      if (!is_array($booked_dates)) {
          $booked_dates = [];
      }
      
      // Добавляем даты с привязкой к ID бронирования
      foreach ($daterange as $date) {
          $date_string = $date->format('Y-m-d');
          $booked_dates[$date_string] = $booking_id;
      }
      
      // Сохраняем обновленные даты
      update_post_meta($apartament_id, '_apartament_booked_dates', $booked_dates);
      
      // Для обратной совместимости также обновляем старое поле _apartament_availability
      $old_availability = get_post_meta($apartament_id, '_apartament_availability', true);
      $old_availability = $old_availability ? json_decode($old_availability, true) : [];
      
      foreach ($daterange as $date) {
          $date_string = $date->format('Y-m-d');
          if (!in_array($date_string, $old_availability)) {
              $old_availability[] = $date_string;
          }
      }
      
      update_post_meta($apartament_id, '_apartament_availability', json_encode(array_values($old_availability)));
      
      return true;
  }
  
  /**
   * Удаляет даты бронирования из списка занятых дат апартамента
   * 
   * @param int $apartament_id ID апартамента
   * @param string $checkin_date Дата заезда (d.m.Y)
   * @param string $checkout_date Дата выезда (d.m.Y)
   * @param int $booking_id ID бронирования
   * @return bool Результат операции
   */
  public function remove_booking_dates($apartament_id, $checkin_date, $checkout_date, $booking_id) {
      if (!$apartament_id || !$checkin_date || !$checkout_date || !$booking_id) {
          return false;
      }
      
      // Преобразуем даты в формат Y-m-d для работы с DateTime
      $checkin_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkin_date)));
      $checkout_formatted = date('Y-m-d', strtotime(str_replace('.', '-', $checkout_date)));
      
      // Получаем существующие занятые даты
      $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
      if (!is_array($booked_dates)) {
          $booked_dates = [];
      }
      
      // Генерируем диапазон дат
      $start = new \DateTime($checkin_formatted);
      $end = new \DateTime($checkout_formatted);
      
      // ВАЖНО: Создаем массив дат вручную, чтобы включить дату выезда
      $current = clone $start;
      $dates_to_remove = [];
      
      while ($current <= $end) {
          $date_string = $current->format('Y-m-d');
          
          // Проверяем, принадлежит ли дата текущему бронированию
          if (isset($booked_dates[$date_string]) && $booked_dates[$date_string] == $booking_id) {
              unset($booked_dates[$date_string]);
              $dates_to_remove[] = $date_string;
          }
          
          $current->modify('+1 day');
      }
      
      // Сохраняем обновленные даты
      update_post_meta($apartament_id, '_apartament_booked_dates', $booked_dates);
      
      // Для обратной совместимости также обновляем старое поле _apartament_availability
      $old_availability = get_post_meta($apartament_id, '_apartament_availability', true);
      if ($old_availability) {
          $old_availability = json_decode($old_availability, true);
          if (!is_array($old_availability)) {
              $old_availability = [];
          }
          
          $old_availability = array_values(array_diff($old_availability, $dates_to_remove));
          update_post_meta($apartament_id, '_apartament_availability', json_encode($old_availability));
      }
      
      return true;
  }
}