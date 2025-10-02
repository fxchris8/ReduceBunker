@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Dashboard</h1>
        </div>
        <div class="p-6">
            @if(isset($error))
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                    {{ $error }}
                </div>
            @endif

            <div class="px-4 py-2 rounded-md text-lg">
                <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Laporan
                </label>
                <input type="date" id="report_date" name="report_date"
                    class="border border-gray-300 rounded-md px-4 py-2 w-64"
                    value="{{ request('report_date', date('Y-m-d')) }}"
                    onchange="window.location='{{ route('dashboard') }}?report_date='+this.value">
            </div>

            @if(isset($uniqueVessels) && $uniqueVessels > 0)
                <div class="mt-6 bg-gray-100 p-6 rounded-lg shadow-inner">
                    <h2 class="text-xl font-semibold mb-4">Operating Vessels and Fleets</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-white via-blue-50 to-blue-100 p-10 rounded-2xl border border-blue-200 shadow flex flex-col items-center justify-center text-center h-full">
                            <h3 class="text-blue-700 text-[6rem] font-extrabold mb-4 leading-none">{{ $uniqueVessels }}</h3>
                            <p class="text-gray-700 text-xl font-semibold">Total Vessel</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            @foreach($fleetCounts as $fleet => $count)
                                <div class="bg-white p-4 rounded-lg shadow flex flex-col justify-center items-center text-center h-full">
                                    <h3 class="text-blue-600 text-3xl font-bold mb-2">{{ $count }}</h3>
                                    <p class="text-gray-700 text-lg">Fleet {{ $fleet }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-6 rounded-lg text-center bg-gray-100 shadow-inner">
                        <h2 class="text-xl font-semibold mb-4">At PORT</h2>
                        <h3 class="text-xl font-semibold mb-4 text-left">Total Consumption</h3>
                        <div class="grid grid-cols-1 gap-4 mb-4 flex justify-center">
                            <script>
                                const totalLabels_port = {!! json_encode(array_keys($totalConsumption_port)) !!};
                                const totalValues_port = {!! json_encode(array_values($totalConsumption_port)) !!};

                                const hsdLabels_port = totalLabels_port.filter(label => label.includes('HSD'));
                                const hsdValues_port = hsdLabels_port.map(label => {
                                    const index = totalLabels_port.indexOf(label);
                                    return totalValues_port[index];
                                });

                                const mfoLabels_port = totalLabels_port.filter(label => label.includes('MFO'));
                                const mfoValues_port = mfoLabels_port.map(label => {
                                    const index = totalLabels_port.indexOf(label);
                                    return totalValues_port[index];
                                });
                            </script>

                            <div class="grid grid-cols-1 gap-4 mb-4 justify-center">
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-green-700 mb-2">HSD Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="hsdChart_port" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-green-700 mb-2">MFO Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="mfoChart_port" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                            </div>

                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                            <script>
                                const hsdCtx_port = document.getElementById('hsdChart_port').getContext('2d');
                                const mfoCtx_port = document.getElementById('mfoChart_port').getContext('2d');

                                const total_hsd_port = hsdValues_port.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const hsdLabelsWithPercent_port = hsdLabels_port.map((label, i) => {
                                    const percent = total_hsd_port > 0
                                        ? ((hsdValues_port[i] / total_hsd_port) * 100).toFixed(1)
                                        : '0.0';
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(hsdCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabelsWithPercent_port,
                                        datasets: [{
                                            data: hsdValues_port,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });

                                const total_mfo_port = mfoValues_port.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const mfoLabelsWithPercent_port = mfoLabels_port.map((label, i) => {
                                    const percent = total_mfo_port > 0
                                        ? ((mfoValues_port[i] / total_mfo_port) * 100).toFixed(1)
                                        : '0.0';
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(mfoCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabelsWithPercent_port,
                                        datasets: [{
                                            data: mfoValues_port,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false
                                    }
                                });
                            </script>
                        </div>

                        <h3 class="mt-6 text-xl font-semibold mb-4 text-left">Anomaly Consumption</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_me_hsd_maneuvering_port }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption Without Maneuvering</p>
                                <a href="{{ route('details.mehsd', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_time_port }}</h3>
                                <p class="text-gray-700 mb-8 text-lg">Excess A/E Pararel Duration</p>
                                <a href="{{ route('details.time', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_minus_port['SELISIH ME Maneuvering'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption For Maneuvering > BL</p>
                                <a href="{{ route('details.bl_mehsd', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_minus_port['EXCESS AE'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">A/E Consumption > BL</p>
                                <a href="{{ route('details.bl_ae', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                    </div>
                        
                    <div class="p-6 rounded-lg text-center bg-gray-100 shadow-inner">
                        <h2 class="text-xl font-semibold mb-4">At SEA</h2>
                        <h3 class="text-xl font-semibold mb-4 text-left">Total Consumption</h3>
                        <div class="grid grid-cols-1 gap-4 mb-4 flex justify-center">
                            <script>
                                const totalLabels_sea = {!! json_encode(array_keys($totalConsumption_sea)) !!};
                                const totalValues_sea = {!! json_encode(array_values($totalConsumption_sea)) !!};

                                const hsdLabels_sea = totalLabels_sea.filter(label => label.includes('HSD'));
                                const hsdValues_sea = hsdLabels_sea.map(label => {
                                    const index = totalLabels_sea.indexOf(label);
                                    return totalValues_sea[index];
                                });

                                const mfoLabels_sea = totalLabels_sea.filter(label => label.includes('MFO'));
                                const mfoValues_sea = mfoLabels_sea.map(label => {
                                    const index = totalLabels_sea.indexOf(label);
                                    return totalValues_sea[index];
                                });
                            </script>

                            <div class="grid grid-cols-1 gap-4 mb-4 justify-center">
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-blue-700 mb-2">HSD Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="hsdChart_sea" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-blue-700 mb-2">MFO Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="mfoChart_sea" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                            </div>

                            <script>
                                const hsdCtx_sea = document.getElementById('hsdChart_sea').getContext('2d');
                                const mfoCtx_sea = document.getElementById('mfoChart_sea').getContext('2d');

                                const total_hsd_sea = hsdValues_sea.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const hsdLabelsWithPercent_sea = hsdLabels_sea.map((label, i) => {
                                    const percent = total_hsd_sea > 0
                                        ? ((hsdValues_sea[i] / total_hsd_sea) * 100).toFixed(1)
                                        : '0.0';
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(hsdCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabelsWithPercent_sea,
                                        datasets: [{
                                            data: hsdValues_sea,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });

                                const total_mfo_sea = mfoValues_sea.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const mfoLabelsWithPercent_sea = mfoLabels_sea.map((label, i) => {
                                    const percent = total_mfo_sea > 0
                                        ? ((mfoValues_sea[i] / total_mfo_sea) * 100).toFixed(1)
                                        : '0.0';
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(mfoCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabelsWithPercent_sea,
                                        datasets: [{
                                            data: mfoValues_sea,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });
                            </script>
                        </div>

                        <h3 class="mt-6 text-xl font-semibold mb-4 text-left">Anomaly Consumption</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_me_hsd_maneuvering_sea }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption Without Maneuvering</p>
                                <a href="{{ route('details.mehsd', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_time_sea }}</h3>
                                <p class="text-gray-700 mb-8 text-lg">Excess A/E Pararel Duration</p>
                                <a href="{{ route('details.time', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_minus_sea['SELISIH ME Maneuvering'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption For Maneuvering > BL</p>
                                <a href="{{ route('details.bl_mehsd', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_minus_sea['EXCESS AE'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">A/E Consumption > BL</p>
                                <a href="{{ route('details.bl_ae', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_minus_sea['EXCESS ME MFO L/NM (%)'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME MFO Consumption (L/NM) > BL</p>
                                <a href="{{ route('details.bl_memfo', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                    </div>
                </div>    
            @else
                <p class="text-gray-500 mt-6">Sheet kosong atau tidak ditemukan.</p>
            @endif
        </div>
    </div>
</div>


            <!-- @if(isset($tanggal))
                <div class="mt-6 bg-gray-100 p-6 rounded-lg shadow-inner">
                    <h2 class="text-xl font-semibold mb-4">Summary Data on {{ $tanggal }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gradient-to-br from-white via-blue-50 to-blue-100 p-10 rounded-2xl border border-blue-200 shadow flex flex-col items-center justify-center text-center h-full">
                            <h3 class="text-blue-700 text-[6rem] font-extrabold mb-4 leading-none">{{ $uniqueVessels }}</h3>
                            <p class="text-gray-700 text-xl font-semibold">Total Vessel</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            @foreach($fleetCounts as $fleet => $count)
                                <div class="bg-white p-4 rounded-lg shadow text-center">
                                    <h3 class="text-blue-600 text-3xl font-bold mb-2">{{ $count }}</h3>
                                    <p class="text-gray-700 text-lg">Fleet {{ $fleet }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-6 rounded-lg text-center bg-gray-100 shadow-inner">
                        <h2 class="text-xl font-semibold mb-4">At PORT</h2>
                        <h3 class="text-xl font-semibold mb-4 text-left">Total Consumption</h3>
                        <div class="grid grid-cols-1 gap-4 mb-4 flex justify-center">
                            <script>
                                const hsdLabels_port = {!! json_encode(array_keys($hsdData_port)) !!};
                                const hsdValues_port = {!! json_encode(array_values($hsdData_port)) !!};

                                const mfoLabels_port = {!! json_encode(array_keys($mfoData_port)) !!};
                                const mfoValues_port = {!! json_encode(array_values($mfoData_port)) !!};
                            </script>

                            <div class="grid grid-cols-1 gap-4 mb-4 justify-center">
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-green-700 mb-2">HSD Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="hsdChart_port" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-green-700 mb-2">MFO Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="mfoChart_port" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                            </div>

                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

                            <script>
                                const hsdCtx_port = document.getElementById('hsdChart_port').getContext('2d');
                                const mfoCtx_port = document.getElementById('mfoChart_port').getContext('2d');

                                const total_hsd_port = hsdValues_port.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const hsdLabelsWithPercent_port = hsdLabels_port.map((label, i) => {
                                    const percent = ((hsdValues_port[i] / total_hsd_port) * 100).toFixed(1);
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(hsdCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabelsWithPercent_port,
                                        datasets: [{
                                            data: hsdValues_port,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });

                                const total_mfo_port = mfoValues_port.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const mfoLabelsWithPercent_port = mfoLabels_port.map((label, i) => {
                                    const percent = ((mfoValues_port[i] / total_mfo_port) * 100).toFixed(1);
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(mfoCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabelsWithPercent_port,
                                        datasets: [{
                                            data: mfoValues_port,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false
                                    }
                                });
                            </script>
                        </div>

                        <h3 class="mt-6 text-xl font-semibold mb-4 text-left">Anomaly Consumption</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_me_hsd_maneuvering_port['me_hsd_maneuvering'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption Without Maneuvering</p>
                                <a href="{{ route('details.mehsd', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $count_time_port }}</h3>
                                <p class="text-gray-700 mb-8 text-lg">Excess A/E Pararel Duration</p>
                                <a href="{{ route('details.time', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $minusCounts_port['ME HSD Maneuvering Consumption > BL'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption For Maneuvering > BL</p>
                                <a href="{{ route('details.bl_mehsd', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-green-700 mb-2">{{ $minusCounts_port['A/E Consumption > BL'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">A/E Consumption > BL</p>
                                <a href="{{ route('details.bl_ae', ['sessionType' => 'port']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 rounded-lg text-center bg-gray-100 shadow-inner">
                        <h2 class="text-xl font-semibold mb-4">At SEA</h2>
                        <h3 class="text-xl font-semibold mb-4 text-left">Total Consumption</h3>
                        <div class="grid grid-cols-1 gap-4 mb-4 flex justify-center">
                            <script>
                                const hsdLabels_sea = {!! json_encode(array_keys($hsdData_sea)) !!};
                                const hsdValues_sea = {!! json_encode(array_values($hsdData_sea)) !!};

                                const mfoLabels_sea = {!! json_encode(array_keys($mfoData_sea)) !!};
                                const mfoValues_sea = {!! json_encode(array_values($mfoData_sea)) !!};
                            </script>

                            <div class="grid grid-cols-1 gap-4 mb-4 justify-center">
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-blue-700 mb-2">HSD Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="hsdChart_sea" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-xl font-bold text-blue-700 mb-2">MFO Consumption</h3>
                                    <div class="flex justify-center">
                                        <canvas id="mfoChart_sea" class="w-[450px] h-[450px]"></canvas>
                                    </div>
                                </div>
                            </div>

                            <script>
                                const hsdCtx_sea = document.getElementById('hsdChart_sea').getContext('2d');
                                const mfoCtx_sea = document.getElementById('mfoChart_sea').getContext('2d');

                                const total_hsd_sea = hsdValues_sea.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const hsdLabelsWithPercent_sea = hsdLabels_sea.map((label, i) => {
                                    const percent = ((hsdValues_sea[i] / total_hsd_sea) * 100).toFixed(1);
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(hsdCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabelsWithPercent_sea,
                                        datasets: [{
                                            data: hsdValues_sea,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });

                                const total_mfo_sea = mfoValues_sea.reduce((sum, val) => sum + val, 0);

                                // Gabungkan label dengan persentase
                                const mfoLabelsWithPercent_sea = mfoLabels_sea.map((label, i) => {
                                    const percent = ((mfoValues_sea[i] / total_mfo_sea) * 100).toFixed(1);
                                    return `${label} (${percent}%)`;
                                });

                                new Chart(mfoCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabelsWithPercent_sea,
                                        datasets: [{
                                            data: mfoValues_sea,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false,
                                    }
                                });
                            </script>
                        </div>

                        <h3 class="mt-6 text-xl font-semibold mb-4 text-left">Anomaly Consumption</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-blue-700 mb-2">{{ $count_me_hsd_maneuvering_sea['me_hsd_maneuvering'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption Without Maneuvering</p>
                                <a href="{{ route('details.mehsd', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-blue-700 mb-2">{{ $count_time_sea }}</h3>
                                <p class="text-gray-700 mb-8 text-lg">Excess A/E Pararel Duration</p>
                                <a href="{{ route('details.time', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-blue-700 mb-2">{{ $minusCounts_sea['ME HSD Consumption For Maneuvering > BL'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">ME HSD Consumption For Maneuvering > BL</p>
                                <a href="{{ route('details.bl_mehsd', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-blue-700 mb-2">{{ $minusCounts_sea['ME MFO Consumption > BL'] }}</h3>
                                <p class="text-gray-700 mb-8 text-lg">ME MFO Consumption > BL</p>
                                <a href="{{ route('details.bl_memfo', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="bg-white p-4 rounded-lg shadow">
                                <h3 class="text-blue-600 text-2xl font-bold text-blue-700 mb-2">{{ $minusCounts_sea['A/E Consumption > BL'] }}</h3>
                                <p class="text-gray-700 mb-2 text-lg">A/E Consumption > BL</p>
                                <a href="{{ route('details.bl_ae', ['sessionType' => 'sea']) }}" class="text-blue-600 underline">See Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-gray-500">Sheet kosong atau tidak ditemukan.</p>
            @endif 
        </div>
    </div>
</div> -->

@endsection
