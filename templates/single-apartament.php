<?php
/**
 * Шаблон для отображения одиночного апартамента
 *
 * @package Sun_Apartament
 * @since 1.0.0
 */

// Если нет прямого доступа - выходим
if (!defined('ABSPATH')) {
    exit;
}

get_header('flat'); ?>

<main>
    <section class="section">
        <div class="container">
            <?php
            if (have_posts()) {
                // Load posts loop.
                while (have_posts()) {
                    the_post(); ?>

                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <div class="row">
                            <div class="col-xl-7 mb-4">
                                <?php
                                // Вывод галереи изображений
                                $gallery_images = get_post_meta(get_the_ID(), 'sunapartament_gallery', true);
                                if ($gallery_images) {
                                    echo '<div id="gallery-container" class="slick">';
                                    foreach ($gallery_images as $image_id) {
                                        $alt_text = get_post_meta($image_id, '_wp_attachment_image_alt', true);
                                        $image_url = wp_get_attachment_image_src($image_id, 'large')[0]; 
                                        echo '<a class="gallery-item" href="' . esc_url($image_url) . '">' . 
                                            wp_get_attachment_image($image_id, 'large', false, array(
                                                'class' => 'img-fluid img-content',
                                                'alt' => esc_attr($alt_text),
                                            )) . '</a>';
                                    }
                                    echo '</div>';
                                }
                                ?>

                                <div class="">
                                    <div class="apartment-header">
                                        <div class="apartment-title">
                                            <h2 class="detail-room__title h4-title"><?php the_title(); ?></h2>
                                        </div>
                                        <div class="features-section">
                                            <?php
                                            // Вывод основных характеристик
                                            $square_footage = get_post_meta(get_the_ID(), 'sunapartament_square_footage', true);
                                            $guest_count = get_post_meta(get_the_ID(), 'sunapartament_guest_count', true);
                                            $floor_count = get_post_meta(get_the_ID(), 'sunapartament_floor_count', true);

                                            $square_footage_icon = get_post_meta(get_the_ID(), 'sunapartament_square_footage_icon', true);
                                            $guest_count_icon = get_post_meta(get_the_ID(), 'sunapartament_guest_count_icon', true);
                                            $floor_count_icon = get_post_meta(get_the_ID(), 'sunapartament_floor_count_icon', true);

                                            if ($square_footage || $guest_count || $floor_count) {
                                                echo '<div class="card-meta">';
                                                echo '<ul class="detail-main__list">';
                                                
                                                if ($square_footage) {
                                                    echo '<li class="header-social__item">';
                                                    echo '<div class="wrapper feature-item">';
                                                    if ($square_footage_icon) {
                                                        echo '<img class="card-icon" src="' . esc_url($square_footage_icon) . '" alt="Квадратура" class="icon">';
                                                    }
                                                    echo '<span class="detail-info__text">' . esc_html($square_footage) . ' м²</span>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }

                                                if ($floor_count) {
                                                    echo '<li class="header-social__item">';
                                                    echo '<div class="wrapper feature-item">';
                                                    if ($floor_count_icon) {
                                                        echo '<img class="card-icon" src="' . esc_url($floor_count_icon) . '" alt="Кровать" class="icon">';
                                                    }
                                                    echo '<span class="detail-info__text">' . esc_html($floor_count) . ' этаж</span>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }

                                                // Вывод количества гостей
                                                if ($guest_count) {
                                                    echo '<li class="header-social__item">';
                                                    echo '<div class="wrapper feature-item">';
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
                                            ?>
                                        </div>
                                    </div>

                                    <div class="description-section card-description">
                                        <h2 class="amenities-title section-title">О жилье</h2>
                                        <div class="text-wrapper" id="textWrapper">
                                            <?php the_content(); ?>
                                        </div>
                                        <button class="toggle-btn" id="toggleBtn">Показать полностью</button>
                                    </div>
                                    
                                    <div class="rules-section">
                                        <h2 class="amenities-title detail-room__title h4-title">Правила объекта размещения</h2>
                                        <ul class="rules-list">
                                            <li class="rule-item">
                                                <span class="rules">Заезд с 14:00 до 21:00</span>
                                            </li>
                                            <li class="rule-item">
                                                <span class="rules">Выезд до 12:00</span>
                                            </li>
                                            <li class="rule-item">
                                                <span class="rules">Курение запрещено</span>
                                            </li>
                                            <li class="rule-item">
                                                <span class="rules">Без домашних животных</span>
                                            </li>
                                            <li class="rule-item">
                                                <span class="rules">Без вечеринок и мероприятий</span>
                                            </li>
                                            <li class="rule-item">
                                                <span class="rules rules-deposit">*Депозит возвращается в полном размере после проверки состояния помещения при отсутствии повреждений</span>
                                            </li>
                                        </ul>
                                    </div>
                                    
                                    <div class="property_info">
                                        <?php
                                        // Функция для получения меток категорий удобств
                                        function get_category_label($category_slug) {
                                            $category_labels = [
                                                'beds' => 'Кровати',
                                                'internet' => 'Интернет',
                                                'furniture' => 'Мебель',
                                                'bathroom' => 'Ванная комната',
                                                'kitchen' => 'Кухня',
                                                'video' => 'Ванная комната',
                                                'electronics' => 'Электроника',
                                                'area' => 'Внутренний двор и вид из окна',
                                                'other' => 'Прочее',
                                            ];

                                            return isset($category_labels[$category_slug]) ? $category_labels[$category_slug] : $category_slug;
                                        }
                                        
                                        // Вывод дополнительных удобств
                                        $amenities = get_post_meta(get_the_ID(), 'sunapartament_additional_amenities', true);

                                        if ($amenities && is_array($amenities)) {
                                            // Группируем удобства по категориям
                                            $grouped_amenities = [];
                                            foreach ($amenities as $amenity) {
                                                if (isset($amenity['category']) && isset($amenity['name']) && isset($amenity['icon'])) {
                                                    $category = $amenity['category'];
                                                    if (!isset($grouped_amenities[$category])) {
                                                        $grouped_amenities[$category] = [];
                                                    }
                                                    $grouped_amenities[$category][] = $amenity;
                                                }
                                            }

                                            echo '<h2 class="amenities-title section-title mb-3">Что вас ждет в апартаментах</h2>';

                                            // Выводим удобства по категориям
                                            echo '<div class="additional-amenities row row-cols-1 row-cols-xl-3 row-cols-lg-2 row-cols-md-2 g-4 mb-5 section-grid">';
                                            
                                            foreach ($grouped_amenities as $category => $amenities_in_category) {
                                                echo '<div class="category">';
                                                echo '<h3 class="amenities-subtitle">' . esc_html(get_category_label($category)) . '</h3>'; 
                                                
                                                echo '<ul class="detail-info__list">'; 
                                                foreach ($amenities_in_category as $amenity) {
                                                    echo '<li class="wrapper ' . esc_attr($category) . '">';
                                                    echo '<div class="detail-info__wrapper">';
                                                    
                                                    if ($amenity['icon']) {
                                                        echo '<img class="card-icon" src="' . esc_url($amenity['icon']) . '" alt="' . esc_attr($amenity['name']) . '">';
                                                    }
                                                    
                                                    echo '<span class="detail-info__text">' . esc_html($amenity['name']) . '</span>';
                                                    echo '</div>';
                                                    echo '</li>';
                                                }
                                                echo '</ul>';
                                                echo '</div>';
                                            }
                                            
                                            echo '</div>';
                                        } else {
                                            // Если удобств нет, выводим сообщение
                                            echo '<p>Дополнительные удобства отсутствуют.</p>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-xl-5 mb-4">
                                <div class="position-sticky" style="top:60px;">
                                    <div class="price">
                                        <div class="price-info">
                                            <div class="d-flex justify-content-between wrapper-cost">
                                                <div>
                                                    <div class="price-value">
                                                        <?php
                                                        // Используем существующий сервис для получения цены
                                                        $booking_info_service = new \Sun\Apartament\Services\BookingInfoService();
                                                        $current_price = $booking_info_service->display_current_price(get_the_ID());
                                                        echo '<span class="cost">' . esc_html($current_price) . ' ₽</span>';
                                                        ?>
                                                    </div>
                                                    <div class="price-label">
                                                        <span class="day">Цена за ночь</span>
                                                    </div>
                                                    <div class="price-value">
                                                        <?php echo '<span class="cost">' . esc_html($current_price) . ' ₽</span>'; ?>
                                                    </div>
                                                    <div class="price-label">
                                                        <span class="day">Депозит*</span>
                                                    </div>
                                                </div>
                                                <div class="ya-share2" data-curtain data-shape="round" data-limit="0"
                                                    data-more-button-type="short"
                                                    data-services="vkontakte,odnoklassniki,telegram,whatsapp"></div>
                                            </div>

                                            <?php 
                                            // Использование шорткода для формы бронирования
                                            echo do_shortcode('[sunapartament_booking_form]'); 
                                            ?>

                                            <!-- Button trigger modal -->
                                            <button type="button" class="call-btn" data-bs-toggle="modal" data-bs-target="#exampleModal">
                                                Заказать звонок
                                            </button>

                                            <!-- Modal -->
                                            <div class="modal fade" data-backdrop="false" id="exampleModal" tabindex="-1"
                                                aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ...
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="host-info">
                                                <div class="phone">+7 (927) 111-33-22</div>
                                                <div>Звоните с 9:00 до 21:00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php }
            }
            ?>
            
            <?php get_template_part('template-parts/section-accardion'); ?>
        </div>
    </section>
</main>

<?php get_footer(); ?>