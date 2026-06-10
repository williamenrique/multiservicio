<?php
/**
 * Excepción personalizada para errores controlados de la aplicación.
 * Permite definir un código de estado HTTP y un mensaje amigable.
 */
class AppException extends Exception {
    public function __construct($message, $code = 400) {
        // Aseguramos que el código sea un entero (HTTP Status Code)
        parent::__construct($message, (int)$code);
    }
}