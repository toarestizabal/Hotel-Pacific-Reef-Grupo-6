## Funciones 

- Catálogo de habitaciones Turista y Premium.
- Formulario con fechas, cantidad de huéspedes y tipo de habitación.
- Cálculo del valor de la estadía y del abono del 30 %.
- Interfaz en español e inglés.
- Modelo relacional compatible con MariaDB.
- Administración de habitaciones con operaciones de creación, consulta, actualización y eliminación.
- Reserva integrada con traslado opcional, pago de prueba, ticket verificable y código QR. El desayuno y el estacionamiento están incluidos en la estadía.
- Correo de confirmación generado en una bandeja local de prueba.
- Panel operativo para trabajadores con calendario, huéspedes y servicios contratados.
- Reportes administrativos filtrados por fecha y estado, con descarga en CSV.
- API REST pública para consultar habitaciones disponibles por fechas y cantidad de huéspedes.
- Conversión referencial de precios desde CLP a USD y EUR mediante ExchangeRate-API.

El catálogo público y el módulo administrativo utilizan MariaDB. Los cambios realizados en el CRUD se reflejan automáticamente en las habitaciones disponibles del sitio.

## Organización del código

El proyecto separa las responsabilidades principales para facilitar su mantenimiento y sus pruebas:

- `public/`: páginas y componentes de presentación accesibles desde el navegador.
- `src/Services/`: reglas de negocio y coordinación de casos de uso, como reservas, habitaciones, confirmaciones y exportación de reportes.
- `src/Repositories/`: consultas y persistencia de información en MariaDB.
- `src/Auth/`: autenticación, sesión, roles y protección CSRF.
- `src/Support/`: funciones reutilizables de idioma y presentación.
- `src/Data/`: datos de respaldo utilizados por el prototipo cuando corresponde.
- `tests/`: pruebas funcionales independientes de cada módulo.
- `scripts/`: tareas de instalación y actualización ejecutadas desde la consola.

Las clases utilizan carga automática y las páginas delegan la validación y la lógica de negocio a servicios, evitando consultas SQL y reglas duplicadas dentro de las vistas.

## API REST y servicio externo

La disponibilidad puede ser consultada por otras aplicaciones mediante una petición `GET`:

```text
/api/rooms.php?check_in=2026-10-10&check_out=2026-10-12&guests=2
```

La respuesta utiliza JSON e incluye las habitaciones disponibles y los datos de la consulta. Los parámetros son obligatorios, las fechas usan el formato `YYYY-MM-DD` y `check_out` debe ser posterior a `check_in`.

Los valores aproximados en USD y EUR se obtienen desde el endpoint público de ExchangeRate-API. La aplicación conserva las tasas durante 24 horas, muestra la atribución requerida por el proveedor y continúa operativa si el servicio externo no está disponible.

## Puesta en marcha

1. Para una base nueva, importar `database/schema.sql` en MariaDB. Este archivo ya incluye la capacidad individual de cada habitación.
2. Copiar `config/database.example.php` como `config/database.php` y completar los datos de la conexión local.
3. Copiar `config/php.example.ini` como `config/php.ini`, ajustar `extension_dir` y habilitar `pdo_mysql`, `openssl`, `mbstring`, `gd` y `zip`.
4. Instalar las dependencias PHP bloqueadas en `composer.lock`:

```powershell
composer install
```

5. Iniciar el servidor desde la raíz del proyecto:

```powershell
php -c config\php.ini -S localhost:8000 -t public
```

Sitio público: `http://localhost:8000`

CRUD de habitaciones: `http://localhost:8000/admin/rooms.php`

La aplicación incluye registro, inicio de sesión, preferencia persistente de idioma y control de acceso por roles. Una instalación nueva creada con `database/schema.sql` incluye esta cuenta administrativa de demostración:

- Correo: `admin@hotelpacificreef.cl`
- Contraseña: `Pacific.Reef2026`

Para crearla o restablecerla en una base existente:

```powershell
php -c config\php.ini scripts\create_admin.php "Administrador Hotel" "admin@hotelpacificreef.cl" "Pacific.Reef2026"
```

Panel administrativo: `http://localhost:8000/admin/index.php`

La instalación nueva también incluye una cuenta de trabajador para revisar el módulo operativo:

- Correo: `trabajador@hotelpacificreef.cl`
- Contraseña: `Pacific.Reef2026`

Para crearla o restablecerla en una base existente:

```powershell
php -c config\php.ini scripts\create_worker.php "Trabajador Hotel" "trabajador@hotelpacificreef.cl" "Pacific.Reef2026"
```

Panel del trabajador: `http://localhost:8000/worker/index.php`

Reportes administrativos: `http://localhost:8000/admin/reports.php`

El código QR se genera como PNG dentro de la aplicación, sin servicios externos. Cada reserva recibe un token aleatorio de 256 bits y el QR abre una página dinámica que consulta en MariaDB el estado vigente del ticket. Se genera solamente después de confirmar el pago de prueba.



```powershell
$env:APP_URL = "http://IP_DEL_EQUIPO:8000"
php -c config\php.ini -S 0.0.0.0:8000 -t public
```

En un despliegue real, `APP_URL` debe ser la dirección HTTPS pública del sistema. El correo se guarda como HTML en `.local/mail` porque el prototipo no utiliza credenciales SMTP reales; se puede abrir desde la confirmación de la reserva.

### Actualizar una base de datos anterior

Si ya existen las tablas y quiere conservar sus datos, **no volver a importar `schema.sql`**: crear un respaldo y ejecutar, en orden, las migraciones pendientes de `database/migrations` sobre `hotel_pacific_reef`. `20260917_room_capacity.sql` incorpora la capacidad de las habitaciones y `20260927_reservation_verification_token.sql` añade los tokens seguros del QR dinámico sin borrar reservas. En una instalación nueva basta con `schema.sql`.

Comprobación automática de las cuatro operaciones se ejecuta con:

```powershell
php -c config\php.ini tests\crud_smoke.php
php -c config\php.ini tests\reservation_capacity_smoke.php
php -c config\php.ini tests\auth_smoke.php
php -c config\php.ini tests\reservation_services_smoke.php
php -c config\php.ini tests\confirmation_smoke.php
php -c config\php.ini tests\operation_smoke.php
php -c config\php.ini tests\report_smoke.php
php -c config\php.ini tests\api_integration_smoke.php
```
