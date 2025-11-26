<div class="menu-header">
    <div class="row justify-content-between">
        <div class="col-md-3 pl-5">
             <div class="d-flex align-items-center">
                <label for="productSelect" class="mb-0 me-3 fw-bold">Product:</label>
                <select id="productSelect" class="form-control" style="max-width: 250px;">
                </select>
            </div>
        </div>
        <div class="col-md-3 align-content-center">
            <div class="settings-box">
                {{-- <a href="" class="notify"><i class="fa fa-bell" aria-hidden="true"></i></a> --}}
                <div class="profi-blk">
                    <img src="{{ asset('images/profile-pic.png') }}" class="img-fluid">
                    <p>{{ Auth::check() ? Auth::user()->name : 'Guest' }}</p>
                </div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fa fa-sign-out" aria-hidden="true" style="font-size: 35px; color:#A02625;margin-left:15px;"></i>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
                
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Fetch products from DB
    $.get('{{ route("get.products") }}', function(products) {
        let options = '';
        products.forEach(p => {
            options += `<option value="${p.product_code}">${p.product_name}</option>`;
        });
        $('#productSelect').html(options);

        @if(Session::has('selected_product_code'))
            $('#productSelect').val('{{ Session::get("selected_product_code") }}');
        @else
            // Otherwise, select the first product by default
            if (products.length > 0) {
                $('#productSelect').val(products[0].product_code);
                
                // Optionally store in session via AJAX
                $.post('{{ route("set.product") }}', {
                    _token: '{{ csrf_token() }}',
                    product_code: products[0].product_code
                });
            }
        @endif
    });

    // When product changes, save in session
    $('#productSelect').on('change', function() {
        let productId = $(this).val();
        // alert(productId);
        if(productId) {
            $.post('{{ route("set.product") }}', {
                _token: '{{ csrf_token() }}',
                product_id: productId
            }, function(response) {
                if(response.success) {
                    location.reload(); 
                }
            });
        }
    });
});
</script>