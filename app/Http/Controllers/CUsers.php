<?php

namespace App\Http\Controllers;

use App\Models\MPenjualan;
use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CUsers extends Controller
{
    public function index()
    {
        try {
            $users = MUsers::orderBy('id_user', 'asc')->get();
            return response()->json($users, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch users', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data pengguna'], 500);
        }
    }

    public function store(Request $request)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak menambah user.'], 403);
        }

        try {
            $validated = $request->validate([
                'username' => 'required|string|unique:user,username|max:255',
                'password' => 'required|string|min:6',
                'level' => 'required|in:owner,sales',
                'nama' => 'required|string|max:255',
            ]);

            $user = MUsers::create($validated);

            return response()->json(['success' => 'Akun pengguna berhasil dibuat', 'user' => $user], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create user', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal membuat akun pengguna'], 500);
        }
    }

    public function show($id_user)
    {
        try {
            $user = MUsers::findOrFail($id_user);
            return response()->json($user, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Pengguna tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch user', ['id_user' => $id_user, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data pengguna'], 500);
        }
    }

    public function update(Request $request, $id_user)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak mengubah user.'], 403);
        }

        try {
            $validated = $request->validate([
                'username' => 'required|string|max:255|unique:user,username,' . $id_user . ',id_user',
                'password' => 'nullable|string|min:6',
                'level' => 'required|in:owner,sales',
                'nama' => 'required|string|max:255',
            ]);

            $user = MUsers::findOrFail($id_user);

            if ($user->level === 'owner' && $validated['level'] !== 'owner') {
                $ownerCount = MUsers::where('level', 'owner')->count();
                if ($ownerCount <= 1) {
                    return response()->json([
                        'error' => 'Tidak dapat menurunkan hak akses owner terakhir.'
                    ], 422);
                }
            }

            if (empty($validated['password'])) {
                unset($validated['password']);
            }

            $user->update($validated);

            return response()->json(['success' => 'Data pengguna berhasil diperbarui', 'user' => $user], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Pengguna tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update user', ['id_user' => $id_user, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memperbarui data pengguna'], 500);
        }
    }

    public function destroy(Request $request, $id_user)
    {
        if ($request->user() && $request->user()->level !== 'owner') {
            return response()->json(['error' => 'Akses ditolak. Hanya owner yang berhak menghapus user.'], 403);
        }

        if ($request->user() && (int) $request->user()->id_user === (int) $id_user) {
            return response()->json(['error' => 'Tidak dapat menghapus akun sendiri yang sedang aktif.'], 422);
        }

        try {
            $user = MUsers::findOrFail($id_user);

            $hasSales = MPenjualan::where('id_sales', $id_user)->exists();
            $hasStocks = MStock::where('id_sales', $id_user)->exists();
            if ($hasSales || $hasStocks) {
                return response()->json([
                    'error' => 'User ini memiliki riwayat transaksi atau pencatatan stok. User tidak dapat dihapus demi menjaga integritas data riwayat keuangan.'
                ], 422);
            }

            $user->delete();
            return response()->json(['success' => 'Pengguna berhasil dihapus'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Pengguna tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', ['id_user' => $id_user, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menghapus akun pengguna'], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);

            $user = MUsers::where('username', $validated['username'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'message' => 'Login successful',
                'user' => $user,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Login failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memproses login'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            if ($request->user()) {
                $request->user()->currentAccessToken()?->delete();
            }
            return response()->json(['message' => 'Logged out successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memproses logout'], 500);
        }
    }

    public function me(Request $request)
    {
        return response()->json($request->user(), 200);
    }
}
