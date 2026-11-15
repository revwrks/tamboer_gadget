<?php

namespace App\Http\Controllers;

use App\Models\MPenjualan;
use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CPenjualan extends Controller
{
    public function index()
    {
        try {
            $penjualan = MPenjualan::with(['stokHp', 'user'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('id_penjualan', 'desc')
                ->get();
            return response()->json($penjualan, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data penjualan'], 500);
        }
    }

    /**
     * Single item sale (Backward compatibility)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'id_stock' => 'required|exists:stok_hp,id_stock',
                'id_sales' => 'nullable|exists:user,id_user',
                'harga_jual' => 'required|numeric|min:0',
                'met_pem' => 'required|string|max:50',
                'diskon' => 'nullable|numeric|min:0',
                'uang_bayar' => 'nullable|numeric|min:0',
                'kembalian' => 'nullable|numeric',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $sellerId = $validated['id_sales'] ?? $request->user()?->id_user;
            if (!$sellerId) {
                return response()->json(['error' => 'Identitas penjual (sales) wajib ada'], 422);
            }

            $penjualan = DB::transaction(function () use ($validated, $sellerId) {
                $stock = MStock::lockForUpdate()->find($validated['id_stock']);

                if (!$stock || $stock->status !== 'ada') {
                    throw new \DomainException('Unit barang ini sudah terjual atau statusnya tidak tersedia!');
                }

                $dateStr = date('Ymd', strtotime($validated['tanggal']));
                $seq = MPenjualan::whereDate('tanggal', $validated['tanggal'])->distinct('no_invoice')->count('no_invoice') + 1;
                $noInvoice = sprintf('INV/%s/%04d', $dateStr, $seq);

                $sale = MPenjualan::create([
                    'no_invoice' => $noInvoice,
                    'tanggal' => $validated['tanggal'],
                    'id_stock' => $stock->id_stock,
                    'id_sales' => $sellerId,
                    'harga_jual' => $validated['harga_jual'],
                    'diskon' => $validated['diskon'] ?? 0,
                    'met_pem' => $validated['met_pem'],
                    'uang_bayar' => $validated['uang_bayar'] ?? 0,
                    'kembalian' => $validated['kembalian'] ?? 0,
                    'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                    'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                $stock->status = 'terjual';
                $stock->save();

                return $sale->load(['stokHp', 'user']);
            });

            return response()->json([
                'success' => 'Penjualan berhasil disimpan',
                'penjualan' => $penjualan
            ], 201);

        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Sales creation error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan sistem: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Multi-Item Cart POS Checkout
     */
    public function checkout(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'items' => 'required|array|min:1',
                'items.*.id_stock' => 'required|exists:stok_hp,id_stock',
                'items.*.harga_jual' => 'required|numeric|min:0',
                'id_sales' => 'nullable|exists:user,id_user',
                'met_pem' => 'required|string|max:50',
                'diskon' => 'nullable|numeric|min:0',
                'uang_bayar' => 'nullable|numeric|min:0',
                'kembalian' => 'nullable|numeric',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $sellerId = $validated['id_sales'] ?? $request->user()?->id_user;
            if (!$sellerId) {
                return response()->json(['error' => 'Identitas penjual (sales) wajib ada'], 422);
            }

            // Cegah duplikasi ID stok dalam 1 keranjang belanja
            $stockIds = array_column($validated['items'], 'id_stock');
            if (count($stockIds) !== count(array_unique($stockIds))) {
                return response()->json(['error' => 'Ada item duplikat di keranjang belanja!'], 422);
            }

            $invoiceResult = DB::transaction(function () use ($request, $validated, $sellerId, $stockIds) {
                // Lock semua baris stok yang akan dibeli
                $stocks = MStock::lockForUpdate()->whereIn('id_stock', $stockIds)->get()->keyBy('id_stock');

                // Validasi status semua stok
                foreach ($validated['items'] as $item) {
                    $stock = $stocks->get($item['id_stock']);
                    if (!$stock || $stock->status !== 'ada') {
                        $label = $stock ? ($stock->nama . ' [' . ($stock->imei ?: 'Tanpa IMEI') . ']') : 'ID ' . $item['id_stock'];
                        throw new \DomainException("Barang {$label} sudah terjual atau statusnya tidak tersedia!");
                    }
                }

                // Generate 1 Nomor Faktur untuk seluruh item belanja
                $dateStr = date('Ymd', strtotime($validated['tanggal']));
                $seq = MPenjualan::whereDate('tanggal', $validated['tanggal'])->distinct('no_invoice')->count('no_invoice') + 1;
                $noInvoice = sprintf('INV/%s/%04d', $dateStr, $seq);

                $createdSales = [];
                $diskonTotal = (float) ($validated['diskon'] ?? 0);
                $uangBayar = (float) ($validated['uang_bayar'] ?? 0);
                $kembalian = (float) ($validated['kembalian'] ?? 0);

                foreach ($validated['items'] as $idx => $item) {
                    $stock = $stocks->get($item['id_stock']);

                    // Catat diskon, uang bayar, kembalian di baris pertama transaksi
                    $sale = MPenjualan::create([
                        'no_invoice' => $noInvoice,
                        'tanggal' => $validated['tanggal'],
                        'id_stock' => $stock->id_stock,
                        'id_sales' => $sellerId,
                        'harga_jual' => $item['harga_jual'],
                        'diskon' => $idx === 0 ? $diskonTotal : 0,
                        'met_pem' => $validated['met_pem'],
                        'uang_bayar' => $idx === 0 ? $uangBayar : 0,
                        'kembalian' => $idx === 0 ? $kembalian : 0,
                        'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                        'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                        'catatan' => $validated['catatan'] ?? null,
                    ]);

                    $stock->status = 'terjual';
                    $stock->save();

                    $createdSales[] = $sale->load(['stokHp', 'user']);
                }

                return [
                    'no_invoice' => $noInvoice,
                    'tanggal' => $validated['tanggal'],
                    'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                    'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                    'met_pem' => $validated['met_pem'],
                    'diskon' => $diskonTotal,
                    'uang_bayar' => $uangBayar,
                    'kembalian' => $kembalian,
                    'catatan' => $validated['catatan'] ?? null,
                    'kasir' => $request->user()?->nama ?? 'Kasir',
                    'items' => $createdSales,
                ];
            });

            return response()->json([
                'success' => 'Transaksi POS berhasil diselesaikan!',
                'data' => $invoiceResult,
            ], 201);

        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('POS Checkout error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Checkout gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get full invoice receipt details for thermal printing
     */
    public function getInvoice($no_invoice)
    {
        try {
            $sales = MPenjualan::where('no_invoice', $no_invoice)
                ->with(['stokHp', 'user'])
                ->get();

            if ($sales->isEmpty()) {
                return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
            }

            $first = $sales->first();
            $subtotal = $sales->sum('harga_jual');
            $diskon = (float) $first->diskon;
            $total = max(0, $subtotal - $diskon);

            $receipt = [
                'no_invoice' => $first->no_invoice,
                'tanggal' => $first->tanggal,
                'created_at' => $first->created_at?->format('d/m/Y H:i') ?? date('d/m/Y H:i'),
                'kasir' => $first->user?->nama ?? 'Kasir',
                'nama_pembeli' => $first->nama_pembeli ?? 'Pelanggan Umum',
                'no_hp_pembeli' => $first->no_hp_pembeli,
                'met_pem' => $first->met_pem,
                'uang_bayar' => (float) $first->uang_bayar,
                'kembalian' => (float) $first->kembalian,
                'subtotal' => (float) $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'catatan' => $first->catatan,
                'items' => $sales->map(function ($s) {
                    $item = $s->stokHp;
                    return [
                        'id_penjualan' => $s->id_penjualan,
                        'id_stock' => $s->id_stock,
                        'nama' => $item?->nama ?? 'Barang',
                        'brand' => $item?->brand,
                        'warna' => $item?->warna,
                        'imei' => $item?->imei,
                        'kategori' => $item?->kategori,
                        'ram' => $item?->ram,
                        'storage' => $item?->storage,
                        'kondisi' => $item?->kondisi,
                        'garansi' => $item?->garansi,
                        'harga_jual' => (float) $s->harga_jual,
                    ];
                })->values(),
            ];

            return response()->json($receipt, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memuat invoice: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $penjualan = MPenjualan::with(['stokHp', 'user'])->findOrFail($id);
            return response()->json($penjualan, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data penjualan'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'sometimes|date',
                'id_sales' => 'sometimes|exists:user,id_user',
                'harga_jual' => 'sometimes|numeric|min:0',
                'met_pem' => 'sometimes|string|max:50',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $penjualan = MPenjualan::findOrFail($id);
            $penjualan->update($validated);

            return response()->json([
                'success' => 'Data penjualan berhasil diperbarui',
                'penjualan' => $penjualan->load(['stokHp', 'user'])
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal memperbarui data penjualan'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::transaction(function () use ($id) {
                $penjualan = MPenjualan::findOrFail($id);

                $stock = MStock::find($penjualan->id_stock);
                if ($stock) {
                    $stock->status = 'ada';
                    $stock->save();
                }

                $penjualan->delete();
            });

            return response()->json(['success' => 'Data penjualan berhasil dihapus dan unit stok telah dikembalikan'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Sales delete error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menghapus data penjualan'], 500);
        }
    }
}
