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
                            <label for="product_id" class="form-label">Product Name</label>
                            <select name="product_id" id="product_id" class="form-select" required>
                                <option value="">-- Select Product Name --</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3 mt-3 align-items-end">
                        @foreach ($productTypes as $type)
                            <div class="row mb-2">
                                <input type="hidden" name="types[{{ $loop->index }}][product_type_id]" value="{{ $type->id }}">
                    
                                <div class="col-md-4">
                                    <label class="form-label">Product Type</label>
                                    <input type="text" class="form-control" value="{{ $type->type_name }}" readonly>
                                </div>
                    
                                <div class="col-md-4">
                                    <label class="form-label">Dealer Price</label>
                                    <input type="number" name="types[{{ $loop->index }}][dealer_price]" class="form-control" step="0.01"  required>
                                </div>
                    
                                <div class="col-md-4">
                                    <label class="form-label">Advance Dealer Price</label>
                                    <input type="number" name="types[{{ $loop->index }}][advance_dealer_price]" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        @endforeach
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

