-- Añade un identificador seguro para verificar reservas mediante QR dinámico.
-- Conserva todas las reservas existentes y puede ejecutarse más de una vez.

USE hotel_pacific_reef;

ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS verification_token CHAR(64) NULL AFTER reservation_code;

UPDATE reservations
SET verification_token = SHA2(CONCAT(UUID(), '-', id, '-', RAND()), 256)
WHERE verification_token IS NULL OR verification_token = '';

ALTER TABLE reservations
    MODIFY COLUMN verification_token CHAR(64) NOT NULL;

SET @hpr_verification_index_exists = (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'reservations'
      AND INDEX_NAME = 'uq_reservations_verification_token'
);

SET @hpr_verification_index_sql = IF(
    @hpr_verification_index_exists = 0,
    'CREATE UNIQUE INDEX uq_reservations_verification_token ON reservations (verification_token)',
    'DO 1'
);

PREPARE hpr_verification_index_stmt FROM @hpr_verification_index_sql;
EXECUTE hpr_verification_index_stmt;
DEALLOCATE PREPARE hpr_verification_index_stmt;
