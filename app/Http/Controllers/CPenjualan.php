<?php

namespace App\Http\Controllers;

use App\Models\MPenjualan;
use App\Models\MStock;
use App\Models\MUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CPenjualan extends Controller
{
    public function index()
    {
        // Fetch all sales data with stock and sales relations
        $penjualan = MPenjualan::with(['stokHp', 'users'])->get();
        return response()->json($penjualan, 200);
    }

    public function store(Request $request)
    {
        $id_sales = $request->input('id_sales');
        // Validate input request
        $validator = Validator::make($request->all(), [
            'tanggal' => 'required|date',
            'id_stock' => 'required|exists:stok_hp,id_stock',
            'id_sales' => 'required|exists:user,id_user',
            'harga_jual' => 'required|numeric',
        ]);

        // Return error messages if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Create the new sale record
        $penjualan = MPenjualan::create([
            'tanggal' => $request->tanggal,
            'id_stock' => $request->id_stock,
            'id_sales' => $id_sales,  // Automatically set the current user as the sales
            'harga_jual' => $request->harga_jual,
        ]);

        // Update the status of the stock to 'terjual'
        $stock = MStock::find($request->id_stock);
        if ($stock) {
            $stock->status = 'terjual';
            $stock->save();
        }

        return response()->json(['success' => 'Penjualan created successfully', 'penjualan' => $penjualan], 201);
    }


    public function show($id)
    {
        // Retrieve sales data by ID with stock and sales relations
        $penjualan = MPenjualan::with(['stokHp', 'users'])->find($id);

        // Return error message if sales data is not found
        if (!$penjualan) {
            return response()->json(['error' => 'Penjualan not found'], 404);
        }

        return response()->json($penjualan, 200);
    }

    public function update(Request $request, $id)
    {
        // Validate input request
        $validator = Validator::make($request->all(), [
            'tanggal' => 'sometimes|date',
            'id_stock' => 'sometimes|exists:stok_hp,id_stock',
            'id_sales' => 'sometimes|exists:users,id_user',
            'harga_jual' => 'sometimes|numeric',
        ]);

        // Return error messages if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Find sales data by ID
        $penjualan = MPenjualan::find($id);

        // Return error message if sales data is not found
        if (!$penjualan) {
            return response()->json(['error' => 'Penjualan not found'], 404);
        }

        // Update sales data
        $penjualan->update($request->all());

        return response()->json(['success' => 'Penjualan updated successfully', 'penjualan' => $penjualan], 200);
    }

    public function destroy($id)
    {
        // Find sales data by ID
        $penjualan = MPenjualan::find($id);

        // Return error message if sales data is not found
        if (!$penjualan) {
            return response()->json(['error' => 'Penjualan not found'], 404);
        }

        // Delete sales data
        $penjualan->delete();

        return response()->json(['success' => 'Penjualan deleted successfully'], 200);
    }
}
