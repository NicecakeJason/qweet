<?php
/**
 * Шаблон общей формы бронирования
 *
 * @var array $atts Атрибуты шорткода
 */

// Защита от прямого доступа
if (!defined('ABSPATH')) {
    exit;
}

// Определяем какой шаблон загружать
if (is_front_page() || is_archive()) {
    include dirname(__FILE__) . '/booking-form-full.php';
} elseif (is_singular()) {
    include dirname(__FILE__) . '/booking-form-compact.php';
} else {
    include dirname(__FILE__) . '/booking-form-full.php';
}