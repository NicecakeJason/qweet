<?php
/**
 * Шаблон элемента апартамента для списка
 *
 * @package Sun\Apartament
 */

// Получаем экземпляр сервиса цен, переданный из архивного шаблона
$price_service = isset($price_service) ? $price_service : new \Sun\Apartament\Services\PriceService();
?>

<div id="post-<?php the_ID(); ?>" <?php post_class('col'); ?>>
    <div class="card">

        <?php
        // Получаем галерею изображений (поддержка обоих форматов метаполей)
        $gallery_images = get_post_meta(get_the_ID(), 'sun_apartament_gallery', true);
        if (empty($gallery_images)) {
            // Пробуем получить из старого формата метаполя
            $gallery_images = get_post_meta(get_the_ID(), 'sunapartament_gallery', true);
        }
        
        if ($gallery_images && is_array($gallery_images)) {
            echo '<div class="slick">';
            foreach ($gallery_images as $image_id) {
                // Получаем URL текущего поста
                $post_url = get_permalink();
                // Получаем альтернативный текст изображения
                $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                
                // Выводим изображение со ссылкой на страницу апартамента
                echo '<div><a href="' . esc_url($post_url) . '" class="gallery-link">' . wp_get_attachment_image($image_id, 'large', false, array(
                    'class' => 'img-fluid img-content',
                    'alt' => esc_attr($alt_text),
                )) . '</a></div>';
            }
            echo '</div>';
        }
        ?>

        <div class="card-body">
            <a href="<?php the_permalink(); ?>">
                <h4 class="detail-room__title amenities-card__title h4-title">
                    <?php the_title(); ?>
                </h4>
            </a>
            <?php
            // Получаем метаданные апартамента (поддержка обоих форматов метаполей)
            function get_meta_with_fallback($post_id, $new_key, $old_key, $default = '') {
                $value = get_post_meta($post_id, $new_key, true);
                if (empty($value) && $value !== '0') {
                    $value = get_post_meta($post_id, $old_key, true);
                }
                return !empty($value) ? $value : $default;
            }
            
            $square_footage = get_meta_with_fallback(get_the_ID(), 'sun_apartament_square_footage', 'sunapartament_square_footage');
            $guest_count = get_meta_with_fallback(get_the_ID(), 'sun_apartament_guest_count', 'sunapartament_guest_count');
            $floor_count = get_meta_with_fallback(get_the_ID(), 'sun_apartament_floor_count', 'sunapartament_floor_count');
            
            // Получаем иконки для метаданных
            $square_footage_icon = get_meta_with_fallback(get_the_ID(), 'sun_apartament_square_footage_icon', 'sunapartament_square_footage_icon');
            $guest_count_icon = get_meta_with_fallback(get_the_ID(), 'sun_apartament_guest_count_icon', 'sunapartament_guest_count_icon');
            $floor_count_icon = get_meta_with_fallback(get_the_ID(), 'sun_apartament_floor_count_icon', 'sunapartament_floor_count_icon');

            // Выводим метаданные, если они существуют
            if ($square_footage || $guest_count || $floor_count) {
                echo '<div class="card-meta">';
                echo '<ul class="d-flex justify-content-between">';
                
                // Площадь
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

                // Этаж
                if ($floor_count) {
                    echo '<li class="header-social__item">';
                    echo '<div class="wrapper">';
                    if ($floor_count_icon) {
                        echo '<img class="card-icon" src="' . esc_url($floor_count_icon) . '" alt="Этаж" class="icon">';
                    }
                    echo '<span class="detail-info__text">' . esc_html($floor_count) . ' этаж</span>';
                    echo '</div>';
                    echo '</li>';
                }
                
                // Количество гостей
                if ($guest_count) {
                    echo '<li class="header-social__item">';
                    echo '<div class="wrapper">';
                    if ($guest_count_icon) {
                        echo '<img class="card-icon" src="' . esc_url($guest_count_icon) . '" alt="Гости" class="icon">';
                    }
                    echo '<span class="detail-info__text">До ' . esc_html($guest_count) . ' мест</span>';
                    echo '</div>';
                    echo '</li>';
                }
                
                echo '</ul>';
                echo '</div>';
            }
            
            // Получаем и выводим удобства
            $amenities = array();
            
            // Проверяем новый формат метаполя
            $new_amenities = get_post_meta(get_the_ID(), 'sun_apartament_amenities', true);
            if (!empty($new_amenities) && is_array($new_amenities)) {
                $amenities = $new_amenities;
            } else {
                // Проверяем старый формат метаполя
                $old_amenities = get_post_meta(get_the_ID(), 'sunapartament_amenities', true);
                if (!empty($old_amenities) && is_array($old_amenities)) {
                    $amenities = $old_amenities;
                }
            }
            
            if (!empty($amenities)) {
                echo '<div class="card-amenities">';
                echo '<h5 class="amenities-title">' . esc_html__('Удобства', 'sun-apartament') . '</h5>';
                echo '<ul class="amenities-list">';
                
                // Ограничиваем количество отображаемых удобств (не более 5)
                $max_amenities = 5;
                $count = 0;
                
                foreach ($amenities as $amenity) {
                    if ($count >= $max_amenities) {
                        break;
                    }
                    
                    $icon = '';
                    if (isset($amenity['icon']) && !empty($amenity['icon'])) {
                        $icon = '<img src="' . esc_url($amenity['icon']) . '" alt="' . esc_attr($amenity['name']) . '" class="amenity-icon">';
                    }
                    
                    echo '<li class="amenity-item">' . $icon . ' ' . esc_html($amenity['name']) . '</li>';
                    $count++;
                }
                
                echo '</ul>';
                
                // Если есть больше удобств, добавляем ссылку на страницу апартамента
                if (count($amenities) > $max_amenities) {
                    echo '<a href="' . get_permalink() . '#amenities" class="more-amenities-link">' . 
                         esc_html__('Еще ', 'sun-apartament') . (count($amenities) - $max_amenities) . 
                         esc_html__(' удобств', 'sun-apartament') . '</a>';
                }
                
                echo '</div>';
            }
            ?>
        </div>

        <div class="card-cost d-flex justify-content-between align-items-center">
            <?php
            // Получаем текущую цену с использованием нового сервиса цен
            $current_date = date('Y-m-d');
            $current_price = $price_service->get_price_for_date(get_the_ID(), $current_date);
            
            // Если цена не получена, пробуем использовать старый метод
            if (empty($current_price)) {
                $base_price = get_meta_with_fallback(get_the_ID(), '_apartament_base_price', 'sunapartament_price', 0);
                $current_price = !empty($base_price) ? $base_price : 0;
            }
            
            echo '<div class="d-flex flex-column">';
            echo '<span class="cost">' . esc_html(number_format($current_price, 0, '.', ' ')) . ' ₽</span>';
            echo '<span class="day">за ночь</span>';
            echo '</div>';
            ?>
            <a class="card-btn" href="<?php the_permalink(); ?>"><?php esc_html_e('Подробнее', 'sun-apartament'); ?></a>
        </div>
    </div>
</div>