const bookingForm = document.querySelector('#bookingForm');
const bookingResult = document.querySelector('#bookingResult');
const checkInInput = document.querySelector('#checkIn');
const checkOutInput = document.querySelector('#checkOut');
const roomSelect = document.querySelector('#room');
const guestsSelect = document.querySelector('#guests');
const languageButton = document.querySelector('#languageButton');

const translations = {
    es: {
        navRooms: 'Habitaciones',
        navBooking: 'Reservar',
        heroTitle: 'Hotel Pacific Reef',
        heroText: 'Consulta fechas disponibles, compara nuestras habitaciones y calcula el valor de tu estadía.',
        heroButton: 'Consultar disponibilidad',
        bookingTitle: 'Consulta de disponibilidad',
        checkIn: 'Llegada',
        checkOut: 'Salida',
        guests: 'Huéspedes',
        room: 'Habitación',
        calculate: 'Calcular reserva',
        resultHint: 'Selecciona las fechas para obtener un cálculo preliminar.',
        roomsTitle: 'Habitaciones disponibles',
        roomsText: 'Muestra inicial de las categorías Turista y Premium definidas para el sistema.',
    },
    en: {
        navRooms: 'Rooms',
        navBooking: 'Book',
        heroTitle: 'Hotel Pacific Reef',
        heroText: 'Check available dates, compare our rooms and calculate the cost of your stay.',
        heroButton: 'Check availability',
        bookingTitle: 'Availability search',
        checkIn: 'Check-in',
        checkOut: 'Check-out',
        guests: 'Guests',
        room: 'Room',
        calculate: 'Calculate booking',
        resultHint: 'Select dates to get a preliminary estimate.',
        roomsTitle: 'Available rooms',
        roomsText: 'Initial sample of the Tourist and Premium categories defined for the system.',
    },
};

let currentLanguage = 'es';

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function addDays(date, amount) {
    const copy = new Date(date);
    copy.setDate(copy.getDate() + amount);
    return copy;
}

function setInitialDates() {
    const today = new Date();
    const arrival = addDays(today, 1);
    const departure = addDays(today, 3);

    checkInInput.min = formatDate(today);
    checkInInput.value = formatDate(arrival);
    checkOutInput.min = formatDate(addDays(arrival, 1));
    checkOutInput.value = formatDate(departure);
}

function currency(value) {
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        maximumFractionDigits: 0,
    }).format(value);
}

function showError(message) {
    bookingResult.classList.add('is-visible');
    bookingResult.innerHTML = `<div class="booking-error">${message}</div>`;
}

function calculateBooking() {
    const checkIn = new Date(`${checkInInput.value}T12:00:00`);
    const checkOut = new Date(`${checkOutInput.value}T12:00:00`);
    const millisecondsPerDay = 1000 * 60 * 60 * 24;
    const nights = Math.round((checkOut - checkIn) / millisecondsPerDay);

    if (!checkInInput.value || !checkOutInput.value || Number.isNaN(nights)) {
        showError('Debes seleccionar las fechas de llegada y salida.');
        return;
    }

    if (nights < 1) {
        showError('La fecha de salida debe ser posterior a la fecha de llegada.');
        return;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const dailyPrice = Number(selectedOption.dataset.price);
    const guests = Number(guestsSelect.value);

    if (guests > Number(selectedOption.dataset.capacity)) {
        showError('La habitación seleccionada no admite la cantidad de huéspedes indicada.');
        return;
    }

    const total = dailyPrice * nights;
    const deposit = Math.round(total * 0.3);

    bookingResult.classList.add('is-visible');
    bookingResult.innerHTML = `
        <div class="result-item"><span>Habitación</span><strong>${selectedOption.textContent.trim()}</strong></div>
        <div class="result-item"><span>Noches</span><strong>${nights}</strong></div>
        <div class="result-item"><span>Total estadía</span><strong>${currency(total)}</strong></div>
        <div class="result-item"><span>Abono requerido (30 %)</span><strong>${currency(deposit)}</strong></div>
        <a class="primary-button continue-button" href="reservation.php?room=${encodeURIComponent(roomSelect.value)}&check_in=${encodeURIComponent(checkInInput.value)}&check_out=${encodeURIComponent(checkOutInput.value)}&guests=${encodeURIComponent(guestsSelect.value)}">Continuar reserva</a>
    `;
}

bookingForm.addEventListener('submit', (event) => {
    event.preventDefault();
    calculateBooking();
});

checkInInput.addEventListener('change', () => {
    if (!checkInInput.value) {
        return;
    }

    const nextDay = addDays(new Date(`${checkInInput.value}T12:00:00`), 1);
    checkOutInput.min = formatDate(nextDay);

    if (!checkOutInput.value || checkOutInput.value < checkOutInput.min) {
        checkOutInput.value = checkOutInput.min;
    }
});

document.querySelectorAll('.room-select-button').forEach((button) => {
    button.addEventListener('click', () => {
        roomSelect.value = button.dataset.roomId;
        button.closest('dialog')?.close();
        document.querySelector('#reserva').scrollIntoView({ behavior: 'smooth' });
        calculateBooking();
    });
});

document.querySelectorAll('.room-detail-button').forEach((button) => {
    button.addEventListener('click', () => {
        document.querySelector(`#${button.dataset.dialogId}`)?.showModal();
    });
});

document.querySelectorAll('.dialog-close').forEach((button) => {
    button.addEventListener('click', () => button.closest('dialog')?.close());
});

document.querySelectorAll('.room-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});

languageButton.addEventListener('click', () => {
    currentLanguage = currentLanguage === 'es' ? 'en' : 'es';
    document.documentElement.lang = currentLanguage;

    document.querySelectorAll('[data-i18n]').forEach((element) => {
        const key = element.dataset.i18n;
        element.textContent = translations[currentLanguage][key];
    });
});

setInitialDates();
