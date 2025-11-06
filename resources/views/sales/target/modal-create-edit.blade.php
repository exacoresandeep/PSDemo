<div class="modal fade" id="createEditModal" tabindex="-1" aria-labelledby="createEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditModalLabel">Create Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="targetForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="id" id="target_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Employee Type</label>
                            <select class="form-control" name="employee_type" id="employee_type">
                                <option value="">-Select Employee Type-</option>
                                @foreach($employeeTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->type_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Employee</label>
                            <select class="form-control" name="employee_id" id="employee_id">
                                <option value="">-Select Employee-</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Target Year</label>
                            <select class="form-control" name="year" id="year">
                                @php
                                    $currentYear = date('Y');
                                    for ($i = 0; $i < 5; $i++) {
                                        echo '<option value="' . ($currentYear + $i) . '">' . ($currentYear + $i) . '</option>';
                                    }
                                @endphp
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Target Month</label>
                            <select class="form-control" name="month" id="month">
                                <option value="">-Select Month-</option>
                                @foreach(['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'] as $month)
                                    <option value="{{ $month }}">{{ $month }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Unique Lead</label>
                            <input type="number" class="form-control" name="unique_lead" id="unique_lead">
                        </div>
                        <div class="col-md-6">
                            <label>Customer Visit</label>
                            <input type="number" class="form-control" name="customer_visit" id="customer_visit">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Aashiyana Count</label>
                            <input type="number" class="form-control" name="aashiyana" id="aashiyana">
                        </div>
                        <div class="col-md-6">
                            <label>Targets in Tons</label>
                            <input type="number" class="form-control" name="order_quantity" id="order_quantity">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" id="saveTargetBtn" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>

