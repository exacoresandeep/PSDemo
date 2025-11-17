<div class="modal fade" id="createEditPriceModal" tabindex="-1" aria-labelledby="priceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <form id="priceForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="priceModalLabel">Create Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="start_date" required>
                        </div>

                        <div class="col-md-6">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="end_date" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Product</label>
                            <input type="text" id="selected_product_name" class="form-control" readonly>
                            <input type="hidden" id="selected_product_id" name="product_id">
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3 mt-3 align-items-end product-div">
                        
                    </div>
                    
                </div>

                <div class="modal-footer">
                    <input type="hidden" id="price_id" name="price_id">
                    <button type="submit" class="btn btn-primary" id="savePriceBtn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

