# Hotel Pacific Reef

Prototipo del sistema de reservas del Hotel Pacific Reef, desarrollado por el Grupo 6.

## Funciones disponibles

- Catálogo de habitaciones Turista y Premium.
- Formulario con fechas, cantidad de huéspedes y tipo de habitación.
- Cálculo del valor de la estadía y del abono del 30 %.
- Interfaz en español e inglés.
- Modelo relacional compatible con MariaDB.
- Administración de habitaciones con operaciones de creación, consulta, actualización y eliminación.

El catálogo público y el módulo administrativo utilizan MariaDB. Los cambios realizados en el CRUD se reflejan automáticamente en las habitaciones disponibles del sitio.

## Puesta en marcha

1. Importar `database/schema.sql` en MariaDB.
2. Copiar `config/database.example.php` como `config/database.php` y completar los datos de la conexión local.
3. Copiar `config/php.example.ini` como `config/php.ini`, ajustar `extension_dir` y habilitar `pdo_mysql`.
4. Iniciar el servidor desde la raíz del proyecto:

```powershell
php -c config\php.ini -S localhost:8000 -t public
```

Sitio público: `http://localhost:8000`

CRUD de habitaciones: `http://localhost:8000/admin/rooms.php`

La comprobación automática de las cuatro operaciones se ejecuta con:

```powershell
php -c config\php.ini tests\crud_smoke.php
```

Los archivos `config/database.php`, `config/php.ini` y `.local/` son configuraciones locales y no se incluyen en Git.
