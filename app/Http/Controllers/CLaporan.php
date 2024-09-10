<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MPenjualan;
use App\Models\MStock;

class CLaporan extends Controller
{
    public function getMonthlyReport(Request $request)
    {
        $month = $request->input('month', Carbon::now()->month); // default is current month
        $year = $request->input('year', Carbon::now()->year); // default is current year

        // Get sales for the month
        $sales = MPenjualan::whereYear('tanggal', $year)
                          ->whereMonth('tanggal', $month)
                          ->with('stokHp')
                          ->get();

        // Count the number of phones sold
        $phonesSold = $sales->count();

        // Calculate total income (sum of all 'harga_jual')
        $totalIncome = $sales->sum('harga_jual');

        // Calculate total expenses (sum of all 'harga_masuk' from related stokHp records)
        $totalExpenses = $sales->sum(function ($sale) {
            return $sale->stokHp->harga_masuk;
        });

        // Calculate profit (income - expenses)
        $profit = $totalIncome - $totalExpenses;

        return response()->json([
            'phones_sold' => $phonesSold,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit' => $profit
        ]);
    }
}
