<?php
/**
 * Шаблон страницы результатов поиска апартаментов
 * 
 *
 * @package Sun_Apartament
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

get_header('flat'); // Подключаем header темы

// Функция для склонения слова "ночь"
if (!function_exists('pluralize_nights')) {
   function pluralize_nights($number)
   {
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

?>
<main>
   <section class="section">
      <div class="container">
         <?php if (function_exists('custom_breadcrumbs')) custom_breadcrumbs(); ?>
         <h1 class="section-title h3-title"><?php echo esc_html__('Результаты поиска', 'sun-apartament'); ?></h1>
         <?php
         // Получаем параметры из URL
         $checkin_date = isset($_GET['checkin_date']) ? sanitize_text_field($_GET['checkin_date']) : '';
         $checkout_date = isset($_GET['checkout_date']) ? sanitize_text_field($_GET['checkout_date']) : '';
         $guest_count = isset($_GET['guest_count']) ? intval($_GET['guest_count']) : 0;
         $children_count = isset($_GET['children_count']) ? intval($_GET['children_count']) : 0;
         $total_guests = $guest_count + $children_count;

         if ($checkin_date && $checkout_date && $total_guests > 0) {
            // Получаем все квартиры
            $args = array(
               'post_type' => 'apartament',
               'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            $available_apartaments = []; // Массив для хранения доступных квартир
            
            // Создаем экземпляр класса для работы с ценами
            $price_service = new \Sun\Apartament\Services\PriceService();
         
            if ($query->have_posts()) {
               while ($query->have_posts()) {
                  $query->the_post();
                  $apartament_id = get_the_ID();
                  
                  // Получаем количество гостей, которое может вместить квартира
                  $apartament_guest_count = get_post_meta($apartament_id, 'sunapartament_guest_count', true);

                  // Проверяем, подходит ли квартира по количеству гостей
                  if ($apartament_guest_count >= $total_guests) {
                     // Проверяем доступность квартиры на указанные даты
                     $booked_dates = get_post_meta($apartament_id, '_apartament_availability', true);
                     $booked_dates = $booked_dates ? json_decode($booked_dates, true) : [];
                     $is_available = true;
                     
                     // Проверяем свободные даты
                     $current_date = $checkin_date;
                     while (strtotime($current_date) < strtotime($checkout_date)) {
                        if (in_array($current_date, $booked_dates)) {
                           $is_available = false;
                           break;
                        }
                        $current_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
                     }

                     // Если квартира доступна, получаем цены на весь период
                     if ($is_available) {
                        // Получаем цены на весь период
                        $period_prices_data = $price_service->get_prices_for_period($apartament_id, $checkin_date, $checkout_date);
                        
                        // Получаем количество ночей и другие данные
                        $nights_count = $period_prices_data['nights'];
                        $total_price = $period_prices_data['total_price'];
                        $price_details = $period_prices_data['daily_prices'];
                        
                        $average_price_per_night = $nights_count > 0 ? $total_price / $nights_count : 0;
                        
                        // Добавляем квартиру в список доступных
                        $available_apartaments[] = [
                           'id' => $apartament_id,
                           'title' => get_the_title(),
                           'total_price' => $total_price,
                           'average_price_per_night' => $average_price_per_night,
                           'days_count' => $nights_count,
                           'price_details' => $price_details
                        ];
                     }
                  }
               }
               wp_reset_postdata(); // Сбрасываем данные поста
            }

            // Если есть доступные квартиры, отображаем их
            if (!empty($available_apartaments)) {
               echo '<div class="row row-cols-1 row-cols-xl-3 row-cols-lg-2 row-cols-md-2 g-4 section-grid">';
               foreach ($available_apartaments as $apartament) {
                  echo '<div id="post-' . $apartament['id'] . '" class="post-' . $apartament['id'] . '">';
                  echo '<div class="card">';
                  // Вывод галереи
                  $gallery_images = get_post_meta($apartament['id'], 'sunapartament_gallery', true);
                  if ($gallery_images && is_array($gallery_images)) {
                     echo '<div class="slick">';
                     foreach ($gallery_images as $image_id) {
                        $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                        echo '<div>' . wp_get_attachment_image($image_id, 'large', false, array(
                           'class' => 'img-fluid img-content',
                          'alt' => esc_attr($alt_text),
                        )) . '</div>';
                     }
                     echo '</div>';
                  } else {
                     echo '<p>Галерея не найдена.</p>';
                  }
                  echo '<div class="card-body">';
                  echo '<a href="' . add_query_arg('source', 'results', get_permalink($apartament['id'])) . '"><h4 class="detail-room__title amenities-card__title h4-title">' . esc_html($apartament['title']) . '</h4></a>';
                  // Вывод удобств
                  $square_footage = get_post_meta($apartament['id'], 'sunapartament_square_footage', true);
                  $guest_count_meta = get_post_meta($apartament['id'], 'sunapartament_guest_count', true);
                  $floor_count = get_post_meta($apartament['id'], 'sunapartament_floor_count', true);
                  $square_footage_icon = get_post_meta($apartament['id'], 'sunapartament_square_footage_icon', true);
                  $guest_count_icon = get_post_meta($apartament['id'], 'sunapartament_guest_count_icon', true);
                  $floor_count_icon = get_post_meta($apartament['id'], 'sunapartament_floor_count_icon', true);
                  if ($square_footage || $guest_count_meta || $floor_count) {
                     echo '<div class="card-meta">';
                     echo '<ul class="d-flex justify-content-between">';
                     if ($square_footage) {
                        echo '<li class="header-social__item">';
                        echo '<div class="wrapper">';
                        if ($square_footage_icon) {
                           echo '<img class="card-icon" src="' . esc_url($square_footage_icon) . '" alt="Квадратура" class="icon">';
                        }
                        echo '<span class="detail-info__text">' . esc_html($square_footage) . ' м²</span>';
                        echo '</div>';
                        echo '</li>';
                     }
                     if ($floor_count) {
                        echo '<li class="header-social__item">';
                        echo '<div class="wrapper">';
                        if ($floor_count_icon) {
                           echo '<img class="card-icon" src="' . esc_url($floor_count_icon) . '" alt="Кровать" class="icon">';
                        }
                        echo '<span class="detail-info__text">' . esc_html($floor_count) . ' этаж</span>';
                        echo '</div>';
                        echo '</li>';
                     }
                     if ($guest_count_meta) {
                        echo '<li class="header-social__item">';
                        echo '<div class="wrapper align-items-center">';
                        if ($guest_count_icon) {
                           echo '<img class="card-icon" src="' . esc_url($guest_count_icon) . '" alt="Гости" class="icon">';
                        }
                        echo '<span class="detail-info__text">До ' . esc_html($guest_count_meta) . ' мест</span>';
                        echo '</div>';
                        echo '</li>';
                     }
                     echo '</ul>';
                     echo '</div>';
                  }
                  echo '<div class="card-cost d-flex justify-content-between align-items-center"> ';
                  echo '<div class="d-flex flex-column"> ';
                  
                  // Вывод общей стоимости за весь период
                  echo '<span class="cost">' . number_format($apartament['total_price'], 0, '.', ' ') . ' руб.</span>';
                  
                  // Вывод количества ночей
                  echo '<span class="">' . $apartament['days_count'] . ' ' . pluralize_nights($apartament['days_count']) . '</span>';
                     
                  echo '</div>';
                  echo '<a class="card-btn" href="' . add_query_arg(array(
                     'checkin_date' => $checkin_date,
                     'checkout_date' => $checkout_date,
                     'guest_count' => $guest_count,
                     'children_count' => $children_count,
                     'source' => 'results'
                 ), get_permalink($apartament['id'])) . '">' . esc_html__('Подробнее', 'sun-apartament') . '</a>';
                  echo '</div> ';
                  echo '</div>';
                  echo '</div>';
                  echo '</div>';
               }
               echo '</div>';
            } else {
               // Если доступных квартир нет, выводим сообщение
               echo '<p>Квартиры не найдены.</p>';
            }
         } else {
            echo '<p>Пожалуйста, заполните форму бронирования для поиска доступных квартир.</p>';
         }
         ?>
      </div>
   </section>
</main>
<?php
get_footer(); // Подключаем footer темы
?>