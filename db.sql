-- Elimina la tabla anterior si existe
DROP TABLE IF EXISTS contrasenas;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS logs;
-- Crea la base de datos y la tabla para el gestor de contraseñas con cifrado
CREATE DATABASE IF NOT EXISTS gestor_contrasenas;
USE gestor_contrasenas;

CREATE TABLE contrasenas (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sitio VARCHAR(255) NOT NULL,
    usuario VARCHAR(255) NOT NULL,
    contrasena_cifrada TEXT NOT NULL,
    iv VARBINARY(16) NOT NULL
);

-- Tabla de usuarios para login y registro
CREATE TABLE IF NOT EXISTS usuarios (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL UNIQUE,
    hash_contrasena VARCHAR(255) NOT NULL,
    salt VARBINARY(32) NOT NULL
);

-- Tabla para registrar la actividad de los usuarios
CREATE TABLE IF NOT EXISTS logs (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    accion VARCHAR(255) NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- Relacionar contraseñas con usuario
ALTER TABLE contrasenas ADD usuario_id INT;
ALTER TABLE contrasenas ADD CONSTRAINT fk_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id);
