<?php
namespace Sun\Apartament\Admin;

use Sun\Apartament\Core\Apartament;
use Sun\Apartament\Core\Booking;
use Sun\Apartament\Services\BookingService;

/**
 * Класс для страницы инструментов
 *
 * @since 1.0.0
 */
class Tools {
    /**
     * Регистрирует хуки
     *
     * @return void
     */
    public function register() {
        // Этот метод не используется напрямую, так как класс Tools инициализируется через Menu
        
        // Регистрируем AJAX-обработчики для инструментов
        add_action('wp_ajax_sun_reset_availability', [$this, 'ajax_reset_availability']);
        add_action('wp_ajax_sun_cleanup_orphaned_bookings', [$this, 'ajax_cleanup_orphaned_bookings']);
        add_action('wp_ajax_sun_repair_booking_dates', [$this, 'ajax_repair_booking_dates']);
        add_action('wp_ajax_sun_migrate_bookings', [$this, 'ajax_migrate_bookings']);
    }
    
    /**
     * Отображает страницу инструментов
     *
     * @return void
     */
    public function render() {
        // Заголовок страницы
        echo '<div class="wrap sun-tools-page">';
        echo '<h1 class="wp-heading-inline">' . __('Инструменты для управления', 'sun-apartament') . '</h1>';
        
        // Разделы инструментов
        echo '<div class="sun-tools-sections">';
        
        // Раздел сброса доступности
        $this->render_reset_availability_section();
        
        // Раздел очистки осиротевших бронирований
        $this->render_cleanup_orphaned_section();
        
        // Раздел восстановления дат бронирований
        $this->render_repair_dates_section();
        
        // Раздел миграции бронирований
        $this->render_migrate_bookings_section();
        
        echo '</div>'; // Конец sections
        
        // Скрипт для обработки действий
        $this->init_tools_script();
        
        echo '</div>'; // Конец wrap
    }
    
    /**
     * Отображает раздел сброса доступности
     *
     * @return void
     */
    private function render_reset_availability_section() {
        // Получаем список апартаментов
        $apartaments = get_posts([
            'post_type' => 'apartament',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);
        
        echo '<div class="sun-tools-section">';
        echo '<h2>' . __('Сброс данных о доступности апартамента', 'sun-apartament') . '</h2>';
        
        echo '<div class="sun-tools-warning">';
        echo '<p><strong>' . __('Внимание!', 'sun-apartament') . '</strong> ' . __('Используйте этот инструмент только если у вас возникли проблемы с доступностью апартаментов после удаления бронирований.', 'sun-apartament') . '</p>';
        echo '<p>' . __('Сброс доступности пометит все даты апартамента как свободные.', 'sun-apartament') . '</p>';
        echo '</div>';
        
        echo '<div class="sun-tools-form">';
        echo '<div class="form-row">';
        echo '<label for="apartament_id">' . __('Выберите апартамент', 'sun-apartament') . ':</label>';
        echo '<select id="apartament_id">';
        echo '<option value="">' . __('- Выберите апартамент -', 'sun-apartament') . '</option>';
        
        foreach ($apartaments as $apartament) {
            echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
        }
        
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-actions">';
        echo '<button type="button" id="reset-availability" class="button button-primary">' . __('Сбросить доступность', 'sun-apartament') . '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец section
    }
    
    /**
     * Отображает раздел очистки осиротевших бронирований
     *
     * @return void
     */
    private function render_cleanup_orphaned_section() {
        echo '<div class="sun-tools-section">';
        echo '<h2>' . __('Очистка осиротевших бронирований', 'sun-apartament') . '</h2>';
        
        echo '<div class="sun-tools-info">';
        echo '<p>' . __('Этот инструмент проверит все апартаменты и удалит "осиротевшие" данные о бронированиях - записи, которые остались в метаданных, но соответствующие бронирования уже не существуют.', 'sun-apartament') . '</p>';
        echo '<p>' . __('Рекомендуется выполнять эту операцию после массового удаления бронирований или при проблемах с отображением доступности.', 'sun-apartament') . '</p>';
        echo '</div>';
        
        echo '<div class="sun-tools-form">';
        echo '<div class="form-actions">';
        echo '<button type="button" id="cleanup-orphaned" class="button button-primary">' . __('Очистить осиротевшие бронирования', 'sun-apartament') . '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец section
    }
    
    /**
     * Отображает раздел восстановления дат бронирований
     *
     * @return void
     */
    private function render_repair_dates_section() {
        echo '<div class="sun-tools-section">';
        echo '<h2>' . __('Восстановление дат бронирований', 'sun-apartament') . '</h2>';
        
        echo '<div class="sun-tools-info">';
        echo '<p>' . __('Этот инструмент восстановит связь между бронированиями и занятыми датами. Полезно если даты в календаре отображаются некорректно.', 'sun-apartament') . '</p>';
        echo '<p>' . __('Инструмент проверит все бронирования и добавит соответствующие даты в метаданные апартаментов.', 'sun-apartament') . '</p>';
        echo '</div>';
        
        echo '<div class="sun-tools-form">';
        echo '<div class="form-actions">';
        echo '<button type="button" id="repair-dates" class="button button-primary">' . __('Восстановить даты бронирований', 'sun-apartament') . '</button>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец section
    }
    
   /**
     * Отображает раздел миграции бронирований
     *
     * @return void
     */
    private function render_migrate_bookings_section() {
      echo '<div class="sun-tools-section">';
      echo '<h2>' . __('Миграция бронирований', 'sun-apartament') . '</h2>';
      
      echo '<div class="sun-tools-info">';
      echo '<p>' . __('Этот инструмент мигрирует существующие бронирования из старого формата данных в новый.', 'sun-apartament') . '</p>';
      echo '<p>' . __('Используйте этот инструмент только при обновлении плагина с версии 0.x до версии 1.x.', 'sun-apartament') . '</p>';
      echo '</div>';
      
      // Получаем список апартаментов
      $apartaments = get_posts([
          'post_type' => 'apartament',
          'posts_per_page' => -1,
          'orderby' => 'title',
          'order' => 'ASC'
      ]);
      
      echo '<div class="sun-tools-form">';
      echo '<div class="form-row">';
      echo '<label for="migrate_apartament_id">' . __('Выберите апартамент', 'sun-apartament') . ':</label>';
      echo '<select id="migrate_apartament_id">';
      echo '<option value="0">' . __('Все апартаменты', 'sun-apartament') . '</option>';
      
      foreach ($apartaments as $apartament) {
          echo '<option value="' . $apartament->ID . '">' . esc_html($apartament->post_title) . '</option>';
      }
      
      echo '</select>';
      echo '</div>';
      
      echo '<div class="form-actions">';
      echo '<button type="button" id="migrate-bookings" class="button button-primary">' . __('Мигрировать бронирования', 'sun-apartament') . '</button>';
      echo '</div>';
      echo '</div>';
      
      echo '</div>'; // Конец section
  }
  
  /**
   * Инициализирует скрипт для обработки действий инструментов
   *
   * @return void
   */
  private function init_tools_script() {
      ?>
      <script>
      jQuery(document).ready(function($) {
          // Обработчик сброса доступности
          $('#reset-availability').click(function() {
              const apartamentId = $('#apartament_id').val();
              
              if (!apartamentId) {
                  alert('Пожалуйста, выберите апартамент');
                  return;
              }
              
              if (!confirm('Вы уверены, что хотите сбросить доступность апартамента? Все даты будут помечены как свободные.')) {
                  return;
              }
              
              // Отправляем AJAX-запрос
              $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  dataType: 'json',
                  data: {
                      action: 'sun_reset_availability',
                      nonce: '<?php echo wp_create_nonce('sun-tools-nonce'); ?>',
                      apartament_id: apartamentId
                  },
                  beforeSend: function() {
                      $('#reset-availability').prop('disabled', true).text('Выполняется...');
                  },
                  success: function(response) {
                      if (response.success) {
                          alert(response.data.message);
                      } else {
                          alert(response.data.message || 'Произошла ошибка');
                      }
                  },
                  error: function() {
                      alert('Ошибка выполнения запроса');
                  },
                  complete: function() {
                      $('#reset-availability').prop('disabled', false).text('Сбросить доступность');
                  }
              });
          });
          
          // Обработчик очистки осиротевших бронирований
          $('#cleanup-orphaned').click(function() {
              if (!confirm('Вы уверены, что хотите очистить осиротевшие бронирования?')) {
                  return;
              }
              
              // Отправляем AJAX-запрос
              $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  dataType: 'json',
                  data: {
                      action: 'sun_cleanup_orphaned_bookings',
                      nonce: '<?php echo wp_create_nonce('sun-tools-nonce'); ?>'
                  },
                  beforeSend: function() {
                      $('#cleanup-orphaned').prop('disabled', true).text('Выполняется...');
                  },
                  success: function(response) {
                      if (response.success) {
                          alert(response.data.message);
                      } else {
                          alert(response.data.message || 'Произошла ошибка');
                      }
                  },
                  error: function() {
                      alert('Ошибка выполнения запроса');
                  },
                  complete: function() {
                      $('#cleanup-orphaned').prop('disabled', false).text('Очистить осиротевшие бронирования');
                  }
              });
          });
          
          // Обработчик восстановления дат бронирований
          $('#repair-dates').click(function() {
              if (!confirm('Вы уверены, что хотите восстановить даты бронирований?')) {
                  return;
              }
              
              // Отправляем AJAX-запрос
              $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  dataType: 'json',
                  data: {
                      action: 'sun_repair_booking_dates',
                      nonce: '<?php echo wp_create_nonce('sun-tools-nonce'); ?>'
                  },
                  beforeSend: function() {
                      $('#repair-dates').prop('disabled', true).text('Выполняется...');
                  },
                  success: function(response) {
                      if (response.success) {
                          alert(response.data.message);
                      } else {
                          alert(response.data.message || 'Произошла ошибка');
                      }
                  },
                  error: function() {
                      alert('Ошибка выполнения запроса');
                  },
                  complete: function() {
                      $('#repair-dates').prop('disabled', false).text('Восстановить даты бронирований');
                  }
              });
          });
          
          // Обработчик миграции бронирований
          $('#migrate-bookings').click(function() {
              const apartamentId = $('#migrate_apartament_id').val();
              
              if (!confirm('Вы уверены, что хотите мигрировать бронирования? Этот процесс может занять некоторое время.')) {
                  return;
              }
              
              // Отправляем AJAX-запрос
              $.ajax({
                  url: ajaxurl,
                  type: 'POST',
                  dataType: 'json',
                  data: {
                      action: 'sun_migrate_bookings',
                      nonce: '<?php echo wp_create_nonce('sun-tools-nonce'); ?>',
                      apartament_id: apartamentId
                  },
                  beforeSend: function() {
                      $('#migrate-bookings').prop('disabled', true).text('Выполняется...');
                  },
                  success: function(response) {
                      if (response.success) {
                          alert(response.data.message);
                      } else {
                          alert(response.data.message || 'Произошла ошибка');
                      }
                  },
                  error: function() {
                      alert('Ошибка выполнения запроса');
                  },
                  complete: function() {
                      $('#migrate-bookings').prop('disabled', false).text('Мигрировать бронирования');
                  }
              });
          });
      });
      </script>
      <?php
  }
  
  /**
   * AJAX-обработчик сброса доступности
   *
   * @return void
   */
  public function ajax_reset_availability() {
      // Проверяем nonce для безопасности
      if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-tools-nonce')) {
          wp_send_json_error(['message' => 'Ошибка безопасности']);
      }
      
      // Получаем ID апартамента
      $apartament_id = isset($_POST['apartament_id']) ? intval($_POST['apartament_id']) : 0;
      
      if ($apartament_id <= 0) {
          wp_send_json_error(['message' => 'Некорректный ID апартамента']);
      }
      
      // Сбрасываем данные о доступности
      $result = $this->reset_apartament_availability($apartament_id);
      
      if ($result) {
          wp_send_json_success(['message' => 'Данные о доступности апартамента успешно сброшены!']);
      } else {
          wp_send_json_error(['message' => 'Произошла ошибка при сбросе данных о доступности']);
      }
  }
  
  /**
   * Сбрасывает данные о доступности апартамента
   *
   * @param int $apartament_id ID апартамента
   * @return bool Результат операции
   */
  private function reset_apartament_availability($apartament_id) {
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
   * AJAX-обработчик очистки осиротевших бронирований
   *
   * @return void
   */
  public function ajax_cleanup_orphaned_bookings() {
      // Проверяем nonce для безопасности
      if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-tools-nonce')) {
          wp_send_json_error(['message' => 'Ошибка безопасности']);
      }
      
      // Выполняем очистку осиротевших бронирований
      $result = $this->cleanup_orphaned_bookings();
      
      if ($result) {
          $message = 'Очистка осиротевших бронирований завершена!';
          
          if (count($result) > 0) {
              $message .= "\n\nРезультаты:";
              
              foreach ($result as $apartament_id => $info) {
                  $title = get_the_title($apartament_id);
                  $message .= "\n- {$title}: удалено {$info['removed_dates']} дат и {$info['removed_bookings']} бронирований";
              }
          } else {
              $message .= "\n\nОсиротевших бронирований не найдено.";
          }
          
          wp_send_json_success(['message' => $message]);
      } else {
          wp_send_json_error(['message' => 'Произошла ошибка при очистке осиротевших бронирований']);
      }
  }
  
  /**
   * Очищает осиротевшие бронирования
   *
   * @return array Результаты очистки
   */
  private function cleanup_orphaned_bookings() {
      // Получаем все апартаменты
      $apartaments = get_posts([
          'post_type' => 'apartament',
          'posts_per_page' => -1,
      ]);
      
      if (empty($apartaments)) {
          return false;
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
                  'removed_dates' => $removed_dates,
                  'removed_bookings' => $removed_bookings
              ];
          }
      }
      
      return $results;
  }
  
  /**
   * AJAX-обработчик восстановления дат бронирований
   *
   * @return void
   */
  public function ajax_repair_booking_dates() {
      // Проверяем nonce для безопасности
      if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-tools-nonce')) {
          wp_send_json_error(['message' => 'Ошибка безопасности']);
      }
      
      // Выполняем восстановление дат бронирований
      $result = $this->repair_booking_dates();
      
      if ($result) {
          $message = 'Восстановление дат бронирований завершено!';
          
          $message .= "\n\nОбработано {$result['total_bookings']} бронирований, обновлено {$result['updated_bookings']} бронирований.";
          
          wp_send_json_success(['message' => $message]);
      } else {
          wp_send_json_error(['message' => 'Произошла ошибка при восстановлении дат бронирований']);
      }
  }
  
  /**
   * Восстанавливает даты бронирований
   *
   * @return array Результаты восстановления
   */
  private function repair_booking_dates() {
      // Получаем все бронирования
      $bookings = get_posts([
          'post_type' => 'sun_booking',
          'posts_per_page' => -1,
          'post_status' => ['pending', 'confirmed', 'completed', 'cancelled', 'publish', 'draft'],
      ]);
      
      if (empty($bookings)) {
          return false;
      }
      
      $total_bookings = count($bookings);
      $updated_bookings = 0;
      
      // Обрабатываем каждое бронирование
      foreach ($bookings as $booking_post) {
          $booking_id = $booking_post->ID;
          $apartament_id = get_post_meta($booking_id, '_booking_apartament_id', true);
          $checkin_date = get_post_meta($booking_id, '_booking_checkin_date', true);
          $checkout_date = get_post_meta($booking_id, '_booking_checkout_date', true);
          
          // Пропускаем бронирования без необходимых данных
          if (!$apartament_id || !$checkin_date || !$checkout_date) {
              continue;
          }
          
          // Преобразуем даты в формат Y-m-d для работы с DateTime
          $checkin_formatted = str_replace('.', '-', $checkin_date);
          $checkout_formatted = str_replace('.', '-', $checkout_date);
          
          // Генерируем диапазон дат
          $start = new \DateTime($checkin_formatted);
          $end = new \DateTime($checkout_formatted);
          $interval = new \DateInterval('P1D');
          $period = new \DatePeriod($start, $interval, $end);
          
          // Получаем существующие занятые даты
          $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
          if (!is_array($booked_dates)) {
              $booked_dates = [];
          }
          
          $dates_updated = false;
          
          // Добавляем даты с привязкой к ID бронирования
          foreach ($period as $date) {
              $date_string = $date->format('Y-m-d');
              
              // Проверяем, занята ли дата другим бронированием
              if (!isset($booked_dates[$date_string]) || $booked_dates[$date_string] != $booking_id) {
                  $booked_dates[$date_string] = $booking_id;
                  $dates_updated = true;
              }
          }
          
          // Сохраняем обновленные даты
          if ($dates_updated) {
              update_post_meta($apartament_id, '_apartament_booked_dates', $booked_dates);
              
              // Обновляем также старый формат _apartament_availability
              $old_availability = get_post_meta($apartament_id, '_apartament_availability', true);
              $old_availability = $old_availability ? json_decode($old_availability, true) : [];
              
              foreach ($period as $date) {
                  $date_string = $date->format('Y-m-d');
                  if (!in_array($date_string, $old_availability)) {
                      $old_availability[] = $date_string;
                  }
              }
              
              update_post_meta($apartament_id, '_apartament_availability', json_encode(array_values($old_availability)));
              
              $updated_bookings++;
          }
      }
      
      return [
          'total_bookings' => $total_bookings,
          'updated_bookings' => $updated_bookings
      ];
  }
  
  /**
   * AJAX-обработчик миграции бронирований
   *
   * @return void
   */
  public function ajax_migrate_bookings() {
      // Проверяем nonce для безопасности
      if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sun-tools-nonce')) {
          wp_send_json_error(['message' => 'Ошибка безопасности']);
      }
      
      // Получаем ID апартамента
      $apartament_id = isset($_POST['apartament_id']) ? intval($_POST['apartament_id']) : 0;
      
      // Выполняем миграцию бронирований
      $result = $this->migrate_bookings($apartament_id);
      
      if ($result) {
          $message = 'Миграция бронирований завершена!';
          
          $message .= "\n\nВсего обработано: {$result['total_apartaments']} апартаментов";
          $message .= "\nВсего бронирований: {$result['total_bookings']}";
          $message .= "\nМигрировано: {$result['migrated_bookings']}";
          $message .= "\nОшибок: {$result['errors']}";
          
          wp_send_json_success(['message' => $message]);
      } else {
          wp_send_json_error(['message' => 'Произошла ошибка при миграции бронирований']);
      }
  }
  
  /**
   * Мигрирует бронирования
   *
   * @param int $apartament_id ID апартамента (0 - все апартаменты)
   * @return array Результаты миграции
   */
  private function migrate_bookings($apartament_id = 0) {
      // Получаем апартаменты для миграции
      $args = [
          'post_type' => 'apartament',
          'posts_per_page' => -1,
      ];
      
      if ($apartament_id > 0) {
          $args['p'] = $apartament_id;
      }
      
      $apartaments = get_posts($args);
      
      if (empty($apartaments)) {
          return false;
      }
      
      $total_stats = [
          'total_apartaments' => count($apartaments),
          'processed_apartaments' => 0,
          'total_bookings' => 0,
          'migrated_bookings' => 0,
          'errors' => 0,
      ];
      
      foreach ($apartaments as $apartament) {
          $migration_result = $this->migrate_apartament_bookings($apartament->ID);
          
          $total_stats['processed_apartaments']++;
          $total_stats['total_bookings'] += $migration_result['total'];
          $total_stats['migrated_bookings'] += $migration_result['migrated'];
          $total_stats['errors'] += $migration_result['errors'];
      }
      
      return $total_stats;
  }
  
  /**
   * Мигрирует бронирования апартамента
   *
   * @param int $apartament_id ID апартамента
   * @return array Статистика миграции
   */
  private function migrate_apartament_bookings($apartament_id) {
      $stats = [
          'total' => 0,
          'migrated' => 0,
          'errors' => 0,
      ];
      
      // Получаем старые данные бронирований
      $old_bookings = get_post_meta($apartament_id, '_apartament_bookings', true);
      if (!$old_bookings) {
          return $stats;
      }
      
      $old_bookings = json_decode($old_bookings, true);
      if (!is_array($old_bookings) || empty($old_bookings)) {
          return $stats;
      }
      
      $stats['total'] = count($old_bookings);
      
      // Обрабатываем каждое бронирование
      foreach ($old_bookings as $booking_number => $booking_data) {
          // Пропускаем, если уже есть ссылка на новое бронирование
          if (isset($booking_data['booking_id']) && $booking_data['booking_id'] > 0) {
              continue;
          }
          
          // Пропускаем, если нет обязательных данных
          if (empty($booking_data['checkin_date']) || empty($booking_data['checkout_date'])) {
              $stats['errors']++;
              continue;
          }
          
          // Проверяем существование бронирования с таким номером
          $existing_booking = get_posts([
              'post_type' => 'sun_booking',
              'post_title' => $booking_number,
              'posts_per_page' => 1,
              'post_status' => ['pending', 'confirmed', 'cancelled', 'completed', 'publish'],
          ]);
          
          if (!empty($existing_booking)) {
              // Бронирование уже существует, пропускаем
              continue;
          }
          
          // Создаем новое бронирование
          $post_args = [
              'post_type' => 'sun_booking',
              'post_title' => $booking_number,
              'post_status' => isset($booking_data['status']) ? $booking_data['status'] : 'confirmed',
              'post_author' => get_current_user_id(),
              'post_date' => isset($booking_data['booking_date']) ? $booking_data['booking_date'] : current_time('mysql'),
          ];
          
          $post_id = wp_insert_post($post_args);
          
          if (is_wp_error($post_id)) {
              $stats['errors']++;
              continue;
          }
          
          // Устанавливаем мета-поля
          update_post_meta($post_id, '_booking_apartament_id', $apartament_id);
          update_post_meta($post_id, '_booking_first_name', $booking_data['first_name'] ?? '');
          update_post_meta($post_id, '_booking_last_name', $booking_data['last_name'] ?? '');
          update_post_meta($post_id, '_booking_middle_name', $booking_data['middle_name'] ?? '');
          update_post_meta($post_id, '_booking_email', $booking_data['email'] ?? '');
          update_post_meta($post_id, '_booking_phone', $booking_data['phone'] ?? '');
          update_post_meta($post_id, '_booking_checkin_date', $booking_data['checkin_date']);
          update_post_meta($post_id, '_booking_checkout_date', $booking_data['checkout_date']);
          update_post_meta($post_id, '_booking_total_price', $booking_data['total_price'] ?? 0);
          update_post_meta($post_id, '_booking_payment_method', $booking_data['payment_method'] ?? 'card');
          update_post_meta($post_id, '_booking_created_at', $booking_data['booking_date'] ?? current_time('mysql'));
          update_post_meta($post_id, '_booking_guest_count', $booking_data['guest_count'] ?? 1);
          update_post_meta($post_id, '_booking_children_count', $booking_data['children_count'] ?? 0);
          
          // Обновляем ссылку в старой структуре
          $old_bookings[$booking_number]['booking_id'] = $post_id;
          
          // Если статус активный, добавляем занятые даты
          if (!isset($booking_data['status']) || $booking_data['status'] != 'cancelled') {
              // Преобразуем даты в формат Y-m-d для работы с DateTime
              $checkin_formatted = str_replace('.', '-', $booking_data['checkin_date']);
              $checkout_formatted = str_replace('.', '-', $booking_data['checkout_date']);
              
              // Генерируем диапазон дат
              $start = new \DateTime($checkin_formatted);
              $end = new \DateTime($checkout_formatted);
              $interval = new \DateInterval('P1D');
              $period = new \DatePeriod($start, $interval, $end);
              
              // Получаем существующие занятые даты
              $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
              if (!is_array($booked_dates)) {
                  $booked_dates = [];
              }
              
              // Добавляем даты с привязкой к ID бронирования
              foreach ($period as $date) {
                  $date_string = $date->format('Y-m-d');

                  $booked_dates[$date_string] = $post_id;
               }
               
               // Сохраняем обновленные даты
               update_post_meta($apartament_id, '_apartament_booked_dates', $booked_dates);
               
               // Для обратной совместимости также обновляем старое поле _apartament_availability
               $old_availability = get_post_meta($apartament_id, '_apartament_availability', true);
               $old_availability = $old_availability ? json_decode($old_availability, true) : [];
               
               foreach ($period as $date) {
                   $date_string = $date->format('Y-m-d');
                   if (!in_array($date_string, $old_availability)) {
                       $old_availability[] = $date_string;
                   }
               }
               
               update_post_meta($apartament_id, '_apartament_availability', json_encode(array_values($old_availability)));
           }
           
           $stats['migrated']++;
       }
       
       // Сохраняем обновленные бронирования с ссылками
       update_post_meta($apartament_id, '_apartament_bookings', json_encode($old_bookings));
       
       return $stats;
   }
   
   /**
    * Применяет статус "завершено" к прошедшим бронированиям
    *
    * @return array Результаты операции
    */
   public function complete_past_bookings() {
       // Получаем все активные бронирования
       $bookings = get_posts([
           'post_type' => 'sun_booking',
           'posts_per_page' => -1,
           'post_status' => ['confirmed', 'pending'],
           'meta_query' => [
               [
                   'key' => '_booking_checkout_date',
                   'value' => current_time('Y-m-d'),
                   'compare' => '<',
                   'type' => 'DATE'
               ]
           ]
       ]);
       
       if (empty($bookings)) {
           return [
               'total' => 0,
               'completed' => 0
           ];
       }
       
       $completed_count = 0;
       
       // Обрабатываем каждое бронирование
       foreach ($bookings as $booking_post) {
           $booking = new Booking($booking_post);
           
           // Завершаем бронирование
           if ($booking->complete()) {
               $completed_count++;
           }
       }
       
       return [
           'total' => count($bookings),
           'completed' => $completed_count
       ];
   }
   
   /**
    * Генерирует отчет по бронированиям
    *
    * @param string $start_date Начальная дата (Y-m-d)
    * @param string $end_date Конечная дата (Y-m-d)
    * @param int $apartament_id ID апартамента (0 - все апартаменты)
    * @return array Данные отчета
    */
   public function generate_bookings_report($start_date, $end_date, $apartament_id = 0) {
       // Создаем базовый запрос
       $args = [
           'post_type' => 'sun_booking',
           'posts_per_page' => -1,
           'post_status' => ['confirmed', 'pending', 'completed', 'cancelled'],
           'date_query' => [
               [
                   'after' => $start_date,
                   'before' => $end_date,
                   'inclusive' => true,
               ],
           ],
           'orderby' => 'date',
           'order' => 'DESC',
       ];
       
       // Если указан конкретный апартамент, добавляем его в запрос
       if ($apartament_id > 0) {
           $args['meta_query'] = [
               [
                   'key' => '_booking_apartament_id',
                   'value' => $apartament_id,
                   'compare' => '=',
               ],
           ];
       }
       
       $bookings_query = new \WP_Query($args);
       
       // Инициализируем массив для отчета
       $report = [
           'total' => $bookings_query->found_posts,
           'confirmed' => 0,
           'pending' => 0,
           'completed' => 0,
           'cancelled' => 0,
           'total_revenue' => 0,
           'avg_price' => 0,
           'bookings_by_apartment' => [],
           'bookings_by_date' => [],
           'bookings' => [],
       ];
       
       if ($bookings_query->have_posts()) {
           while ($bookings_query->have_posts()) {
               $bookings_query->the_post();
               $post_id = get_the_ID();
               $status = get_post_status();
               
               // Увеличиваем счетчик по статусу
               if (isset($report[$status])) {
                   $report[$status]++;
               }
               
               // Получаем данные бронирования
               $apartament_id = get_post_meta($post_id, '_booking_apartament_id', true);
               $checkin_date = get_post_meta($post_id, '_booking_checkin_date', true);
               $checkout_date = get_post_meta($post_id, '_booking_checkout_date', true);
               $total_price = get_post_meta($post_id, '_booking_total_price', true);
               
               // Преобразуем даты в правильный формат
               $checkin_ymd = date('Y-m-d', strtotime(str_replace('.', '-', $checkin_date)));
               
               // Обновляем общую выручку
               $report['total_revenue'] += (float)$total_price;
               
               // Группировка по апартаментам
               if (!isset($report['bookings_by_apartment'][$apartament_id])) {
                   $report['bookings_by_apartment'][$apartament_id] = [
                       'title' => get_the_title($apartament_id),
                       'count' => 0,
                       'revenue' => 0,
                   ];
               }
               
               $report['bookings_by_apartment'][$apartament_id]['count']++;
               $report['bookings_by_apartment'][$apartament_id]['revenue'] += (float)$total_price;
               
               // Группировка по дате заезда (по месяцам)
               $month_key = date('Y-m', strtotime($checkin_ymd));
               
               if (!isset($report['bookings_by_date'][$month_key])) {
                   $report['bookings_by_date'][$month_key] = [
                       'month' => date_i18n('F Y', strtotime($month_key . '-01')),
                       'count' => 0,
                       'revenue' => 0,
                   ];
               }
               
               $report['bookings_by_date'][$month_key]['count']++;
               $report['bookings_by_date'][$month_key]['revenue'] += (float)$total_price;
               
               // Добавляем данные о бронировании
               $report['bookings'][] = [
                   'id' => $post_id,
                   'number' => get_the_title(),
                   'status' => $status,
                   'apartament' => [
                       'id' => $apartament_id,
                       'title' => get_the_title($apartament_id),
                   ],
                   'guest' => [
                       'name' => get_post_meta($post_id, '_booking_last_name', true) . ' ' . get_post_meta($post_id, '_booking_first_name', true),
                       'email' => get_post_meta($post_id, '_booking_email', true),
                       'phone' => get_post_meta($post_id, '_booking_phone', true),
                   ],
                   'dates' => [
                       'checkin' => $checkin_date,
                       'checkout' => $checkout_date,
                   ],
                   'price' => $total_price,
                   'created_at' => get_post_meta($post_id, '_booking_created_at', true) ?: get_the_date('Y-m-d H:i:s'),
               ];
           }
           
           // Рассчитываем среднюю цену
           if ($report['total'] > 0) {
               $report['avg_price'] = $report['total_revenue'] / $report['total'];
           }
           
           // Сортируем группированные данные
           if (!empty($report['bookings_by_date'])) {
               ksort($report['bookings_by_date']);
           }
       }
       
       wp_reset_postdata();
       
       return $report;
   }
 }