@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align d-flex justify-content-between">
        <h3>Dealer Management</h3>
        {{-- <button class="btn btn-primary" id="importButton">Import</button>
        <input type="file" id="fileInput" style="display: none;" accept=".csv, .xlsx"/> --}}
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped table-responsive w-100" id="employeesTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Dealer Code</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Location</th>
                    <th>Route</th>
                    <th>GST No</th>
                    <th>PAN No</th>
                    <th>Address</th>
                    <th>Reset Password</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // ------------------- Reset User Password -------------------
    
$(document).ready(function() {
     window.table = $('#employeesTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: "{{ route('admin.users.dealers-list') }}",
        columns: [
            { 
                data: null,
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            { data: 'dealer_code', name: 'dealer_code' },
            { data: 'dealer_name', name: 'dealer_name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'location', name: 'location' },
            { data: 'route', name: 'route' },
            { data: 'gst_no', name: 'gst_no' },
            { data: 'pan_no', name: 'pan_no' },
            { data: 'address', name: 'address' },
            { data: 'reset_password', name: 'reset_password' },
        ]
    });

});
function resetDealerPassword(Id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "Do you want to reset the password of this dealer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Reset Password!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {

                $.ajax({
                    url: "{{ url('admin/users/dealers/resetDealerPassword') }}/" + Id,
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
                            title: 'Reset Password!',
                            text: response.message || 'Dealer password resetted successfully.',
                            showConfirmButton: false,
                            timer: 1800
                        });
                    },
                    error: function(xhr) {
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Something went wrong while resettig the dealer password.',
                        });
                    }
                });
            }
        });
    }
</script>
@endsection
