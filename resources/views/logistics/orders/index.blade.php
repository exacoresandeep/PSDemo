@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Sales Order Management</h3>
    </div>

    <div class="listing-sec">
        <table class="table table-bordered table-striped w-100" id="ordersTable">
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>Sl.No</th>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Dealer Name/Code</th>
                    <th>Address</th>
                    <th>Product Name</th>
                    <th>Product Details</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


@endsection
@section('scripts')
<script>
   $(document).ready(function() {
        if ($("#ordersTable").length) {
            $("#ordersTable").DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('logistics.orders.getSalesOrders') }}",
                columns: [
                    { data: "checkbox", orderable: false, searchable: false },
                    { data: "DT_RowIndex", searchable: false, orderable: false },
                    { data: "order_id" },
                    { data: "date" },
                    { data: "dealer_name" },
                    { data: "address" },
                    { data: "product_name" },
                    { data: "product_details" },
                    { data: "status" }
                ],
                error: function(xhr, error, code) {
                    console.log("Error in DataTables:", xhr, error, code);
                }
            });
        }
        $("#selectAll").on("click", function () {
            $(".order-checkbox").prop("checked", this.checked);
        });

        $(document).on("change", ".order-checkbox", function () {
            if (!$(this).prop("checked")) {
                $("#selectAll").prop("checked", false);
            }
        });
    });
</script>
@endsection
