<x-filament-panels::page>
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-primary-600">{{ $summaryMetrics['totalOfferings'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total de Ofertas</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-success-600">{{ $summaryMetrics['completedOfferings'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Doações Concluídas</div>
                <div class="text-xs text-gray-400 mt-1">{{ $summaryMetrics['completionRate'] }}% taxa de sucesso</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold text-info-600">{{ $summaryMetrics['totalQuantity'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Unidades Doadas</div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="text-center">
                <div class="text-3xl font-bold {{ $summaryMetrics['growthRate'] >= 0 ? 'text-success-600' : 'text-danger-600' }}">
                    {{ $summaryMetrics['growthRateFormatted'] }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Crescimento Mensal</div>
                <div class="text-xs text-gray-400 mt-1">{{ $summaryMetrics['offeringsThisMonth'] }} ofertas este mês</div>
            </div>
        </x-filament::section>
    </div>

    {{-- Charts Row 1 --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        {{-- Offerings Last 7 Days --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-archive-box class="h-5 w-5 text-primary-500" />
                    Ofertas - Últimos 7 dias
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="offeringsChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Requests Last 7 Days --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-clipboard-document-check class="h-5 w-5 text-warning-500" />
                    Solicitações - Últimos 7 dias
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="requestsChart"></canvas>
            </div>
        </x-filament::section>
    </div>

    {{-- Charts Row 2 --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        {{-- Status Distribution --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-chart-pie class="h-5 w-5 text-success-500" />
                    Distribuição por Status
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="statusChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Monthly Trend --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-up class="h-5 w-5 text-info-500" />
                    Tendência Mensal (6 meses)
                </div>
            </x-slot>
            <div class="h-64">
                <canvas id="monthlyChart"></canvas>
            </div>
        </x-filament::section>
    </div>

    {{-- Load Chart.js from CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDarkMode = document.documentElement.classList.contains('dark');
            const textColor = isDarkMode ? '#9ca3af' : '#6b7280';
            const gridColor = isDarkMode ? '#374151' : '#e5e7eb';

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: textColor }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: textColor },
                        grid: { color: gridColor }
                    },
                    y: {
                        ticks: { color: textColor },
                        grid: { color: gridColor },
                        beginAtZero: true
                    }
                }
            };

            // Offerings Chart
            new Chart(document.getElementById('offeringsChart'), {
                type: 'line',
                data: {
                    labels: @json($offeringsData['labels']),
                    datasets: [{
                        label: 'Ofertas',
                        data: @json($offeringsData['data']),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: commonOptions
            });

            // Requests Chart
            new Chart(document.getElementById('requestsChart'), {
                type: 'line',
                data: {
                    labels: @json($requestsData['labels']),
                    datasets: [{
                        label: 'Solicitações',
                        data: @json($requestsData['data']),
                        borderColor: '#eab308',
                        backgroundColor: 'rgba(234, 179, 8, 0.1)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: commonOptions
            });

            // Status Distribution Chart
            new Chart(document.getElementById('statusChart'), {
                type: 'doughnut',
                data: {
                    labels: @json($statusData['labels']),
                    datasets: [{
                        data: @json($statusData['data']),
                        backgroundColor: @json($statusData['colors']),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { color: textColor }
                        }
                    }
                }
            });

            // Monthly Trend Chart
            new Chart(document.getElementById('monthlyChart'), {
                type: 'bar',
                data: {
                    labels: @json($monthlyData['labels']),
                    datasets: [
                        {
                            label: 'Ofertas Criadas',
                            data: @json($monthlyData['offerings']),
                            backgroundColor: '#3b82f6'
                        },
                        {
                            label: 'Entregas Concluídas',
                            data: @json($monthlyData['completions']),
                            backgroundColor: '#22c55e'
                        }
                    ]
                },
                options: commonOptions
            });
        });
    </script>
</x-filament-panels::page>
