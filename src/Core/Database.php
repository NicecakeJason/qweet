<?php
namespace Sun\Apartament\Core;

/**
 * Класс для работы с базой данных
 */
class Database {
    /**
     * Префикс таблиц плагина
     *
     * @var string
     */
    private $table_prefix;

    /**
     * Конструктор класса
     */
    public function __construct() {
        global $wpdb;
        $this->table_prefix = $wpdb->prefix . 'sun_';
    }

    /**
     * Создаёт таблицы в базе данных
     *
     * @return array Результаты создания таблиц
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $results = [];

        // Таблица для персональных данных
        $table_personal_data = $this->get_table_name('personal_data');
        $sql_personal_data = "CREATE TABLE IF NOT EXISTS $table_personal_data (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            middle_name varchar(100),
            email varchar(100) NOT NULL,
            phone varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Таблица для бронирований
        $table_bookings = $this->get_table_name('bookings');
        $sql_bookings = "CREATE TABLE IF NOT EXISTS $table_bookings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            booking_id varchar(20) NOT NULL,
            personal_data_id mediumint(9) NOT NULL,
            apartament_id mediumint(9) NOT NULL,
            checkin_date date NOT NULL,
            checkout_date date NOT NULL,
            total_price decimal(10,2) NOT NULL,
            payment_method varchar(50) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            terms_accepted tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY booking_id (booking_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $results['personal_data'] = dbDelta($sql_personal_data);
        $results['bookings'] = dbDelta($sql_bookings);

        return $results;
    }

    /**
     * Проверяет существование таблиц и создает их при необходимости
     *
     * @return bool Результат проверки и создания
     */
    public function check_and_create_tables() {
        global $wpdb;
        
        $personal_data_table = $this->get_table_name('personal_data');
        $bookings_table = $this->get_table_name('bookings');
        
        // Проверяем существование таблиц
        $personal_data_exists = $wpdb->get_var("SHOW TABLES LIKE '$personal_data_table'") === $personal_data_table;
        $bookings_exists = $wpdb->get_var("SHOW TABLES LIKE '$bookings_table'") === $bookings_table;
        
        // Если хотя бы одна таблица не существует, создаем обе таблицы
        if (!$personal_data_exists || !$bookings_exists) {
            $this->create_tables();
            return true;
        }
        
        return false;
    }

    /**
     * Возвращает имя таблицы с префиксом
     *
     * @param string $table Имя таблицы без префикса
     * @return string Полное имя таблицы с префиксом
     */
    public function get_table_name($table) {
        return $this->table_prefix . $table;
    }

    /**
     * Удаляет таблицы плагина из базы данных
     *
     * @return bool Результат удаления
     */
    public function drop_tables() {
        global $wpdb;
        
        $tables = [
            $this->get_table_name('personal_data'),
            $this->get_table_name('bookings')
        ];
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        return true;
    }
}