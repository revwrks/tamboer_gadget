<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\MPenjualan;
use App\Models\MStock;

class CLaporan extends Controller
{
    public function getAvailableDates()
    {
        $dates = MPenjualan::selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month')
            ->groupBy('year', 'month')
            ->orderByRaw('year DESC, month DESC')
            ->get();

        // Adding month names for better display in Vue
        $dates->transform(function ($date) {
            $date->month_name = Carbon::createFromDate($date->year, $date->month, 1)->format('F');
            return $date;
        });

        return response()->json($dates);
    }

    public function getSalesDetails(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        $month = $request->input('month');
        $year = $request->input('year');

        // Fetch detailed sales data with associated user information
        $sales = MPenjualan::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->with(['stokHp', 'users']) // Ensure 'users' is included here
            ->get();

        return response()->json($sales);
    }



    public function getMonthlyReport(Request $request)
    {
        $request->validate([
            'month' => 'integer|between:1,12',
            'year' => 'integer|min:2000',
        ]);

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $sales = MPenjualan::whereYear('tanggal', $year)
            ->whereMonth('tanggal', $month)
            ->with('stokHp')
            ->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'message' => 'No data available for the selected month and year.',
                'phones_sold' => 0,
                'total_income' => 0,
                'total_expenses' => 0,
                'profit' => 0,
            ]);
        }

        $phonesSold = $sales->count();
        $totalIncome = $sales->sum('harga_jual');
        $totalExpenses = $sales->sum(function ($sale) {
            return $sale->stokHp->harga_masuk ?? 0;
        });
        $profit = $totalIncome - $totalExpenses;

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
