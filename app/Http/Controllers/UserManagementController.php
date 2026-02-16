<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserType;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
class UserManagementController extends Controller
{
    public function index()
    {
        $roles = UserType::all();
        $products = Product::all();
        // $users = User::with('role')->get();
        return view('admin.users.index', compact('roles','products'));
    }

    public function create()
    {
        $roles = UserType::all();
        return view('users.create', compact('roles'));
    }

    public function changePassword()
    {
        return view('users.changepassword');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            
            'new_password' => [
                'required',
                'confirmed', // automatically checks confirm field
                Password::min(6)
                    ->mixedCase()      // upper + lower
                    ->numbers()        // at least one number
                    ->symbols(),       // at least one special character
            ],
        ]);

        
        // ✅ Prevent same password reuse
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'New password cannot be same as current password'
            ], 422);
        }

        // ✅ Update password
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'Password Updated Successfully'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:6',
            'role_id' => 'required|exists:user_types,id',
            'product_ids' => 'nullable|array',
        ]);

        // dd( $request);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email ?? null,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'role_id' => $request->role_id,
            'product_ids' => $request->product_ids ?? [],
        ]);

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'User created Successfully',
            'data' => [
            ]
        ]);
       
    }

    public function edit($id)
    {
        $user = User::with('role')->findOrFail($id);

        $productIds = is_array($user->product_ids)
            ? $user->product_ids
            : json_decode($user->product_ids, true);

        $productNames = [];
        if (!empty($productIds)) {
            $productNames = Product::whereIn('id', $productIds)->pluck('product_name');
        }

        return response()->json([
            'success' => true,
            'statusCode' => 200,
            'message' => 'User details fetched successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role_id' => $user->role_id,
                'role_name' => $user->role->name ?? null,
                'product_ids' => $productIds,
                'product_names' => $productNames,
            ]
        ]);
    }



    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $request->validate([
                'name' => 'required',
                'email' => 'required|email|unique:users,email,' . $id,
                'username' => 'required|unique:users,username,' . $id,
                'role_id' => 'required|exists:user_types,id',
            ]);

            $data = $request->only(['name', 'email', 'username', 'role_id']);
            if ($request->password) {
                $data['password'] = Hash::make($request->password);
            }

            if ($request->has('product_ids')) {
                $data['product_ids'] = $request->product_ids; // array to JSON automatically
            }
            $user->update($data);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User updated successfully!',
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to update user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to delete user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function resetPassword($id)
    {
        try {
            $user = User::findOrFail($id);
            $data['password'] = Hash::make("PSS".$user->username."@");
            $user->update($data);

            return response()->json([
                'success' => true,
                'statusCode' => 200,
                'message' => 'User password resetted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'statusCode' => 500,
                'message' => 'Failed to reset pasword of user: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function list()
    {
        $users = User::with('role')->get()->map(function($user) {
            $productNames = [];

            if (!empty($user->product_ids)) {
                // Ensure it's an array
                $productIds = is_array($user->product_ids) ? $user->product_ids : json_decode($user->product_ids, true);
                $productNames = Product::whereIn('id', $productIds)->pluck('product_name')->toArray();
            }

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'role_name' => $user->role->name ?? '—',
                'products' => !empty($productNames) ? implode('<br>', $productNames) : '-',
                'created_at' => $user->created_at ? $user->created_at->format('d-m-Y h:i A') : '—',
                'actions' => '
                    <button onClick="editUser('.$user->id.');" class="btn btn-sm btn-warning">Edit</button>
                    <button onClick="deleteUser('.$user->id.');" class="btn btn-sm btn-danger">Delete</button>
                    <button onClick="resetUserPassword('.$user->id.');" class="btn btn-sm btn-success">Reset Password</button>
                '
            ];
        });

        return response()->json(['data' => $users]);
    }


    public function checkUsername(Request $request)
    {
        $query = User::where('username', $request->username);
        if ($request->has('user_id') && $request->user_id) {
            $query->where('id', '!=', $request->user_id);
        }
        $exists = $query->exists();
        return response()->json(['available' => !$exists]);
    }
}
