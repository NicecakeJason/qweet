<?php
namespace Sun\Apartament\Admin;

use Sun\Apartament\Core\Apartament;
use Sun\Apartament\Core\Booking;
use Sun\Apartament\Services\BookingService;

/**
 * Класс для отображения панели управления
 *
 * @since 1.0.0
 */
class Dashboard {
    /**
     * Регистрирует хуки
     *
     * @return void
     */
    public function register() {
        // Этот метод не используется напрямую, так как класс Dashboard инициализируется через Menu
    }
    
    /**
     * Отображает страницу дашборда
     *
     * @return void
     */
    public function render() {
        // Получаем статистические данные
        $stats = $this->get_dashboard_stats();
        
        // Заголовок страницы
        echo '<div class="wrap sun-dashboard">';
        echo '<h1 class="sun-dashboard-title"><i class="fas fa-building"></i> ' . __('Sun Apartament - Панель управления', 'sun-apartament') . '</h1>';
        
        // Верхняя панель с кнопками действий
        echo '<div class="sun-dashboard-header">';
        echo '<div class="sun-dashboard-actions">';
        echo '<a href="' . admin_url('post-new.php?post_type=apartament') . '" class="button button-primary"><i class="fas fa-plus"></i> ' . __('Добавить апартамент', 'sun-apartament') . '</a>';
        echo '<a href="' . admin_url('post-new.php?post_type=sun_booking') . '" class="button button-primary"><i class="fas fa-calendar-plus"></i> ' . __('Новое бронирование', 'sun-apartament') . '</a>';
        echo '<a href="' . admin_url('admin.php?page=sun-booking-calendar') . '" class="button"><i class="fas fa-calendar-alt"></i> ' . __('Календарь', 'sun-apartament') . '</a>';
        echo '</div>';
        echo '</div>';
        
        // Статистические карточки
        echo '<div class="sun-dashboard-widgets">';
        echo '<div class="sun-dashboard-row">';
        
        // Карточка "Всего апартаментов"
        echo '<div class="sun-dashboard-card">';
        echo '<div class="card-icon blue"><i class="fas fa-building"></i></div>';
        echo '<div class="card-content">';
        echo '<h3>' . __('Всего апартаментов', 'sun-apartament') . '</h3>';
        echo '<p class="card-value">' . $stats['total_apartaments'] . '</p>';
        echo '</div>';
        echo '</div>';
        
        // Карточка "Активные бронирования"
        echo '<div class="sun-dashboard-card">';
        echo '<div class="card-icon green"><i class="fas fa-calendar-check"></i></div>';
        echo '<div class="card-content">';
        echo '<h3>' . __('Активные бронирования', 'sun-apartament') . '</h3>';
        echo '<p class="card-value">' . $stats['active_bookings'] . '</p>';
        echo '</div>';
        echo '</div>';
        
        // Карточка "Заезды сегодня"
        echo '<div class="sun-dashboard-card">';
        echo '<div class="card-icon orange"><i class="fas fa-calendar"></i></div>';
        echo '<div class="card-content">';
        echo '<h3>' . __('Заезды сегодня', 'sun-apartament') . '</h3>';
        echo '<p class="card-value">' . $stats['checkins_today'] . '</p>';
        echo '</div>';
        echo '</div>';
        
        // Карточка "Выезды сегодня"
        echo '<div class="sun-dashboard-card">';
        echo '<div class="card-icon purple"><i class="fas fa-calendar-day"></i></div>';
        echo '<div class="card-content">';
        echo '<h3>' . __('Выезды сегодня', 'sun-apartament') . '</h3>';
        echo '<p class="card-value">' . $stats['checkouts_today'] . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец row
        
        // Блоки с графиками и таблицами
        echo '<div class="sun-dashboard-row">';
        
        // Блок "Последние бронирования"
        echo '<div class="sun-dashboard-box">';
        echo '<h2 class="box-title"><i class="fas fa-list"></i> ' . __('Последние бронирования', 'sun-apartament');
        echo '<a href="' . admin_url('edit.php?post_type=sun_booking') . '" class="box-title-link">' . __('Все бронирования', 'sun-apartament') . '</a>';
        echo '</h2>';
        echo '<div class="box-content">';
        $this->render_recent_bookings();
        echo '</div>';
        echo '</div>';
        
        // Блок "Загруженность апартаментов"
        echo '<div class="sun-dashboard-box">';
        echo '<h2 class="box-title"><i class="fas fa-chart-bar"></i> ' . __('Загруженность апартаментов', 'sun-apartament') . '</h2>';
        echo '<div class="box-content">';
        echo '<canvas id="occupancyChart" height="200"></canvas>';
        echo '<script>
            jQuery(document).ready(function($) {
                var ctx = document.getElementById("occupancyChart").getContext("2d");
                var chart = new Chart(ctx, {
                    type: "bar",
                    data: {
                        labels: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
                        datasets: [{
                            label: "Загруженность (%)",
                            data: ' . json_encode($stats['occupancy_data']) . ',
                            backgroundColor: "rgba(54, 162, 235, 0.5)",
                            borderColor: "rgba(54, 162, 235, 1)",
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: 100
                            }
                        }
                    }
                });
            });
        </script>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец row
        
        // Вторая строка блоков
        echo '<div class="sun-dashboard-row">';
        
        // Блок "Ближайшие заезды"
        echo '<div class="sun-dashboard-box">';
        echo '<h2 class="box-title"><i class="fas fa-sign-in-alt"></i> ' . __('Ближайшие заезды', 'sun-apartament') . '</h2>';
        echo '<div class="box-content">';
        $this->render_upcoming_checkins();
        echo '</div>';
        echo '</div>';
        
        // Блок "Доступность апартаментов"
        echo '<div class="sun-dashboard-box">';
        echo '<h2 class="box-title"><i class="fas fa-home"></i> ' . __('Доступность апартаментов', 'sun-apartament') . '</h2>';
        echo '<div class="box-content">';
        $this->render_apartments_availability();
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Конец row
        
        echo '</div>'; // Конец widgets
        
        echo '</div>'; // Конец wrap
    }
    
    /**
     * Получает статистические данные для дашборда
     *
     * @return array Массив со статистикой
     */
    private function get_dashboard_stats() {
        $stats = [];
        
        // Общее количество апартаментов
        $apartaments_count = wp_count_posts('apartament');
        $stats['total_apartaments'] = $apartaments_count->publish;
        
        // Количество активных бронирований
        $bookings_count = wp_count_posts('sun_booking');
        $stats['active_bookings'] = isset($bookings_count->confirmed) ? $bookings_count->confirmed : 0;
        $stats['active_bookings'] += isset($bookings_count->pending) ? $bookings_count->pending : 0;
        
        // Текущая дата в формате d.m.Y
        $today_dmy = date('d.m.Y');
        
        // Количество заездов и выездов сегодня
        $stats['checkins_today'] = $this->count_bookings_for_date('_booking_checkin_date', $today_dmy);
        $stats['checkouts_today'] = $this->count_bookings_for_date('_booking_checkout_date', $today_dmy);
        
        // Данные для графика загруженности по месяцам
        $stats['occupancy_data'] = $this->get_occupancy_by_month();
        
        return $stats;
    }
    
    /**
     * Подсчет количества бронирований для конкретной даты
     *
     * @param string $meta_key Ключ мета-данных
     * @param string $date Дата в формате d.m.Y
     * @return int Количество бронирований
     */
    private function count_bookings_for_date($meta_key, $date) {
        global $wpdb;
        
        // Создаем запрос, который будет работать независимо от формата даты
        $query = $wpdb->prepare(
            "SELECT COUNT(p.ID) FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'sun_booking'
            AND p.post_status IN ('confirmed', 'pending')
            AND pm.meta_key = %s
            AND (pm.meta_value = %s OR pm.meta_value = %s)",
            $meta_key,
            $date,
            date('Y-m-d', strtotime($date)) // Альтернативный формат даты
        );
        
        return intval($wpdb->get_var($query));
    }
    
    /**
     * Получение данных о загруженности по месяцам
     *
     * @return array Массив с процентами загруженности по месяцам
     */
    private function get_occupancy_by_month() {
        // В реальном приложении здесь нужна логика расчета загруженности
        // Пример данных для демонстрации
        return [75, 68, 82, 60, 65, 90, 95, 97, 85, 70, 65, 78];
    }
    
    /**
     * Отображает таблицу с последними бронированиями
     *
     * @return void
     */
    private function render_recent_bookings() {
        $args = [
            'post_type' => 'sun_booking',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
            'post_status' => ['confirmed', 'pending', 'completed', 'cancelled']
        ];
        
        $recent_bookings = new \WP_Query($args);
        
        if ($recent_bookings->have_posts()) {
            echo '<table class="bookings-table">';
            echo '<thead><tr>';
            echo '<th>' . __('№ бронирования', 'sun-apartament') . '</th>';
            echo '<th>' . __('Гость', 'sun-apartament') . '</th>';
            echo '<th>' . __('Апартамент', 'sun-apartament') . '</th>';
            echo '<th>' . __('Даты', 'sun-apartament') . '</th>';
            echo '<th>' . __('Статус', 'sun-apartament') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            while ($recent_bookings->have_posts()) {
                $recent_bookings->the_post();
                $booking_id = get_the_ID();
                $apartament_id = get_post_meta($booking_id, '_booking_apartament_id', true);
                $first_name = get_post_meta($booking_id, '_booking_first_name', true);
                $last_name = get_post_meta($booking_id, '_booking_last_name', true);
                $checkin_date = get_post_meta($booking_id, '_booking_checkin_date', true);
                $checkout_date = get_post_meta($booking_id, '_booking_checkout_date', true);
                $status = get_post_status();
                
                $status_class = 'status-' . $status;
                $status_text = '';
                
                switch ($status) {
                    case 'confirmed':
                        $status_text = __('Подтверждено', 'sun-apartament');
                        break;
                    case 'pending':
                        $status_text = __('Ожидает', 'sun-apartament');
                        break;
                    case 'completed':
                        $status_text = __('Завершено', 'sun-apartament');
                        break;
                    case 'cancelled':
                        $status_text = __('Отменено', 'sun-apartament');
                        break;
                    default:
                        $status_text = $status;
                }
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($booking_id) . '">' . get_the_title() . '</a></td>';
                echo '<td>' . esc_html($last_name . ' ' . $first_name) . '</td>';
                echo '<td>' . ($apartament_id ? '<a href="' . get_edit_post_link($apartament_id) . '">' . esc_html(get_the_title($apartament_id)) . '</a>' : '-') . '</td>';
                echo '<td>' . esc_html($checkin_date . ' - ' . $checkout_date) . '</td>';
                echo '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Нет бронирований.', 'sun-apartament') . '</p>';
        }
        
        wp_reset_postdata();
    }
    
    /**
     * Отображает таблицу с ближайшими заездами
     *
     * @return void
     */
    private function render_upcoming_checkins() {
        $today = date('d.m.Y');
        $week_later = date('d.m.Y', strtotime('+7 days'));
        
        $args = [
            'post_type' => 'sun_booking',
            'posts_per_page' => 5,
            'post_status' => ['confirmed', 'pending'],
            'meta_query' => [
                [
                    'key' => '_booking_checkin_date',
                    'value' => [$today, $week_later],
                    'compare' => 'BETWEEN',
                    'type' => 'DATE'
                ]
            ],
            'orderby' => 'meta_value',
            'meta_key' => '_booking_checkin_date',
            'order' => 'ASC'
        ];
        
        $upcoming_checkins = new \WP_Query($args);
        
        if ($upcoming_checkins->have_posts()) {
            echo '<table class="bookings-table">';
            echo '<thead><tr>';
            echo '<th>' . __('Дата заезда', 'sun-apartament') . '</th>';
            echo '<th>' . __('Гость', 'sun-apartament') . '</th>';
            echo '<th>' . __('Апартамент', 'sun-apartament') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            while ($upcoming_checkins->have_posts()) {
                $upcoming_checkins->the_post();
                $booking_id = get_the_ID();
                $apartament_id = get_post_meta($booking_id, '_booking_apartament_id', true);
                $first_name = get_post_meta($booking_id, '_booking_first_name', true);
                $last_name = get_post_meta($booking_id, '_booking_last_name', true);
                $checkin_date = get_post_meta($booking_id, '_booking_checkin_date', true);
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($booking_id) . '">' . esc_html($checkin_date) . '</a></td>';
                echo '<td>' . esc_html($last_name . ' ' . $first_name) . '</td>';
                echo '<td>' . ($apartament_id ? '<a href="' . get_edit_post_link($apartament_id) . '">' . esc_html(get_the_title($apartament_id)) . '</a>' : '-') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Нет ближайших заездов.', 'sun-apartament') . '</p>';
        }
        
        wp_reset_postdata();
    }
    
    /**
     * Отображает таблицу с доступностью апартаментов
     *
     * @return void
     */
    private function render_apartments_availability() {
        $args = [
            'post_type' => 'apartament',
            'posts_per_page' => 10,
            'orderby' => 'title',
            'order' => 'ASC'
        ];
        
        $apartments = new \WP_Query($args);
        
        if ($apartments->have_posts()) {
            echo '<table class="bookings-table">';
            echo '<thead><tr>';
            echo '<th>' . __('Апартамент', 'sun-apartament') . '</th>';
            echo '<th>' . __('Статус', 'sun-apartament') . '</th>';
            echo '<th>' . __('Тип', 'sun-apartament') . '</th>';
            echo '<th>' . __('Действия', 'sun-apartament') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            while ($apartments->have_posts()) {
                $apartments->the_post();
                $apartament_id = get_the_ID();
                
                // Получаем бронирования для апартамента
                $today = date('Y-m-d');
                
                // Получаем даты недоступности
                $booked_dates = get_post_meta($apartament_id, '_apartament_booked_dates', true);
                
                // Проверяем, занят ли апартамент сегодня
                $is_occupied = is_array($booked_dates) && isset($booked_dates[$today]);
                
                // Получаем термины таксономии "Тип апартамента"
                $types = get_the_terms($apartament_id, 'apartament-type');
                $type_name = $types && !is_wp_error($types) ? $types[0]->name : '-';
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($apartament_id) . '">' . get_the_title() . '</a></td>';
                echo '<td>' . ($is_occupied ? 
                    '<span class="status-badge status-confirmed">' . __('Занят', 'sun-apartament') . '</span>' : 
                    '<span class="status-badge status-completed">' . __('Свободен', 'sun-apartament') . '</span>') . '</td>';
                echo '<td>' . esc_html($type_name) . '</td>';
                echo '<td>';
                echo '<a href="' . admin_url('post.php?post=' . $apartament_id . '&action=edit') . '" class="button button-small">' . __('Редактировать', 'sun-apartament') . '</a> ';
                echo '<a href="' . admin_url('post-new.php?post_type=sun_booking&apartament_id=' . $apartament_id) . '" class="button button-small">' . __('Забронировать', 'sun-apartament') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Нет апартаментов.', 'sun-apartament') . '</p>';
        }
        
        wp_reset_postdata();
    }
}