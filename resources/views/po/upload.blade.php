@extends('layouts.app')

@section('title', 'Consumption Analysis Statis')

@section('loader')
<div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
    <div class="w-16 h-16 border-4 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
    <p class="mt-4 text-red-600 font-semibold">Loading Consumption Analysis...</p>
</div>
@endsection

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Consumption Analysis Statis</h1>
        </div>

        <div class="border rounded-md p-6">
            @if(isset($error))
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                    {{ $error }}
                </div>
            @endif

            <div class="px-4 py-2 rounded-md text-lg">
                <label for="analysis_type" class="block text-sm font-medium text-gray-700 mb-1">    
                    Baseline
                </label>
                <select id="analysis_type"
                        class="border border-gray-300 rounded-md px-4 py-2 w-64"
                        onchange="if(this.value) window.location.href=this.value;">
                    <option value="">-- Pilih Jenis Baseline --</option>
                    <option value="{{ route('po.upload') }}"
                        {{ request()->is('consumption-analysis/statis') ? 'selected' : '' }}>
                        Baseline Statis
                    </option>
                    <option value="{{ route('po.upload_dinamis') }}"
                        {{ request()->is('consumption-analysis/dinamis') ? 'selected' : '' }}>
                        Baseline Dinamis
                    </option>
                </select>
            </div>

            <div class="px-4 py-2 rounded-md text-lg">
                <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                    Tanggal Laporan
                </label>
                <input type="date" id="report_date" name="report_date"
                    class="border border-gray-300 rounded-md px-4 py-2 w-64"
                    value="{{ request('report_date', date('Y-m-d', strtotime('-1 day'))) }}"
                    onchange="window.location='{{ route('po.upload') }}?report_date='+this.value">
            </div>
            
            @if((is_array($report14)) || is_array($report16))
                <form action="{{ route('send.email') }}" method="POST">
                    @csrf
                    <div x-data="{ compact: true }">
                        <div class="mt-3 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">
                                At PORT
                            </h2>
                            <!-- Switch -->
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700">Ringkas</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="compact" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                                peer-checked:bg-green-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </div>
                        </div>

                        <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-md shadow-sm w-full">
                            @if(!empty($report14))
                                @php
                                    $excludedHeaders_port = [
                                        'DEPARTURE PORT','DESTINATION','STEAM. DIST.','STEAM TIME (HOUR : MINUTE)',
                                        'SHIP SPEED','PROPELLER SLIP','ME RPM','BL L/NM','L/NM','EXCESS ME MFO L/NM (%)'
                                    ];
                                    $compactHeaders_port = ['tanggal','POSITION', 'M/E HSD', 'A/E MFO', 'A/E HSD', 'GENSET CONSUMPTION - HSD', 'MANEUVERING TIME (HOURS)', 'CRANE DURATION', 
                                                            'LOAD A/E 1 (KW)', 'LOAD A/E 2 (KW)', 'LOAD A/E 3 (KW)', 'LOAD A/E 4 (KW)',
                                                            'AE PARAREL DURATION', 'REEFER 20"', 'REEFER 40"', 'BL M/E', 'ME Maneuvering Cons. (L/H)',
                                                            'SELISIH ME Maneuvering', 'BL A/E (L/Day)', 'AE Consumption', 'EXCESS AE'
                                    ];
                                @endphp

                                <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                                    <thead class="bg-gray-300 sticky top-0 z-30">
                                        <tr>
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 left-0 bg-gray-300 z-40">
                                                Vessel ID
                                            </th>
                                            @foreach($headers_port as $header)
                                                @if($header !== 'Vessel ID' && !in_array($header, $excludedHeaders_port))
                                                    @php $isCompact = in_array($header, $compactHeaders_port); @endphp
                                                    <th x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                        class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">
                                                        {{ ucfirst($header) }}
                                                    </th>
                                                @endif
                                            @endforeach
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Pilih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($report14 as $index => $row)
                                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['value'] }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID' && !in_array($colIndex, $excludedHeaders_port))
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black {{ $cell['class'] }}">
                                                            {{ is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    <input type="checkbox" name="selected_rows_port[]" value="{{ $index }}" class="form-checkbox">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-center py-4">Tidak ada data untuk At Port.</p>
                            @endif
                        </div>
                    </div>

                    <div x-data="{ compact: true }">
                        <div class="mt-5 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-blue-700 bg-blue-100 inline-block px-2 rounded">
                                At SEA
                            </h2>
                            <!-- Switch -->
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700">Ringkas</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="compact" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                                peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </div>
                        </div>

                        <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-md shadow-sm w-full">
                            @if(!empty($report16))
                                @php
                                    // kolom yang selalu di-exclude
                                    $excludedHeaders_sea = ['POSITION'];

                                    // kolom tambahan yang hanya ditampilkan saat compact = true
                                    $compactHeaders_sea = ['tanggal','DEPARTURE PORT', 'DESTINATION', 'STEAM. DIST.', 'STEAM TIME (HOUR : MINUTE)', 
                                                            'M/E MFO', 'M/E HSD', 'A/E MFO', 'A/E HSD', 'GENSET CONSUMPTION - HSD', 'MANEUVERING TIME (HOURS)', 
                                                            'CRANE DURATION', 'LOAD A/E 1 (KW)', 'LOAD A/E 2 (KW)', 'LOAD A/E 3 (KW)', 'LOAD A/E 4 (KW)',
                                                            'AE PARAREL DURATION', 'REEFER 20"', 'REEFER 40"', 'BL M/E', 'ME Maneuvering Cons. (L/H)',
                                                            'SELISIH ME Maneuvering', 'BL L/NM', 'L/NM', 'EXCESS ME MFO L/NM (%)',
                                                            'BL A/E (L/Day)', 'AE Consumption', 'EXCESS AE',
                                    ];
                                @endphp

                                <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                                    <thead class="bg-gray-300 sticky top-0 z-30">
                                        <tr>
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 left-0 bg-gray-300 z-40">
                                                Vessel ID
                                            </th>
                                            @foreach($headers_sea as $header)
                                                @if($header !== 'Vessel ID' && !in_array($header, $excludedHeaders_sea))
                                                    @php $isCompact = in_array($header, $compactHeaders_sea); @endphp
                                                    <th x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                        class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">
                                                        {{ ucfirst($header) }}
                                                    </th>
                                                @endif
                                            @endforeach
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Pilih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($report16 as $index => $row)
                                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['value'] }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID' && !in_array($colIndex, $excludedHeaders_sea))
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black {{ $cell['class'] }}">
                                                            {{ is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    <input type="checkbox" name="selected_rows_sea[]" value="{{ $index }}" class="form-checkbox">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-center py-4">Tidak ada data untuk At Sea.</p>
                            @endif
                        </div>
                    </div>

                    <div x-data="{ compact: true }">
                        <div class="mt-5 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">
                                Port & Sea
                            </h2>
                            <!-- Switch -->
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700">Ringkas</span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" x-model="compact" class="sr-only peer" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer
                                                peer-checked:bg-green-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                                </label>
                            </div>
                        </div>

                        <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-md shadow-sm w-full">
                            @if(!empty($port_sea_data))
                                @php
                                    $compactHeaders_port_sea = array_values(array_unique(array_merge($compactHeaders_port, $compactHeaders_sea)));
                                @endphp

                                <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                                    <thead class="bg-gray-300 sticky top-0 z-30">
                                        <tr>
                                            <!-- Freeze Vessel ID -->
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 left-0 bg-gray-300 z-40">
                                                Vessel ID
                                            </th>
                                            @foreach($port_sea_header as $header)
                                                @if($header !== 'Vessel ID')
                                                    @php $isCompact = in_array($header, $compactHeaders_port_sea); @endphp
                                                    <th x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                        class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">
                                                        {{ ucfirst($header) }}
                                                    </th>
                                                @endif
                                            @endforeach
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Pilih</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($port_sea_data as $index => $row)
                                            {{-- Baris Port --}}
                                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                                <!-- Freeze Vessel ID -->
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['port'] ?? '' }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID')
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black">
                                                            {{ is_numeric($cell['port']) ? number_format($cell['port'], 2, '.', ',') : $cell['port'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    <input type="checkbox" name="selected_rows_port_sea_port[]" value="{{ $index }}" class="form-checkbox">
                                                </td>
                                            </tr>
                                            {{-- Baris Sea --}}
                                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                                <!-- Freeze Vessel ID -->
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['sea'] ?? '' }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID')
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black">
                                                            {{ is_numeric($cell['sea']) ? number_format($cell['sea'], 2, '.', ',') : $cell['sea'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    <input type="checkbox" name="selected_rows_port_sea_sea[]" value="{{ $index }}" class="form-checkbox">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-center py-4">Tidak ada data untuk Port & Sea.</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="py-2 px-4 bg-green-600 text-white rounded-md">
                            Send Email
                        </button>
                    </div>
                </form>
            @else
                <p>Tidak ada data tersedia.</p>
            @endif
        </div>
    </div>
</div>

@if(session('success_email'))
    <script>
        window.onload = function() {
            alert("{{ session('success_email') }}");
        }
    </script>
@endif

<script>
    function showLoader() {
        document.getElementById("loader").classList.remove("hidden");
    }
    function hideLoader() {
        document.getElementById("loader").classList.add("hidden");
    }

    window.addEventListener("load", hideLoader);

    document.addEventListener("DOMContentLoaded", function() {
        // Loader untuk semua link
        document.querySelectorAll("a").forEach(function(link) {
            link.addEventListener("click", function(e) {
                if (link.target === "_blank" || link.getAttribute("href").startsWith("#") || link.hostname !== window.location.hostname) {
                    return;
                }
                e.preventDefault();
                showLoader();
                window.location = link.href;
            });
        });

        // Loader untuk form submit
        const form = document.getElementById("emailForm");
        if (form) {
            form.addEventListener("submit", function() {
                showLoader();
            });
        }
    });
</script>
@endsection
