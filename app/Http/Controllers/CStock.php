<?php

namespace App\Http\Controllers;

use App\Models\MStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CStock extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = MStock::with('sales')->orderBy('id_stock', 'desc');

            // Filter status: default 'ada', bisa 'all' atau 'terjual'
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            } elseif (!$request->has('status')) {
                $query->where('status', 'ada');
            }

            // Filter kategori jika diminta
            if ($request->filled('kategori')) {
                $query->where('kategori', $request->kategori);
            }

            // Filter kondisi jika diminta
            if ($request->filled('kondisi')) {
                $query->where('kondisi', $request->kondisi);
            }

            $stok = $query->get();
            return response()->json($stok, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch stocks', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data stok'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'imei' => 'nullable|string|max:255|unique:stok_hp,imei',
                'brand' => 'required|string|max:255',
                'kategori' => 'nullable|in:gadget,aksesoris,sparepart',
                'nama' => 'required|string|max:255',
                'warna' => 'nullable|string|max:255',
                'ram' => 'nullable|string|max:50',
                'storage' => 'nullable|string|max:50',
                'kondisi' => 'nullable|in:baru,second,refurbished',
                'kelengkapan' => 'nullable|string|max:100',
                'garansi' => 'nullable|string|max:100',
                'harga_masuk' => 'required|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'id_sales' => 'nullable|exists:user,id_user',
                'catatan' => 'nullable|string',
            ]);

            $sellerId = $validated['id_sales'] ?? $request->user()?->id_user;

            $stok = MStock::create([
                'tanggal' => $validated['tanggal'],
                'imei' => !empty($validated['imei']) ? $validated['imei'] : null,
                'brand' => $validated['brand'],
                'kategori' => $validated['kategori'] ?? 'gadget',
                'nama' => $validated['nama'],
                'warna' => $validated['warna'] ?? '-',
                'ram' => $validated['ram'] ?? null,
                'storage' => $validated['storage'] ?? null,
                'kondisi' => $validated['kondisi'] ?? 'baru',
                'kelengkapan' => $validated['kelengkapan'] ?? null,
                'garansi' => $validated['garansi'] ?? null,
                'harga_masuk' => $validated['harga_masuk'],
                'harga_jual' => $validated['harga_jual'],
                'id_sales' => $sellerId,
                'status' => 'ada',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            return response()->json([
                'success' => 'Stok berhasil ditambahkan',
                'stok_hp' => $stok->load('sales')
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create stock', [
                'input' => $request->all(),
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gagal menambah stok: ' . $e->getMessage()], 500);
        }
    }

    public function show($id_stock)
    {
        try {
            $stok = MStock::with('sales')->findOrFail($id_stock);
            return response()->json($stok, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data stok'], 500);
        }
    }

    public function update(Request $request, $id_stock)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'sometimes|date',
                'imei' => 'nullable|string|max:255|unique:stok_hp,imei,' . $id_stock . ',id_stock',
                'brand' => 'sometimes|string|max:255',
                'kategori' => 'nullable|in:gadget,aksesoris,sparepart',
                'nama' => 'sometimes|string|max:255',
                'warna' => 'nullable|string|max:255',
                'ram' => 'nullable|string|max:50',
                'storage' => 'nullable|string|max:50',
                'kondisi' => 'nullable|in:baru,second,refurbished',
                'kelengkapan' => 'nullable|string|max:100',
                'garansi' => 'nullable|string|max:100',
                'harga_masuk' => 'sometimes|numeric|min:0',
                'harga_jual' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|in:ada,terjual',
                'id_sales' => 'sometimes|nullable|exists:user,id_user',
                'catatan' => 'nullable|string',
            ]);

            $stok = MStock::findOrFail($id_stock);
            $stok->update($validated);

            return response()->json([
                'success' => 'Stok berhasil diperbarui',
                'stok_hp' => $stok->load('sales')
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memperbarui stok'], 500);
        }
    }

    public function destroy($id_stock)
    {
        try {
            $stok = MStock::findOrFail($id_stock);

            // Cegah penghapusan jika barang sudah terjual atau tercatat di riwayat penjualan
            if ($stok->status === 'terjual' || \App\Models\MPenjualan::where('id_stock', $id_stock)->exists()) {
                return response()->json([
                    'error' => 'Barang ini tidak dapat dihapus karena sudah tercatat dalam riwayat penjualan toko.'
                ], 422);
            }

            $stok->delete();
            return response()->json(['success' => 'Stok berhasil dihapus'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal menghapus stok: ' . $e->getMessage()], 500);
        }
    }

    public function searchByImei($imei)
    {
        try {
            $stok = MStock::where('imei', $imei)->with('sales')->first();
            if ($stok) {
                return response()->json($stok, 200);
            }
            return response()->json(['error' => 'Stok dengan IMEI tersebut tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mencari data berdasarkan IMEI'], 500);
        }
    }
}
