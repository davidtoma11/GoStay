document.addEventListener('DOMContentLoaded', function () {
    let slideIndex = 0;
    const slides = document.querySelectorAll('.slide');
    const reserveBtn = document.getElementById('reserveBtn');
    const summary = document.getElementById('bookingSummary');
    const overlay = document.getElementById('reservationOverlay');

    // State variables for the current booking selection
    let selectedCheckIn = null;
    let selectedCheckOut = null;
    let currentFinalPrice = 0;

    // Image Slider Logic
    window.moveSlide = function (n) {
        if (slides.length === 0) return;
        slides[slideIndex].classList.remove('active');
        slideIndex = (slideIndex + n + slides.length) % slides.length;
        slides[slideIndex].classList.add('active');
    };

    // Flatpickr Calendar Setup
    flatpickr("#inlineCalendar", {
        inline: true,
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        disable: ROOM_CONF.bookedDates,
        onChange: function (selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                // Calculate days between dates
                const diff = Math.ceil(Math.abs(selectedDates[1] - selectedDates[0]) / (86400000));

                // Financial calculations
                const subtotal = diff * ROOM_CONF.price;
                const serviceFee = subtotal * 0.05; // 5% fee
                const discount = subtotal * 0.10;   // 10% discount
                currentFinalPrice = subtotal + serviceFee - discount;

                // Update Summary UI
                document.getElementById('nightsCalc').innerText = diff + " nights";
                document.getElementById('subtotalVal').innerText = subtotal.toLocaleString() + " RON";
                document.getElementById('serviceFeeVal').innerText = serviceFee.toLocaleString() + " RON";
                document.getElementById('discountVal').innerText = "-" + discount.toLocaleString() + " RON";
                document.getElementById('totalVal').innerText = currentFinalPrice.toLocaleString() + " RON";

                // Format dates for database storage (ISO format)
                selectedCheckIn = instance.formatDate(selectedDates[0], "Y-m-d");
                selectedCheckOut = instance.formatDate(selectedDates[1], "Y-m-d");

                summary.style.display = 'block';
                reserveBtn.disabled = false;
                reserveBtn.innerText = "RESERVE NOW";
            } else {
                // Hide summary if range is incomplete
                summary.style.display = 'none';
                reserveBtn.disabled = true;
                reserveBtn.innerText = "Check Availability";
            }
        }
    });

    // Reservation AJAX Logic
    reserveBtn.addEventListener('click', function () {
        if (!selectedCheckIn || !selectedCheckOut) return;

        // Visual feedback for processing
        reserveBtn.disabled = true;
        reserveBtn.innerText = "Processing...";

        // Remove previous status messages if they exist
        const oldMsg = document.querySelector('.res-status-msg');
        if (oldMsg) oldMsg.remove();

        // Prepare data for transmission
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
            if (data.success) {
                // Trigger the Premium Animation Overlay
                if (overlay) {
                    overlay.style.display = 'flex';
                }

                // Refresh page after animation finishes (4 seconds)
                setTimeout(() => {
                    window.location.reload();
                }, 4000); 
            } else {
                // Display error message on screen (in-page)
                const statusMsg = document.createElement('div');
                statusMsg.className = 'res-status-msg error';
                statusMsg.innerText = data.message;
                reserveBtn.parentNode.insertBefore(statusMsg, reserveBtn);

                reserveBtn.disabled = false;
                reserveBtn.innerText = "RESERVE NOW";
            }
        })
        .catch(err => {
            console.error('Reservation Error:', err);
            const errorMsg = document.createElement('div');
            errorMsg.className = 'res-status-msg error';
            errorMsg.innerText = "A server error occurred. Please try again.";
            reserveBtn.parentNode.insertBefore(errorMsg, reserveBtn);

            reserveBtn.disabled = false;
            reserveBtn.innerText = "RESERVE NOW";
        });
    });
});