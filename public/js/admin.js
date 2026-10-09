$(function () {
    const $loginForm = $('#adminLoginForm');

    if ($loginForm.length) {
        const $submit = $('#loginSubmit');
        const $alert = $('#loginAlert');

        const clearErrors = function () {
            $('#emailError, #passwordError').text('');
            $('#email, #password').removeClass('is-invalid');
            $alert.addClass('d-none').text('');
        };

        $loginForm.on('submit', function (event) {
            event.preventDefault();
            clearErrors();

            $submit.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: $loginForm.attr('action'),
                method: 'POST',
                data: $loginForm.serialize(),
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function (response) {
                window.location.assign(response.redirect);
            }).fail(function (xhr) {
                const response = xhr.responseJSON || {};

                if (response.errors) {
                    Object.entries(response.errors).forEach(function ([field, messages]) {
                        const $input = $('#' + field);
                        const $error = $('#' + field + 'Error');

                        if ($input.length && $error.length) {
                            $input.addClass('is-invalid');
                            $error.text(messages[0]);
                        }
                    });
                }

                if (response.message) {
                    $alert.text(response.message).removeClass('d-none');
                } else {
                    $alert.text('We could not complete your sign-in. Please try again.').removeClass('d-none');
                }
            }).always(function () {
                $submit.prop('disabled', false).removeClass('is-loading');
            });
        });

        $('[data-password-toggle]').on('click', function () {
            const $button = $(this);
            const $password = $('#password');
            const showPassword = $password.attr('type') === 'password';

            $password.attr('type', showPassword ? 'text' : 'password');
            $button.attr('aria-label', showPassword ? 'Hide password' : 'Show password');
            $button.attr('aria-pressed', showPassword ? 'true' : 'false');
        });

        $('#email, #password').on('input', function () {
            $(this).removeClass('is-invalid');
            $('#' + this.id + 'Error').text('');
            $alert.addClass('d-none').text('');
        });
    }

    const $directorySearch = $('#directorySearch');

    if ($directorySearch.length) {
        $directorySearch.on('input', function () {
            const query = $(this).val().trim().toLocaleLowerCase();
            const $rows = $('[data-account-row]');
            let visibleCount = 0;

            $rows.each(function () {
                const matches = $(this).text().toLocaleLowerCase().includes(query);

                $(this).toggleClass('d-none', !matches);
                visibleCount += matches ? 1 : 0;
            });

            $('#searchEmpty').toggleClass('d-none', visibleCount > 0 || query.length === 0);
        });
    }
});
