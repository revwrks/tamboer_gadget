<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MPenjualan;
use App\Models\MStock;

class CLaporan extends Controller
{
    public function getLastFourMonthsSales(Request $request)
    {
        // Get the last 4 months from the current date
        $fourMonthsAgo = Carbon::now()->subMonths(4)->startOfMonth();

        // Get sales data for the last 4 months
        $salesData = MPenjualan::selectRaw('MONTH(tanggal) as month, COUNT(*) as total_sold')
            ->where('tanggal', '>=', $fourMonthsAgo)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Return the sales data in JSON format
        return response()->json($salesData);
    }

    public function getMonthlyReport(Request $request)
    {
        // Validate input for 'month' and 'year'
        $request->validate([
            'month' => 'integer|between:1,12',
            'year' => 'integer|min:2000', // Assuming year should not be before 2000
        ]);

        $month = $request->input('month', Carbon::now()->month); // default is current month
        $year = $request->input('year', Carbon::now()->year); // default is current year

        // Retrieve the sales data with related stock for the given month and year
        $sales = MPenjualan::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->with('stokHp')
            ->get();

        // Count the number of phones sold for the current month
        $phonesSold = $sales->count();

        // Calculate total income (sum of all 'harga_jual' for the current month)
        $totalIncome = $sales->sum('harga_jual');

        // Calculate total expenses (sum of all 'harga_masuk' from related stokHp records)
        $totalExpenses = $sales->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0; // Handle null 'stokHp' case
        });

        // Calculate profit for the current month (income - expenses)
        $profit = $totalIncome - $totalExpenses;

        // Get total phones sold ever
        $totalPhonesSoldEver = MPenjualan::count();

        // Calculate total income ever (sum of 'harga_jual' across all sales)
        $totalIncomeEver = MPenjualan::sum('harga_jual');

        // Calculate total expenses ever (sum of 'harga_masuk' across all related stocks)
        $totalExpensesEver = MPenjualan::with('stokHp')->get()->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0;
        });

        // Calculate total profit ever
        $totalProfitEver = $totalIncomeEver - $totalExpensesEver;

        return response()->json([
            'phones_sold' => $phonesSold,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit' => $profit,
            'total_phones_sold_ever' => $totalPhonesSoldEver, // Total phones sold ever
            'total_profit_ever' => $totalProfitEver // Total profit ever
        ]);
    }
}
