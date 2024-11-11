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
        $fourMonthsAgo = Carbon::now()->subMonths(4)->startOfMonth();

        $salesData = MPenjualan::selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month, COUNT(*) as total_sold')
            ->where('tanggal', '>=', $fourMonthsAgo)
            ->groupBy('year', 'month')
            ->orderByRaw('year DESC, month DESC')
            ->get();

        return response()->json($salesData);
    }

    public function getMonthlyReport(Request $request)
    {
        // Validate 'month' and 'year' inputs
        $request->validate([
            'month' => 'integer|between:1,12',
            'year' => 'integer|min:2000',
        ]);

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        // Retrieve sales data and related stock info for the given month and year
        $sales = MPenjualan::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->with('stokHp')
            ->get();

        $phonesSold = $sales->count();
        $totalIncome = $sales->sum('harga_jual');

        $totalExpenses = $sales->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0;
        });

        $profit = $totalIncome - $totalExpenses;

        // Total historical data
        $totalPhonesSoldEver = MPenjualan::count();
        $totalIncomeEver = MPenjualan::sum('harga_jual');

        $totalExpensesEver = MPenjualan::with('stokHp')->get()->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0;
        });

        $totalProfitEver = $totalIncomeEver - $totalExpensesEver;

        return response()->json([
            'phones_sold' => $phonesSold,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit' => $profit,
            'total_phones_sold_ever' => $totalPhonesSoldEver,
            'total_income_ever' => $totalIncomeEver,
            'total_expenses_ever' => $totalExpensesEver,
            'total_profit_ever' => $totalProfitEver,
        ]);
    }

    public function getCustomDateRangeReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date'))->endOfDay();

        $sales = MPenjualan::whereBetween('tanggal', [$startDate, $endDate])
            ->with('stokHp')
            ->get();

        $phonesSold = $sales->count();
        $totalIncome = $sales->sum('harga_jual');
        $totalExpenses = $sales->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0;
        });
        $profit = $totalIncome - $totalExpenses;

        return response()->json([
            'phones_sold' => $phonesSold,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'profit' => $profit,
        ]);
    }
}
