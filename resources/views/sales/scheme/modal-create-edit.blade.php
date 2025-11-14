<div class="modal fade" id="createEditModal" tabindex="-1" aria-labelledby="createEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditModalLabel">Create Scheme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="schemeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="scheme_id">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="product_id">Product</label>
                              <input type="text" class="form-control" id="product_name" name="product_name" readonly>
                              <input type="hidden" class="form-control" id="product_id" name="product_id" readonly>
                        </div>
                        <div class="col-md-6">
                            <label for="scheme_amount">Scheme</label>
                            <input type="text" class="form-control" name="scheme_amount" id="scheme_amount" step="0.01" placeholder="Enter scheme">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="status">Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="saveSchemeBtn" class="btn btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>


