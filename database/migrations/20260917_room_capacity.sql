-- Actualiza una instalación anterior sin recrear tablas ni borrar datos.
-- Para una instalación nueva, usar solo database/schema.sql.
-- Ejecutar sobre MariaDB con respaldo previo de la base de datos.

USE hotel_pacific_reef;

ALTER TABLE rooms
    ADD COLUMN IF NOT EXISTS capacity TINYINT UNSIGNED NULL AFTER room_type_id;

-- Las habitaciones conocidas conservan su capacidad real; las demás toman
-- inicialmente el máximo permitido por su tipo. No cambia capacidades ya fijadas.
UPDATE rooms AS r
JOIN room_types AS rt ON rt.id = r.room_type_id
SET r.capacity = LEAST(
    rt.max_guests,
    CASE r.room_number
        WHEN 'T-101' THEN 2
        WHEN 'T-204' THEN 3
        WHEN 'P-301' THEN 2
        WHEN 'P-305' THEN 4
        ELSE rt.max_guests
    END
)
WHERE r.capacity IS NULL;

ALTER TABLE rooms
    MODIFY COLUMN capacity TINYINT UNSIGNED NOT NULL;

-- MariaDB no ofrece IF NOT EXISTS para CHECK; consultar el catálogo evita
-- duplicar la restricción cuando se vuelve a ejecutar esta migración.
SET @hpr_capacity_check_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'rooms'
      AND CONSTRAINT_NAME = 'chk_room_capacity'
      AND CONSTRAINT_TYPE = 'CHECK'
);

SET @hpr_capacity_check_sql = IF(
    @hpr_capacity_check_exists = 0,
    'ALTER TABLE rooms ADD CONSTRAINT chk_room_capacity CHECK (capacity > 0)',
    'DO 1'
);

PREPARE hpr_capacity_check_stmt FROM @hpr_capacity_check_sql;
EXECUTE hpr_capacity_check_stmt;
DEALLOCATE PREPARE hpr_capacity_check_stmt;
