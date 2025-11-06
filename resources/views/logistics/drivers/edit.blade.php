@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('logistics.drivers.index') }}" class="btn me-2">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4 class="mb-0">Edit Driver</h4>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Whoops! Something went wrong.</strong>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-lg p-4">
        <form action="{{ route('logistics.drivers.update', $driver->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" 
                           class="form-control @error('name') is-invalid @enderror" 
                           value="{{ old('name', $driver->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" 
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $driver->phone) }}" required pattern="\d*" maxlength="10">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Alternative Phone</label>
                    <input type="text" name="alternative_phone" 
                           class="form-control @error('alternative_phone') is-invalid @enderror"
                           value="{{ old('alternative_phone', $driver->alternative_phone) }}" pattern="\d*" maxlength="10">
                    @error('alternative_phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <textarea name="address" 
                              class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $driver->address) }}</textarea>
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Driver Photo</label>
                    <input type="file" name="photo" accept=".jpg, .jpeg, .png" 
                        class="form-control @error('photo') is-invalid @enderror">
                    @error('photo')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($driver->photo)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $driver->photo) }}" alt="Driver Photo" style="max-width: 150px;">
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="form-label">District</label>
                    <select name="district_id" 
                            class="form-select @error('district_id') is-invalid @enderror" required>
                        <option value="">Select District</option>
                        @foreach($districts as $district)
                            <option value="{{ $district->id }}" 
                                {{ old('district_id', $driver->district_id) == $district->id ? 'selected' : '' }}>
                                {{ $district->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('district_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Pincode</label>
                    <input type="text" name="pincode" 
                           class="form-control @error('pincode') is-invalid @enderror" 
                           value="{{ old('pincode', $driver->pincode) }}" required>
                    @error('pincode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Aadhar Card No</label>
                    <input type="text" name="adharcard_no" 
                           class="form-control @error('adharcard_no') is-invalid @enderror" 
                           value="{{ old('adharcard_no', $driver->adharcard_no) }}" required>
                    @error('adharcard_no')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Aadhar Attachment</label>
                    <input type="file" name="adhar_attachment" 
                           accept=".pdf, .jpg, .jpeg, .png" 
                           class="form-control @error('adhar_attachment') is-invalid @enderror">
                    @error('adhar_attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($driver->adhar_attachment)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $driver->adhar_attachment) }}" alt="Aadhar" style="max-width: 150px;">
                            
                        </div>
                    @endif
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">License No</label>
                    <input type="text" name="liscence_no" 
                           class="form-control @error('liscence_no') is-invalid @enderror" 
                           value="{{ old('liscence_no', $driver->liscence_no) }}" required>
                    @error('liscence_no')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Liscence Attachment</label>
                    <input type="file" name="liscence_attachment" 
                           accept=".pdf, .jpg, .jpeg, .png" 
                           class="form-control @error('liscence_attachment') is-invalid @enderror">
                    @error('liscence_attachment')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @if ($driver->liscence_attachment)
                        <div class="mt-2">
                            <img src="{{ asset('storage/' . $driver->liscence_attachment) }}" alt="Liscence" style="max-width: 150px;">
                            
                        </div>
                    @endif
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">License Expiry Date</label>
                    <input type="date" name="liscence_exp_date" 
                           class="form-control @error('liscence_exp_date') is-invalid @enderror" 
                           value="{{ old('liscence_exp_date', $driver->liscence_exp_date) }}" required>
                    @error('liscence_exp_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Blood Group</label>
                    <input type="text" name="blood_group" 
                           class="form-control @error('blood_group') is-invalid @enderror" 
                           value="{{ old('blood_group', $driver->blood_group) }}" required>
                    @error('blood_group')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
    
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" 
                            class="form-select @error('status') is-invalid @enderror">
                        <option value="Active" {{ old('status', $driver->status == 1 ? 'Active' : 'Inactive') == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ old('status', $driver->status == 1 ? 'Active' : 'Inactive') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>
    
            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary px-4">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection
