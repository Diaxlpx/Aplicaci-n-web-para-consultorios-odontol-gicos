-- ---------------------------------------------------------------------------
-- Base de datos: Consultorio Odontológico
-- Motor: MySQL / MariaDB
-- ---------------------------------------------------------------------------
-- Para importar en phpMyAdmin: seleccione su base de datos y ejecute este
-- archivo. Para instalación local puede abrir install.php en el navegador.
-- ---------------------------------------------------------------------------

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------------
-- Usuarios del sistema (odontólogos / personal)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    rol         ENUM('odontologo', 'asistente') NOT NULL DEFAULT 'odontologo',
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Pacientes
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres           VARCHAR(120) NOT NULL,
    apellidos         VARCHAR(120) NOT NULL,
    cedula            VARCHAR(30) NOT NULL UNIQUE,
    fecha_nacimiento  DATE NULL,
    sexo              ENUM('M', 'F', 'O') NULL,
    telefono          VARCHAR(30) NULL,
    email             VARCHAR(150) NULL,
    direccion         VARCHAR(255) NULL,
    alergias          TEXT NULL,
    antecedentes      TEXT NULL,
    creado_en         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pacientes_nombre (apellidos, nombres),
    INDEX idx_pacientes_cedula (cedula)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Procedimientos odontológicos ofrecidos
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS procedimientos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(150) NOT NULL UNIQUE,
    descripcion  TEXT NULL,
    precio       DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    activo       TINYINT(1) NOT NULL DEFAULT 1,
    creado_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Consultas (cada visita forma parte de la historia clínica)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS consultas (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT UNSIGNED NOT NULL,
    usuario_id      INT UNSIGNED NOT NULL,
    fecha           DATE NOT NULL,
    motivo          VARCHAR(255) NULL,
    diagnostico     TEXT NULL,
    tratamiento     TEXT NULL,
    observaciones   TEXT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_consultas_paciente (paciente_id, fecha),
    CONSTRAINT fk_consulta_paciente
        FOREIGN KEY (paciente_id) REFERENCES pacientes (id) ON DELETE CASCADE,
    CONSTRAINT fk_consulta_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Procedimientos realizados en cada consulta
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS consulta_procedimientos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    consulta_id       INT UNSIGNED NOT NULL,
    procedimiento_id  INT UNSIGNED NOT NULL,
    costo             DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    observaciones     VARCHAR(255) NULL,
    INDEX idx_cp_consulta (consulta_id),
    CONSTRAINT fk_cp_consulta
        FOREIGN KEY (consulta_id) REFERENCES consultas (id) ON DELETE CASCADE,
    CONSTRAINT fk_cp_procedimiento
        FOREIGN KEY (procedimiento_id) REFERENCES procedimientos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Datos iniciales
-- ---------------------------------------------------------------------------

-- Usuario administrador. Correo: admin@odontologia.com  Contraseña: admin123
INSERT INTO usuarios (nombre, email, password, rol)
VALUES (
    'Dr. Juan Medina',
    'admin@odontologia.com',
    '$2y$10$39jhhGOJ2kqXCrs2rAzyouEk1Dw/8gUQCjPrJhg3b65NFiD9b.AA6',
    'odontologo'
)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Catálogo inicial de procedimientos odontológicos.
INSERT INTO procedimientos (nombre, descripcion, precio) VALUES
    ('Limpieza dental', 'Profilaxis y eliminación de placa bacteriana y sarro.', 25.00),
    ('Blanqueo dental', 'Blanqueamiento de la dentadura con gel y luz LED.', 80.00),
    ('Extracción de muela', 'Extracción simple de una muela.', 40.00),
    ('Extracción de cordales', 'Cirugía para extracción de muelas del juicio.', 120.00),
    ('Tratamiento de conductos', 'Endodoncia: limpieza y sellado del conducto radicular.', 150.00),
    ('Calzas de muelas', 'Restauración de caries con amalgama o resina.', 35.00)
ON DUPLICATE KEY UPDATE
    descripcion = VALUES(descripcion),
    precio = VALUES(precio);
