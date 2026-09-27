(() => {
    const summary = document.querySelector('.reservation-summary');
    const serviceInputs = [...document.querySelectorAll('.service-option input[type="checkbox"]')];
    if (!summary || serviceInputs.length === 0) return;

    const money = new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        maximumFractionDigits: 0,
    });

    const update = () => {
        const checkIn = document.querySelector('input[name="check_in"]')?.value;
        const checkOut = document.querySelector('input[name="check_out"]')?.value;
        const start = checkIn ? new Date(`${checkIn}T00:00:00`) : null;
        const end = checkOut ? new Date(`${checkOut}T00:00:00`) : null;
        const nights = start && end ? Math.max(0, Math.round((end - start) / 86400000)) : 0;
        const roomTotal = Number(summary.dataset.dailyRate || 0) * nights;
        const guestsInput = document.querySelector('input[name="guests"]');
        const guests = Math.max(1, Number(guestsInput?.value || summary.dataset.guests || 1));
        let serviceTotal = 0;

        for (const input of serviceInputs) {
            if (!input.checked) continue;
            const quantity = nights > 0 ? 1 : 0;
            serviceTotal += Number(input.dataset.price || 0) * quantity;
        }

        const grandTotal = roomTotal + serviceTotal;
        document.querySelector('#nightsCount').textContent = String(nights);
        document.querySelector('#roomTotal').textContent = money.format(roomTotal);
        document.querySelector('#serviceTotal').textContent = money.format(serviceTotal);
        document.querySelector('#grandTotal').textContent = money.format(grandTotal);
        document.querySelector('#depositTotal').textContent = money.format(grandTotal * 0.3);
    };

    serviceInputs.forEach((input) => input.addEventListener('change', update));
    document.querySelector('input[name="guests"]')?.addEventListener('input', update);
    document.querySelector('input[name="check_in"]')?.addEventListener('change', update);
    document.querySelector('input[name="check_out"]')?.addEventListener('change', update);
    update();
})();
