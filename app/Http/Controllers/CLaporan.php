<?php

namespace App\Http\Controllers;

use App\Models\MPenjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CLaporan extends Controller
{
    public function getAvailableDates()
    {
        try {
            $rawDates = MPenjualan::whereNotNull('tanggal')
                ->orderBy('tanggal', 'desc')
                ->pluck('tanggal');

            $grouped = [];
            foreach ($rawDates as $tgl) {
                $dt = Carbon::parse($tgl);
                $key = $dt->format('Y-m');
                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'year' => (int) $dt->year,
                        'month' => (int) $dt->month,
                        'month_name' => $dt->format('F'),
                    ];
                }
            }

            return response()->json(array_values($grouped));
        } catch (\Exception $e) {
            Log::error('Failed to fetch available dates', ['error' => $e->getMessage()]);
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

            $start = Carbon::createFromDate($validated['year'], $validated['month'], 1)->startOfMonth()->toDateString();
            $end = Carbon::createFromDate($validated['year'], $validated['month'], 1)->endOfMonth()->toDateString();

            $sales = MPenjualan::whereBetween('tanggal', [$start, $end])
                ->with(['stokHp', 'user'])
                ->orderBy('tanggal', 'desc')
                ->orderBy('id_penjualan', 'desc')
                ->get();

            return response()->json($sales);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil rincian penjualan'], 500);
        }
    }

    public function getMonthlyReport(Request $request)
    {
        try {
            $request->validate([
                'month' => 'nullable|integer|between:1,12',
                'year' => 'nullable|integer|min:2000',
            ]);

            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);

            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

            $monthlyStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$start, $end])
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as phones_sold,
                    COALESCE(SUM(penjualan.harga_jual), 0) as gross_income,
                    COALESCE(SUM(penjualan.diskon), 0) as total_discount,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_income,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses
                ')
                ->first();

            $phonesSold = (int) ($monthlyStats->phones_sold ?? 0);
            $grossIncome = (float) ($monthlyStats->gross_income ?? 0);
            $totalDiscount = (float) ($monthlyStats->total_discount ?? 0);
            $totalIncome = (float) ($monthlyStats->total_income ?? 0);
            $totalExpenses = (float) ($monthlyStats->total_expenses ?? 0);
            $profit = $totalIncome - $totalExpenses;

            $allTimeStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold_ever,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_income_ever,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses_ever
                ')
                ->first();

            $totalPhonesSoldEver = (int) ($allTimeStats->total_sold_ever ?? 0);
            $totalIncomeEver = (float) ($allTimeStats->total_income_ever ?? 0);
            $totalExpensesEver = (float) ($allTimeStats->total_expenses_ever ?? 0);
            $totalProfitEver = $totalIncomeEver - $totalExpensesEver;

            return response()->json([
                'phones_sold' => $phonesSold,
                'gross_income' => $grossIncome,
                'total_discount' => $totalDiscount,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'profit' => $profit,
                'total_phones_sold_ever' => $totalPhonesSoldEver,
                'total_income_ever' => $totalIncomeEver,
                'total_expenses_ever' => $totalExpensesEver,
                'total_profit_ever' => $totalProfitEver,
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch monthly report', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil laporan bulanan'], 500);
        }
    }

    public function getCustomDateRangeReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($validated['start_date'])->toDateString();
            $endDate = Carbon::parse($validated['end_date'])->toDateString();

            $stats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$startDate, $endDate])
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as phones_sold,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_income,
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
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Failed to fetch custom date range report', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil laporan rentang tanggal'], 500);
        }
    }

    public function getSalesPerformance(Request $request)
    {
        try {
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);

            $start = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $end = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

            $sales = MPenjualan::whereBetween('tanggal', [$start, $end])
                ->with(['stokHp', 'user'])
                ->get()
                ->groupBy('id_sales');

            $result = [];
            foreach ($sales as $salesId => $items) {
                $salesUser = $items->first()->user;
                $grossRevenue = (float) $items->sum('harga_jual');
                $discounts = (float) $items->sum('diskon');
                $totalRevenue = $grossRevenue - $discounts;
                $totalExpenses = (float) $items->sum(fn ($item) => (float) ($item->stokHp->harga_masuk ?? 0));
                $totalLaba = $totalRevenue - $totalExpenses;

                $result[] = [
                    'id_sales' => $salesId,
                    'nama_sales' => $salesUser ? $salesUser->nama : 'Unknown',
                    'jumlah_unit' => $items->count(),
                    'total_revenue' => $totalRevenue,
                    'total_laba' => $totalLaba,
                    'items' => $items->map(function ($item) {
                        return [
                            'id_penjualan' => $item->id_penjualan,
                            'no_invoice' => $item->no_invoice,
                            'tanggal' => $item->tanggal?->format('Y-m-d') ?? $item->tanggal,
                            'tipe_hp' => $item->stokHp->nama ?? '-',
                            'brand' => $item->stokHp->brand ?? '-',
                            'warna' => $item->stokHp->warna ?? '-',
                            'imei' => $item->stokHp->imei ?? '-',
                            'harga_jual' => (float) $item->harga_jual,
                            'diskon' => (float) $item->diskon,
                            'harga_masuk' => (float) ($item->stokHp->harga_masuk ?? 0),
                            'met_pem' => $item->met_pem ?? '-',
                            'nama_pembeli' => $item->nama_pembeli,
                        ];
                    })->values(),
                ];
            }

            usort($result, fn ($a, $b) => $b['total_revenue'] <=> $a['total_revenue']);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to fetch sales performance', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil performa sales'], 500);
        }
    }

    /**
     * Dashboard comprehensive analytics (role-scoped for owner vs sales)
     */
    public function getDashboardAnalytics(Request $request)
    {
        try {
            $month = (int) $request->input('month', Carbon::now()->month);
            $year = (int) $request->input('year', Carbon::now()->year);
            $isOwner = $request->user()?->level === 'owner';

            $currStart = Carbon::createFromDate($year, $month, 1)->startOfMonth()->toDateString();
            $currEnd = Carbon::createFromDate($year, $month, 1)->endOfMonth()->toDateString();

            $currMonth = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$currStart, $currEnd])
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_revenue,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses,
                    COALESCE(SUM(penjualan.diskon), 0) as total_discounts
                ')
                ->first();

            $soldThisMonth = (int) ($currMonth->total_sold ?? 0);

            $stockSummary = DB::table('stok_hp')
                ->where('status', 'ada')
                ->selectRaw("
                    COUNT(id_stock) as total_ready,
                    COALESCE(SUM(harga_masuk), 0) as asset_value,
                    SUM(CASE WHEN kategori = 'gadget' THEN 1 ELSE 0 END) as gadget_ready,
                    SUM(CASE WHEN kategori = 'aksesoris' THEN 1 ELSE 0 END) as aksesoris_ready,
                    SUM(CASE WHEN kategori = 'sparepart' THEN 1 ELSE 0 END) as sparepart_ready
                ")
                ->first();

            $allTime = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold_ever,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon - stok_hp.harga_masuk), 0) as total_profit_ever
                ')
                ->first();

            if (!$isOwner) {
                return response()->json([
                    'cards' => [
                        'sold_this_month' => $soldThisMonth,
                        'ready_stock_count' => (int) ($stockSummary->total_ready ?? 0),
                        'ready_gadget' => (int) ($stockSummary->gadget_ready ?? 0),
                        'ready_aksesoris' => (int) ($stockSummary->aksesoris_ready ?? 0),
                        'ready_sparepart' => (int) ($stockSummary->sparepart_ready ?? 0),
                        'total_sold_ever' => (int) ($allTime->total_sold_ever ?? 0),
                    ],
                    'trend' => null,
                    'category_breakdown' => null,
                ]);
            }

            $revenueThisMonth = (float) ($currMonth->total_revenue ?? 0);
            $expensesThisMonth = (float) ($currMonth->total_expenses ?? 0);
            $profitThisMonth = $revenueThisMonth - $expensesThisMonth;

            $lastMonthDate = Carbon::createFromDate($year, $month, 1)->subMonth();
            $lastStart = $lastMonthDate->copy()->startOfMonth()->toDateString();
            $lastEnd = $lastMonthDate->copy()->endOfMonth()->toDateString();

            $lastMonth = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$lastStart, $lastEnd])
                ->selectRaw('
                    COUNT(penjualan.id_penjualan) as total_sold,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_revenue,
                    COALESCE(SUM(stok_hp.harga_masuk), 0) as total_expenses
                ')
                ->first();

            $revenueLastMonth = (float) ($lastMonth->total_revenue ?? 0);
            $profitLastMonth = $revenueLastMonth - (float) ($lastMonth->total_expenses ?? 0);

            // 6-Month Trend (driver-agnostic aggregation)
            $sixMonthsAgo = Carbon::createFromDate($year, $month, 1)->subMonths(5)->startOfMonth()->toDateString();
            $trendRows = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$sixMonthsAgo, $currEnd])
                ->select(
                    'penjualan.tanggal',
                    'penjualan.harga_jual',
                    'penjualan.diskon',
                    'stok_hp.harga_masuk'
                )
                ->get();

            $buckets = [];
            foreach ($trendRows as $row) {
                $period = substr((string) $row->tanggal, 0, 7);
                if (!isset($buckets[$period])) {
                    $buckets[$period] = ['units' => 0, 'revenue' => 0.0, 'profit' => 0.0];
                }
                $netRev = (float) $row->harga_jual - (float) $row->diskon;
                $cost = (float) $row->harga_masuk;
                $buckets[$period]['units'] += 1;
                $buckets[$period]['revenue'] += $netRev;
                $buckets[$period]['profit'] += ($netRev - $cost);
            }

            $chartLabels = [];
            $revenueSeries = [];
            $profitSeries = [];
            $unitsSeries = [];

            for ($i = 5; $i >= 0; $i--) {
                $d = Carbon::createFromDate($year, $month, 1)->subMonths($i);
                $key = $d->format('Y-m');
                $chartLabels[] = $d->format('M Y');
                $bucket = $buckets[$key] ?? null;
                $revenueSeries[] = $bucket ? $bucket['revenue'] : 0;
                $profitSeries[] = $bucket ? $bucket['profit'] : 0;
                $unitsSeries[] = $bucket ? $bucket['units'] : 0;
            }

            $categoryStats = DB::table('penjualan')
                ->join('stok_hp', 'penjualan.id_stock', '=', 'stok_hp.id_stock')
                ->whereBetween('penjualan.tanggal', [$currStart, $currEnd])
                ->selectRaw("
                    COALESCE(stok_hp.kategori, 'gadget') as kategori,
                    COUNT(penjualan.id_penjualan) as total_qty,
                    COALESCE(SUM(penjualan.harga_jual - penjualan.diskon), 0) as total_revenue
                ")
                ->groupBy('kategori')
                ->get();

            $categoryLabelMap = [
                'gadget' => 'Smartphone',
                'aksesoris' => 'Aksesoris',
                'sparepart' => 'Sparepart',
            ];

            $catLabels = [];
            $catData = [];
            $catRevenues = [];
            foreach ($categoryStats as $stat) {
                $catLabels[] = $categoryLabelMap[$stat->kategori] ?? ucfirst((string) $stat->kategori);
                $catData[] = (int) $stat->total_qty;
                $catRevenues[] = (float) $stat->total_revenue;
            }

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
                    'ready_sparepart' => (int) ($stockSummary->sparepart_ready ?? 0),
                    'total_sold_ever' => (int) ($allTime->total_sold_ever ?? 0),
                    'total_profit_ever' => (float) ($allTime->total_profit_ever ?? 0),
                ],
                'trend' => [
                    'labels' => $chartLabels,
                    'revenue' => $revenueSeries,
                    'profit' => $profitSeries,
                    'units' => $unitsSeries,
                ],
                'category_breakdown' => [
                    'labels' => $catLabels,
                    'data' => $catData,
                    'revenues' => $catRevenues,
                    'items' => $categoryStats,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch dashboard analytics', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal mengambil analitik dashboard'], 500);
        }
    }
}
