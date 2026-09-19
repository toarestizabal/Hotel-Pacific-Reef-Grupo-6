CREATE DATABASE IF NOT EXISTS hotel_pacific_reef
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hotel_pacific_reef;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client', 'worker', 'administrator') NOT NULL DEFAULT 'client',
    preferred_language ENUM('es', 'en') NOT NULL DEFAULT 'es',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE room_types (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(500) NOT NULL,
    base_price DECIMAL(12, 2) NOT NULL,
    max_guests TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_room_type_price CHECK (base_price > 0),
    CONSTRAINT chk_room_type_capacity CHECK (max_guests > 0)
);

CREATE TABLE rooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_type_id BIGINT UNSIGNED NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    location VARCHAR(120) NOT NULL,
    description VARCHAR(500) NOT NULL,
    equipment JSON NOT NULL,
    image_url VARCHAR(500) NULL,
    status ENUM('available', 'occupied', 'maintenance', 'inactive') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_rooms_room_type
        FOREIGN KEY (room_type_id) REFERENCES room_types(id),
    CONSTRAINT chk_room_capacity CHECK (capacity > 0)
);

CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_code VARCHAR(30) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    guests TINYINT UNSIGNED NOT NULL,
    daily_rate DECIMAL(12, 2) NOT NULL,
    total_amount DECIMAL(12, 2) NOT NULL,
    deposit_amount DECIMAL(12, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_reservations_room
        FOREIGN KEY (room_id) REFERENCES rooms(id),
    CONSTRAINT chk_reservation_dates CHECK (check_out > check_in),
    CONSTRAINT chk_reservation_guests CHECK (guests > 0),
    CONSTRAINT chk_reservation_total CHECK (total_amount > 0),
    CONSTRAINT chk_reservation_deposit CHECK (deposit_amount >= 0 AND deposit_amount <= total_amount)
);

CREATE INDEX idx_reservations_dates
    ON reservations (check_in, check_out, status);

CREATE TABLE payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    amount DECIMAL(12, 2) NOT NULL,
    method ENUM('credit_card', 'debit_card', 'transfer', 'test') NOT NULL DEFAULT 'test',
    status ENUM('pending', 'approved', 'rejected', 'refunded') NOT NULL DEFAULT 'pending',
    external_reference VARCHAR(120) NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations(id),
    CONSTRAINT chk_payment_amount CHECK (amount > 0)
);

CREATE TABLE services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(500) NOT NULL,
    price DECIMAL(12, 2) NOT NULL DEFAULT 0,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT chk_service_price CHECK (price >= 0)
);

CREATE TABLE reservation_services (
    reservation_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(12, 2) NOT NULL,
    PRIMARY KEY (reservation_id, service_id),
    CONSTRAINT fk_reservation_services_reservation
        FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_reservation_services_service
        FOREIGN KEY (service_id) REFERENCES services(id),
    CONSTRAINT chk_reservation_service_quantity CHECK (quantity > 0),
    CONSTRAINT chk_reservation_service_price CHECK (unit_price >= 0)
);

INSERT INTO room_types (name, description, base_price, max_guests) VALUES
    ('Turista', 'Habitación cómoda con equipamiento esencial.', 68000, 3),
    ('Premium', 'Habitación superior con vista privilegiada y equipamiento ampliado.', 125000, 4);

INSERT INTO rooms (room_type_id, capacity, room_number, location, description, equipment) VALUES
    (1, 2, 'T-101', 'Primer piso, vista jardín', 'Habitación Turista para dos personas.', JSON_ARRAY('Wi-Fi', 'TV', 'Baño privado')),
    (1, 3, 'T-204', 'Segundo piso, vista interior', 'Habitación Turista para tres personas.', JSON_ARRAY('Wi-Fi', 'TV', 'Minibar')),
    (2, 2, 'P-301', 'Tercer piso, vista al mar', 'Habitación Premium para dos personas.', JSON_ARRAY('Wi-Fi', 'Smart TV', 'Jacuzzi')),
    (2, 4, 'P-305', 'Tercer piso, terraza privada', 'Habitación Premium para cuatro personas.', JSON_ARRAY('Wi-Fi', 'Smart TV', 'Terraza'));

INSERT INTO services (name, description, price) VALUES
    ('Desayuno', 'Desayuno por huésped y por día.', 12000),
    ('Traslado', 'Traslado coordinado entre el hotel y el aeropuerto.', 35000),
    ('Estacionamiento', 'Estacionamiento privado durante la estadía.', 10000);
