-- Script de creación de tablas para Usuarios, Personal y Sesiones
-- Sistema: Multiservicio

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla de Roles (Para definir niveles de acceso)
CREATE TABLE IF NOT EXISTS `table_roles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nombre_rol` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabla de Personal (Información laboral/física)
CREATE TABLE IF NOT EXISTS `table_staff` (
    `id` VARCHAR(50) PRIMARY KEY, -- Usamos VARCHAR para IDs como 'STAFF-1' según tu app.js
    `nombre` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `telefono` VARCHAR(20),
    `direccion` TEXT,
    `cargo` VARCHAR(50),           -- Cargo (Mecánico, Administrador, etc)
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tabla de Usuarios (Credenciales de acceso al sistema)
CREATE TABLE IF NOT EXISTS `table_usuarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` VARCHAR(50) NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE, -- Este será el "nick"
    `password` VARCHAR(255) NOT NULL,
    `role_id` INT NOT NULL,
    `estado` TINYINT(1) DEFAULT 1, -- 1: Activo, 0: Inactivo
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) REFERENCES `table_roles` (`id`),
    CONSTRAINT `fk_user_staff` FOREIGN KEY (`staff_id`) REFERENCES `table_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Tabla de Sesiones Activas (Para control de sesión única)
CREATE TABLE IF NOT EXISTS `table_usuario_sessions` (
    `usuario_id` INT PRIMARY KEY, -- Clave primaria para asegurar una sola entrada por usuario
    `session_id` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45),
    `usuario_agent` TEXT,
    `created_at` DATETIME,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_session_user` FOREIGN KEY (`usuario_id`) REFERENCES `table_usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- INSERCIÓN DE DATOS INICIALES (SEMILLAS)
-- ==========================================

-- Insertar roles básicos
INSERT INTO `table_roles` (`nombre_rol`) VALUES ('Administrador'), ('Mecánico'), ('Empleado');

-- Insertar un empleado administrativo inicial
INSERT INTO `table_staff` (`id`, `nombre`, `email`, `telefono`, `direccion`, `cargo`) 
VALUES ('STAFF-1', 'ADMINISTRADOR PRINCIPAL', 'admin@tallerpro.com', '3001234567', 'Calle Principal #123', 'Administrador');

-- Insertar usuario inicial (Email: admin@tallerpro.com / Password: password123)
-- Nota: Actualmente tu ControllerAuth no usa password_verify, por lo que se guarda en texto plano para las pruebas iniciales.
INSERT INTO `table_usuarios` (`staff_id`, `username`, `password`, `role_id`) 
VALUES ('STAFF-1', 'admin', 'password123', 1);

SET FOREIGN_KEY_CHECKS = 1;