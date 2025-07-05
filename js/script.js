// dr_portfolio/js/script.js

// --- Admin Sidebar Toggle (for admin/appointments.php, admin/index.php etc.) ---
// This assumes jQuery is loaded (as it is in your Bootstrap setup)
$(document).ready(function() {
    // Check if the element #menu-toggle exists, meaning we are on an admin page
    if ($("#menu-toggle").length) {
        $("#menu-toggle").on("click", function(e) {
            e.preventDefault();
            $("#wrapper").toggleClass("toggled");
        });
    }
});


// --- Public Site Smooth Scrolling for Anchor Links ---
// This makes navigation to sections on the same page smoother.
$(document).ready(function() {
    // Select all links with hashes and not part of Bootstrap's data-toggle (like dropdowns)
    $('a[href*="#"]:not([data-toggle="collapse"])').on('click', function(event) {
        if (
            location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') &&
            location.hostname == this.hostname
        ) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            if (target.length) {
                event.preventDefault(); // Prevent default anchor click behavior
                $('html, body').animate({
                    scrollTop: target.offset().top - 70 // Adjust 70px for fixed header/navbar height if applicable
                }, 1000, function() {
                    // Callback after animation
                    // Add hash (#) to URL when done scrolling (optional)
                    // window.location.hash = target.selector;
                });
            }
        }
    });
});


// --- General Client-Side Form Validation (Example for a basic contact form) ---
// This is a more advanced example. You would apply it to specific forms.
// For now, HTML5 validation (e.g., 'required', type='email') handles most basics.
// If you implement this, ensure you have IDs for your form and input fields.


$(document).ready(function() {
    $('#contactForm').submit(function(e) {
        let isValid = true;
        const name = $('#name').val();
        const email = $('#email').val();
        const subject = $('#subject').val();
        const message = $('#message').val();

        // Clear previous errors
        $('.error-message').remove();
        $('.form-control').removeClass('is-invalid');

        if (name.trim() === '') {
            $('#name').addClass('is-invalid').after('<div class="invalid-feedback error-message">Name is required.</div>');
            isValid = false;
        }
        if (email.trim() === '') {
            $('#email').addClass('is-invalid').after('<div class="invalid-feedback error-message">Email is required.</div>');
            isValid = false;
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $('#email').addClass('is-invalid').after('<div class="invalid-feedback error-message">Please enter a valid email.</div>');
            isValid = false;
        }
        if (subject.trim() === '') {
            $('#subject').addClass('is-invalid').after('<div class="invalid-feedback error-message">Subject is required.</div>');
            isValid = false;
        }
        if (message.trim() === '') {
            $('#message').addClass('is-invalid').after('<div class="invalid-feedback error-message">Message is required.</div>');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault(); // Stop form submission if validation fails
        }
    });
});
