@extends('layouts.app')

@section('title', 'Baseline Analysis')

@section('content')

<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Baseline Analysis</h1>
        </div>

        <div class="border rounded-md p-6">
            @if(isset($error))
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                    {{ $error }}
                </div>
            @endif

            <div class="px-4 py-2 rounded-md text-lg">
                @isset($vessels)
                    <div>
                        <label for="vessel" class="block text-sm font-medium text-gray-700 mb-2">Pilih Vessel:</label>
                        <select id="vessel" name="vessel"
                            class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out"
                            onchange="location.href='{{ route('po.baseline') }}?vessel=' + this.value + '&density={{ request('density') }}&power_kw={{ request('power_kw') }}&steam_time={{ request('steam_time') }}'">
                            @foreach($vessels as $vessel)
                                <option value="{{ $vessel }}" {{ $vessel === $selectedVessel ? 'selected' : '' }}>
                                    {{ $vessel }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-6">
                            <label for="density" class="block text-sm font-medium text-gray-700 mb-2">Masukkan Density (g/L):</label>
                            <input type="number" step="any" name="density" id="density"
                                value="{{ request('density', 950) }}"
                                class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out"
                                onchange="location.href='{{ route('po.baseline') }}?vessel={{ $selectedVessel }}&density=' + this.value + '&power_kw={{ request('power_kw') }}&steam_time={{ request('steam_time') }}'"
                        </div>
                    </div>

                    <div class="mt-8">
                        <h2 class="text-lg font-semibold mb-4">Grafik Kurva</h2>
                        <div class="flex flex-wrap gap-6">
                            <div class="flex-1 min-w-[300px]">
                                <h3 class="text-md font-semibold mb-2">Grafik BHP</h3>
                                <canvas id="grafikKurvaBHP" height="100"></canvas>
                            </div>
                            <div class="flex-1 min-w-[300px]">
                                <h3 class="text-md font-semibold mb-2">Grafik kW</h3>
                                <canvas id="grafikKurvaKW" height="100"></canvas>
                            </div>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('grafikKurvaBHP').getContext('2d');
                        const dataPoints = @json($grafikData_bhp);

                        const chart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                datasets: [{
                                    label: @json($labelKurva_bhp),
                                    data: dataPoints,
                                    borderColor: 'rgba(255, 99, 132, 1)',
                                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                                    fill: false,
                                    tension: 0.1,
                                    pointRadius: 0
                                }]
                            },
                            options: {
                                scales: {
                                    x: {
                                        min: {{ $ori_x_min }}, 
                                        max: {{ $ori_x_max }}, 
                                        type: 'linear',
                                        title: {
                                            display: true,
                                            text: 'BHP'
                                        }
                                    },
                                    y: {
                                        min: {{ $ori_y_min }}, 
                                        max: {{ $ori_y_max }}, 
                                        title: {
                                            display: true,
                                            text: 'g/BHP/hr'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true
                                    }
                                }
                            }
                        });
                    </script>

                    <script>
                        const ctxKW = document.getElementById('grafikKurvaKW').getContext('2d');
                        const dataPointsKW = @json($grafikData_kw);

                        const chartKW = new Chart(ctxKW, {
                            type: 'line',
                            data: {
                                datasets: [{
                                    label: @json($labelKurva_kw),
                                    data: dataPointsKW,
                                    borderColor: 'rgba(54, 162, 235, 1)',
                                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                    fill: false,
                                    tension: 0.1,
                                    pointRadius: 0
                                }]
                            },
                            options: {
                                scales: {
                                    x: {
                                        min: {{ $convert_x_min }}, 
                                        max: {{ $convert_x_max }}, 
                                        type: 'linear',
                                        title: {
                                            display: true,
                                            text: 'kW'
                                        }
                                    },
                                    y: {
                                        min: {{ $convert_y_min }}, 
                                        max: {{ $convert_y_max }}, 
                                        title: {
                                            display: true,
                                            text: 'L/kW/hr'
                                        }
                                    }
                                },
                                plugins: {
                                    legend: {
                                        display: true
                                    }
                                }
                            }
                        });
                    </script>

                    <div class="flex space-x-4 mt-6 mb-4 font-bold">
                        <h3 class="text-lg">Persamaan Kurva kW: <span class="text-red-800">{{ $labelKurva_kw }}</span></h3>
                    </div>

                    <div>
                        <div class="mt-6">
                            <label for="power_kw" class="block text-sm font-medium text-gray-700 mb-2">Power (kW):</label>
                            <input type="number" step="any" name="power_kw" id="power_kw"
                                value="{{ request('power_kw') }}"
                                class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out"
                                onchange="location.href='{{ route('po.baseline') }}?vessel={{ $selectedVessel }}&density=' + document.getElementById('density').value + '&power_kw=' + this.value + '&steam_time=' + document.getElementById('steam_time').value"
                        </div>

                        <div class="mt-6">
                            <label for="steam_time" class="block text-sm font-medium text-gray-700 mb-2">Steam Time (hr):</label>
                            <input type="number" step="any" name="steam_time" id="steam_time"
                                value="{{ request('steam_time') }}"
                                class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out"
                                onchange="location.href='{{ route('po.baseline') }}?vessel={{ $selectedVessel }}&density=' + document.getElementById('density').value + '&power_kw=' + document.getElementById('power_kw').value + '&steam_time=' + this.value"
                        </div>

                        <div class="flex space-x-4 mt-6 mb-4 font-bold">
                            <h3 class="text-lg">SFOC: <span class="text-red-800">{{ $sfoc_kw }}</span> L/kW/hr</h3>
                        </div>

                        <div class="flex space-x-4 mb-4 font-bold   ">
                            <h3 class="text-lg">Baseline Konsumsi: <span class="text-red-800">{{ $konsumsi }}</span> L</h1>
                        </div>
                    </div>
                @endisset
            </div>
        </div>
    </div>
</div>


@endsection