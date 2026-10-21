-- sql/schema.sql — esquema completo de Bike Center (spec 01).
-- Se puede recargar tantas veces como haga falta: primero borra las tablas en
-- orden inverso a sus dependencias y las vuelve a crear vacías.
-- El archivo no nombra la base: se lo carga sobre bike_center (desarrollo) o
-- bike_center_test (tests), según corresponda.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS photos;
DROP TABLE IF EXISTS bikes;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- Cuentas de los vendedores/compradores (RF-1).
CREATE TABLE users (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(190) NOT NULL, -- guardado en minúsculas, único
    password_hash VARCHAR(255) NOT NULL, -- hash de PHP, nunca la contraseña real
    phone         VARCHAR(20)  NOT NULL, -- formato +54 11 seguido de los 10 dígitos (RF-1)
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Publicaciones de motos (RF-3). Una fila = una moto (fuera de alcance: varias unidades).
CREATE TABLE bikes (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(80)  NOT NULL,
    brand       VARCHAR(60)  NOT NULL,
    `condition` ENUM('new','used') NOT NULL,           -- nueva o usada
    price       DECIMAL(12,2) NOT NULL,                -- > 0, hasta 2 decimales
    currency    ENUM('ARS','USD') NOT NULL,            -- pesos o dólares, sin conversión (RNF-6)
    description TEXT NOT NULL,
    status      ENUM('available','sold') NOT NULL DEFAULT 'available', -- RF-8
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,          -- orden del feed (RF-4)
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bikes_created (created_at, id), -- feed ordenado por creación, con desempate por id
    KEY idx_bikes_user (user_id),           -- "Mis publicaciones" (RF-9)
    -- Índices de los filtros de la vitrina (spec 02, RNF-9): aceleran el
    -- filtrado por condición+moneda, el rango de precio y el "solo disponibles".
    KEY idx_bikes_condition (`condition`, currency),
    KEY idx_bikes_price (currency, price),
    KEY idx_bikes_status (status),
    CONSTRAINT fk_bikes_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fotos de cada moto (RF-3, RNF-5). El archivo vive en disco y acá solo queda
-- su referencia; al borrar la moto, la cascada borra también sus fotos (RF-7).
CREATE TABLE photos (
    id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    bike_id  INT UNSIGNED NOT NULL,
    filename VARCHAR(190) NOT NULL, -- nombre generado por el servidor, nunca el original
    position TINYINT UNSIGNED NOT NULL DEFAULT 0, -- orden de muestra (1 a 4)
    PRIMARY KEY (id),
    KEY idx_photos_bike (bike_id, position),
    CONSTRAINT fk_photos_bike FOREIGN KEY (bike_id) REFERENCES bikes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
