@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Dashboard</h1>
        </div>
        <div class="p-6">
            <form action="{{ route('file.dashboard') }}" method="POST" enctype="multipart/form-data" class="flex justify-center">
                @csrf
                <div class="w-full max-w-2xl rounded-lg flex items-center gap-4">
                    <input type="file" name="file" accept=".csv, .xlsx, .xls, .ods" required
                        class="w-full py-2 px-4 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    
                    <button type="submit"
                        class="w-full py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                        Upload
                    </button>
                </div>
            </form>

            @if(isset($tanggal))
                <div class="mt-6 bg-gray-100 p-6 rounded-lg shadow-inner">
                    <h2 class="text-xl font-semibold mb-4">Summary Data on {{ $tanggal }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Kolom Kiri: Total Vessel -->
                        <div class="bg-gradient-to-br from-white via-blue-50 to-blue-100 p-10 rounded-2xl border border-blue-200 shadow flex flex-col items-center justify-center text-center h-full">
                            <h3 class="text-blue-700 text-[6rem] font-extrabold mb-4 leading-none">{{ $uniqueVessels }}</h3>
                            <p class="text-gray-700 text-xl font-semibold">Total Vessel</p>
                        </div>

                        <!-- Kolom Kanan: Fleet Breakdown -->
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

                                new Chart(hsdCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabels_port,
                                        datasets: [{
                                            data: hsdValues_port,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false
                                    }
                                });

                                new Chart(mfoCtx_port, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabels_port,
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

                            <!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
                            <script>
                                const hsdCtx_sea = document.getElementById('hsdChart_sea').getContext('2d');
                                const mfoCtx_sea = document.getElementById('mfoChart_sea').getContext('2d');

                                new Chart(hsdCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: hsdLabels_sea,
                                        datasets: [{
                                            data: hsdValues_sea,
                                            backgroundColor: ['#EF4444', '#FBBF24', '#10B981', '#1D4ED8']
                                        }]
                                    },
                                    options: {
                                        responsive: false,
                                        maintainAspectRatio: false
                                    }
                                });

                                new Chart(mfoCtx_sea, {
                                    type: 'pie',
                                    data: {
                                        labels: mfoLabels_sea,
                                        datasets: [{
                                            data: mfoValues_sea,
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
</div>
@endsection
