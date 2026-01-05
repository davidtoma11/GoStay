// Manager Dashboard Logic: Handles status updates, date editing, modals and calendars
document.addEventListener('DOMContentLoaded', function () {
    let currentResId = null;
    let currentActionType = '';
    let currentPendingStatus = null;

    // Attach function to window to make it accessible from HTML onclick attributes
    window.confirmStatus = function(resId, newStatus) {
        currentResId = resId;
        currentActionType = 'status';
        currentPendingStatus = newStatus;
        const modal = document.getElementById('managerActionModal');
        const title = document.getElementById('modalTitle');
        const desc = document.getElementById('modalDescription');
        const icon = document.getElementById('modalIcon');
        const inputSection = document.getElementById('inputSection');
        const submitBtn = document.getElementById('modalSubmitBtn');
        if(!modal) return;
        inputSection.style.display = 'none';
        modal.style.display = 'flex';
        title.innerText = "Confirm Action";
        desc.innerText = `Are you sure you want to mark this reservation as ${newStatus}?`;
        submitBtn.innerText = "YES, PROCEED";
        if (newStatus === 'cancelled') {
            icon.innerHTML = '<i class="fa-solid fa-ban" style="color: #e74c3c;"></i>';
        } else if (newStatus === 'confirmed' || newStatus === 'completed') {
            icon.innerHTML = '<i class="fa-solid fa-circle-check" style="color: #27ae60;"></i>';
        }
    };

    window.toggleEdit = function(id) {
        const row = document.getElementById(`edit-row-${id}`);
        if (row) {
            row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
        }
    };

    window.saveDates = function(resId) {
        const checkIn = document.getElementById(`in-${resId}`).value;
        const checkOut = document.getElementById(`out-${resId}`).value;
        if (!checkIn || !checkOut) {
            alert("Please select both dates.");
            return;
        }
        const data = new URLSearchParams();
        data.append('id', resId);
        data.append('check_in', checkIn);
        data.append('check_out', checkOut);
        data.append('action', 'edit');
        fetch('../utils/update_reservation.php', {
            method: 'POST',
            body: data
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                location.reload();
            } else {
                alert("Error: " + res.message);
            }
        });
    };

    window.openModal = function(type, id) {
        currentResId = id;
        currentActionType = type;
        const modal = document.getElementById('managerActionModal');
        const title = document.getElementById('modalTitle');
        const desc = document.getElementById('modalDescription');
        const label = document.getElementById('inputLabel');
        const unit = document.getElementById('valueUnit');
        const icon = document.getElementById('modalIcon');
        const reasonGroup = document.getElementById('reasonGroup');
        const inputSection = document.getElementById('inputSection');
        const submitBtn = document.getElementById('modalSubmitBtn');
        if (!modal) return;
        modal.style.display = 'flex';
        inputSection.style.display = 'block';
        submitBtn.innerText = "Apply Action";
        desc.innerText = ""; 
        if (type === 'discount') {
            title.innerText = 'Apply Discount';
            label.innerText = 'Discount Percentage';
            unit.innerText = '%';
            icon.innerHTML = '<i class="fa-solid fa-percent" style="color: #7b2bd4;"></i>';
            reasonGroup.style.display = 'none';
        } else {
            title.innerText = 'Apply Penalty';
            label.innerText = 'Penalty Amount';
            unit.innerText = 'RON';
            icon.innerHTML = '<i class="fa-solid fa-circle-exclamation" style="color: #f39c12;"></i>';
            reasonGroup.style.display = 'block';
        }
    };

    window.closeModal = function() {
        const modal = document.getElementById('managerActionModal');
        if (modal) {
            modal.style.display = 'none';
            document.getElementById('modalValue').value = '';
            document.getElementById('modalReason').value = '';
        }
    };

    window.toggleRoomCalendar = function(roomId) {
        const container = document.getElementById(`calendar-container-${roomId}`);
        const calendarEl = document.getElementById(`calendar-${roomId}`);
        if (container.style.display === 'none') {
            container.style.display = 'block';
            if (!calendarEl.classList.contains('flatpickr-input')) {
                flatpickr(calendarEl, {
                    inline: true,
                    mode: "range",
                    showMonths: 1,
                    dateFormat: "Y-m-d",
                    disable: BOOKED_DATES_MASTER[roomId] || [],
                    locale: { firstDayOfWeek: 1 }
                });
            }
        } else {
            container.style.display = 'none';
        }
    };

    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    if (modalSubmitBtn) {
        modalSubmitBtn.onclick = function() {
            const data = new URLSearchParams();
            data.append('id', currentResId);
            if (currentActionType === 'status') {
                data.append('status', currentPendingStatus);
                data.append('action', 'status');
            } else {
                const val = document.getElementById('modalValue').value;
                const reason = document.getElementById('modalReason').value;
                if (!val || val <= 0) {
                    alert("Please enter a valid value.");
                    return;
                }
                data.append('action', currentActionType);
                data.append('value', val);
                if (currentActionType === 'penalty') {
                    data.append('reason', reason);
                }
            }
            fetch('../utils/update_reservation.php', {
                method: 'POST',
                body: data
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    location.reload();
                } else {
                    alert("Error: " + res.message);
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                alert("Critical Error: Check database connection.");
            });
        };
    }
});