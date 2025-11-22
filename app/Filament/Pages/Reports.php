<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DailyReportExport;
use Filament\Notifications\Notification;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    
    protected static ?string $navigationLabel = 'Sales Reports';
    
    protected static ?string $navigationGroup = 'Reports';
    
    protected static ?string $title = 'Sales Reports';
    
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.reports';

    // Authorization
    public static function canAccess(): bool
    {
        return auth()->user()->hasPermission('view_reports');
    }
    
    public $reportType = 'daily';
    public $selectedDate;
    public $startDate;
    public $endDate;
    
    public $dailySummary = null;
    public $periodSummary = null;
    public $topProducts = [];
    
    public function mount(): void
    {
        $this->selectedDate = Carbon::today()->format('Y-m-d');
        $this->startDate = Carbon::today()->startOfMonth()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
        
        $this->loadDailyReport();
    }
    
    protected function getReportService()
    {
        return app(ReportService::class);
    }
    
    public function loadDailyReport()
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            $this->dailySummary = null;
            return;
        }
        
        try {
            $service = $this->getReportService();
            $this->dailySummary = $service->getDailySummary($tenantId, $this->selectedDate);
            $this->topProducts = $service->getTopProducts($tenantId, $this->selectedDate, $this->selectedDate, 10);
        } catch (\Exception $e) {
            \Log::error('Load daily report error', ['error' => $e->getMessage()]);
            $this->dailySummary = null;
            $this->topProducts = [];
        }
    }
    
    public function loadPeriodReport()
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            $this->periodSummary = null;
            return;
        }
        
        try {
            $service = $this->getReportService();
            $this->periodSummary = $service->getPeriodSummary($tenantId, $this->startDate, $this->endDate);
            $this->topProducts = $service->getTopProducts($tenantId, $this->startDate, $this->endDate, 10);
        } catch (\Exception $e) {
            \Log::error('Load period report error', ['error' => $e->getMessage()]);
            $this->periodSummary = null;
            $this->topProducts = [];
        }
    }
    
    public function updatedReportType()
    {
        if ($this->reportType === 'daily') {
            $this->loadDailyReport();
        } else {
            $this->loadPeriodReport();
        }
    }
    
    public function updatedSelectedDate()
    {
        $this->loadDailyReport();
    }
    
    public function updatedStartDate()
    {
        $this->loadPeriodReport();
    }
    
    public function updatedEndDate()
    {
        $this->loadPeriodReport();
    }
    
    public function generateCache()
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) {
            return;
        }
        
        try {
            $service = $this->getReportService();
            $service->generateDailySummary($tenantId, $this->selectedDate, true);
            $this->loadDailyReport();
            
            \Filament\Notifications\Notification::make()
                ->title('Cache berhasil di-generate')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal generate cache')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getTenantId()
    {
        if (auth()->check()) {
            $userId = auth()->id();
            $user = DB::table('users')->where('id', $userId)->first();
            if ($user && $user->tenant_id) {
                return $user->tenant_id;
            }
        }
        return null;
    }
    
    protected function getViewData(): array
    {
        return [
            'dailySummary' => $this->dailySummary,
            'periodSummary' => $this->periodSummary,
            'topProducts' => $this->topProducts,
            'salesTrendData' => $this->getSalesTrendData(),
            'paymentChartData' => $this->getPaymentChartData(),
        ];
    }
    
    // Chart Data Methods
    protected function getSalesTrendData()
    {
        $tenantId = auth()->user()->tenant_id;
        
        if ($this->reportType === 'daily') {
            // Get last 7 days for daily report
            $endDate = Carbon::parse($this->selectedDate);
            $startDate = $endDate->copy()->subDays(6);
            
            $trend = $this->getReportService()->getSalesTrend(
                $tenantId,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'daily'
            );
            
            return [
                'labels' => collect($trend)->pluck('label')->toArray(),
                'data' => collect($trend)->pluck('amount')->toArray(),
            ];
        } else {
            // Get daily breakdown for period
            $startDate = Carbon::parse($this->startDate);
            $endDate = Carbon::parse($this->endDate);
            
            $trend = $this->getReportService()->getSalesTrend(
                $tenantId,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
                'daily'
            );
            
            return [
                'labels' => collect($trend)->pluck('label')->toArray(),
                'data' => collect($trend)->pluck('amount')->toArray(),
            ];
        }
    }
    
    protected function getPaymentChartData()
    {
        $breakdown = $this->reportType === 'daily' 
            ? ($this->dailySummary['payment_breakdown'] ?? [])
            : ($this->periodSummary['payment_breakdown'] ?? []);
        
        // Filter out payment methods with 0 amount
        $breakdown = collect($breakdown)->filter(fn($item) => $item['amount'] > 0)->values()->toArray();
            
        return [
            'labels' => collect($breakdown)->pluck('method')->map(fn($m) => strtoupper($m))->toArray(),
            'data' => collect($breakdown)->pluck('amount')->toArray(),
            'percentages' => collect($breakdown)->pluck('percentage')->toArray(),
        ];
    }
    
    // Export Methods
    public function exportPdf()
    {
        try {
            $data = $this->reportType === 'daily' ? $this->dailySummary : $this->periodSummary;
            
            if (!$data) {
                Notification::make()
                    ->title('Tidak ada data')
                    ->body('Tidak ada data untuk diekspor.')
                    ->warning()
                    ->send();
                return;
            }
            
            // Use different template based on report type
            $template = $this->reportType === 'daily' ? 'reports.daily-pdf' : 'reports.period-pdf';
            
            $pdf = Pdf::loadView($template, [
                'type' => $this->reportType,
                'data' => $data,
                'products' => $this->topProducts,
                'date' => $this->selectedDate,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]);
            
            $filename = 'laporan-' . ($this->reportType === 'daily' ? $this->selectedDate : $this->startDate . '_' . $this->endDate) . '.pdf';
            
            return response()->streamDownload(function() use ($pdf) {
                echo $pdf->output();
            }, $filename);
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal membuat PDF: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    public function exportExcel()
    {
        try {
            $data = $this->reportType === 'daily' ? $this->dailySummary : $this->periodSummary;
            
            if (!$data) {
                Notification::make()
                    ->title('Tidak ada data')
                    ->body('Tidak ada data untuk diekspor.')
                    ->warning()
                    ->send();
                return;
            }
            
            $filename = 'laporan-' . ($this->reportType === 'daily' ? $this->selectedDate : $this->startDate . '_' . $this->endDate) . '.xlsx';
            
            return Excel::download(
                new DailyReportExport($data, $this->topProducts),
                $filename
            );
            
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('Gagal membuat Excel: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
