@extends('layouts.app')

@section('content')
<div class="activity-sec">
    <div class="inner-header button-align">
        <h3>Order Management</h3>
    </div>

    <div class="listing-sec">

	<div class="d-flex align-items-end mb-3">
	    <div class="me-2">
        	<label for="statusFilter" class="form-label">Filter by Status:</label>
        	<select id="statusFilter" class="form-select" style="width: 200px;">
           	 <option value="">All</option>
            	 <option value="Pending">Pending</option>
            	 <option value="Rejected">Rejected</option>
            	 <option value="Approved">Approved</option>
        	</select>
    		</div>
    <div>
        <button id="exportFiltered" class="btn btn-primary">Export</button>
    </div>
</div>
        <table class="table table-bordered table-striped w-100" id="ordersTable">
            <thead>
                <tr>
                    <th>Sl.No</th>
                    <th>Date</th>
                    <th>Order ID</th>
                    <th>Dealer Name</th>
                    <th>Dealer Code</th>
                    <th>Emp Type</th>
                    <th>Emp Name - Code</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>


@include('operations.order-request.view')

@endsection
@section('scripts')
<script>
    $(document).ready(function() {
       var table =  $('#ordersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
            url: "{{ route('operations.orders.list') }}",
            data: function (d) {
                d.status = $('#statusFilter').val(); // Pass status to server
            }
        	},
	    columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', searchable: false, orderable: false },
                { data: 'date', name: 'date' },
                { data: 'order_id', name: 'order_id' },
                { data: 'dealer_name', name: 'dealer_name' },
                { data: 'dealer_code', name: 'dealer_code' },
                { data: 'employee_type', name: 'employee_type' },
                { data: 'employee_name_code', name: 'employee_name_code' },
                { data: 'amount', name: 'amount' },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            error: function(xhr, error, code) {
                console.log("Error in DataTables:", xhr, error, code);
            }
        });
	$('#statusFilter').on('change', function () {
        table.ajax.reload();
   	 });
    });
         
  $(document).on('click', '.view-order', function () {
            let orderId = $(this).data('id');

            $.ajax({
                url: "/operations/orders/view/" + orderId,
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        let order = response.order;
                        // console.log(response.order);
                        // Set Order Details
                        $('#view_order_id').text(order.order_id);
                        $('#view_date').text(order.date);
                        $('#view_employee_type').text(order.employee_type);
                        $('#view_employee_name').text(order.employee_name_code);
                        $('#view_dealer_name').text(order.dealer_name);
                        $('#view_dealer_code').text(order.dealer_code);
                        $('#view_dealer_phone').text(order.dealer_phone);
                        $('#view_dealer_address').text(order.dealer_address);
                        $('#view_order_type').text(order.order_type);
                        $('#view_payment_type').text(order.payment_type);
                        $('#view_billing_date').text(order.billing_date);
                        $('#view_status').html(order.status_badge);
		                $('#view_scheme').html(order.scheme);
                        $('#order_status').html(order.order_status);
                        $('#view_total_outstanding').text(order.total_outstanding);
                        // $('#view_total_outstanding').text(order.total_outstanding);
                        // $('#view_due_date_days').text(order.due_date_days >= 0 
                        //     ? order.due_date_days + ' days remaining' 
                        //     : Math.abs(order.due_date_days) + ' days overdue');
                        if (response.order.total_outstanding !== "0.00") {
                            $('#view_total_outstanding').text(response.order.total_outstanding);

                            // Handle due_in_days
                            if (response.order.due_in_days !== null) {
                                let label = parseInt(response.order.due_in_days) < 0 ? 'Overdue by' : 'Due in';
                                let days = Math.abs(response.order.due_in_days);
                                $('#view_due_in_days').text(`${label} ${days} days`);
                            } else {
                                $('#view_due_in_days').text('-');
                            }

                            $('#credit_details_row').show();
                        } else {
                            $('#credit_details_row').hide();
                        }
                        if (order.order_type === "Retail" && order.payment_type === "Advance") {
                            $('#view_product_price_label').show();
                            $('#view_product_price_label').text('ADP Price');
                        } else if(order.order_type === "Retail" && order.payment_type === "Credit"){
                            $('#view_product_price_label').show();
                            $('#view_product_price_label').text('DP Price');
                        }else {
                            $('#view_product_price_label').hide();
                        }
                        let productHtml = '';
                        let totalQuantity = 0;
                        let totalAmount = 0;
                        let totalProductPrice = 0;

                        order.order_items.forEach(item => {
                            totalQuantity += item.quantity;
                            totalAmount += item.rate;
                            let priceColumn = '';
                            let productPrice = 0;

                            if (order.order_type === "Retail" && order.payment_type === "Advance") {
                                if (item.adp_price) {
                                    productPrice = parseFloat(item.adp_price);
                                    priceColumn = `<td>${item.adp_price}</td>`;
                                } else {
                                    priceColumn = `<td></td>`;
                                }
                            } else if (order.order_type === "Retail" && order.payment_type === "Credit") {
                                if (item.dp_price) {
                                    productPrice = parseFloat(item.dp_price);
                                    priceColumn = `<td>${item.dp_price}</td>`;
                                } else {
                                    priceColumn = `<td></td>`;
                                }
                            } 
                            totalProductPrice += productPrice;
                            productHtml += `
                                <tr>
                                    <td>${item.product_name}</td>
                                    <td>${item.type_name}</td>
                                    <td>${item.quantity}</td>
                                    ${priceColumn}
                                    <td>${(item.rate).toFixed(2)}</td>
                                </tr>
                            `;
                        });

                        $('#view_product_list').html(productHtml);
                        $('#view_total_quantity').text((Math.round(totalQuantity * 1000000) / 1000000).toString());
                        $('#view_total_amount').text(totalAmount.toFixed(2));
                        if (order.order_type === "Retail" && (order.payment_type === "Advance" || order.payment_type === "Credit")) {
                           // $('#view_total_product_price').text(totalProductPrice.toFixed(2));
                           // $('#view_total_product_price').show();
                            $('#view_product_price_label').show();
                        } else {
                            $('#view_total_product_price').hide();
                            $('#view_product_price_label').hide();
                        }
                        // Handle Order Approval Status
                        if (order.order_approved == '1') {
                            $('#payment-form').hide();
                            $('#approval-buttons').hide();
                            $('#payment-details').show();
                            $('#view_payment_term_row').show();
                            $('#view_status_row').show();
                            $('#view_reason_row').hide();
                            $('#view_payment_term').text(order.payment_term);
                            $('#view_remarks').text(order.remarks);

                        } else if (order.order_approved == '2') {
                            $('#payment-form').hide();
                            $('#approval-buttons').hide();
                            $('#payment-details').show();
                            $('#view_payment_term_row').hide();
                            $('#view_status_row').show();
                            $('#view_reason_row').show();

                            $('#view_reason').text(order.reason_for_rejection);

                        } else {
                            $('#payment-form').show();
                            $('#approval-buttons').show();
                            $('#payment-details').hide();

                        }

                        // Store orderId for later use
                        $('#approve_order, #reject_order').data('id', orderId);

                        // Show Modal
                        $('#viewModal').modal({
                            backdrop: 'static', // Prevent clicking outside to close
                            keyboard: false     // Prevent "Esc" key from closing
                        }).modal('show');
                    }
                }
            });
    });

    
    $('#exportFiltered').on('click', function () {
        const status = $('#statusFilter').val();
        let url = "{{ route('operations.orders.export') }}";
        if (status) {
            url += '?status=' + encodeURIComponent(status);
        }
        window.open(url, '_blank');
    });
</script>
@endsection

