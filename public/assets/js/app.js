(() => {
  const bookingForm = document.querySelector("#bookingForm");
  const bookingResult = document.querySelector("#bookingResult");
  const checkInInput = document.querySelector("#checkIn");
  const checkOutInput = document.querySelector("#checkOut");
  const roomSelect = document.querySelector("#room");
  const guestsSelect = document.querySelector("#guests");
  const submitButton = bookingForm?.querySelector('button[type="submit"]');
  if (
    !bookingForm ||
    !bookingResult ||
    !checkInInput ||
    !checkOutInput ||
    !roomSelect ||
    !guestsSelect
  ) {
    return;
  }
  const language = document.documentElement.lang === "en" ? "en" : "es";
  const copy = {
    es: {
      datesRequired: "Debes seleccionar las fechas de llegada y salida.",
      invalidDates:
        "La fecha de salida debe ser posterior a la fecha de llegada.",
      notAvailable:
        "La habitación seleccionada no está disponible para esas fechas.",
      apiError:
        "No fue posible consultar la disponibilidad. Inténtalo nuevamente.",
      checking: "Consultando...",
      calculate: "Calcular reserva",
      approximate: "Valores referenciales",
      room: "Habitación",
      nights: "Noches",
      total: "Total estadía",
      deposit: "Abono requerido (30 %)",
      continue: "Continuar reserva",
    },
    en: {
      datesRequired: "You must select the check-in and check-out dates.",
      invalidDates: "The check-out date must be after the check-in date.",
      notAvailable: "The selected room is not available for those dates.",
      apiError: "Availability could not be checked. Please try again.",
      checking: "Checking...",
      calculate: "Calculate booking",
      approximate: "Reference values",
      room: "Room",
      nights: "Nights",
      total: "Stay total",
      deposit: "Required deposit (30%)",
      continue: "Continue booking",
    },
  }[language];

  function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
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
    return new Intl.NumberFormat("es-CL", {
      style: "currency",
      currency: "CLP",
      maximumFractionDigits: 0,
    }).format(value);
  }

  function foreignCurrency(value, currencyCode) {
    return new Intl.NumberFormat(language === "en" ? "en-US" : "es-CL", {
      style: "currency",
      currency: currencyCode,
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value);
  }

  function escapeHtml(value) {
    const element = document.createElement("span");
    element.textContent = String(value);
    return element.innerHTML;
  }

  function showError(message) {
    bookingResult.classList.add("is-visible");
    bookingResult.innerHTML = `<div class="booking-error">${escapeHtml(message)}</div>`;
  }

  async function calculateBooking() {
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

    const guests = Number(guestsSelect.value);
    const selectedOption = roomSelect.options[roomSelect.selectedIndex];
    const parameters = new URLSearchParams({
      check_in: checkInInput.value,
      check_out: checkOutInput.value,
      guests: String(guests),
    });

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = copy.checking;
    }

    let availableRoom;
    try {
      const response = await fetch(`api/rooms.php?${parameters.toString()}`, {
        headers: { Accept: "application/json" },
      });
      const payload = await response.json();
      if (!response.ok || payload.success !== true) {
        throw new Error(payload.error || copy.apiError);
      }
      availableRoom = payload.data.find(
        (room) => Number(room.id) === Number(roomSelect.value),
      );
    } catch (error) {
      showError(error instanceof Error ? error.message : copy.apiError);
      return;
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.textContent = copy.calculate;
      }
    }

    if (!availableRoom) {
      showError(copy.notAvailable);
      return;
    }

    const dailyPrice = Number(availableRoom.daily_rate);
    const total = dailyPrice * nights;
    const deposit = Math.round(total * 0.3);
    const usdRate = Number(bookingForm.dataset.usdRate || 0);
    const eurRate = Number(bookingForm.dataset.eurRate || 0);
    const foreignValues =
      usdRate > 0 && eurRate > 0
        ? `<div class="result-item foreign-result">
            <span>${copy.approximate}</span>
            <strong>
                ${foreignCurrency(total * usdRate, "USD")} ·
                ${foreignCurrency(total * eurRate, "EUR")}
            </strong>
        </div>`
        : "";
    const reservationParameters = new URLSearchParams({
      room: roomSelect.value,
      check_in: checkInInput.value,
      check_out: checkOutInput.value,
      guests: guestsSelect.value,
    });

    bookingResult.classList.add("is-visible");
    bookingResult.innerHTML = `
        <div class="result-item"><span>${copy.room}</span><strong>${escapeHtml(selectedOption.textContent.trim())}</strong></div>
        <div class="result-item"><span>${copy.nights}</span><strong>${nights}</strong></div>
        <div class="result-item"><span>${copy.total}</span><strong>${currency(total)}</strong></div>
        <div class="result-item"><span>${copy.deposit}</span><strong>${currency(deposit)}</strong></div>
        ${foreignValues}
        <a class="primary-button continue-button" href="reservation.php?${reservationParameters.toString()}">
            ${copy.continue}
        </a>
    `;
  }

  bookingForm.addEventListener("submit", (event) => {
    event.preventDefault();
    void calculateBooking();
  });

  checkInInput.addEventListener("change", () => {
    if (!checkInInput.value) {
      return;
    }

    const nextDay = addDays(new Date(`${checkInInput.value}T12:00:00`), 1);
    checkOutInput.min = formatDate(nextDay);

    if (!checkOutInput.value || checkOutInput.value < checkOutInput.min) {
      checkOutInput.value = checkOutInput.min;
    }
  });

  document.querySelectorAll(".room-select-button").forEach((button) => {
    button.addEventListener("click", () => {
      roomSelect.value = button.dataset.roomId;
      button.closest("dialog")?.close();
      document.querySelector("#reserva").scrollIntoView({ behavior: "smooth" });
      void calculateBooking();
    });
  });

  document.querySelectorAll(".room-detail-button").forEach((button) => {
    button.addEventListener("click", () => {
      document.querySelector(`#${button.dataset.dialogId}`)?.showModal();
    });
  });

  document.querySelectorAll(".dialog-close").forEach((button) => {
    button.addEventListener("click", () => button.closest("dialog")?.close());
  });

  document.querySelectorAll(".room-dialog").forEach((dialog) => {
    dialog.addEventListener("click", (event) => {
      if (event.target === dialog) {
        dialog.close();
      }
    });
  });

  setInitialDates();
})();
