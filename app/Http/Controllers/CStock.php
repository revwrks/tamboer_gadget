<?php

namespace App\Http\Controllers;

use App\Models\MDeviceTac;
use App\Models\MPenjualan;
use App\Models\MStock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CStock extends Controller
{
    private function maskCostForUser($stock, $user)
    {
        if ($stock && $user?->level !== 'owner') {
            $stock->makeHidden('harga_masuk');
        }
        return $stock;
    }

    public function index(Request $request)
    {
        try {
            $query = MStock::with('sales')->orderBy('id_stock', 'desc');

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            } elseif (!$request->has('status')) {
                $query->where('status', 'ada');
            }

            if ($request->filled('kategori')) {
                $query->where('kategori', $request->kategori);
            }

            if ($request->filled('kondisi')) {
                $query->where('kondisi', $request->kondisi);
            }

            $user = $request->user();
            $stok = $query->get()->map(fn ($item) => $this->maskCostForUser($item, $user));

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
                'harga_masuk' => 'nullable|numeric|min:0',
                'harga_jual' => 'required|numeric|min:0',
                'id_sales' => 'nullable|exists:user,id_user',
                'catatan' => 'nullable|string',
            ]);

            $authUser = $request->user();
            $sellerId = ($authUser?->level === 'owner' && !empty($validated['id_sales']))
                ? $validated['id_sales']
                : $authUser?->id_user;

            $hargaMasuk = $authUser?->level === 'owner'
                ? (float) ($validated['harga_masuk'] ?? 0)
                : 0;

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
                'harga_masuk' => $hargaMasuk,
                'harga_jual' => $validated['harga_jual'],
                'id_sales' => $sellerId,
                'status' => 'ada',
                'catatan' => $validated['catatan'] ?? null,
            ]);

            if (!empty($validated['imei']) && !empty($validated['brand']) && !empty($validated['nama'])) {
                $cleanImei = preg_replace('/\D/', '', $validated['imei']);
                if (strlen($cleanImei) >= 8) {
                    $tac = substr($cleanImei, 0, 8);
                    MDeviceTac::firstOrCreate(
                        ['tac' => $tac],
                        [
                            'brand' => $validated['brand'],
                            'model' => $validated['nama'],
                            'storage' => $validated['storage'] ?? null,
                            'ram' => $validated['ram'] ?? null,
                            'kategori' => $validated['kategori'] ?? 'gadget',
                        ]
                    );
                }
            }

            $stok->load('sales');
            $this->maskCostForUser($stok, $authUser);

            return response()->json([
                'success' => 'Stok berhasil ditambahkan',
                'stok_hp' => $stok,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create stock', [
                'user_id' => $request->user()?->id_user,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Gagal menambah stok'], 500);
        }
    }

    public function show(Request $request, $id_stock)
    {
        try {
            $stok = MStock::with('sales')->findOrFail($id_stock);
            $this->maskCostForUser($stok, $request->user());
            return response()->json($stok, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch stock', ['id_stock' => $id_stock, 'error' => $e->getMessage()]);
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
            $stok->load('sales');
            $this->maskCostForUser($stok, $request->user());

            return response()->json([
                'success' => 'Stok berhasil diperbarui',
                'stok_hp' => $stok,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update stock', ['id_stock' => $id_stock, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memperbarui stok'], 500);
        }
    }

    public function destroy($id_stock)
    {
        try {
            $stok = MStock::findOrFail($id_stock);

            if ($stok->status === 'terjual' || MPenjualan::where('id_stock', $id_stock)->exists()) {
                return response()->json([
                    'error' => 'Barang ini tidak dapat dihapus karena sudah tercatat dalam riwayat penjualan toko.'
                ], 422);
            }

            $stok->delete();
            return response()->json(['success' => 'Stok berhasil dihapus'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data stok tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to delete stock', ['id_stock' => $id_stock, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menghapus stok'], 500);
        }
    }

    public function searchByImei(Request $request, $imei)
    {
        try {
            $stok = MStock::where('imei', $imei)->with('sales')->first();
            if ($stok) {
                $this->maskCostForUser($stok, $request->user());
                return response()->json($stok, 200);
            }
            return response()->json(['error' => 'Stok dengan IMEI tersebut tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to search stock by IMEI', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mencari data berdasarkan IMEI'], 500);
        }
    }

    public function lookupTac($imei)
    {
        try {
            $clean = preg_replace('/\D/', '', $imei);
            if (strlen($clean) < 8) {
                return response()->json([
                    'found' => false,
                    'error' => 'Nomor IMEI harus memiliki minimal 8 digit TAC',
                ], 400);
            }

            $tac = substr($clean, 0, 8);

            $device = MDeviceTac::where('tac', $tac)->first();
            if ($device) {
                return response()->json([
                    'found' => true,
                    'source' => 'tac_dictionary',
                    'tac' => $tac,
                    'brand' => $device->brand,
                    'nama' => $device->model,
                    'storage' => $device->storage,
                    'ram' => $device->ram,
                    'kategori' => $device->kategori ?? 'gadget',
                ], 200);
            }

            $history = MStock::where('imei', 'LIKE', $tac . '%')->orderBy('id_stock', 'desc')->first();
            if ($history) {
                return response()->json([
                    'found' => true,
                    'source' => 'stock_history',
                    'tac' => $tac,
                    'brand' => $history->brand,
                    'nama' => $history->nama,
                    'storage' => $history->storage,
                    'ram' => $history->ram,
                    'warna' => $history->warna,
                    'kategori' => $history->kategori ?? 'gadget',
                ], 200);
            }

            return response()->json([
                'found' => false,
                'tac' => $tac,
                'message' => 'Model belum terdaftar di database TAC. Lengkapi detail unit sekali, sistem akan menyimpannya otomatis.',
            ], 200);
        } catch (\Exception $e) {
            Log::error('TAC lookup failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal melakukan TAC lookup'], 500);
        }
    }
}
