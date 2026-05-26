<?php
/**
 * Clases de excepciones personalizadas para el sistema.
 */
class AppException extends Exception {}

/** Lanzada cuando el stock en inventario no cubre el pedido */
class StockException extends AppException {}

/** Lanzada para errores críticos de base de datos */
class DatabaseException extends AppException {}