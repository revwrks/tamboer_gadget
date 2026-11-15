<?php

namespace App\Http\Controllers;

use App\Models\MUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class CUsers extends Controller
{
    public function index()
    {
        try {
            $users = MUsers::all();
            return response()->json($users, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch users'], 500);
        }
    }

    public function store(Request $request)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak menambah user.'], 403);
        }

        try {
            $validated = $request->validate([
                'username' => 'required|unique:user|max:255',
                'password' => 'required|min:6',
                'level' => 'required|in:owner,sales',
                'nama' => 'required',
            ]);

            $user = MUsers::create($validated);

            return response()->json(['success' => 'User created successfully', 'user' => $user], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create user'], 500);
        }
    }

    public function show($id_user)
    {
        try {
            $user = MUsers::findOrFail($id_user);
            return response()->json($user, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch user'], 500);
        }
    }

    public function update(Request $request, $id_user)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak mengubah user.'], 403);
        }

        try {
            $validated = $request->validate([
                'username' => 'required|max:255|unique:user,username,' . $id_user . ',id_user',
                'password' => 'nullable|min:6',
                'level' => 'required|in:owner,sales',
                'nama' => 'required',
            ]);

            $user = MUsers::findOrFail($id_user);

            // Hanya update password jika diisi
            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $user->update($validated);

            return response()->json(['success' => 'User updated successfully', 'user' => $user], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }

    public function destroy(Request $request, $id_user)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak menghapus user.'], 403);
        }

        // Cegah owner menghapus akunnya sendiri
        if ($request->user() && $request->user()->id_user == $id_user) {
            return response()->json(['error' => 'Tidak dapat menghapus akun sendiri yang sedang aktif.'], 422);
        }

        try {
            $user = MUsers::findOrFail($id_user);

            // Cegah penghapusan jika user memiliki riwayat transaksi atau stok
            $hasSales = \App\Models\MPenjualan::where('id_sales', $id_user)->exists();
            $hasStocks = \App\Models\MStock::where('id_sales', $id_user)->exists();
            if ($hasSales || $hasStocks) {
                return response()->json([
                    'error' => 'User ini memiliki riwayat transaksi atau pencatatan stok. User tidak dapat dihapus demi menjaga integritas data riwayat keuangan.'
                ], 422);
            }

            $user->delete();
            return response()->json(['success' => 'User deleted successfully'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete user: ' . $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required',
                'password' => 'required'
            ]);

            $user = MUsers::where('username', $validated['username'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            // Revoke old tokens if any to keep clean session
            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'message' => 'Login successful',
                'user' => $user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Login failed: ' . $e->getMessage()], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()->delete();
            }
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Logout failed'], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }
}
