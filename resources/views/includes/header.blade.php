<div class="menu-header">
    <div class="row justify-content-between">
        <div class="col-md-3 pl-5">
             <div class="d-flex align-items-center">
                <label for="productSelect" class="mb-0 me-3 fw-bold">Product:</label>
                <select id="productSelect" class="form-control" style="max-width: 250px;">
                </select>
            </div>
        </div>
        <div class="col-md-3 align-content-center">
            <div class="settings-box">
                <div class="dropdown">
                    <div class="profi-blk dropdown-toggle d-flex align-items-center"
                        id="profileDropdown"
                        data-bs-toggle="dropdown"
                        aria-expanded="false"
                        style="cursor: pointer;">

                        <img src="{{ asset('images/profile-pic.png') }}" 
                            class="img-fluid rounded-circle" width="40">

                        <p class="mb-0 ms-2">
                            {{ Auth::check() ? Auth::user()->name : 'Guest' }}
                        </p>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end"
                        aria-labelledby="profileDropdown">

                        <li>
                            <a class="dropdown-item" id="changePassword">
                                <i class="fa fa-key me-2"></i> Change Password
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <a class="dropdown-item text-danger"
                            href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                                <i class="fa fa-sign-out me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>

                <form id="logout-form"
                    action="{{ route('logout') }}"
                    method="POST"
                    style="display: none;">
                    @csrf
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="changePasswordForm">
                <div class="modal-body">

                    <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" 
                            class="form-control password-field" 
                            id="new_password"
                            name="new_password" required>
                        <span class="input-group-text toggle-password" style="cursor:pointer;">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                    <small id="newPasswordError" class="text-danger"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password" 
                            class="form-control password-field" 
                            id="confirm_password"
                            name="confirm_password" required>
                        <span class="input-group-text toggle-password" style="cursor:pointer;">
                            <i class="fa fa-eye"></i>
                        </span>
                    </div>
                    <small id="confirmPasswordError" class="text-danger"></small>
                </div>



                </div>

                <div class="modal-footer">
                    <button type="button" 
                            class="btn btn-secondary" 
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" 
                            class="btn btn-primary">
                        Update Password
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Fetch products from DB
    $.get('{{ route("get.products") }}', function(products) {
        let options = '';
        products.forEach(p => {
            options += `<option value="${p.product_code}">${p.product_name}</option>`;
        });
        $('#productSelect').html(options);

        @if(Session::has('selected_product_code'))
            $('#productSelect').val('{{ Session::get("selected_product_code") }}');
        @else
            // Otherwise, select the first product by default
            if (products.length > 0) {
                $('#productSelect').val(products[0].product_code);
                
                // Optionally store in session via AJAX
                $.post('{{ route("set.product") }}', {
                    _token: '{{ csrf_token() }}',
                    product_code: products[0].product_code
                });
            }
        @endif
    });

    // When product changes, save in session
    $('#productSelect').on('change', function() {
        let productId = $(this).val();
        // alert(productId);
        if(productId) {
            $.post('{{ route("set.product") }}', {
                _token: '{{ csrf_token() }}',
                product_id: productId
            }, function(response) {
                if(response.success) {
                    location.reload(); 
                }
            });
        }
    });
    $('#changePassword').on('click', function() {
        $('#changePasswordModal').modal('show');
    });

    $('#changePasswordForm').on('submit', function(e){
        e.preventDefault();

        $.ajax({
            url: "{{ url('admin/users/updatePassword') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                current_password: $('input[name="current_password"]').val(),
                new_password: $('input[name="new_password"]').val(),
                new_password_confirmation: $('input[name="confirm_password"]').val(),
            },
            success: function(response){

                Swal.fire({
                    title: 'Password Changed Successfully!',
                    text: 'You need to login again.',
                    icon: 'success',
                    confirmButtonText: 'Logout Now',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        logoutUser();
                    }
                });

            },
            error: function(xhr){

                let errorMessage = 'Failed to change the password';

                if(xhr.responseJSON && xhr.responseJSON.message){
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    title: 'Error',
                    text: errorMessage,
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });


    $(document).on('click', '.toggle-password', function () {

        let input = $(this).closest('.input-group').find('.password-field');
        let icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Password validation regex
    function validatePassword(password) {
        const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#^()_+=\-{}[\]|\\:;"'<>,.?/]).{6,}$/;
        return regex.test(password);
    }

    // 🔑 New Password Keyup
    $('#changePasswordForm #new_password').on('keyup', function () {

        let password = $(this).val();

        if (!validatePassword(password)) {
            $('#changePasswordForm #newPasswordError').html(
                "Password must be at least 6 characters and include: <br>" +
                "• 1 Uppercase <br>" +
                "• 1 Lowercase <br>" +
                "• 1 Number <br>" +
                "• 1 Special Character"
            );
        } else {
            $('#changePasswordForm #newPasswordError').html('');
        }

        // Also re-check confirm password
        $('#changePasswordForm #confirm_password').trigger('keyup');
    });


    // 🔁 Confirm Password Keyup
    $('#changePasswordForm #confirm_password').on('keyup', function () {

        let newPassword = $('#changePasswordForm #new_password').val();
        let confirmPassword = $(this).val();

        if (confirmPassword !== newPassword) {
            $('#changePasswordForm #confirmPasswordError').text("Passwords do not match");
        } else {
            $('#changePasswordForm #confirmPasswordError').text('');
        }
    });


});
function logoutUser() {
    document.getElementById('logout-form').submit();
}
</script>