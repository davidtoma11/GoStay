document.addEventListener('DOMContentLoaded', function () {
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const reserveBtn = document.getElementById('reserveBtn');
    const summary = document.getElementById('bookingSummary');

    // Variables to store current selection
    let selectedCheckIn = null;
    let selectedCheckOut = null;
    let currentFinalPrice = 0;

    // Slider Logic
    window.moveSlide = function (n) {
        if (slides.length === 0) return;
        slides[slideIndex].classList.remove('active');
        slideIndex = (slideIndex + n + slides.length) % slides.length;
        slides[slideIndex].classList.add('active');
    };

    // Flatpickr Logic
    flatpickr("#inlineCalendar", {
        inline: true,
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        disable: ROOM_CONF.bookedDates,
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const diff = Math.ceil(Math.abs(selectedDates[1] - selectedDates[0]) / (86400000));

                const subtotal = diff * ROOM_CONF.price;
                const serviceFee = subtotal * 0.05;
                const discount = subtotal * 0.10;
                currentFinalPrice = subtotal + serviceFee - discount;

                // Update UI
                document.getElementById('nightsCalc').innerText = diff + " nights";
                document.getElementById('subtotalVal').innerText = subtotal.toLocaleString() + " RON";
                document.getElementById('serviceFeeVal').innerText = serviceFee.toLocaleString() + " RON";
                document.getElementById('discountVal').innerText = "-" + discount.toLocaleString() + " RON";
                document.getElementById('totalVal').innerText = currentFinalPrice.toLocaleString() + " RON";

                // Save formatted dates for DB
                selectedCheckIn = instance.formatDate(selectedDates[0], "Y-m-d");
                selectedCheckOut = instance.formatDate(selectedDates[1], "Y-m-d");

                summary.style.display = 'block';
                reserveBtn.disabled = false;
                reserveBtn.innerText = "RESERVE NOW";
            } else {
                summary.style.display = 'none';
                reserveBtn.disabled = true;
                reserveBtn.innerText = "Check Availability";
            }
        }
    });

    // Reservation Logic (AJAX)
    reserveBtn.addEventListener('click', function () {
        if (!selectedCheckIn || !selectedCheckOut) return;

        reserveBtn.disabled = true;
        reserveBtn.innerText = "Processing...";

        const oldMsg = document.querySelector('.res-status-msg');
        if (oldMsg) oldMsg.remove();

        const formData = new FormData();
        formData.append('room_id', ROOM_CONF.id);
        formData.append('check_in', selectedCheckIn);
        formData.append('check_out', selectedCheckOut);
        formData.append('total_price', currentFinalPrice);

        fetch('../utils/process_reservation.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Create status message
                const statusMsg = document.createElement('div');
                statusMsg.className = 'res-status-msg ' + (data.success ? 'success' : 'error');
                statusMsg.innerText = data.message;

                // Insert message above button
                reserveBtn.parentNode.insertBefore(statusMsg, reserveBtn);

                if (data.success) {
                    reserveBtn.innerText = "SUCCESSFUL";
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000); 
                } else {
                    reserveBtn.disabled = false;
                    reserveBtn.innerText = "RESERVE NOW";
                }
            })
            .catch(err => {
                console.error(err);
                const errorMsg = document.createElement('div');
                errorMsg.className = 'res-status-msg error';
                errorMsg.innerText = "A server error occurred. Please try again.";
                reserveBtn.parentNode.insertBefore(errorMsg, reserveBtn);

                reserveBtn.disabled = false;
                reserveBtn.innerText = "RESERVE NOW";
            });
    });
});