const bookingForm = document.querySelector('#bookingForm');
const bookingResult = document.querySelector('#bookingResult');
const checkInInput = document.querySelector('#checkIn');
const checkOutInput = document.querySelector('#checkOut');
const roomSelect = document.querySelector('#room');
const guestsSelect = document.querySelector('#guests');
const language = document.documentElement.lang === 'en' ? 'en' : 'es';
const copy = {
    es: {
        datesRequired: 'Debes seleccionar las fechas de llegada y salida.',
        invalidDates: 'La fecha de salida debe ser posterior a la fecha de llegada.',
        invalidCapacity: 'La habitación seleccionada no admite la cantidad de huéspedes indicada.',
        room: 'Habitación', nights: 'Noches', total: 'Total estadía', deposit: 'Abono requerido (30 %)', continue: 'Continuar reserva',
    },
    en: {
        datesRequired: 'You must select the check-in and check-out dates.',
        invalidDates: 'The check-out date must be after the check-in date.',
        invalidCapacity: 'The selected room does not allow the requested number of guests.',
        room: 'Room', nights: 'Nights', total: 'Stay total', deposit: 'Required deposit (30%)', continue: 'Continue booking',
    },
}[language];

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
        showError(copy.datesRequired);
        return;
    }

    if (nights < 1) {
        showError(copy.invalidDates);
        return;
    }

    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const dailyPrice = Number(selectedOption.dataset.price);
    const guests = Number(guestsSelect.value);

    if (guests > Number(selectedOption.dataset.capacity)) {
        showError(copy.invalidCapacity);
        return;
    }

    const total = dailyPrice * nights;
    const deposit = Math.round(total * 0.3);

    bookingResult.classList.add('is-visible');
    bookingResult.innerHTML = `
        <div class="result-item"><span>${copy.room}</span><strong>${selectedOption.textContent.trim()}</strong></div>
        <div class="result-item"><span>${copy.nights}</span><strong>${nights}</strong></div>
        <div class="result-item"><span>${copy.total}</span><strong>${currency(total)}</strong></div>
        <div class="result-item"><span>${copy.deposit}</span><strong>${currency(deposit)}</strong></div>
        <a class="primary-button continue-button" href="reservation.php?room=${encodeURIComponent(roomSelect.value)}&check_in=${encodeURIComponent(checkInInput.value)}&check_out=${encodeURIComponent(checkOutInput.value)}&guests=${encodeURIComponent(guestsSelect.value)}">${copy.continue}</a>
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

setInitialDates();
