<?php

namespace App\Http\Controllers;

use App\Models\MStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;


class CStock extends Controller
{
    public function index()
    {
        // Mengembalikan semua stok HP dalam bentuk JSON
        $stok_hp = MStock::all();
        return response()->json($stok_hp, 200);
    }

    public function store(Request $request)
{
    try {
        // Validate request data
        $validated = $request->validate([
            'imei' => 'required',
            'brand' => 'required',
            'nama' => 'required',
            'warna' => 'required',
            'harga_masuk' => 'required|numeric',
            'harga_jual' => 'required|numeric',
        ]);

        // Create stock HP
        $stok_hp = MStock::create([
            'tanggal' => $request->tanggal,
            'imei' => $request->imei,
            'brand' => $request->brand,
            'nama' => $request->nama,
            'warna' => $request->warna,
            'harga_masuk' => $request->harga_masuk,
            'harga_jual' => $request->harga_jual,
            'status' => 'ada', // Always set to "ada"
        ]);

        return response()->json(['stok_hp' => $stok_hp], 201);
    } catch (\Exception $e) {
        Log::error('Failed to process request', [
            'input' => $request->all(),
            'error' => $e->getMessage(),
        ]);        
    }
}


    public function show($id_stock)
    {
        // Menemukan stok HP berdasarkan ID
        $stok_hp = MStock::find($id_stock);

        // Jika stok HP tidak ditemukan, kembalikan pesan error
        if (!$stok_hp) {
            return response()->json(['error' => 'Stok HP not found'], 404);
        }

        // Kembalikan data stok HP
        return response()->json($stok_hp, 200);
    }

    public function update(Request $request, $id_stock)
    {
        // Validasi request
        $validator = Validator::make($request->all(), [
            'tanggal' => 'sometimes|date',
            'imei' => 'sometimes|max:255|unique:stok_hp,imei,' . $id_stock . ',id_stock',
            'brand' => 'sometimes|max:255',
            'nama' => 'sometimes|max:255',
            'warna' => 'sometimes|max:255',
            'harga_masuk' => 'sometimes|numeric',
            'harga_jual' => 'sometimes|numeric',
            'status' => 'sometimes|in:ada,terjual',
        ]);
        
        // Simplify the validator check:
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }
        

        // Menemukan stok HP berdasarkan ID
        $stok_hp = MStock::find($id_stock);

        // Jika stok HP tidak ditemukan, kembalikan pesan error
        $stok_hp = MStock::findOrFail($id_stock);

        // Update data stok HP
        $stok_hp->update($request->all());

        // Mengembalikan respons sukses
        return response()->json(['success' => 'Stok HP updated successfully', 'stok_hp' => $stok_hp], 200);
    }

    public function destroy($id_stock)
    {
        // Menemukan stok HP berdasarkan ID
        $stok_hp = MStock::find($id_stock);

        // Jika stok HP tidak ditemukan, kembalikan pesan error
        $stok_hp = MStock::findOrFail($id_stock);

        // Hapus stok HP
        $stok_hp->delete();

        // Mengembalikan respons sukses
        return response()->json(['success' => 'Stok HP deleted successfully'], 200);
    }
}
