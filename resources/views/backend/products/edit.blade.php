@extends('backend.master')

@section('title', __('Update Product'))

@section('content')
<div class="card">
  <div class="card-body">
    <form action="{{ route('backend.admin.products.update',$product->id) }}" method="post" class="accountForm"
      enctype="multipart/form-data">
      @csrf
      @method('PUT')
      <div class="card-body">
        <div class="row">
          <div class="mb-3 col-md-6">
            <label for="title" class="form-label">
              {{ __('Name') }}
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="{{ __('Enter title') }}" name="name"
              value="{{ old('name', $product->name) }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="sku" class="form-label">
              {{ __('Sku') }}
              <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" placeholder="{{ __('Enter sku') }}" name="sku"
              value="{{ old('sku',$product->sku)}}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="brand_id" class="form-label">
              {{ __('Brand') }}
              <span class="text-danger">*</span>
            </label>
            <select class="form-control select2" style="width: 100%;" name="brand_id" required>
              <option value="">{{ __('Select Brand') }}</option>
              @foreach ($brands as $item)
              <option value={{ $item->id }}
                {{ $product->brand_id == $item->id ? 'selected' : '' }}>
                {{ $item->name }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="mb-3 col-md-6">
            <label for="category_id" class="form-label">
              {{ __('Category') }}
              <span class="text-danger">*</span>
            </label>
            <select class="form-control select2" style="width: 100%;" name="category_id" required>
              <option value="">{{ __('Select Category') }}</option>
              @foreach ($categories as $item)
              <option value={{ $item->id }}
                {{ $product->category_id == $item->id ? 'selected' : '' }}>
                {{ $item->name }}
              </option>
              @endforeach
            </select>
          </div>
          <div class="mb-3 col-md-6">
            <label for="price" class="form-label">
              {{ __('Price') }}
              <span class="text-danger">*</span>
            </label>
            <input type="number" step="0.01" min="0" class="form-control"
              placeholder="{{ __('Enter price') }}" name="price" value="{{ old('price',$product->price) }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="unit_id" class="form-label">
              {{ __('Unit') }}
              <span class="text-danger">*</span>
            </label>
            <select class="form-control" style="width: 100%;" name="unit_id" required>
              <option value="">{{ __('Select Unit') }}</option>
              @foreach ($units as $item)
              <option value={{ $item->id }}
                {{ $product->unit_id == $item->id ? 'selected' : '' }}>
                {{ $item->title . ' (' . $item->short_name . ')' }}
              </option>
              @endforeach
            </select>
          </div>
          <!-- <div class="mb-3 col-md-6">
          <label for="quantity" class="form-label">
            {{ __('Initial Stock') }}
            <span class="text-danger">*</span>
          </label>
          <input type="number" class="form-control" placeholder="{{ __('Enter quantity') }}" name="quantity"
            value="{{ old('quantity',$product->quantity) }}" required>
        </div> -->
          <div class="mb-3 col-md-6">
            <label for="discount_type" class="form-label">
              {{ __('Discount Type') }}
            </label>
            <select class="form-control form-select" name="discount_type">
              <option value="">{{ __('Select Discount Type') }}</option>
              <option value="fixed" {{ $product->discount_type == 'fixed' ? 'selected' : '' }}>
                {{ __('Fixed') }}
              </option>
              <option value="percentage"
                {{ $product->discount_type  == 'percentage' ? 'selected' : '' }}>
                {{ __('Percentage') }}
              </option>
            </select>
          </div>
          <div class="mb-3 col-md-6">
            <label for="discount_value" class="form-label">
              {{ __('Discount Amount') }}
            </label>
            <input type="number" step="0.01" min="0" class="form-control"
              placeholder="{{ __('Enter discount') }}" name="discount" value="{{ old('discount',$product->discount) }}">
          </div>
          <div class="mb-3 col-md-6">
            <label for="purchase_price" class="form-label">
              {{ __('Purchase Price') }}
              <span class="text-danger">*</span>
            </label>
            <input type="number" step="0.01" min="0" class="form-control"
              placeholder="{{ __('Enter purchase Price') }}" name="purchase_price" value="{{ old('purchase_price',$product->purchase_price) }}" required>
          </div>
          <div class="mb-3 col-md-6">
            <label for="thumbnailInput" class="form-label">
              {{ __('Image') }}
            </label>
            <div class="image-upload-container" id="imageUploadContainer">
              <input type="file" class="form-control" name="product_image" id="thumbnailInput" accept="image/*" style="display: none;">
              <div class="thumb-preview" id="thumbPreviewContainer">
                <img src="{{ asset('storage/' . $product->image) }}" alt="Thumbnail Preview"
                  class="img-thumbnail" id="thumbnailPreview" onerror="this.onerror=null; this.src='{{ asset('assets/images/no-image.png') }}'">
                <div class="upload-text d-none">
                  <i class="fas fa-plus-circle"></i>
                  <span>{{ __('Upload Image') }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-3 col-md-12">
            <label for="description" class="form-label">
              {{ __('Description') }}
            </label>
            <textarea class="form-control" placeholder="{{ __('Enter description') }}" name="description">{{ old('description',$product->description) }}</textarea>
          </div>

          <div class="mb-3 col-md-6">
            <label for="expire_date" class="form-label">
              {{ __('Expire date') }}
            </label>

            <div class="input-group date" id="reservationdate" data-target-input="nearest">
              <input type="text" placeholder="{{ __('Enter product expire date') }}" class="form-control datetimepicker-input" data-target="#reservationdate" name="expire_date" value="{{ old('expire_date',$product->expire_date) }}" />
              <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                <div class="input-group-text"><i class="fa fa-calendar"></i></div>
              </div>
            </div>
          </div>
          <div class="mb-3 col-md-12">
            <div class="form-switch px-4">
              <input type="hidden" name="status" value="0">
              <input class="form-check-input" type="checkbox" name="status" id="active"
                value="1" @if($product->status==1) checked @endif>
              <label class="form-check-label" for="active">
                {{ __('Active') }}
              </label>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <button type="submit" class="btn bg-gradient-primary">{{ __('Update') }}</button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
@push('script')
<script src="{{ asset('js/image-field.js') }}"></script>

<script>
  $(function() {
    //Date picker
    $('#reservationdate').datetimepicker({
      format: 'YYYY-MM-DD'
    });
  })
</script>
@endpush
