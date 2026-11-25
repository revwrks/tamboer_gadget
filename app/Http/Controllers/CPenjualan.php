<?php

namespace App\Http\Controllers;

use App\Models\MPenjualan;
use App\Models\MStock;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CPenjualan extends Controller
{
    private function maskStockCost($sale, $user)
    {
        if ($sale && $sale->stokHp && $user?->level !== 'owner') {
            $sale->stokHp->makeHidden('harga_masuk');
        }
        return $sale;
    }

    private function generateInvoiceNumber(string $tanggal): string
    {
        $dateStr = date('Ymd', strtotime($tanggal));
        $prefix = "INV/{$dateStr}/";

        $existing = MPenjualan::where('no_invoice', 'LIKE', $prefix . '%')
            ->pluck('no_invoice');

        $maxSeq = 0;
        foreach ($existing as $inv) {
            $suffix = substr($inv, strlen($prefix));
            if (ctype_digit($suffix)) {
                $maxSeq = max($maxSeq, (int) $suffix);
            }
        }

        return sprintf('%s%04d', $prefix, $maxSeq + 1);
    }

    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $penjualan = MPenjualan::with(['stokHp', 'user'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('id_penjualan', 'desc')
                ->get()
                ->map(fn ($sale) => $this->maskStockCost($sale, $user));

            return response()->json($penjualan, 200);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil data penjualan'], 500);
        }
    }

    /**
     * Single item sale
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'id_stock' => 'required|exists:stok_hp,id_stock',
                'id_sales' => 'nullable|exists:user,id_user',
                'harga_jual' => 'required|numeric|min:0',
                'met_pem' => 'required|in:Cash,Transfer,QRIS',
                'diskon' => 'nullable|numeric|min:0',
                'uang_bayar' => 'nullable|numeric|min:0',
                'kembalian' => 'nullable|numeric',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $authUser = $request->user();
            $sellerId = ($authUser?->level === 'owner' && !empty($validated['id_sales']))
                ? $validated['id_sales']
                : $authUser?->id_user;

            if (!$sellerId) {
                return response()->json(['error' => 'Identitas penjual (sales) wajib ada'], 422);
            }

            $penjualan = DB::transaction(function () use ($validated, $sellerId, $authUser) {
                $stock = MStock::lockForUpdate()->find($validated['id_stock']);

                if (!$stock || $stock->status !== 'ada') {
                    throw new \DomainException('Unit barang ini sudah terjual atau statusnya tidak tersedia!');
                }

                $subtotal = (float) $validated['harga_jual'];
                $diskon = (float) ($validated['diskon'] ?? 0);
                if ($diskon > $subtotal) {
                    throw new \DomainException('Diskon tidak boleh melebihi subtotal transaksi!');
                }

                $grandTotal = $subtotal - $diskon;
                $method = $validated['met_pem'];

                if ($method === 'Cash') {
                    $uangBayar = (float) ($validated['uang_bayar'] ?? 0);
                    if ($uangBayar < $grandTotal) {
                        throw new \DomainException('Nominal uang yang diterima kurang dari total akhir!');
                    }
                    $kembalian = $uangBayar - $grandTotal;
                } else {
                    $uangBayar = $grandTotal;
                    $kembalian = 0.0;
                }

                $noInvoice = $this->generateInvoiceNumber($validated['tanggal']);

                $sale = MPenjualan::create([
                    'no_invoice' => $noInvoice,
                    'tanggal' => $validated['tanggal'],
                    'id_stock' => $stock->id_stock,
                    'id_sales' => $sellerId,
                    'harga_jual' => $subtotal,
                    'diskon' => $diskon,
                    'met_pem' => $method,
                    'uang_bayar' => $uangBayar,
                    'kembalian' => $kembalian,
                    'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                    'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                    'catatan' => $validated['catatan'] ?? null,
                ]);

                $stock->status = 'terjual';
                $stock->save();

                $sale->load(['stokHp', 'user']);
                return $this->maskStockCost($sale, $authUser);
            });

            return response()->json([
                'success' => 'Penjualan berhasil disimpan',
                'penjualan' => $penjualan,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Sales creation error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Terjadi kesalahan sistem saat menyimpan penjualan'], 500);
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
                'met_pem' => 'required|in:Cash,Transfer,QRIS',
                'diskon' => 'nullable|numeric|min:0',
                'uang_bayar' => 'nullable|numeric|min:0',
                'kembalian' => 'nullable|numeric',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $authUser = $request->user();
            $sellerId = ($authUser?->level === 'owner' && !empty($validated['id_sales']))
                ? $validated['id_sales']
                : $authUser?->id_user;

            if (!$sellerId) {
                return response()->json(['error' => 'Identitas penjual (sales) wajib ada'], 422);
            }

            $stockIds = array_column($validated['items'], 'id_stock');
            if (count($stockIds) !== count(array_unique($stockIds))) {
                return response()->json(['error' => 'Ada item duplikat di keranjang belanja!'], 422);
            }

            $invoiceResult = DB::transaction(function () use ($validated, $sellerId, $stockIds, $authUser) {
                $stocks = MStock::lockForUpdate()->whereIn('id_stock', $stockIds)->get()->keyBy('id_stock');

                $subtotal = 0.0;
                foreach ($validated['items'] as $item) {
                    $stock = $stocks->get($item['id_stock']);
                    if (!$stock || $stock->status !== 'ada') {
                        $label = $stock ? ($stock->nama . ' [' . ($stock->imei ?: 'Tanpa IMEI') . ']') : 'ID ' . $item['id_stock'];
                        throw new \DomainException("Barang {$label} sudah terjual atau statusnya tidak tersedia!");
                    }
                    $subtotal += (float) $item['harga_jual'];
                }

                $diskonTotal = (float) ($validated['diskon'] ?? 0);
                if ($diskonTotal > $subtotal) {
                    throw new \DomainException('Diskon tidak boleh melebihi subtotal belanja!');
                }

                $grandTotal = $subtotal - $diskonTotal;
                $method = $validated['met_pem'];

                if ($method === 'Cash') {
                    $uangBayar = (float) ($validated['uang_bayar'] ?? 0);
                    if ($uangBayar < $grandTotal) {
                        throw new \DomainException('Nominal uang yang diterima kurang dari total belanja!');
                    }
                    $kembalian = $uangBayar - $grandTotal;
                } else {
                    $uangBayar = $grandTotal;
                    $kembalian = 0.0;
                }

                $noInvoice = $this->generateInvoiceNumber($validated['tanggal']);
                $createdSales = [];

                foreach ($validated['items'] as $idx => $item) {
                    $stock = $stocks->get($item['id_stock']);

                    $sale = MPenjualan::create([
                        'no_invoice' => $noInvoice,
                        'tanggal' => $validated['tanggal'],
                        'id_stock' => $stock->id_stock,
                        'id_sales' => $sellerId,
                        'harga_jual' => (float) $item['harga_jual'],
                        'diskon' => $idx === 0 ? $diskonTotal : 0,
                        'met_pem' => $method,
                        'uang_bayar' => $idx === 0 ? $uangBayar : 0,
                        'kembalian' => $idx === 0 ? $kembalian : 0,
                        'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                        'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                        'catatan' => $validated['catatan'] ?? null,
                    ]);

                    $stock->status = 'terjual';
                    $stock->save();

                    $sale->load(['stokHp', 'user']);
                    $createdSales[] = $this->maskStockCost($sale, $authUser);
                }

                return [
                    'no_invoice' => $noInvoice,
                    'tanggal' => $validated['tanggal'],
                    'nama_pembeli' => $validated['nama_pembeli'] ?? null,
                    'no_hp_pembeli' => $validated['no_hp_pembeli'] ?? null,
                    'met_pem' => $method,
                    'subtotal' => $subtotal,
                    'diskon' => $diskonTotal,
                    'total' => $grandTotal,
                    'uang_bayar' => $uangBayar,
                    'kembalian' => $kembalian,
                    'catatan' => $validated['catatan'] ?? null,
                    'kasir' => $authUser?->nama ?? 'Kasir',
                    'items' => $createdSales,
                ];
            });

            return response()->json([
                'success' => 'Transaksi POS berhasil diselesaikan!',
                'data' => $invoiceResult,
            ], 201);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('POS Checkout error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Checkout gagal diproses'], 500);
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
                ->orderBy('id_penjualan', 'asc')
                ->get();

            if ($sales->isEmpty()) {
                return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
            }

            $first = $sales->first();
            $subtotal = (float) $sales->sum('harga_jual');
            $diskon = (float) $sales->sum('diskon');
            $total = max(0, $subtotal - $diskon);
            $uangBayar = (float) $sales->sum('uang_bayar');
            $kembalian = max(0, $uangBayar - $total);

            $receipt = [
                'no_invoice' => $first->no_invoice,
                'tanggal' => $first->tanggal?->format('Y-m-d') ?? $first->tanggal,
                'created_at' => $first->created_at?->format('d/m/Y H:i') ?? date('d/m/Y H:i'),
                'kasir' => $first->user?->nama ?? 'Kasir',
                'nama_pembeli' => $first->nama_pembeli ?? 'Pelanggan Umum',
                'no_hp_pembeli' => $first->no_hp_pembeli,
                'met_pem' => $first->met_pem,
                'uang_bayar' => $uangBayar,
                'kembalian' => $kembalian,
                'subtotal' => $subtotal,
                'diskon' => $diskon,
                'total' => $total,
                'catatan' => $first->catatan,
                'store' => config('store'),
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
            Log::error('Failed to load invoice', ['no_invoice' => $no_invoice, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memuat invoice'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $penjualan = MPenjualan::with(['stokHp', 'user'])->findOrFail($id);
            $this->maskStockCost($penjualan, $request->user());
            return response()->json($penjualan, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sale', ['id' => $id, 'error' => $e->getMessage()]);
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
                'met_pem' => 'sometimes|in:Cash,Transfer,QRIS',
                'nama_pembeli' => 'nullable|string|max:255',
                'no_hp_pembeli' => 'nullable|string|max:50',
                'catatan' => 'nullable|string',
            ]);

            $penjualan = MPenjualan::findOrFail($id);
            $penjualan->update($validated);
            $penjualan->load(['stokHp', 'user']);
            $this->maskStockCost($penjualan, $request->user());

            return response()->json([
                'success' => 'Data penjualan berhasil diperbarui',
                'penjualan' => $penjualan,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Failed to update sale', ['id' => $id, 'error' => $e->getMessage()]);
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

                if ($penjualan->no_invoice && ($penjualan->diskon > 0 || $penjualan->uang_bayar > 0)) {
                    $siblings = MPenjualan::where('no_invoice', $penjualan->no_invoice)
                        ->where('id_penjualan', '!=', $penjualan->id_penjualan)
                        ->orderBy('id_penjualan', 'asc')
                        ->get();

                    if ($siblings->isNotEmpty()) {
                        $remainingSubtotal = (float) $siblings->sum('harga_jual');
                        $transferredDiskon = min((float) $penjualan->diskon, $remainingSubtotal);
                        $remainingTotal = max(0, $remainingSubtotal - $transferredDiskon);
                        $transferredUangBayar = (float) $penjualan->uang_bayar;
                        $recalculatedKembalian = max(0, $transferredUangBayar - $remainingTotal);

                        $firstSibling = $siblings->first();
                        $firstSibling->update([
                            'diskon' => $transferredDiskon,
                            'uang_bayar' => $transferredUangBayar,
                            'kembalian' => $recalculatedKembalian,
                        ]);
                    }
                }

                $penjualan->delete();
            });

            return response()->json(['success' => 'Data penjualan berhasil dihapus dan unit stok telah dikembalikan'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Data penjualan tidak ditemukan'], 404);
        } catch (\Exception $e) {
            Log::error('Sales delete error', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal menghapus data penjualan'], 500);
        }
    }
}
