<div class="modal fade" id="dealerCreateEditModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dealerCreateEditModalLabel">Create Dealer Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="dealerTargetForm">
                @csrf
                <input type="hidden" name="dealer_target_id" id="dealer_target_id">

                <div class="modal-body">

                    <div class="mb-3">
                        <label>Dealer</label>
                        <select class="form-control" name="dealer_id" id="dealer_id">
                            <option value="">-Select Dealer-</option>
                            @foreach($dealers as $dealer)
                                <option value="{{ $dealer->id }}">{{ $dealer->dealer_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Year</label>
                        <select class="form-control" name="year" id="dealer_year">
                            @php
                                $currentYear = date('Y');
                                for ($i = 0; $i < 5; $i++) {
                                    echo '<option value="' . ($currentYear + $i) . '">' . ($currentYear + $i) . '</option>';
                                }
                            @endphp
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Month</label>
                        <select class="form-control" name="month" id="dealer_month">
                            <option value="">-Select Month-</option>
                            @foreach(['January','February','March','April','May','June','July','August','September','October','November','December'] as $month)
                                <option value="{{ $month }}">{{ $month }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Targets in Tons</label>
                        <input type="number" class="form-control" name="order_quantity" id="dealer_order_quantity">
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>

            </form>
        </div>
    </div>
</div>