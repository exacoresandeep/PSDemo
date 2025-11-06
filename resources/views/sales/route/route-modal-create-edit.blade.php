<div class="modal fade" id="createEditRouteModal" tabindex="-1" aria-labelledby="createEditRouteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createEditRouteModalLabel">Create Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="routeForm">
                <div class="modal-body">
                    <input type="hidden" name="id" id="route_id">

                    <div class="mb-3">
                        <label for="district" class="form-label">District</label>
                        <select class="form-control" id="district" name="district" required>
                            <option value="" selected disabled>-- Select District --</option>
                            @foreach($districts as $district)
                                <option value="{{ $district->id }}">{{ $district->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="locations" class="form-label">Locations</label>
                        <input type="text" class="form-control" id="locations" name="locations" placeholder="Enter locations separated by commas" required>
                    </div>
                </div>
                <div class="modal-footer">
                    {{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button> --}}
                    <button type="submit" class="btn btn-primary submit-btn">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
