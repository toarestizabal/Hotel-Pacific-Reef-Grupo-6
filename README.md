## Funciones 

- Catalogo de habitaciones Turista y Premium.
- Formulario con fechas, cantidad de huéspedes y tipo de habitación.
- Cálculo del valor de la estadía y del abono del 30 %.
- Interfaz en español e ingles.
- Modelo relacional compatible con MariaDB.
- Administración de habitaciones con operaciones de creacion, consulta, actualización y eliminacion.

El catálogo público y el modulo administrativo utilizan MariaDB. Los cambios realizados en el CRUD se reflejan automáticamente en las habitaciones disponibles del sitio.

## Puesta en marcha

1. Para una base nueva, importar `database/schema.sql` en MariaDB. Este archivo ya incluye la capacidad individual de cada habitacion.
2. Copiar `config/database.example.php` como `config/database.php` y completar los datos de la conexion local.
3. Copiar `config/php.example.ini` como `config/php.ini`, ajustar `extension_dir` y habilitar `pdo_mysql`.
4. Iniciar el servidor desde la raíz del proyecto:

```powershell
php -c config\php.ini -S localhost:8000 -t public
```

Sitio público: `http://localhost:8000`

CRUD de habitaciones: `http://localhost:8000/admin/rooms.php`

La aplicación incluye registro, inicio de sesion, preferencia persistente de idioma y control de acceso por roles. Una instalación nueva creada con `database/schema.sql` incluye esta cuenta administrativa de demostracion:

- Correo: `admin@hotelpacificreef.cl`
- Contraseña: `Pacific.Reef2026`

Para crearla o restablecerla en una base existente:

```powershell
php -c config\php.ini scripts\create_admin.php "Administrador Hotel" "admin@hotelpacificreef.cl" "Pacific.Reef2026"
```

Panel administrativo: `http://localhost:8000/admin/index.php`

### Actualizar una base de datos anterior

Si ya existen las tablas y se quiere conservar sus datos, **no volver a importar `schema.sql`**: crear un respaldo y ejecutar `database/migrations/20260917_room_capacity.sql` en HeidiSQL sobre la base `hotel_pacific_reef`. La migracion añade `rooms.capacity`, asigna las capacidades de las habitaciones existentes y agrega su validacion; no elimina tablas ni reservas. Puede ejecutarse otra vez sin duplicar la columna o la restricción. En una instalación nueva basta con `schema.sql`; no hace falta ejecutar la migración.

Comprobación automática de las cuatro operaciones se ejecuta con:

```powershell
php -c config\php.ini tests\crud_smoke.php
php -c config\php.ini tests\reservation_capacity_smoke.php
php -c config\php.ini tests\auth_smoke.php
```
