<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheme;
use Yajra\DataTables\DataTables;
use App\Models\Product;


class SchemeController extends Controller
{
    public function index()
    {
        return view('sales.scheme.index');
    }
    public function schemeList(Request $request)
    {
        $selectedCode = session('selected_product_code');
        if (empty($selectedCode)) {
            $user = Auth()->user();
            $product_ids = is_array($user->product_ids)? $user->product_ids : json_decode($user->product_ids, true);
            if(!empty($product_ids)){
                $firstProduct = Product::where('id', $product_ids[0])->first();
                if ($firstProduct) {
                    $selectedCode = $firstProduct->product_code;
                    session(['selected_product_code' => $selectedCode]);
                }
            } else {
                $firstProduct = Product::orderBy('id')->first();
                if ($firstProduct) {
                    $selectedCode = $firstProduct->product_code;
                    session(['selected_product_code' => $selectedCode]);
                }
            }        
        }

        // Build query
        $query = Scheme::with('product');

        // 3. Filter by selected product code
        if (!empty($selectedCode)) {
            $query->whereHas('product', function ($q) use ($selectedCode) {
                $q->where('product_code', $selectedCode);
            });
        }

        return DataTables::of($query)
            ->filter(function ($query) use ($request) {
                if ($search = $request->get('search')['value'] ?? false) {

                    $query->where(function ($q) use ($search) {

                        // Search own table columns
                        $q->where('scheme.scheme', 'LIKE', "%{$search}%")
                        ->orWhere('scheme.status', 'LIKE', "%{$search}%");

                        // Search in related product table
                        $q->orWhereHas('product', function ($p) use ($search) {
                            $p->where('product_name', 'LIKE', "%{$search}%")
                            ->orWhere('product_code', 'LIKE', "%{$search}%");
                        });
                    });
                }
            })
            ->addIndexColumn()
            ->addColumn('product_name', fn($scheme) => $scheme->product->product_name ?? '-')
            ->addColumn('scheme', fn($scheme) => $scheme->scheme ?? '-')
            ->addColumn('status', function ($scheme) {
                return $scheme->status == 1
                    ? '<span class="badge bg-success">Active</span>'
                    : '<span class="badge bg-secondary">Inactive</span>';
            })
            ->addColumn('action', function ($scheme) {
                return '
                    <button class="btn btn-sm btn-warning" onclick="handleAction(' . $scheme->id . ', \'edit\')" title="Edit">
                        <i class="fa fa-edit"></i>
                    </button>
                ';
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'scheme_amount' => 'required|string',
            'status' => 'required|in:0,1'
        ]);

	$existing = Scheme::where('product_id', $request->product_id)
                      ->where('scheme', $request->scheme_amount)
                      ->first(); 
	if($existing) {
            return response()->json([
                'message' => 'Error',
                'errors' => ['product_id' => ['Scheme already exists for this product.']]
            ], 422);
        }

        $scheme = Scheme::create([
            'product_id' => $request->product_id,
            'scheme' => $request->scheme_amount,
            'status' => $request->status
        ]);

        return response()->json([
            'message' => 'Scheme created successfully!',
            'scheme' => $scheme
        ], 200);
    }
    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:scheme,id',
            'product_id' => 'required|exists:products,id',
            'scheme_amount' => 'required|string',
            'status' => 'required|in:0,1'
        ]);

        $scheme = Scheme::find($request->id);

        if (!$scheme) {
            return response()->json(['error' => 'Scheme not found'], 404);
        }

        $existing = Scheme::where('product_id', $request->product_id)
        ->where('scheme', $request->scheme_amount)
        ->where('id', '!=', $request->id)
        ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Error',
                'errors' => ['product_id' => ['Another scheme already exists for this product.']]
            ], 422);
        }

        $scheme->update([
            'product_id' => $request->product_id,
            'scheme' => $request->scheme_amount,
            'status' => $request->status
        ]);

        return response()->json(['message' => 'Scheme updated successfully']);
    }
    public function getSchemeDetails($id)
    {
        $scheme = Scheme::with('product')->find($id);

        if (!$scheme) {
            return response()->json(['error' => 'Scheme not found'], 404);
        }

        return response()->json([
            'scheme' => [
                'product_name'   => optional($scheme->product)->product_name ?? '-',
                'product_id'     => $scheme->product_id ?? '',
                'scheme'         => $scheme->scheme ?? '0.00',
                'status'         => $scheme->status ?? '0',
            ]
        ]);
    }
}

?>
