@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $columns = [
        'me_mfo' => 'ME MFO',
        'me_hsd' => 'ME HSD',
        'ae_mfo' => 'AE MFO',
        'ae_hsd' => 'AE HSD',
        'boiler_hsd' => 'Boiler HSD',
        'boiler_mfo' => 'Boiler MFO',
        'genset_consum_hsd' => 'Genset Consumption',
    ];
@endphp

<style>
    [x-cloak] { display: none !important; }
</style>

<div class="container mx-auto px-4 py-6" x-data="dashboardComponent()">
    <div class="rounded-lg bg-white shadow-md">
        <div class="border-b bg-gray-50 px-4 py-4">
            <h1 class="text-xl font-bold">Dashboard Konsumsi Bulanan</h1>
        </div>

        <div class="p-6">
            <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label for="report_month" class="mb-1 block text-sm font-medium text-gray-700">
                        Bulan Laporan
                    </label>
                    <input type="month" id="report_month" name="report_month"
                        value="{{ $reportMonth }}"
                        class="w-64 rounded-md border border-gray-300 px-4 py-2">
                    @error('report_month')
                        <p class="mt-1 text-sm text-red-600">Format bulan tidak valid.</p>
                    @enderror
                </div>
                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                    Tampilkan
                </button>
                <a href="{{ route('dashboard.export', ['report_month' => $reportMonth]) }}"
                   target="_blank"
                   class="rounded-md bg-green-600 px-4 py-2 text-white hover:bg-green-700 inline-flex items-center gap-1.5 shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download</span>
                </a>
            </form>

            <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-lg bg-amber-500 px-5 py-4 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-100">Total Konsumsi MFO</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">
                        <span x-text="formatNumber(mainSummary.total_mfo)"></span> <span class="text-2xl font-semibold text-white">L</span>
                    </p>
                </div>

                <div class="rounded-lg bg-blue-600 px-5 py-4 text-white shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-100">Total Konsumsi HSD</p>
                    <p class="mt-1 text-2xl font-bold tabular-nums">
                        <span x-text="formatNumber(mainSummary.total_hsd)"></span> <span class="text-2xl font-semibold text-white">L</span>
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h4 class="text-sm font-semibold text-gray-800">Proporsi Penggunaan MFO</h4>
                        <span class="text-xs text-gray-400">Bulanan</span>
                    </div>
                    <div class="mt-3 flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                        <div class="relative h-40 w-40 flex-shrink-0">
                            <canvas id="mainMfoChart"></canvas>
                        </div>
                        <div class="w-full flex-1 overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-[11px] text-gray-400">
                                        <th class="pb-1 text-left font-medium">Jenis</th>
                                        <th class="pb-1 text-right font-medium">Konsumsi</th>
                                        <th class="pb-1 text-right font-medium">Porsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-gray-700">
                                    <tr>
                                        <td class="py-1.5 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#F59E0B] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">Boiler MFO</span>
                                        </td>
                                        <td class="py-1.5 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.mfo_breakdown.boiler)"></span> L
                                        </td>
                                        <td class="py-1.5 text-right font-bold text-amber-600 whitespace-nowrap" x-text="getPercentage(mainSummary.mfo_breakdown.boiler, mainSummary.total_mfo)"></td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#2563EB] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">ME MFO</span>
                                        </td>
                                        <td class="py-1.5 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.mfo_breakdown.me)"></span> L
                                        </td>
                                        <td class="py-1.5 text-right font-bold text-blue-600 whitespace-nowrap" x-text="getPercentage(mainSummary.mfo_breakdown.me, mainSummary.total_mfo)"></td>
                                    </tr>
                                    <tr>
                                        <td class="py-1.5 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#10B981] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">AE MFO</span>
                                        </td>
                                        <td class="py-1.5 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.mfo_breakdown.ae)"></span> L
                                        </td>
                                        <td class="py-1.5 text-right font-bold text-emerald-600 whitespace-nowrap" x-text="getPercentage(mainSummary.mfo_breakdown.ae, mainSummary.total_mfo)"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                        <h4 class="text-sm font-semibold text-gray-800">Proporsi Penggunaan HSD</h4>
                        <span class="text-xs text-gray-400">Bulanan</span>
                    </div>
                    <div class="mt-3 flex flex-col items-center gap-4 sm:flex-row sm:items-center">
                        <div class="relative h-40 w-40 flex-shrink-0">
                            <canvas id="mainHsdChart"></canvas>
                        </div>
                        <div class="w-full flex-1 overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="border-b border-gray-100 text-[11px] text-gray-400">
                                        <th class="pb-1 text-left font-medium">Jenis</th>
                                        <th class="pb-1 text-right font-medium">Konsumsi</th>
                                        <th class="pb-1 text-right font-medium">Porsi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-gray-700">
                                    <tr>
                                        <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#EA580C] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">Boiler HSD</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.hsd_breakdown.boiler)"></span> L
                                        </td>
                                        <td class="py-1 text-right font-bold text-orange-600 whitespace-nowrap" x-text="getPercentage(mainSummary.hsd_breakdown.boiler, mainSummary.total_hsd)"></td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#2563EB] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">ME HSD</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.hsd_breakdown.me)"></span> L
                                        </td>
                                        <td class="py-1 text-right font-bold text-blue-600 whitespace-nowrap" x-text="getPercentage(mainSummary.hsd_breakdown.me, mainSummary.total_hsd)"></td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#10B981] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">AE HSD</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.hsd_breakdown.ae)"></span> L
                                        </td>
                                        <td class="py-1 text-right font-bold text-emerald-600 whitespace-nowrap" x-text="getPercentage(mainSummary.hsd_breakdown.ae, mainSummary.total_hsd)"></td>
                                    </tr>
                                    <tr>
                                        <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                            <span class="h-2.5 w-2.5 rounded-full bg-[#7C3AED] flex-shrink-0"></span>
                                            <span class="font-medium text-gray-900">Genset</span>
                                        </td>
                                        <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                            <span x-text="formatNumber(mainSummary.hsd_breakdown.genset)"></span> L
                                        </td>
                                        <td class="py-1 text-right font-bold text-purple-600 whitespace-nowrap" x-text="getPercentage(mainSummary.hsd_breakdown.genset, mainSummary.total_hsd)"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-xl font-semibold">Konsumsi {{ $reportMonthLabel }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $consumptions->count() }} vessel tercatat.</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        @if($lastSyncedAt)
                            <p class="text-sm text-gray-500">
                                Data terakhir disinkronkan: {{ $lastSyncedAt->format('d M Y H:i') }} WIB
                            </p>
                        @endif
                        <input type="text" x-model="search" placeholder="Filter Vessel ID..." class="border border-gray-300 rounded-md px-3 py-2 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                    </div>
                </div>

                @if($consumptions->isEmpty())
                    <p class="mt-6 rounded-md bg-gray-50 p-4 text-gray-500">
                        Belum ada data konsumsi untuk bulan yang dipilih.
                    </p>
                @else
                    <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                <tr>
                                    <th scope="col" class="whitespace-nowrap px-4 py-3">Vessel ID</th>
                                    @foreach($columns as $label)
                                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-right">{{ $label }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                @foreach($consumptions as $consumption)
                                    <tr x-show="search === '' || '{{ strtolower(addslashes($consumption->vessel_id)) }}'.includes(search.toLowerCase())" class="hover:bg-gray-50">
                                        <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 cursor-pointer hover:text-blue-600 hover:underline"
                                            @click="openDailyModal('{{ $consumption->vessel_id }}')">
                                            {{ $consumption->vessel_id }}
                                        </td>
                                        @foreach($columns as $column => $label)
                                            <td id="cell-{{ $consumption->vessel_id }}-{{ $column }}"
                                                @click="openDailyModal('{{ $consumption->vessel_id }}')"
                                                class="whitespace-nowrap px-4 py-3 text-right cursor-pointer hover:bg-blue-100 hover:text-blue-700 font-medium transition-colors">
                                                <span x-text="formatNumber(@js($consumption->{$column}))"></span>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-blue-50 font-semibold text-gray-800">
                                <tr>
                                    <th scope="row" class="whitespace-nowrap px-4 py-3 text-left">Total</th>
                                    @foreach($columns as $column => $label)
                                        <td id="footer-total-{{ $column }}" class="whitespace-nowrap px-4 py-3 text-right">
                                            <span x-text="formatNumber(@js($totalConsumption[$column]))"></span>
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL DETAIL KONSUMSI HARIAN -->
    <div x-show="modalOpen"
         x-cloak
         @keydown.escape.window="closeDailyModal()"
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
             @click="closeDailyModal()"></div>

        <!-- Modal Dialog -->
        <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-7xl overflow-hidden max-h-[90vh] flex flex-col z-10"
             @click.stop>
            <!-- Header -->
            <div class="bg-gray-800 text-white px-6 py-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">Detail Konsumsi Harian</p>
                    <div class="flex items-center gap-2">
                        <h3 class="font-bold text-lg" x-text="vesselId"></h3>
                        <span class="text-gray-400">&bull;</span>
                        <span class="text-sm font-medium text-gray-300" x-text="reportMonthLabel || '{{ $reportMonthLabel }}'"></span>
                    </div>
                </div>
                <button type="button" @click="closeDailyModal()"
                        class="text-gray-400 hover:text-white transition-colors p-1.5 rounded-lg focus:outline-none"
                        aria-label="Tutup modal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Loading State -->
                <div x-show="loading" class="py-12 flex flex-col items-center justify-center text-gray-500">
                    <div class="w-10 h-10 border-4 border-gray-200 border-t-blue-600 rounded-full animate-spin"></div>
                    <p class="mt-3 text-sm font-medium">Memuat data konsumsi...</p>
                </div>

                <!-- Error State -->
                <div x-show="!loading && error" class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                    <p class="text-sm text-red-600" x-text="error"></p>
                    <button type="button"
                            @click="openDailyModal(vesselId)"
                            class="mt-2 inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded bg-red-600 text-white hover:bg-red-700">
                        Coba Lagi
                    </button>
                </div>

                <!-- Content State -->
                <div x-show="!loading && !error">
                    <!-- Success Toast Message -->
                    <div x-show="successMessage" x-cloak
                         class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 text-xs rounded-lg flex items-center justify-between shadow-sm">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-600 text-sm"></i>
                            <span x-text="successMessage" class="font-medium"></span>
                        </div>
                        <button type="button" @click="successMessage = ''" class="text-green-600 hover:text-green-800 font-bold px-1 text-base leading-none">&times;</button>
                    </div>

                    <!-- Empty State -->
                    <div x-show="dailyRows.length === 0" class="py-8 text-center text-gray-500 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                        <p class="text-sm">Tidak ada data konsumsi harian untuk kapal ini pada bulan yang dipilih.</p>
                    </div>

                    <div x-show="dailyRows.length > 0" class="mb-6 grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div class="rounded-lg bg-amber-500 px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-amber-100">Total Konsumsi MFO</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">
                                <span x-text="formatNumber(modalSummary.total_mfo)"></span> <span class="text-m font-semibold text-amber-100">L</span>
                            </p>
                        </div>

                        <div class="rounded-lg bg-blue-600 px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-blue-100">Total Konsumsi HSD</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">
                                <span x-text="formatNumber(modalSummary.total_hsd)"></span> <span class="text-m font-semibold text-blue-100">L</span>
                            </p>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-center justify-between border-b border-gray-200/70 pb-1.5">
                                <h5 class="text-xs font-semibold text-gray-700">Proporsi MFO</h5>
                                <span class="text-[11px] text-gray-400">Akumulasi harian</span>
                            </div>
                            <div class="mt-2.5 flex flex-col items-center gap-3 sm:flex-row sm:items-center">
                                <div class="relative h-36 w-36 flex-shrink-0">
                                    <canvas id="modalMfoChart"></canvas>
                                </div>
                                <div class="w-full flex-1 overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-[10px] text-gray-500">
                                                <th class="pb-1 text-left font-medium">Jenis</th>
                                                <th class="pb-1 text-right font-medium">Konsumsi</th>
                                                <th class="pb-1 text-right font-medium">Porsi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200/60 text-gray-700">
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#F59E0B] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">Boiler</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.mfo_breakdown.boiler)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-amber-600 whitespace-nowrap" x-text="getPercentage(modalSummary.mfo_breakdown.boiler, modalSummary.total_mfo)"></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#2563EB] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">ME</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.mfo_breakdown.me)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-blue-600 whitespace-nowrap" x-text="getPercentage(modalSummary.mfo_breakdown.me, modalSummary.total_mfo)"></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#10B981] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">AE</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.mfo_breakdown.ae)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-emerald-600 whitespace-nowrap" x-text="getPercentage(modalSummary.mfo_breakdown.ae, modalSummary.total_mfo)"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                            <div class="flex items-center justify-between border-b border-gray-200/70 pb-1.5">
                                <h5 class="text-xs font-semibold text-gray-700">Proporsi HSD</h5>
                                <span class="text-[11px] text-gray-400">Akumulasi harian</span>
                            </div>
                            <div class="mt-2.5 flex flex-col items-center gap-3 sm:flex-row sm:items-center">
                                <div class="relative h-36 w-36 flex-shrink-0">
                                    <canvas id="modalHsdChart"></canvas>
                                </div>
                                <div class="w-full flex-1 overflow-x-auto">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-[10px] text-gray-500">
                                                <th class="pb-1 text-left font-medium">Peralatan</th>
                                                <th class="pb-1 text-right font-medium">Konsumsi</th>
                                                <th class="pb-1 text-right font-medium">Porsi</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200/60 text-gray-700">
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#EA580C] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">Boiler</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.hsd_breakdown.boiler)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-orange-600 whitespace-nowrap" x-text="getPercentage(modalSummary.hsd_breakdown.boiler, modalSummary.total_hsd)"></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#2563EB] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">ME</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.hsd_breakdown.me)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-blue-600 whitespace-nowrap" x-text="getPercentage(modalSummary.hsd_breakdown.me, modalSummary.total_hsd)"></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#10B981] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">AE</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.hsd_breakdown.ae)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-emerald-600 whitespace-nowrap" x-text="getPercentage(modalSummary.hsd_breakdown.ae, modalSummary.total_hsd)"></td>
                                            </tr>
                                            <tr>
                                                <td class="py-1 flex items-center gap-1.5 whitespace-nowrap">
                                                    <span class="h-2 w-2 rounded-full bg-[#7C3AED] flex-shrink-0"></span>
                                                    <span class="font-medium text-gray-900">Genset</span>
                                                </td>
                                                <td class="py-1 text-right font-semibold text-gray-900 whitespace-nowrap">
                                                    <span x-text="formatNumber(modalSummary.hsd_breakdown.genset)"></span> L
                                                </td>
                                                <td class="py-1 text-right font-bold text-purple-600 whitespace-nowrap" x-text="getPercentage(modalSummary.hsd_breakdown.genset, modalSummary.total_hsd)"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Performance Metrics (Average ME & AE) -->
                    <div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-indigo-600 px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-indigo-100">Avg ME (L / Jam)</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">
                                <span x-text="formatNumber(modalAverages.avg_me_per_hour)"></span> <span class="text-base font-semibold text-indigo-100">L/hr</span>
                            </p>
                            <p class="mt-0.5 text-[11px] text-indigo-200">
                                Eff. Steam: <span class="font-medium text-white" x-text="formatNumber(modalAverages.total_steam_time)"></span> Jam
                            </p>
                        </div>

                        <div class="rounded-lg bg-purple-600 px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-purple-100">Avg ME (L / Hari)</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">
                                <span x-text="formatNumber(modalAverages.avg_me_per_day)"></span> <span class="text-base font-semibold text-purple-100">L/day</span>
                            </p>
                            <p class="mt-0.5 text-[11px] text-purple-200">
                                Total ME: <span class="font-medium text-white" x-text="formatNumber(modalAverages.total_me)"></span> L
                            </p>
                        </div>

                        <div class="rounded-lg bg-emerald-600 px-4 py-3 text-white">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-emerald-100">Avg AE + Genset (L / Hari)</p>
                            <p class="mt-1 text-xl font-bold tabular-nums">
                                <span x-text="formatNumber(modalAverages.avg_ae_per_day)"></span> <span class="text-base font-semibold text-emerald-100">L/day</span>
                            </p>
                            <p class="mt-0.5 text-[11px] text-emerald-200">
                                Total AE: <span class="font-medium text-white" x-text="formatNumber(modalAverages.total_ae)"></span> L / <span class="font-medium text-white" x-text="modalAverages.total_days"></span> Hari
                            </p>
                        </div>
                    </div>

                    <!-- Daily Breakdown Table (Unified All Fuels + Work Logs) -->
                    <div x-show="dailyRows.length > 0" class="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 font-semibold uppercase tracking-wider text-gray-600 text-[12px]">
                                <tr>
                                    <th scope="col" class="px-3 py-3 text-center w-10">No</th>
                                    <th scope="col" class="px-3 py-3 text-left whitespace-nowrap">Tanggal</th>
                                    <th scope="col" class="px-3 py-3 text-center whitespace-nowrap">Posisi</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap bg-indigo-50/50 text-indigo-900 font-bold">Steam Time (hr)</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">ME MFO</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">ME HSD</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">AE MFO</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">AE HSD</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Boiler HSD</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Boiler MFO</th>
                                    <th scope="col" class="px-3 py-3 text-right whitespace-nowrap">Genset Consump.</th>
                                    <th scope="col" class="px-3 py-3 text-left min-w-[180px] max-w-xs whitespace-normal">Remarks</th>
                                    <th scope="col" class="px-3 py-3 text-left min-w-[200px] max-w-sm whitespace-normal">Engine Daily Work</th>
                                    <th scope="col" class="px-3 py-3 text-left min-w-[200px] max-w-sm whitespace-normal">Deck Daily Work</th>
                                    <template x-if="canEdit">
                                        <th scope="col" class="px-3 py-3 text-center w-24 whitespace-nowrap">Aksi</th>
                                    </template>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                <template x-for="(row, idx) in dailyRows" :key="getRowKey(row)">
                                    <tr class="hover:bg-gray-50 transition-colors" :class="editingRowKey === getRowKey(row) ? 'bg-amber-50/70 ring-1 ring-amber-300' : ''">
                                        <td class="px-3 py-2 text-center text-gray-400 text-[11px]" x-text="idx + 1"></td>
                                        <td class="px-3 py-2 font-medium whitespace-nowrap text-gray-900" x-text="row.date_label"></td>
                                        <td class="px-3 py-2 text-center whitespace-nowrap">
                                            <span :class="row.session_type === 'port' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                                  class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider inline-block"
                                                  x-text="row.position_label"></span>
                                        </td>

                                        <!-- Steam Time (Read-only, At Sea only) -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium text-indigo-950 bg-indigo-50/20">
                                            <span x-text="row.session_type === 'sea' ? formatNumber(row.steam_time) : '-'"></span>
                                        </td>

                                        <!-- ME MFO -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.me_mfo)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.me_mfo"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- ME HSD -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.me_hsd)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.me_hsd"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- AE MFO -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.ae_mfo)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.ae_mfo"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- AE HSD -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.ae_hsd)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.ae_hsd"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- Boiler HSD -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.boiler_hsd)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.boiler_hsd"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- Boiler MFO -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.boiler_mfo)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.boiler_mfo"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- Genset Consumption -->
                                        <td class="px-3 py-2 text-right whitespace-nowrap font-medium">
                                            <template x-if="editingRowKey !== getRowKey(row)">
                                                <span x-text="formatNumber(row.genset_consum_hsd)"></span>
                                            </template>
                                            <template x-if="editingRowKey === getRowKey(row)">
                                                <input type="number" step="any" min="0" x-model.number="editRowForm.genset_consum_hsd"
                                                       class="w-20 text-right px-1.5 py-1 text-xs border border-gray-300 rounded focus:ring-1 focus:ring-blue-500 bg-white">
                                            </template>
                                        </td>

                                        <!-- Remarks -->
                                        <td class="px-3 py-2 text-left text-gray-600 min-w-[180px] max-w-xs whitespace-pre-line text-[11px]">
                                            <span x-text="row.remarks"></span>
                                        </td>

                                        <!-- Engine Daily Work -->
                                        <td class="px-3 py-2 text-left text-gray-600 min-w-[200px] max-w-sm whitespace-pre-line text-[11px]">
                                            <span x-text="row.engine_daily_work"></span>
                                        </td>

                                        <!-- Deck Daily Work -->
                                        <td class="px-3 py-2 text-left text-gray-600 min-w-[200px] max-w-sm whitespace-pre-line text-[11px]">
                                            <span x-text="row.deck_daily_work"></span>
                                        </td>

                                        <!-- Action Column (Admin Only) -->
                                        <template x-if="canEdit">
                                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                                <template x-if="editingRowKey !== getRowKey(row)">
                                                    <button type="button" @click="startEdit(row)"
                                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded transition-colors"
                                                            title="Edit baris">
                                                        <i class="fas fa-edit"></i>
                                                        <span>Edit</span>
                                                    </button>
                                                </template>
                                                <template x-if="editingRowKey === getRowKey(row)">
                                                    <div class="inline-flex items-center gap-1.5">
                                                        <button type="button" @click="saveRow(row)" :disabled="editRowForm.saving"
                                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded bg-green-600 text-white hover:bg-green-700 disabled:opacity-50 transition-colors shadow-sm"
                                                                title="Simpan perubahan">
                                                            <template x-if="!editRowForm.saving">
                                                                <span class="flex items-center gap-1"><i class="fas fa-check"></i> Simpan</span>
                                                            </template>
                                                            <template x-if="editRowForm.saving">
                                                                <span class="flex items-center gap-1"><i class="fas fa-spinner fa-spin"></i></span>
                                                            </template>
                                                        </button>
                                                        <button type="button" @click="cancelEdit()" :disabled="editRowForm.saving"
                                                                class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-200 text-gray-700 hover:bg-gray-300 disabled:opacity-50 transition-colors"
                                                                title="Batal">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                            </td>
                                        </template>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-blue-50 font-semibold text-gray-800 border-t-2 border-blue-200">
                                <tr>
                                    <th scope="row" colspan="3" class="px-3 py-3 text-left">Total Akumulasi</th>
                                    <td class="px-3 py-3 text-right text-indigo-950 font-bold bg-indigo-50/40" x-text="formatNumber(totals.steam_time ? totals.steam_time.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.me_mfo ? totals.me_mfo.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.me_hsd ? totals.me_hsd.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.ae_mfo ? totals.ae_mfo.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.ae_hsd ? totals.ae_hsd.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.boiler_hsd ? totals.boiler_hsd.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.boiler_mfo ? totals.boiler_mfo.total : 0)"></td>
                                    <td class="px-3 py-3 text-right text-blue-950 font-bold" x-text="formatNumber(totals.genset_consum_hsd ? totals.genset_consum_hsd.total : 0)"></td>
                                    <td colspan="3" class="px-3 py-3"></td>
                                    <template x-if="canEdit">
                                        <td class="px-3 py-3"></td>
                                    </template>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex justify-end gap-3">
                <a :href="`{{ route('dashboard.daily-detail.export') }}?vessel_id=${encodeURIComponent(vesselId)}&report_month=${encodeURIComponent('{{ $reportMonth }}')}`"
                    target="_blank"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-semibold rounded-md bg-green-600 text-white hover:bg-green-700 transition-colors shadow-sm"
                    download>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Download</span>
                </a> 
                <button type="button" @click="closeDailyModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:ay-100 transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function dashboardComponent() {
        return {
            search: '',
            modalOpen: false,
            loading: false,
            error: null,
            vesselId: '',
            canEdit: {{ auth()->check() && auth()->user()->can('admin-access') ? 'true' : 'false' }},
            editingRowKey: null,
            editRowForm: {},
            successMessage: '',
            fuelColumns: {
                'me_mfo': 'ME MFO',
                'me_hsd': 'ME HSD',
                'ae_mfo': 'AE MFO',
                'ae_hsd': 'AE HSD',
                'boiler_hsd': 'Boiler HSD',
                'boiler_mfo': 'Boiler MFO',
                'genset_consum_hsd': 'Genset Consumption'
            },
            dailyRows: [],
            totals: {},
            reportMonthLabel: '',

            // Main and Modal Summaries
            mainSummary: {
                total_mfo: @js($totalMfo),
                total_hsd: @js($totalHsd),
                mfo_breakdown: @js($mfoBreakdown),
                hsd_breakdown: @js($hsdBreakdown)
            },
            modalSummary: {
                total_mfo: 0,
                total_hsd: 0,
                mfo_breakdown: { boiler: 0, me: 0, ae: 0 },
                hsd_breakdown: { boiler: 0, me: 0, ae: 0, genset: 0 }
            },
            modalAverages: {
                total_steam_time: 0,
                total_days: 0,
                total_me: 0,
                total_ae: 0,
                avg_me_per_hour: 0,
                avg_me_per_day: 0,
                avg_ae_per_day: 0
            },

            // Chart instances
            mainMfoChartInstance: null,
            mainHsdChartInstance: null,
            modalMfoChartInstance: null,
            modalHsdChartInstance: null,

            init() {
                this.$nextTick(() => {
                    this.renderMainCharts();
                });
            },

            openDailyModal(vessel) {
                this.vesselId = vessel;
                this.modalOpen = true;
                this.loading = true;
                this.error = null;
                this.editingRowKey = null;
                this.editRowForm = {};
                this.successMessage = '';
                this.dailyRows = [];
                this.totals = {};
                this.modalSummary = {
                    total_mfo: 0,
                    total_hsd: 0,
                    mfo_breakdown: { boiler: 0, me: 0, ae: 0 },
                    hsd_breakdown: { boiler: 0, me: 0, ae: 0, genset: 0 }
                };
                this.modalAverages = {
                    total_steam_time: 0,
                    total_days: 0,
                    total_me: 0,
                    total_ae: 0,
                    avg_me_per_hour: 0,
                    avg_me_per_day: 0,
                    avg_ae_per_day: 0
                };

                const reportMonth = '{{ $reportMonth }}';
                axios.get('{{ route("dashboard.daily-detail") }}', {
                    params: {
                        vessel_id: vessel,
                        report_month: reportMonth
                    }
                })
                .then(res => {
                    this.dailyRows = res.data.daily_rows || [];
                    this.totals = res.data.totals || {};
                    if (res.data.summary) {
                        this.modalSummary = res.data.summary;
                    }
                    if (res.data.averages) {
                        this.modalAverages = res.data.averages;
                    }
                    this.reportMonthLabel = res.data.report_month_label || '';
                    this.loading = false;
                    this.$nextTick(() => {
                        this.renderModalCharts();
                    });
                })
                .catch(err => {
                    console.error('Gagal mengambil detail konsumsi harian:', err);
                    this.error = 'Gagal memuat data konsumsi harian. Silakan coba lagi.';
                    this.loading = false;
                });
            },

            closeDailyModal() {
                this.modalOpen = false;
                this.editingRowKey = null;
                this.editRowForm = {};
                this.successMessage = '';
                this.dailyRows = [];
                if (this.modalMfoChartInstance) {
                    this.modalMfoChartInstance.destroy();
                    this.modalMfoChartInstance = null;
                }
                if (this.modalHsdChartInstance) {
                    this.modalHsdChartInstance.destroy();
                    this.modalHsdChartInstance = null;
                }
            },

            getRowKey(row) {
                return row.id ? String(row.id) : (row.date + '-' + row.session_type);
            },

            startEdit(row) {
                this.editingRowKey = this.getRowKey(row);
                this.editRowForm = {
                    me_mfo: row.me_mfo ?? 0,
                    me_hsd: row.me_hsd ?? 0,
                    ae_mfo: row.ae_mfo ?? 0,
                    ae_hsd: row.ae_hsd ?? 0,
                    boiler_hsd: row.boiler_hsd ?? 0,
                    boiler_mfo: row.boiler_mfo ?? 0,
                    genset_consum_hsd: row.genset_consum_hsd ?? 0,
                    saving: false
                };
                this.successMessage = '';
            },

            cancelEdit() {
                this.editingRowKey = null;
                this.editRowForm = {};
            },

            saveRow(row) {
                const fuelKeys = ['me_mfo', 'me_hsd', 'ae_mfo', 'ae_hsd', 'boiler_hsd', 'boiler_mfo', 'genset_consum_hsd'];
                for (const k of fuelKeys) {
                    if (this.editRowForm[k] < 0) {
                        alert('Nilai konsumsi tidak boleh negatif.');
                        return;
                    }
                }

                this.editRowForm.saving = true;
                const reportMonth = '{{ $reportMonth }}';

                const payload = {
                    vessel_id: this.vesselId,
                    report_date: row.date,
                    session_type: row.session_type,
                    report_month: reportMonth,
                    me_mfo: this.editRowForm.me_mfo,
                    me_hsd: this.editRowForm.me_hsd,
                    ae_mfo: this.editRowForm.ae_mfo,
                    ae_hsd: this.editRowForm.ae_hsd,
                    boiler_hsd: this.editRowForm.boiler_hsd,
                    boiler_mfo: this.editRowForm.boiler_mfo,
                    genset_consum_hsd: this.editRowForm.genset_consum_hsd,
                };

                axios.post('{{ route("dashboard.daily-detail.update") }}', payload)
                .then(res => {
                    for (const k of fuelKeys) {
                        row[k] = Number(this.editRowForm[k]) || 0;
                    }

                    if (res.data.totals) {
                        this.totals = res.data.totals;
                    }

                    if (res.data.summary) {
                        this.modalSummary = res.data.summary;
                        this.renderModalCharts();
                    }

                    if (res.data.averages) {
                        this.modalAverages = res.data.averages;
                    }

                    if (res.data.dashboard_vessel_totals) {
                        for (const k of fuelKeys) {
                            const cell = document.getElementById(`cell-${this.vesselId}-${k}`);
                            if (cell) {
                                const span = cell.querySelector('span') || cell;
                                span.textContent = this.formatNumber(res.data.dashboard_vessel_totals[k]);
                            }
                        }
                    }

                    if (res.data.dashboard_grand_totals) {
                        for (const k of fuelKeys) {
                            const footer = document.getElementById(`footer-total-${k}`);
                            if (footer) {
                                const span = footer.querySelector('span') || footer;
                                span.textContent = this.formatNumber(res.data.dashboard_grand_totals[k]);
                            }
                        }
                    }

                    if (res.data.dashboard_grand_summary) {
                        this.mainSummary = res.data.dashboard_grand_summary;
                        this.renderMainCharts();
                    }

                    this.successMessage = `Data konsumsi tanggal ${row.date_label} (${row.position_label}) berhasil disimpan.`;
                    this.editingRowKey = null;
                    this.editRowForm = {};

                    setTimeout(() => {
                        this.successMessage = '';
                    }, 4000);
                })
                .catch(err => {
                    console.error('Gagal menyimpan konsumsi harian:', err);
                    const msg = err.response?.data?.message || 'Terjadi kesalahan saat menyimpan data.';
                    alert(msg);
                    this.editRowForm.saving = false;
                });
            },

            formatNumber(val) {
                if (val === null || val === undefined || isNaN(val)) return '0';
                return Number(val).toLocaleString('id-ID', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2
                });
            },

            getPercentage(val, total) {
                if (!total || Number(total) <= 0.00001) return '0%';
                const pct = ((Number(val || 0) / Number(total)) * 100).toFixed(1);
                return pct + '%';
            },

            buildDoughnutChart(canvas, labels, data, colors) {
                const total = data.reduce((acc, curr) => acc + (Number(curr) || 0), 0);
                const isZero = total <= 0.00001;
                const chartData = isZero ? [1] : data;
                const chartLabels = isZero ? ['Tidak ada konsumsi'] : labels;
                const chartColors = isZero ? ['#E5E7EB'] : colors;

                return new Chart(canvas.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: chartLabels,
                        datasets: [{
                            data: chartData,
                            backgroundColor: chartColors,
                            borderWidth: 2,
                            borderColor: '#ffffff',
                            hoverOffset: isZero ? 0 : 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '65%',
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: !isZero,
                                callbacks: {
                                    label: (context) => {
                                        const val = Number(context.raw || 0);
                                        const pct = total > 0 ? ((val / total) * 100).toFixed(1) : '0';
                                        return ` ${context.label}: ${this.formatNumber(val)} L (${pct}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            },

            renderMainCharts() {
                this.$nextTick(() => {
                    const mfoCanvas = document.getElementById('mainMfoChart');
                    if (mfoCanvas) {
                        const mfoData = [
                            this.mainSummary.mfo_breakdown.boiler || 0,
                            this.mainSummary.mfo_breakdown.me || 0,
                            this.mainSummary.mfo_breakdown.ae || 0
                        ];
                        const mfoLabels = ['Boiler MFO', 'ME MFO', 'AE MFO'];
                        const mfoColors = ['#F59E0B', '#2563EB', '#10B981'];

                        if (this.mainMfoChartInstance) {
                            this.mainMfoChartInstance.destroy();
                        }
                        this.mainMfoChartInstance = this.buildDoughnutChart(mfoCanvas, mfoLabels, mfoData, mfoColors);
                    }

                    const hsdCanvas = document.getElementById('mainHsdChart');
                    if (hsdCanvas) {
                        const hsdData = [
                            this.mainSummary.hsd_breakdown.boiler || 0,
                            this.mainSummary.hsd_breakdown.me || 0,
                            this.mainSummary.hsd_breakdown.ae || 0,
                            this.mainSummary.hsd_breakdown.genset || 0
                        ];
                        const hsdLabels = ['Boiler HSD', 'ME HSD', 'AE HSD', 'Genset Consumption'];
                        const hsdColors = ['#EA580C', '#2563EB', '#10B981', '#7C3AED'];

                        if (this.mainHsdChartInstance) {
                            this.mainHsdChartInstance.destroy();
                        }
                        this.mainHsdChartInstance = this.buildDoughnutChart(hsdCanvas, hsdLabels, hsdData, hsdColors);
                    }
                });
            },

            renderModalCharts() {
                this.$nextTick(() => {
                    const mfoCanvas = document.getElementById('modalMfoChart');
                    if (mfoCanvas) {
                        const mfoData = [
                            this.modalSummary.mfo_breakdown.boiler || 0,
                            this.modalSummary.mfo_breakdown.me || 0,
                            this.modalSummary.mfo_breakdown.ae || 0
                        ];
                        const mfoLabels = ['Boiler MFO', 'ME MFO', 'AE MFO'];
                        const mfoColors = ['#F59E0B', '#2563EB', '#10B981'];

                        if (this.modalMfoChartInstance) {
                            this.modalMfoChartInstance.destroy();
                        }
                        this.modalMfoChartInstance = this.buildDoughnutChart(mfoCanvas, mfoLabels, mfoData, mfoColors);
                    }

                    const hsdCanvas = document.getElementById('modalHsdChart');
                    if (hsdCanvas) {
                        const hsdData = [
                            this.modalSummary.hsd_breakdown.boiler || 0,
                            this.modalSummary.hsd_breakdown.me || 0,
                            this.modalSummary.hsd_breakdown.ae || 0,
                            this.modalSummary.hsd_breakdown.genset || 0
                        ];
                        const hsdLabels = ['Boiler HSD', 'ME HSD', 'AE HSD', 'Genset Consumption'];
                        const hsdColors = ['#EA580C', '#2563EB', '#10B981', '#7C3AED'];

                        if (this.modalHsdChartInstance) {
                            this.modalHsdChartInstance.destroy();
                        }
                        this.modalHsdChartInstance = this.buildDoughnutChart(hsdCanvas, hsdLabels, hsdData, hsdColors);
                    }
                });
            }
        };
    }
</script>
@endsection
