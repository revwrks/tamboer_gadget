<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\MPenjualan;
use App\Models\MStock;

class CLaporan extends Controller
{
    public function getAvailableDates()
    {
        try {
            $dates = MPenjualan::selectRaw('YEAR(tanggal) as year, MONTH(tanggal) as month')
                ->groupBy('year', 'month')
                ->orderByRaw('year DESC, month DESC')
                ->get();

            $dates->transform(function ($date) {
                $date->month_name = Carbon::createFromDate($date->year, $date->month, 1)->format('F');
                return $date;
            });

            return response()->json($dates);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data tanggal'], 500);
        }
    }

    public function getSalesDetails(Request $request)
    {
        try {
            $validated = $request->validate([
                'month' => 'required|integer|between:1,12',
                'year' => 'required|integer|min:2000',
            ]);

            $sales = MPenjualan::whereYear('tanggal', $validated['year'])
                ->whereMonth('tanggal', $validated['month'])
                ->with(['stokHp', 'user'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('id_penjualan', 'desc')
                ->get();

            return response()->json($sales);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil rincian penjualan'], 500);
        }
    }

    public function getMonthlyReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'month' => 'nullable|integer|between:1,12',
                'year' => 'nullable|integer|min:2000',
            ]);

            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);

            // 1. Agregasi data bulan terpilih (Pure SQL Join)
            $monthlyStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereYear('penjualan.tanggal', $year)
                ->whereMonth('penjualan.tanggal', $month)
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as phones_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_income,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses
                ')
                ->first();

            $phonesSold = (int) ($monthlyStats->phones_sold ?? 0);
            $totalIncome = (float) ($monthlyStats->total_income ?? 0);
            $totalExpenses = (float) ($monthlyStats->total_expenses ?? 0);
            $profit = $totalIncome - $totalExpenses;

            // 2. Agregasi all-time seumur hidup toko (Pure SQL)
            $allTimeStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold_ever,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_income_ever,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses_ever
                ')
                ->first();

            $totalPhonesSoldEver = (int) ($allTimeStats->total_sold_ever ?? 0);
            $totalIncomeEver = (float) ($allTimeStats->total_income_ever ?? 0);
            $totalExpensesEver = (float) ($allTimeStats->total_expenses_ever ?? 0);
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil laporan bulanan: ' . $e->getMessage()], 500);
        }
    }

    public function getCustomDateRangeReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            $stats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as phones_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_income,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses
                ')
                ->first();

            $phonesSold = (int) ($stats->phones_sold ?? 0);
            $totalIncome = (float) ($stats->total_income ?? 0);
            $totalExpenses = (float) ($stats->total_expenses ?? 0);
            $profit = $totalIncome - $totalExpenses;

            return response()->json([
                'phones_sold' => $phonesSold,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'profit' => $profit,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil laporan rentang tanggal'], 500);
        }
    }

    public function getSalesPerformance(Request $request)
    {
        try {
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);

            $sales = MPenjualan::whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->with(['stokHp', 'user'])
                ->get()
                ->groupBy('id_sales');

            $result = [];
            foreach ($sales as $salesId => $items) {
                $salesUser = $items->first()->user;
                $totalRevenue = (float) $items->sum('harga_jual');
                $totalExpenses = (float) $items->sum(function ($item) {
                    return (float) ($item->stokHp->harga_masuk ?? 0);
                });
                $totalLaba = $totalRevenue - $totalExpenses;

                $result[] = [
                    'id_sales' => $salesId,
                    'nama_sales' => $salesUser ? $salesUser->nama : 'Unknown',
                    'jumlah_unit' => $items->count(),
                    'total_revenue' => $totalRevenue,
                    'total_laba' => $totalLaba,
                    'items' => $items->map(function ($item) {
                        return [
                            'no_invoice' => $item->no_invoice,
                            'tanggal' => $item->tanggal,
                            'tipe_hp' => $item->stokHp->nama ?? '-',
                            'brand' => $item->stokHp->brand ?? '-',
                            'warna' => $item->stokHp->warna ?? '-',
                            'imei' => $item->stokHp->imei ?? '-',
                            'harga_jual' => (float) $item->harga_jual,
                            'harga_masuk' => (float) ($item->stokHp->harga_masuk ?? 0),
                            'met_pem' => $item->met_pem ?? '-',
                            'nama_pembeli' => $item->nama_pembeli,
                        ];
                    })->values(),
                ];
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil performa sales: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Dashboard comprehensive analytics (executive summary, trends & asset valuation)
     */
    public function getDashboardAnalytics(Request $request)
    {
        try {
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);

            // 1. Data Bulan Berjalan (Revenue, Expenses, Profit, Units)
            $currMonth = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereYear('penjualan.tanggal', $year)
                ->whereMonth('penjualan.tanggal', $month)
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_revenue,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses,
                    COALESCE(SUM(penjualan.diskon), 0) as total_discounts
                ')
                ->first();

            $revenueThisMonth = (float) ($currMonth->total_revenue ?? 0);
            $expensesThisMonth = (float) ($currMonth->total_expenses ?? 0);
            $profitThisMonth = $revenueThisMonth - $expensesThisMonth;
            $soldThisMonth = (int) ($currMonth->total_sold ?? 0);

            // 2. Data Bulan Lalu (Untuk MoM growth rate)
            $lastMonthDate = Carbon::createFromDate($year, $month, 1)->subMonth();
            $lastMonth = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereYear('penjualan.tanggal', $lastMonthDate->year)
                ->whereMonth('penjualan.tanggal', $lastMonthDate->month)
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_revenue,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses
                ')
                ->first();

            $revenueLastMonth = (float) ($lastMonth->total_revenue ?? 0);
            $profitLastMonth = $revenueLastMonth - (float) ($lastMonth->total_expenses ?? 0);

            // 3. Ringkasan Aset & Stok Ready
            $stockSummary = DB::table('stok_hp')
                ->where('status', 'ada')
                ->selectRaw('
                    COUNT(id_stock) as total_ready,
                    COALESCE(SUM(harga_masuk), 0) as asset_value,
                    SUM(CASE WHEN kategori = "gadget" THEN 1 ELSE 0 END) as gadget_ready,
                    SUM(CASE WHEN kategori = "aksesoris" THEN 1 ELSE 0 END) as aksesoris_ready
                ')
                ->first();

            // 4. Tren 6 Bulan Terakhir untuk Chart
            $sixMonthsAgo = Carbon::createFromDate($year, $month, 1)->subMonths(5)->startOfMonth();
            $trendQuery = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->where('penjualan.tanggal', '>=', $sixMonthsAgo->toDateString())
                ->where('penjualan.tanggal', '<=', Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString())
                ->selectRaw('
                    DATE_FORMAT(penjualan.tanggal, "%Y-%m") as period,
                    COUNT(penjualan.id_penjualan) as units_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as revenue,
                    COALESCE(SUM(penjualan.harga_jual - stok_hp.harga_masuk), 0) as profit
                ')
                ->groupBy('period')
                ->orderBy('period', 'asc')
                ->get()
                ->keyBy('period');

            $chartLabels = [];
            $revenueSeries = [];
            $profitSeries = [];
            $unitsSeries = [];

            for ($i = 5; $i >= 0; $i--) {
                $d = Carbon::createFromDate($year, $month, 1)->subMonths($i);
                $key = $d->format('Y-m');
                $chartLabels[] = $d->format('M Y');
                $item = $trendQuery->get($key);
                $revenueSeries[] = $item ? (float) $item->revenue : 0;
                $profitSeries[] = $item ? (float) $item->profit : 0;
                $unitsSeries[] = $item ? (int) $item->units_sold : 0;
            }

            // 5. Proporsi Penjualan Kategori
            $categoryStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereYear('penjualan.tanggal', $year)
                ->whereMonth('penjualan.tanggal', $month)
                ->selectRaw('
                    COALESCE(stok_hp.kategori, "gadget") as kategori,
                    COUNT(penjualan.id_penjualan) as total_qty,
                    COALESCE(SUM(penjualan.harga_jual), 0) as total_revenue
                ')
                ->groupBy('kategori')
                ->get();

            // 6. Akumulasi Global Toko
            $allTime = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold_ever,
                    COALESCE(SUM(penjualan.harga_jual - stok_hp.harga_masuk), 0) as total_profit_ever
                ')
                ->first();

            return response()->json([
                'cards' => [
                    'sold_this_month' => $soldThisMonth,
                    'revenue_this_month' => $revenueThisMonth,
                    'profit_this_month' => $profitThisMonth,
                    'growth_revenue_pct' => $revenueLastMonth > 0 ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1) : null,
                    'growth_profit_pct' => $profitLastMonth > 0 ? round((($profitThisMonth - $profitLastMonth) / $profitLastMonth) * 100, 1) : null,
                    'ready_stock_count' => (int) ($stockSummary->total_ready ?? 0),
                    'ready_asset_value' => (float) ($stockSummary->asset_value ?? 0),
                    'ready_gadget' => (int) ($stockSummary->gadget_ready ?? 0),
                    'ready_aksesoris' => (int) ($stockSummary->aksesoris_ready ?? 0),
                    'total_sold_ever' => (int) ($allTime->total_sold_ever ?? 0),
                    'total_profit_ever' => (float) ($allTime->total_profit_ever ?? 0),
                ],
                'trend' => [
                    'labels' => $chartLabels,
                    'revenue' => $revenueSeries,
                    'profit' => $profitSeries,
                    'units' => $unitsSeries,
                ],
                'category_breakdown' => $categoryStats,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil analitik dashboard: ' . $e->getMessage()], 500);
        }
    }
}
