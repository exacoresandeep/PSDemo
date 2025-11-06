<div class="modal fade" id="createEditActivityTypeModal" tabindex="-1" aria-labelledby="createEditActivityTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditActivityTypeModalLabel">Create Activity Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="activityTypeForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="activity_type_id" id="activity_type_id">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Activity Name</label>
                            <input type="text" class="form-control" name="activity_name" id="activity_name" required>
                        </div>
                        <div class="col-md-6">
                            <label>Status</label>
                            <select class="form-control" name="status" id="status">
                                <option value="1">Active</option>
                                <option value="2">Inactive</option>
                            </select>
                        </div>
                        
                    </div>
                    
                    <div id="customFieldsContainer"></div>
                    <div class="row mb-3">
                        <p style="font-size: 12px;">If you need to include additional details or inputs, click on the "Add Field" button to create custom fields. You can label each new field as required to capture the necessary information for your activity.</p>
                        <p style="color:#A02625; cursor:pointer;" id="addFieldBtn">
                            <i class="fa fa-plus"></i> Add Field</p>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary submit-btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
