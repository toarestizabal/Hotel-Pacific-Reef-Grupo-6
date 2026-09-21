<?php

declare(strict_types=1);

namespace App\Support;

final class I18n
{
    private const LANGUAGES = ['es', 'en'];

    public static function boot(): void
    {
        $language = (string) ($_SESSION['language'] ?? $_COOKIE['hpr_language'] ?? 'es');
        $_SESSION['language'] = in_array($language, self::LANGUAGES, true) ? $language : 'es';
    }

    public static function language(): string
    {
        return (string) ($_SESSION['language'] ?? 'es');
    }

    public static function setLanguage(string $language): void
    {
        if (!in_array($language, self::LANGUAGES, true)) {
            $language = 'es';
        }
        $_SESSION['language'] = $language;
        setcookie('hpr_language', $language, [
            'expires' => time() + 31536000,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
    }

    public static function beginOutputTranslation(): void
    {
        if (self::language() === 'en') {
            ob_start([self::class, 'translateHtml']);
        }
    }

    public static function translateHtml(string $html): string
    {
        return strtr($html, self::englishDictionary());
    }

    private static function englishDictionary(): array
    {
        return [
            'Sistema de Gestión de Reserva Hotelera' => 'Hotel Reservation Management System',
            'Sistema de reservas' => 'Booking system',
            'Panel administrativo' => 'Administration panel',
            'Navegación administrativa' => 'Administrative navigation',
            'Navegación principal' => 'Main navigation',
            'Cambiar idioma' => 'Change language',
            'Crear una cuenta' => 'Create an account',
            'Crear cuenta' => 'Create account',
            'Iniciar sesión' => 'Sign in',
            'Cerrar sesión' => 'Sign out',
            'Mi cuenta' => 'My account',
            'Nombre completo' => 'Full name',
            'Correo electrónico' => 'Email address',
            'Contraseña' => 'Password',
            'Confirmar contraseña' => 'Confirm password',
            'Idioma preferido' => 'Preferred language',
            'Español' => 'Spanish',
            'Inglés' => 'English',
            'Registrarme' => 'Create account',
            'Ingresar' => 'Sign in',
            'Ya tengo una cuenta' => 'I already have an account',
            'No tengo una cuenta' => 'I do not have an account',
            'Credenciales incorrectas o cuenta inactiva.' => 'Incorrect credentials or inactive account.',
            'Cuenta creada correctamente.' => 'Account created successfully.',
            'Regístrate para acceder al sistema de reservas.' => 'Register to access the booking system.',
            'Ingresa con tu correo y contraseña.' => 'Enter your email and password.',
            'Ingresa un nombre completo válido.' => 'Enter a valid full name.',
            'Ingresa un correo electrónico válido.' => 'Enter a valid email address.',
            'Las contraseñas no coinciden.' => 'Passwords do not match.',
            'El correo ya está registrado.' => 'This email is already registered.',
            'La contraseña debe tener al menos 8 caracteres.' => 'The password must contain at least 8 characters.',
            'Reservas en línea' => 'Online booking',
            'Consulta fechas disponibles, compara nuestras habitaciones y calcula el valor de tu estadía.' => 'Check available dates, compare our rooms and calculate the value of your stay.',
            'Consultar disponibilidad' => 'Check availability',
            'Habitación Turista' => 'Tourist room',
            'Habitación Premium' => 'Premium room',
            'por noche' => 'per night',
            'El abono para confirmar la reserva corresponde al 30 % del valor total.' => 'The deposit required to confirm the booking is 30% of the total amount.',
            'Ingresa los datos principales para obtener una estimación de la estadía.' => 'Enter the main details to estimate your stay.',
            'Consulta de disponibilidad' => 'Availability search',
            'Habitaciones disponibles' => 'Available rooms',
            'Muestra inicial de las categorías Turista y Premium definidas para el sistema.' => 'Initial selection of the Tourist and Premium room categories.',
            'Selecciona las fechas para obtener un cálculo preliminar.' => 'Select the dates to obtain a preliminary estimate.',
            'Seleccionar esta habitación' => 'Select this room',
            'Capacidad para ' => 'Capacity for ',
            ' personas' => ' guests',
            'Ver detalles' => 'View details',
            'Seleccionar' => 'Select',
            'Catálogo' => 'Catalogue',
            'Reserva' => 'Booking',
            'Reservar' => 'Book',
            'Habitaciones' => 'Rooms',
            'Huéspedes' => 'Guests',
            'huéspedes' => 'guests',
            'huésped' => 'guest',
            'Llegada' => 'Check-in',
            'Salida' => 'Check-out',
            'Calcular reserva' => 'Calculate booking',
            'Habitación' => 'Room',
            'Volver al catálogo' => 'Back to catalogue',
            'Proceso de reserva' => 'Booking process',
            'Revisa y confirma tu estadía' => 'Review and confirm your stay',
            'Datos y pago' => 'Details and payment',
            'Confirmación' => 'Confirmation',
            'Selección' => 'Selection',
            'Datos de la reserva' => 'Booking details',
            'Fecha de llegada' => 'Check-in date',
            'Fecha de salida' => 'Check-out date',
            'Método de pago' => 'Payment method',
            'Tarjeta de prueba' => 'Test card',
            'Ambiente de prueba' => 'Test environment',
            'No se solicitarán ni almacenarán datos bancarios reales.' => 'No real banking details will be requested or stored.',
            'Confirmo que los datos y fechas de la reserva son correctos.' => 'I confirm that the booking details and dates are correct.',
            'Pagar abono y confirmar' => 'Pay deposit and confirm',
            'Tarifa diaria' => 'Daily rate',
            'Total estadía' => 'Stay total',
            'Abono requerido (30 %)' => 'Required deposit (30%)',
            'Por definir' => 'To be defined',
            'Noches' => 'Nights',
            'Fechas' => 'Dates',
            'Pago de prueba aprobado' => 'Test payment approved',
            'Reserva confirmada' => 'Booking confirmed',
            'La confirmación fue preparada para enviarse a ' => 'The confirmation was prepared to be sent to ',
            'Código de reserva' => 'Booking code',
            'Estadía' => 'Stay',
            'Abono pagado' => 'Deposit paid',
            'Volver al inicio' => 'Back to home',
            'No fue posible abrir la reserva' => 'The booking could not be opened',
            'Selecciona una habitación desde el catálogo.' => 'Select a room from the catalogue.',
            'Ver habitaciones' => 'View rooms',
            'Prototipo: el pago, el correo y el código QR se representan en ambiente de prueba.' => 'Prototype: payment, email, and the QR code are represented in a test environment.',
            'Administración' => 'Administration',
            'Resumen operativo' => 'Operational overview',
            'Gestión interna' => 'Internal management',
            'Información principal del Hotel Pacific Reef' => 'Hotel Pacific Reef key information',
            'Habitaciones registradas' => 'Registered rooms',
            'Inventario del prototipo' => 'Prototype inventory',
            'Disponibles' => 'Available',
            'Estado actual' => 'Current status',
            'Reservas confirmadas' => 'Confirmed bookings',
            'Próximas estadías' => 'Upcoming stays',
            'Clientes registrados' => 'Registered clients',
            'Cuentas de cliente' => 'Client accounts',
            'Accesos rápidos' => 'Quick access',
            'Gestión del hotel' => 'Hotel management',
            'Crear, editar, cambiar estados y equipamiento.' => 'Create, edit, and change room status and equipment.',
            'Actualizar el valor diario por categoría.' => 'Update the daily rate by category.',
            'Buscar, confirmar, completar o cancelar.' => 'Search, confirm, complete, or cancel.',
            'Actividad reciente' => 'Recent activity',
            'Últimas reservas' => 'Latest bookings',
            'Todavía no existen reservas registradas.' => 'There are no registered bookings yet.',
            'Resumen' => 'Overview',
            'Precios' => 'Rates',
            'Usuarios' => 'Users',
            'Sitio público' => 'Public site',
            'Gestión de usuarios' => 'User management',
            'Cuentas y permisos' => 'Accounts and permissions',
            'Administra los roles y el estado de acceso de cada cuenta.' => 'Manage the role and access status of each account.',
            'Rol' => 'Role',
            'Activo' => 'Active',
            'Inactivo' => 'Inactive',
            'Cliente' => 'Client',
            'Trabajador' => 'Worker',
            'Administrador' => 'Administrator',
            'Guardar cambios' => 'Save changes',
            'Usuario actualizado correctamente.' => 'User updated successfully.',
            'No existen usuarios registrados.' => 'There are no registered users.',
            'Gestión de reservas' => 'Booking management',
            'Consulta y actualiza las reservas registradas.' => 'Review and update registered bookings.',
            'Código, cliente, correo o habitación' => 'Code, client, email, or room',
            'No se encontraron reservas.' => 'No bookings were found.',
            'Estado de la reserva actualizado.' => 'Booking status updated.',
            'Buscar' => 'Search',
            'Código' => 'Code',
            'Pago' => 'Payment',
            'Estado' => 'Status',
            'Pendiente' => 'Pending',
            'Confirmada' => 'Confirmed',
            'Cancelada' => 'Cancelled',
            'Completada' => 'Completed',
            'Precios por categoría' => 'Rates by category',
            'Categoría' => 'Category',
            'El precio actualizado se refleja automáticamente en el sitio público.' => 'The updated rate is automatically reflected on the public site.',
            'Capacidad máxima' => 'Maximum capacity',
            'Precio actual' => 'Current rate',
            'Nuevo precio diario' => 'New daily rate',
            'Guardar precio' => 'Save rate',
            'Precio actualizado correctamente.' => 'Rate updated successfully.',
            'Gestión de habitaciones' => 'Room management',
            'Administración de habitaciones' => 'Room administration',
            'Actualizar habitación' => 'Update room',
            'Registrar habitación' => 'Register room',
            'Tipo de habitación' => 'Room type',
            'Personas por habitación' => 'Guests per room',
            'Separado por comas' => 'Comma separated',
            'Imagen URL' => 'Image URL',
            'Opcional' => 'Optional',
            'Crear habitación' => 'Create room',
            'Cancelar' => 'Cancel',
            'Acciones' => 'Actions',
            'No existen habitaciones registradas.' => 'There are no registered rooms.',
            'No fue posible completar la operación.' => 'The operation could not be completed.',
            'Habitación creada correctamente.' => 'Room created successfully.',
            'Habitación actualizada correctamente.' => 'Room updated successfully.',
            'Habitación eliminada correctamente.' => 'Room deleted successfully.',
            '¿Eliminar esta habitación?' => 'Delete this room?',
            'Nueva habitación' => 'New room',
            'Editar habitación' => 'Edit room',
            'Número' => 'Number',
            'Ubicación' => 'Location',
            'Descripción' => 'Description',
            'Equipamiento' => 'Equipment',
            'Capacidad' => 'Capacity',
            'Guardar habitación' => 'Save room',
            'Cancelar edición' => 'Cancel editing',
            'Editar' => 'Edit',
            'Eliminar' => 'Delete',
            'Disponible' => 'Available',
            'Ocupada' => 'Occupied',
            'Mantención' => 'Maintenance',
            'Inactiva' => 'Inactive',
            'Registros' => 'Records',
            'Guardar' => 'Save',
            'hasta' => 'to',
            'No tienes permisos para acceder a esta página.' => 'You do not have permission to access this page.',
            'La sesión del formulario expiró. Vuelve a intentarlo.' => 'The form session expired. Please try again.',
            'La sesión del formulario expiró.' => 'The form session expired.',
            'El rol seleccionado no es válido.' => 'The selected role is invalid.',
            'La cuenta seleccionada no existe.' => 'The selected account does not exist.',
            'No puedes quitar tu propio acceso de administrador.' => 'You cannot remove your own administrator access.',
            'Debe permanecer al menos un administrador activo.' => 'At least one active administrator must remain.',
        ];
    }
}
