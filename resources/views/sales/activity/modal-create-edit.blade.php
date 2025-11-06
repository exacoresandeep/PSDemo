<div class="modal fade" id="createEditActivityModal" tabindex="-1" aria-labelledby="createEditActivityModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditActivityModalLabel">Create Activity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityForm">
                @csrf
                <input type="hidden" id="activity_id">

                <div class="modal-body">
                    <div class="row mb-3">
                        <!-- District -->
                        <div class="col-md-6">
                            <label for="district">District</label>
                            <select class="form-control" id="district" name="district" required>
                                <option value="">-Select District-</option>
                                @foreach($districts as $district)
                                    <option value="{{ $district->id }}">{{ $district->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Employee Type -->
                        <div class="col-md-6">
                            <label for="employee_type_id">Employee Type</label>
                            <select class="form-control" id="employee_type_id" name="employee_type_id" required>
                                <option value="">-Select Employee Type-</option>
                                <option value="1">Sales Executive</option>
                                <option value="2">Area Sales Officer</option>
                                <option value="3">District Sales Manager</option>
                                <option value="4">Regional Sales Manager</option>
                                <option value="5">Sales Manager</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <!-- Employee -->
                        <div class="col-md-6">
                            <label for="employee_id">Assigned Employee</label>
                            <select class="form-control" id="employee_id" name="employee_id" required>
                                <option value="">-Select Employee-</option>
                            </select>
                        </div>

                        <!-- Dealer -->
                        <div class="col-md-6">
                            <label for="dealer_id">Dealer</label>
                            <select class="form-control" id="dealer_id" name="dealer_id" required>
                                <option value="">-Select Dealer-</option>
                            </select>
                        </div>
                    </div>

                    <!-- Other fields -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="activity_type_id">Activity Type</label>
                            <select class="form-control" id="activity_type_id" name="activity_type_id" required>
                                <option value="">-Select Activity Type-</option>
                                @foreach($activityTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="assigned_date">Assigned Date</label>
                            <input type="date" class="form-control" id="assigned_date" name="assigned_date" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="due_date">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>

                        <div class="col-md-6">
                            <label for="instruction">Instruction</label>
                            <textarea class="form-control" id="instruction" name="instruction" rows="2" required></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary submit-btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>


