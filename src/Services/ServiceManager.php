<?php
namespace Sun\Apartament\Services;

/**
 * Класс для управления сервисами
 *
 * @since 1.0.0
 */
class ServiceManager {
    /**
     * Экземпляр класса (для реализации паттерна Singleton)
     *
     * @var ServiceManager
     */
    private static $instance;
    
    /**
     * Зарегистрированные сервисы
     *
     * @var array
     */
    private $services = [];
    
    /**
     * Получает экземпляр класса (реализация паттерна Singleton)
     *
     * @return ServiceManager
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Конструктор класса
     */
    private function __construct() {
        // Приватный конструктор для реализации паттерна Singleton
    }
    
    /**
     * Инициализирует все сервисы
     *
     * @return void
     */
    public function init() {
        $this->register_services();
    }
    
    /**
     * Регистрирует все сервисы
     *
     * @return void
     */
    private function register_services() {
        // Список всех сервисов
        $services = [
            BookingService::class,
            NotificationService::class,
            PriceService::class,
            AvailabilityService::class,
            TemplateService::class, // Добавляем TemplateService
        ];
        
        // Регистрация каждого сервиса
        foreach ($services as $service_class) {
            $this->register_service($service_class);
        }
    }
    
    /**
     * Регистрирует отдельный сервис
     *
     * @param string $service_class Класс сервиса
     * @return void
     */
    public function register_service($service_class) {
        if (!class_exists($service_class)) {
            return;
        }
        
        // Создаем экземпляр сервиса и регистрируем его
        $service = new $service_class();
        
        if ($service instanceof AbstractService) {
            $service->register();
            $this->services[$service_class] = $service;
        }
    }
    
    /**
     * Получает сервис по классу
     *
     * @param string $service_class Класс сервиса
     * @return AbstractService|null
     */
    public function get_service($service_class) {
        if (isset($this->services[$service_class])) {
            return $this->services[$service_class];
        }
        
        return null;
    }
    
    /**
     * Получает все зарегистрированные сервисы
     *
     * @return array
     */
    public function get_services() {
        return $this->services;
    }
}