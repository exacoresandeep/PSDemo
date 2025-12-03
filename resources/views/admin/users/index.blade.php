@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align d-flex justify-content-between">
        <h3>User Management</h3>
        <div>
             <button class="btn btn-primary" id="addUserBtn">Add User</button>
        </div>
    </div>

    <div class="listing-sec mt-3">
        <table class="table table-bordered table-striped w-100 table-responsive" id="usersTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Products</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<!-- Modal (replace your existing modal HTML with this) -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header ">
        <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="addUserForm">
          @csrf
          <input type="hidden" id="user_id" name="user_id" value="">
          <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                      <label>Name</label>
                      <input type="text" name="name" id="name" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                      <label>Email</label>
                      <input type="email" name="email" id="email" class="form-control" required>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                      <label>Username</label>
                      <input type="text" name="username" id="username" class="form-control" required>
                      <small id="usernameCheck" class="text-danger"></small>
                  </div>
                </div>
                <div class="col-md-6"  id="passwordField">
                  <div class="form-group">
                      <label>Password</label>
                      <input type="password" name="password" id="password" class="form-control">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                      <label>Role</label>
                      <select name="role_id" id="role_id" class="form-control" required>
                          <option value="">Select Role</option>
                          @foreach($roles as $role)
                              <option value="{{ $role->id }}">{{ $role->name }}</option>
                          @endforeach
                      </select>
                  </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Products</label>
                        <select name="product_ids[]" id="product_ids" class="form-control" multiple>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
              {{-- </div> --}}
          </div>
          <div class="text-right">
              <button type="submit" class="btn btn-primary" id="saveUserBtn">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection

@section('scripts')

<script>
$(document).ready(function() {
    // make table global so other functions can access it
    window.table = $('#usersTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: "{{ route('admin.users.list') }}",
        columns: [
            {
                data: null, // no direct data from backend
                name: 'sl_no',
                render: function (data, type, row, meta) {
                    return meta.row + 1; // ✅ Row index starts from 0, so +1
                }
            },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'username', name: 'username' },
            { data: 'role_name', name: 'role_name' },
            { data: 'products', name: 'products' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ]
    });

    // Open modal for Add
    $('#addUserBtn').click(function() {
        resetUserModal();
        $('#passwordField').show();
        $('#addUserModalLabel').text('Add New User');
        $('#saveUserBtn').text('Save');
        $('#product_ids').val(null).trigger('change');
        $('#addUserModal').modal('show');
    });

    // Username uniqueness check (on keyup or blur)
    $('#username').on('keyup blur', function() {
        var username = $(this).val();
        var userId = $('#user_id').val(); // to allow same username for current user
        if (!username) {
            $('#usernameCheck').text('');
            return;
        }
        $.get("{{ route('admin.users.check-username') }}", { username: username, user_id: userId }, function(data) {
            // If you don't send user_id in controller, it will mark existing username as taken — adjust controller if you want to ignore current user's username
            if (!data.available) {
                $('#usernameCheck').text('Username already exists.');
            } else {
                $('#usernameCheck').text('');
            }
        });
    });

    // Add / Update form submit
    $('#addUserForm').submit(function(e) {
        e.preventDefault();

        if ($('#usernameCheck').text() !== '') {
            Swal.fire({
                icon: 'warning',
                title: 'Username Already Exists',
                text: 'Please choose a unique username before saving.',
                confirmButtonColor: '#3085d6',
            });
            return;
        }

        var userId = $('#user_id').val();
        var url, method;

        if (userId) {
            url = "{{ url('admin/users/update') }}/" + userId; // your route: PUT /admin/users/update/{id}
            method = 'POST';
        } else {
            url = "{{ route('admin.users.store') }}";
            method = 'POST';
        }

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            beforeSend: function() {
                Swal.showLoading();
            },
            success: function(response) {
                Swal.close();
                $('#addUserModal').modal('hide');
                resetUserModal();
                window.table.ajax.reload();

                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: (response.message) ? response.message : 'User saved successfully!',
                    showConfirmButton: false,
                    timer: 1800
                });
            },
            error: function(xhr) {
                Swal.close();
                let msg = 'Error saving user. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg,
                });
            }
        });
    });

    // reset modal when closed
    $('#addUserModal').on('hidden.bs.modal', function() {
        resetUserModal();
    });

}); // end document ready

// helper to reset modal inputs
function resetUserModal() {
    $('#addUserForm')[0].reset();
    $('#user_id').val('');
    $('#usernameCheck').text('');
    $('#addUserModalLabel').text('Add New User');
    $('#saveUserBtn').text('Save');
}

// ------------------- EDIT -------------------
function editUser(userId) {
    $.ajax({
        url: "{{ url('admin/users/edit') }}/" + userId,
        type: "GET",
        beforeSend: function() { Swal.showLoading(); },
        success: function(response) {
            Swal.close();
            if (response.success) {
                const user = response.data;

                // Show modal
                $('#passwordField').hide();
                $('#addUserModal').modal('show');
                $('#addUserModalLabel').text('Edit User');
                // Fill in form values
                $('input[name="name"]').val(user.name);
                $('input[name="email"]').val(user.email);
                $('input[name="username"]').val(user.username);
                $('select[name="role_id"]').val(user.role_id);
                $('input[name="password"]').val(''); // Optional reset

                 $('#product_ids').val(user.product_ids).trigger('change');
                // Add hidden field to identify update mode
                if (!$('#user_id').length) {
                    $('#addUserForm').append('<input type="hidden" name="user_id" id="user_id">');
                }
                $('#user_id').val(user.id);

                // Change submit button text
                $('#addUserForm button[type="submit"]').text('Update');
            } else {
                Swal.fire('Error', response.message || 'User not found.', 'error');
            }
        },
        error: function() {
            Swal.close();
            Swal.fire('Error', 'Unable to fetch user details.', 'error');
        }
    });
}

// ------------------- DELETE -------------------
function deleteUser(userId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action will permanently delete the user.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {

            $.ajax({
                url: "{{ url('admin/users/delete') }}/" + userId,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                beforeSend: function() { Swal.showLoading(); },
                success: function(response) {
                    Swal.close();
                    window.table.ajax.reload();

                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: response.message || 'User has been deleted successfully.',
                        showConfirmButton: false,
                        timer: 1800
                    });
                },
                error: function(xhr) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Something went wrong while deleting the user.',
                    });
                }
            });
        }
    });
}
</script>
<script>
  $('#product_ids').select2({
      placeholder: "Select Products",
      allowClear: true,
      width: '100%',
      dropdownParent: $('#addUserModal') 
  });
</script>
@endsection
