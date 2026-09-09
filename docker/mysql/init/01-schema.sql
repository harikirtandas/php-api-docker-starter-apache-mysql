-- Este script solo corre en el PRIMER arranque del volumen mysql-data
-- (docker-entrypoint-initdb.d se ejecuta una unica vez, cuando /var/lib/mysql
-- esta vacio). Para reaplicarlo hace falta recrear el volumen: make fresh
--
-- Es el schema del vertical slice demo (auth + notas). En un proyecto real se
-- reemplaza entero por el schema propio, o se agregan archivos .sql numerados
-- (02-..., 03-...) y se aplican con: make db-import FILE=docker/mysql/init/0N-....sql

CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(120)  NOT NULL,
    email         VARCHAR(180)  NOT NULL UNIQUE,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tokens (
    token      CHAR(64)     PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    expira_en  DATETIME     NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notas (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    titulo     VARCHAR(120) NOT NULL,
    cuerpo     TEXT         NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario demo: email demo@demo.test / password "secret".
-- El hash es password_hash('secret', PASSWORD_DEFAULT) (bcrypt, cost 12).
INSERT INTO usuarios (nombre, email, password_hash) VALUES
    ('Usuario Demo', 'demo@demo.test', '$2y$12$eNWn12PjjgzIRk7LvzdyLeOgIeb070nMD5IpAJthQQBMMU97eobNK');

INSERT INTO notas (usuario_id, titulo, cuerpo) VALUES
    (1, 'Primera nota', 'Cuerpo de ejemplo de la primera nota.'),
    (1, 'Segunda nota', 'Cuerpo de ejemplo de la segunda nota.');
