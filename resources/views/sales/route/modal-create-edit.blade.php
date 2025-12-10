<div class="modal fade" id="createEditAssignRouteModal" tabindex="-1" aria-labelledby="createEditAssignedRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" style="--bs-modal-width: 90%;font-size: 10px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Assigned Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="assignedRouteForm">
                @csrf
                <input type="hidden" name="id" id="route_id">

                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="employee_type" class="form-label">Employee Type</label>
                            <select id="employee_type" name="employee_type_id" class="form-control select2" required>
                                <option value="">Select Employee Type</option>
                                <option value="1">Sales Executive (SE)</option>
                                <option value="2">Area Sales Officer (ASO)</option>
                                
                                <?php
                                    if ($productId=="1" || $productId=="4") {
                                        echo '
                                              <option value="3">District Sales Manager (DSM)</option>
                                              <option value="4">Regional Sales Manager (RSM)</option>';
                                    } else {
                                        echo '<option value="7">Area Sales Manager (ASM)</option>';
                                    }
                                ?>
                                <option value="5">Sales Manager (SM)</option>
                            </select>
                        </div>
                           
                        <div class="col-md-8">
                            <label for="employee" class="form-label">Employee</label>
                            <select id="employee" name="employee_id" class="form-control select2" required>
                                <option value="">Select Employee</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-md-12">
                            <label class="form-label">Assign Routes, Locations & Dealers</label>
                        </div>
                    </div>

                    @foreach(range(1, 6) as $i)
                        <div class="row mt-2 route-row">
                            <div class="col-md-1">
                                <select name="routes[{{ $i }}][route_name]" class="form-control route-select" required>
                                    <option value="">Select Route</option>
                                    <option value="R1">R1</option>
                                    <option value="R2">R2</option>
                                    <option value="R3">R3</option>
                                    <option value="R4">R4</option>
                                    <option value="R5">R5</option>
                                    <option value="R6">R6</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <select class="form-control select2-multi location-select" name="routes[{{ $i }}][locations][]" multiple="multiple" required></select>
                            </div>

                            <div class="col-md-8">
                                <select class="form-control select2-multi dealer-select" name="routes[{{ $i }}][dealers][]" multiple="multiple"></select>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
