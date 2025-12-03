<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Order ID:</strong> <span id="view_order_id"></span></div>
                        <div class="col-md-6"><strong>Date:</strong> <span id="view_date"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Employee Type:</strong> <span id="view_employee_type"></span></div>
                        <div class="col-md-6"><strong>Employee Name:</strong> <span id="view_employee_name"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Dealer Name:</strong> <span id="view_dealer_name"></span></div>
                        <div class="col-md-6"><strong>Dealer Code:</strong> <span id="view_dealer_code"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Phone:</strong> <span id="view_dealer_phone"></span></div>
                        <div class="col-md-6"><strong>Address:</strong> <span id="view_dealer_address"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Order Type:</strong> <span id="view_order_type"></span></div>
                        <div class="col-md-6"><strong>Payment Type:</strong> <span id="view_payment_type"></span></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6">
                          <strong>Attachment:</strong>
                          <span id="view_attachment"></span>
                        </div>
                        <div class="col-md-6"><strong>Billing Date:</strong> <span id="view_billing_date"></span></div>
                    </div>
                    <div class="row mb-2">
                        
                        <div class="col-md-6"><strong>Status:</strong> <span id="view_status"></span></div>
                        <div class="col-md-6"><strong>Scheme:</strong> <span id="view_scheme"></span></div>
                    </div>
                    <div class="row mb-2">
                    	
                        <div class="col-md-6"><strong>District:</strong> <span id="view_dealer_district"></span></div>
                        <div class="col-md-6"><strong>Vehicle Category:</strong> <span id="view_vehicle_category"></span></div>
               
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Vehicle number:</strong> <span id="view_vehicle_number"></span></div>
                        <div class="col-md-6"><strong>Driver number:</strong> <span id="view_driver_number"></span></div>
                    </div>
                    <div class="row mb-2">
                       
                        <div class="col-md-6"><strong>Driver name:</strong> <span id="view_driver_name"></span></div>
                        <div class="col-md-6"><strong>Instructions:</strong> <span id="view_instructions"></span></div>
                    </div>

                    <h5 class="mt-3">Product Details</h5>
                    <table class="table table-bordered table-responsive">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th id="view_product_price_label" style="display: none;">ADP Price</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody id="view_product_list"></tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th id="view_total_quantity"></th>
                        	    <th id="view_total_product_price"></th>  
			                    <th id="view_total_amount"></th>
                            </tr>
                        </tfoot>
                    </table>


		            <div class="row" id="credit_details_row" style="display: block;">
                        <h5 class="mt-3">Credit Details</h5>
                        <div class="col-md-12">
                            <div class="alert alert-danger">
                                <strong>Total Outstanding Amount:</strong>
                                <span id="view_total_outstanding"></span>
                            </div>
                        </div>
       
                    </div>
                
                    <div id="payment-details" style="display: none;">                       
                        <div class="row mb-2" id="view_status_row">
                            <div class="col-md-6"><strong>Status:</strong> <span id="order_status"></span></div>
                            <div class="col-md-6" id="view_reason_row" style="display: none;"><strong>Rejection Reason:</strong> <span id="view_reason"></span></div>
                        </div>
                        <div class="row mb-2" >
                        </div>
                    </div>
                    <div id="vehicle_status_section" style="display: none;" class="mt-4">
                    <h5>Yard Status Update</h5>
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label for="vehicle_status" class="form-label"><strong>Yard Status</strong></label>
                            <select class="form-select" id="vehicle_status">
                                <option value="">Select Status</option>
                                    <option value="Priority">Priority</option>
                                    <option value="Planning">Planning</option>
                                    <option value="Vehicle called">Vehicle called</option>
                                    <option value="Vehicle reached">Vehicle reached</option>
                                    <option value="Loading">Loading</option>
                                    <option value="Despatch">Despatch</option>
                                    <option value="Billed">Billed</option>
                                    <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="vehicle_remarks" class="form-label"><strong>Remarks</strong></label>
                            <textarea class="form-control" id="vehicle_remarks" rows="2" placeholder="Enter remarks..."></textarea>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 text-end">
                            <button class="btn btn-primary" id="submit_vehicle_status">Submit</button>
                        </div>
                    </div>
                </div>


                </div>
            </div>
        </div>
    </div>
</div>
<div id="imageModal" class="modal" style="display: none;">
    <div class="modal-content" style="width: 80%; max-width: 800px; height: 500px; position: relative; margin: auto; top: 50%; transform: translateY(-50%); border-radius: 10px; overflow: hidden; background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.3);">

        <!-- Close button -->
        <span class="close" style="position: absolute; top: 10px; right: 15px; font-size: 24px; font-weight: bold; color: #333; cursor: pointer; z-index: 10;">&times;</span>

        <!-- Slider Container -->
        <div class="slider-container" style="display: flex; align-items: center; justify-content: center; height: 100%; position: relative;">

            <!-- Prev Button -->
            <button class="prev" style="position: absolute; left: 10px; background: rgba(0,0,0,0.5); border: none; color: #fff; font-size: 24px; padding: 10px; border-radius: 50%; cursor: pointer; z-index: 5;">&#10094;</button>

            <!-- Image Wrapper -->
            <div class="slider" style="flex: 1; height: 100%; display: flex; justify-content: center; align-items: center; overflow: hidden;">
                <img id="modalImage" src="" style="max-height: 100%; max-width: 100%; object-fit: contain;">
            </div>

            <!-- Next Button -->
            <button class="next" style="position: absolute; right: 10px; background: rgba(0,0,0,0.5); border: none; color: #fff; font-size: 24px; padding: 10px; border-radius: 50%; cursor: pointer; z-index: 5;">&#10095;</button>
        </div>
    </div>
</div>


