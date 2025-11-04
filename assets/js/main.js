// Main JavaScript File

// Confirm Delete Function
function confirmDelete(message = 'هل أنت متأكد من الحذف؟') {
    return confirm(message);
}

// Form Validation
(function () {
    'use strict';
    window.addEventListener('load', function () {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Auto-generate Asset Code
function generateAssetCode() {
    // This can be enhanced with AJAX call to get next code
    const timestamp = Date.now().toString().slice(-6);
    return 'AST-' + timestamp;
}

// Date Picker Initialization (if using flatpickr or similar)
document.addEventListener('DOMContentLoaded', function () {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// AJAX Helper Function
function ajaxRequest(url, method = 'GET', data = null, callback = null) {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                if (callback) {
                    callback(JSON.parse(xhr.responseText));
                }
            } else {
                console.error('Request failed:', xhr.status);
            }
        }
    };

    if (data) {
        xhr.send(new URLSearchParams(data).toString());
    } else {
        xhr.send();
    }
}

