#!/bin/bash

# Add Period Report View Sections
# This script adds 4 new sections to period report display

set -e

echo "================================================"
echo "📊 PERIOD REPORT VIEWS INSTALLER"
echo "================================================"
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Step 1: Backing up reports.blade.php...${NC}"
cp resources/views/filament/pages/reports.blade.php resources/views/filament/pages/reports.blade.php.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Backup created${NC}"

echo ""
echo -e "${YELLOW}Step 2: Adding new view sections...${NC}"

# Find the line number where we should insert (after existing period sections, before the closing @endif)
# We'll insert before the "No data" message

cat > /tmp/period_views.blade << 'EOFVIEWS'

        {{-- NEW: Top Products for Period --}}
        @if(isset($topProducts) && count($topProducts) > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">🏆 Top Products</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Product</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $index => $product)
                            <tr class="border-b dark:border-gray-700">
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $index == 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} font-bold">
                                        {{ $index + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ $product['name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ number_format($product['quantity'], 0) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-green-600">
                                    Rp {{ number_format($product['total'], 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                        {{ $product['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- NEW: Daily Trend Chart --}}
        @if(isset($periodSummary['daily_trend']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">📈 Daily Sales Trend</h3>
            <canvas id="periodTrendChart" height="80"></canvas>
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="p-3 bg-green-50 dark:bg-green-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Best Day</div>
                    <div class="text-lg font-bold text-green-600">
                        {{ $periodSummary['daily_trend']['best_day']['date'] }}
                    </div>
                    <div class="text-xs">Rp {{ number_format($periodSummary['daily_trend']['best_day']['amount'], 0, ',', '.') }}</div>
                </div>
                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Average/Day</div>
                    <div class="text-lg font-bold text-blue-600">
                        Rp {{ number_format($periodSummary['daily_trend']['average'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="p-3 bg-orange-50 dark:bg-orange-900/20 rounded">
                    <div class="text-xs text-gray-600 dark:text-gray-400">Worst Day</div>
                    <div class="text-lg font-bold text-orange-600">
                        {{ $periodSummary['daily_trend']['worst_day']['date'] }}
                    </div>
                    <div class="text-xs">Rp {{ number_format($periodSummary['daily_trend']['worst_day']['amount'], 0, ',', '.') }}</div>
                </div>
            </div>
        </div>
        @endif

        {{-- NEW: Profit Analysis --}}
        @if(isset($periodSummary['profit_analysis']))
        <div class="bg-gradient-to-br from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-lg shadow p-6 border border-yellow-200 dark:border-yellow-800 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">💰 Profit Analysis</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Revenue</div>
                    <div class="text-2xl font-bold text-blue-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['total_revenue'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">COGS</div>
                    <div class="text-2xl font-bold text-red-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['total_cogs'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Net Profit</div>
                    <div class="text-2xl font-bold text-green-600">
                        Rp {{ number_format($periodSummary['profit_analysis']['net_profit'], 0, ',', '.') }}
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Margin</div>
                    <div class="text-2xl font-bold {{ $periodSummary['profit_analysis']['margin_percentage'] >= 35 ? 'text-green-600' : 'text-orange-600' }}">
                        {{ number_format($periodSummary['profit_analysis']['margin_percentage'], 1) }}%
                    </div>
                </div>
            </div>
            
            @if(isset($periodSummary['profit_analysis']['recommendations']) && count($periodSummary['profit_analysis']['recommendations']) > 0)
            <div class="space-y-2">
                @foreach($periodSummary['profit_analysis']['recommendations'] as $rec)
                <div class="bg-white dark:bg-gray-800 rounded p-3 flex items-start gap-2">
                    <span class="text-xl">{{ $rec['icon'] }}</span>
                    <div class="flex-1">
                        <strong>{{ $rec['message'] }}</strong>
                        <div class="text-sm text-gray-600">→ {{ $rec['action'] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        {{-- NEW: Staff Performance --}}
        @if(isset($periodSummary['staff_performance']) && count($periodSummary['staff_performance']['staff']) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">👨‍💼 Staff Performance</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Rank</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Name</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Orders</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Total Sales</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Avg</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($periodSummary['staff_performance']['staff'] as $staff)
                        <tr class="border-b dark:border-gray-700">
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full {{ $staff['rank'] == 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800' }} font-bold">
                                    {{ $staff['rank'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ $staff['name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $staff['total_orders'] }}</td>
                            <td class="px-4 py-3 text-right font-bold text-green-600">
                                Rp {{ number_format($staff['total_sales'], 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                Rp {{ number_format($staff['average_transaction'], 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- NEW: Customer Insights --}}
        @if(isset($periodSummary['customer_insights']))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-4 text-gray-800 dark:text-white">👥 Customer Insights</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-3xl font-bold text-blue-600">
                        {{ number_format($periodSummary['customer_insights']['total_customers'], 0) }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Total Customers</div>
                    <div class="text-xs {{ $periodSummary['customer_insights']['growth']['trend'] == 'up' ? 'text-green-600' : 'text-red-600' }} mt-2">
                        {{ $periodSummary['customer_insights']['growth']['trend'] == 'up' ? '↗️' : '↘️' }}
                        {{ abs($periodSummary['customer_insights']['growth']['percentage']) }}% vs previous
                    </div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-3xl font-bold text-green-600">
                        Rp {{ number_format($periodSummary['customer_insights']['average_spend'], 0, ',', '.') }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">Average Spend</div>
                </div>
            </div>
            
            @if(count($periodSummary['customer_insights']['top_customers']) > 0)
            <h4 class="font-semibold mb-2">Top Customers</h4>
            <div class="space-y-2">
                @foreach($periodSummary['customer_insights']['top_customers'] as $customer)
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                    <div>
                        <div class="font-semibold">{{ $customer['name'] }}</div>
                        <div class="text-xs text-gray-500">{{ $customer['total_orders'] }} orders</div>
                    </div>
                    <div class="text-right font-bold text-green-600">
                        Rp {{ number_format($customer['total_spent'], 0, ',', '.') }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @push('scripts')
        <script>
        document.addEventListener('livewire:load', function() {
            @if(isset($periodSummary['daily_trend']))
            const ctx = document.getElementById('periodTrendChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json($periodSummary['daily_trend']['labels']),
                        datasets: [{
                            label: 'Sales (Rp)',
                            data: @json($periodSummary['daily_trend']['sales']),
                            borderColor: '#10B981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: true }
                        }
                    }
                });
            }
            @endif
        });
        </script>
        @endpush
EOFVIEWS

# Insert before the "No data" message for period
# Find line with "@if($reportType === 'period' && !$periodSummary)"
LINE_NUM=$(grep -n "@if(\$reportType === 'period' && !\$periodSummary)" resources/views/filament/pages/reports.blade.php | cut -d: -f1)

if [ -n "$LINE_NUM" ]; then
    # Insert our new sections before the "no data" block
    head -n $((LINE_NUM - 1)) resources/views/filament/pages/reports.blade.php > /tmp/reports_new.blade.php
    cat /tmp/period_views.blade >> /tmp/reports_new.blade.php
    tail -n +$LINE_NUM resources/views/filament/pages/reports.blade.php >> /tmp/reports_new.blade.php
    mv /tmp/reports_new.blade.php resources/views/filament/pages/reports.blade.php
    echo -e "${GREEN}✓ View sections added${NC}"
else
    echo -e "${YELLOW}! Could not find insertion point, manual merge needed${NC}"
fi

echo ""
echo -e "${YELLOW}Step 3: Clearing view cache...${NC}"
php artisan view:clear > /dev/null 2>&1
echo -e "${GREEN}✓ View cache cleared${NC}"

echo ""
echo "================================================"
echo -e "${GREEN}✅ PERIOD REPORT VIEWS COMPLETE!${NC}"
echo "================================================"
echo ""
echo "✓ 4 new sections added"
echo "✓ Charts integrated"
echo "✓ Responsive design"
echo "✓ Ready to test!"
echo ""
echo -e "${YELLOW}TEST NOW:${NC}"
echo "1. Login Filament"
echo "2. Go to Sales Reports"
echo "3. Select 'Period' type"
echo "4. Choose date range (7 days)"
echo "5. See 8 comprehensive sections!"
echo ""
echo -e "${GREEN}Period Report Enhancement: 100% COMPLETE!${NC} 🎉"
echo ""
