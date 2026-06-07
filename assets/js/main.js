document.addEventListener('DOMContentLoaded', function () {

    // Mobile nav toggle
    var toggle = document.getElementById('navToggle');
    var menu = document.getElementById('navMenu');
    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            menu.classList.toggle('active');
        });
    }

    // Dynamic price calculation on package detail page
    var numPeopleInput = document.getElementById('num_people');
    var totalPriceDisplay = document.getElementById('totalPrice');
    if (numPeopleInput && totalPriceDisplay) {
        var pricePerPerson = parseFloat(numPeopleInput.getAttribute('data-price'));
        numPeopleInput.addEventListener('input', function () {
            var count = parseInt(this.value) || 1;
            var total = pricePerPerson * count;
            totalPriceDisplay.textContent = formatMMK(total);
        });
    }

    // Dynamic price calculation on booking page
    var bookingNumPeople = document.querySelector('form[method="POST"] #num_people');
    var bookingTotal = document.getElementById('bookingTotal');
    var displayPeople = document.getElementById('displayPeople');
    if (bookingNumPeople && bookingTotal) {
        var pprice = parseFloat(bookingNumPeople.getAttribute('data-price'));
        bookingNumPeople.addEventListener('input', function () {
            var count = parseInt(this.value) || 1;
            var total = pprice * count;
            bookingTotal.textContent = formatMMK(total);
            if (displayPeople) displayPeople.textContent = count;
        });
    }

    // Auto-dismiss flash alerts after 5 seconds
    var alerts = document.querySelectorAll('.alert:not(.alert-error)');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });
});

function formatMMK(amount) {
    return amount.toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }) + ' MMK';
}
