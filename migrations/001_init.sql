CREATE TABLE IF NOT EXISTS rooms (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_rooms_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bookings (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    VARCHAR(64)     NOT NULL,
    room_id    INT UNSIGNED    NOT NULL,
    title      VARCHAR(255)    NOT NULL DEFAULT '',
    starts_at  DATETIME        NOT NULL,
    ends_at    DATETIME        NOT NULL,
    created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bookings_user (user_id),
    KEY idx_bookings_room (room_id, starts_at),
    CONSTRAINT fk_bookings_room
        FOREIGN KEY (room_id) REFERENCES rooms(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO rooms (name) VALUES ('Alfa'), ('Betta'), ('Gamma');
