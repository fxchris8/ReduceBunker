@extends('layouts.app')

@section('title', 'Consumption Analysis')

@if(!$isDinamis)
@section('loader')
<div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
    <div class="w-16 h-16 border-4 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
    <p class="mt-4 text-red-600 font-semibold">Loading Consumption Analysis...</p>
</div>
@endsection
@endif

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">
                Consumption Analysis
            </h1>
        </div>

        <div class="border rounded-md p-6">
            @if(isset($error))
                <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                    {{ $error }}
                </div>
            @endif

            <form method="GET" action="{{ route('pages.consumption') }}" class="flex flex-wrap items-end gap-3 mb-6">
                <div>
                    <label for="report_date" class="mb-1 block text-sm font-medium text-gray-700">
                        Tanggal Laporan
                    </label>
                    <input type="date" id="report_date" name="report_date"
                        value="{{ request('report_date', session('consumption_report_date')) }}"
                        class="w-64 rounded-md border border-gray-300 px-4 py-2">
                </div>

                <div>
                    <label for="density" class="mb-1 block text-sm font-medium text-gray-700">
                        Masukkan Density (g/L)
                    </label>
                    <input type="number" step="any" name="density" id="density"
                        value="{{ request('density', session('consumption_density', 950)) }}"
                        class="w-64 rounded-md border border-gray-300 px-4 py-2">
                </div>

                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
                    Tampilkan
                </button>
            </form>

            @php
                $hasData = $isDinamis
                    ? is_array($report16 ?? null)
                    : (is_array($report14 ?? null) || is_array($report16 ?? null));
            @endphp

            @if($hasData)
                <form action="{{ route('send.email') }}" method="POST">
                    @csrf
                    @if(!$isDinamis && !empty($report14))
                        @php
                            $excludedHeaders_port = [
                                'DEPARTURE PORT','DESTINATION','STEAM. DIST.','STEAM TIME (HOUR : MINUTE)',
                                'SHIP SPEED','PROPELLER SLIP','ME RPM','BL L/NM','L/NM','EXCESS ME L/NM (%)',
                                'BL MFO','BL HSD','BL REFFER', 'SELISIH ME Maneuvering',
                                'EXCESS ME', 'EXCESS AE Tolerance', '_anomalies'
                            ];
                            $compactHeaders_port = [
                                'tanggal','POSITION','M/E HSD', 'GENSET CONSUMPTION - HSD', 'M/E MFO', 'A/E MFO','A/E HSD',
                                'MANEUVERING TIME (HOURS)','CRANE DURATION', 'TOTAL CRANE','LOAD A/E 1 (KW)','LOAD A/E 2 (KW)',
                                'LOAD A/E 3 (KW)','LOAD A/E 4 (KW)', 'AE PARAREL DURATION','REEFER 20"','REEFER 40"','BL M/E',
                                'ME Maneuvering Cons. (L/H)', 'BL A/E (L/Day)','AE Consumption','EXCESS AE'
                            ];
                        @endphp

                        <div x-data="{ compact: false, search: '' }">
                            <div class="mt-3 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                                <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">
                                    At PORT
                                </h2>
                                <input type="text" x-model="search" placeholder="Filter Kapal..." class="border border-gray-300 rounded-md px-3 py-1 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600">
                            </div>

                            <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-lg border border-gray-200 shadow-sm w-full">
                                <table class="min-w-full border-separate border-spacing-0 divide-y divide-gray-200 text-sm text-center">
                                    <thead class="bg-gray-50 sticky top-0 z-30">
                                        <tr>
                                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-r border-gray-200 sticky top-0 left-0 bg-gray-50 z-40 shadow-[1px_0_0_0_#e5e7eb]">
                                                Vessel ID
                                            </th>
                                            @foreach($headers_port as $header)
                                                @if($header !== 'Vessel ID' && !in_array($header, $excludedHeaders_port))
                                                    @php $isCompact = in_array($header, $compactHeaders_port); @endphp
                                                    <th x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                        class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">
                                                        {{ ucfirst($header) }}
                                                    </th>
                                                @endif
                                            @endforeach
                                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                        @foreach($report14 as $index => $row)
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr x-show="search === '' || '{{ strtolower(addslashes($row['Vessel ID']['value'])) }}'.includes(search.toLowerCase())" class="text-center hover:bg-gray-50 transition-colors {{ $rowClass ?: 'bg-white' }}">
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 border-b border-r border-gray-200 sticky left-0 z-20 {{ $rowClass ? '' : 'bg-white' }} shadow-[1px_0_0_0_#e5e7eb]">
                                                    {{ $row['Vessel ID']['value'] }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID' && $colIndex !== '_row_class' && !in_array($colIndex, $excludedHeaders_port))
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200 {{ $rowClass ? '' : $cell['class'] }}">
                                                            @php
                                                                $val = $cell['value'];
                                                                $isNum = is_numeric($val);
                                                                $isInt = $isNum && floor($val) == $val;
                                                            @endphp
                                                            {{ is_numeric($cell['value']) ? \App\Helpers\NumberFormatter::idFormat($cell['value']) : $cell['value'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200">
                                                    @php
                                                        $anomalies = $row['_anomalies']['value'] ?? [];
                                                        $hasAnomalies = count($anomalies) > 0;
                                                        $anomaliesJson = htmlspecialchars(json_encode($anomalies), ENT_QUOTES, 'UTF-8');
                                                    @endphp
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            onclick='showDetailModal(
                                                                "{{ $row['Vessel ID']['value'] }}",
                                                                "At Port",
                                                                {{ $row['M/E MFO']['value'] ?? 0 }},
                                                                {{ $row['M/E HSD']['value'] ?? 0 }},
                                                                {{ $row['A/E MFO']['value'] ?? 0 }},
                                                                {{ $row['A/E HSD']['value'] ?? 0 }},
                                                                {{ $row['GENSET CONSUMPTION - HSD']['value'] ?? 0 }},
                                                                {{ $row['REEFER 20"']['value'] ?? 0 }},
                                                                {{ $row['REEFER 40"']['value'] ?? 0 }},
                                                                {{ $row['BL MFO']['value'] ?? 0 }},
                                                                {{ $row['BL HSD']['value'] ?? 0 }},
                                                                {{ $row['BL REFFER']['value'] ?? 0 }},
                                                                {{ $row['MANEUVERING TIME (HOURS)']['value'] ?? 0 }},
                                                                {{ $row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0 }},
                                                                {{ $row['PROPELLER SLIP']['value'] ?? 0 }},
                                                                {{ $row['CRANE DURATION']['value'] ?? 0 }},
                                                                {{ $row['TOTAL CRANE']['value'] ?? 0 }},
                                                                {{ $row['LOAD A/E 1 (KW)']['value'] ?? 0 }},
                                                                {{ $row['LOAD A/E 2 (KW)']['value'] ?? 0 }},
                                                                {{ $row['LOAD A/E 3 (KW)']['value'] ?? 0 }},
                                                                {{ $row['LOAD A/E 4 (KW)']['value'] ?? 0 }},
                                                                {{ $row['AE PARAREL DURATION']['value'] ?? 0 }},
                                                                "", "", "", 0,
                                                                "{{ $row['tanggal']['value'] ?? '' }}",
                                                                "{{ $row['POSITION']['value'] ?? '' }}",
                                                                "", "",
                                                                {{ $row['BL L/NM']['value'] ?? 0 }},
                                                                0, 0,
                                                                {{ $row['EXCESS AE']['value'] ?? 0 }},
                                                                {{ $row['EXCESS AE Tolerance']['value'] ?? 0 }},
                                                                {{ $row['EXCESS ME']['value'] ?? 0 }},
                                                                0,
                                                                0,
                                                                {!! $anomaliesJson !!}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        @if($hasAnomalies)
                                                        <button type="button"
                                                            onclick="showInfoModal('{{ $row['Vessel ID']['value'] }}', {!! $anomaliesJson !!})"
                                                            class="text-blue-500 hover:text-blue-600 p-1 rounded hover:bg-blue-50 transition"
                                                            title="Info Blok Warna">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                        @endif
                                                        <input type="checkbox" name="selected_rows_port[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if(!empty($report16 ?? null))
                        @php
                            $excludedHeaders_sea = ['POSITION', 'SELISIH ME Maneuvering', 'BL MFO', 'BL HSD',
                                'BL REFFER', 'EXCESS ME', 'EXCESS AE Tolerance', '_anomalies',
                                'REMARKS', 'DECK DAILY WORK', 'ENGINE DAILY WORK'
                            ];
                            $seaRows    = $report16 ?? [];
                            $seaHeaders = array_filter(array_keys($seaRows[0] ?? []), fn($k) => $k !== '_row_class');

                            $compactHeaders_sea = [
                                'tanggal','DEPARTURE PORT','DESTINATION','STEAM. DIST.',
                                'STEAM TIME (HOUR : MINUTE)','M/E MFO','M/E HSD','A/E MFO','A/E HSD',
                                'GENSET CONSUMPTION - HSD','MANEUVERING TIME (HOURS)','CRANE DURATION',
                                'LOAD A/E 1 (KW)','LOAD A/E 2 (KW)','LOAD A/E 3 (KW)','LOAD A/E 4 (KW)',
                                'AE PARAREL DURATION','REEFER 20"','REEFER 40"',
                                'ME Maneuvering Cons. (L/H)', 'BL M/E Static (L/Day)', 'BL A/E (L/Day)',
                                'BL L/NM','L/NM','EXCESS ME L/NM (%)',
                                'AE Consumption','EXCESS AE',
                            ];
                        @endphp

                        <div x-data="{ compact: false, search: '' }">
                        <div class="mt-5 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-blue-700 bg-blue-100 inline-block px-2 rounded">
                                At SEA
                            </h2>
                            <input type="text" x-model="search" placeholder="Filter Kapal..." class="border border-gray-300 rounded-md px-3 py-1 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                        </div>

                        <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-lg border border-gray-200 shadow-sm w-full">
                            <table class="min-w-full border-separate border-spacing-0 divide-y divide-gray-200 text-sm text-center">
                                <thead class="bg-gray-50 sticky top-0 z-30">
                                    <tr>
                                        <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-r border-gray-200 sticky top-0 left-0 bg-gray-50 z-40 shadow-[1px_0_0_0_#e5e7eb]">
                                            Vessel ID
                                        </th>
                                        @foreach($seaHeaders as $header)
                                            @if($header !== 'Vessel ID' && !in_array($header, $excludedHeaders_sea))
                                                @php
                                                    $dinamisCols = [
                                                        'Ideal Consumption Static (L/Day)',
                                                        'DAYA ME (KW)',
                                                        'Ideal Consumption Dynamic (L)',
                                                        'Konsumsi M/E MFO Aktual',
                                                        'Gap',
                                                        'Error',
                                                    ];
                                                    $alwaysShow   = in_array($header, $dinamisCols);
                                                    $isCompact    = in_array($header, $compactHeaders_sea);
                                                    $dataCompact  = $alwaysShow ? 'false' : ($isCompact ? 'true' : 'false');
                                                @endphp
                                                <th x-show="!compact || $el.dataset.compact === 'true'"
                                                    data-compact="{{ $dataCompact }}"
                                                    class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">
                                                    {{ ucfirst($header) }}
                                                </th>
                                            @endif
                                        @endforeach
                                        <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                    @foreach($seaRows as $index => $row)
                                        @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                        <tr x-show="search === '' || '{{ strtolower(addslashes($row['Vessel ID']['value'])) }}'.includes(search.toLowerCase())" class="text-center hover:bg-gray-50 transition-colors {{ $rowClass ?: 'bg-white' }}">
                                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 border-b border-r border-gray-200 sticky left-0 z-20 {{ $rowClass ? '' : 'bg-white' }} shadow-[1px_0_0_0_#e5e7eb]">
                                                {{ $row['Vessel ID']['value'] }}
                                            </td>
                                            @foreach($row as $colIndex => $cell)
                                                @if($colIndex !== 'Vessel ID' && $colIndex !== '_row_class' && !in_array($colIndex, $excludedHeaders_sea))
                                                    @php
                                                        $alwaysShow  = in_array($colIndex, $dinamisCols);
                                                        $isCompact   = in_array($colIndex, $compactHeaders_sea);
                                                        $dataCompact = $alwaysShow ? 'false' : ($isCompact ? 'true' : 'false');
                                                    @endphp
                                                    <td x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $dataCompact }}"
                                                        title="{{ $cell['message'] ?? '' }}"
                                                        class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200 {{ $rowClass ? '' : ($cell['class'] ?? '') }}">
                                                        @php
                                                            $val = $cell['value'];
                                                            $isNum = is_numeric($val);
                                                            $isInt = $isNum && floor($val) == $val;
                                                        @endphp
                                                        {{ is_numeric($cell['value']) ? \App\Helpers\NumberFormatter::idFormat($cell['value']) : $cell['value'] }}
                                                    </td>
                                                @endif
                                            @endforeach
                                            <td class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200">
                                                @php
                                                    $anomalies = $row['_anomalies']['value'] ?? [];
                                                    $hasAnomalies = count($anomalies) > 0;
                                                    $anomaliesJson = htmlspecialchars(json_encode($anomalies), ENT_QUOTES, 'UTF-8');
                                                @endphp
                                                <div class="flex items-center justify-center gap-2">
                                                    <button type="button"
                                                        onclick='showDetailModal(
                                                        "{{ $row['Vessel ID']['value'] }}",
                                                        "At Sea",
                                                        {{ $row['M/E MFO']['value'] ?? 0 }},
                                                        {{ $row['M/E HSD']['value'] ?? 0 }},
                                                        {{ $row['A/E MFO']['value'] ?? 0 }},
                                                        {{ $row['A/E HSD']['value'] ?? 0 }},
                                                        {{ $row['GENSET CONSUMPTION - HSD']['value'] ?? 0 }},
                                                        {{ $row['REEFER 20"']['value'] ?? 0 }},
                                                        {{ $row['REEFER 40"']['value'] ?? 0 }},
                                                        {{ $row['BL MFO']['value'] ?? 0 }},
                                                        {{ $row['BL HSD']['value'] ?? 0 }},
                                                        {{ $row['BL REFFER']['value'] ?? 0 }},
                                                        {{ $row['MANEUVERING TIME (HOURS)']['value'] ?? 0 }},
                                                        {{ $row['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0 }},
                                                        {{ $row['PROPELLER SLIP']['value'] ?? 0 }},
                                                        {{ $row['CRANE DURATION']['value'] ?? 0 }},
                                                        {{ $row['TOTAL CRANE']['value'] ?? 0 }},
                                                        {{ $row['LOAD A/E 1 (KW)']['value'] ?? 0 }},
                                                        {{ $row['LOAD A/E 2 (KW)']['value'] ?? 0 }},
                                                        {{ $row['LOAD A/E 3 (KW)']['value'] ?? 0 }},
                                                        {{ $row['LOAD A/E 4 (KW)']['value'] ?? 0 }},
                                                        {{ $row['AE PARAREL DURATION']['value'] ?? 0 }},
                                                        "{{ $row['REMARKS']['value'] ?? '-' }}",
                                                        "{{ $row['DECK DAILY WORK']['value'] ?? '-' }}",
                                                        "{{ $row['ENGINE DAILY WORK']['value'] ?? '-' }}",
                                                        {{ $row['BL AE PARALLEL 2']['value'] ?? 0 }},
                                                        "{{ $row['tanggal']['value'] ?? '' }}",
                                                        "",
                                                        "{{ $row['DEPARTURE PORT']['value'] ?? '' }}",
                                                        "{{ $row['DESTINATION']['value'] ?? '' }}",
                                                        {{ $row['BL L/NM']['value'] ?? 0 }},
                                                        {{ $row['L/NM']['value'] ?? 0 }},
                                                        {{ $row['EXCESS ME L/NM (%)']['value'] ?? 0 }},
                                                        {{ $row['EXCESS AE']['value'] ?? 0 }},
                                                        {{ $row['EXCESS AE Tolerance']['value'] ?? 0 }},
                                                        {{ $row['EXCESS ME']['value'] ?? 0 }},
                                                        {{ $row['SHIP SPEED']['value'] ?? 0 }},
                                                        {{ $row['Ideal Consumption Dynamic (L)']['value'] === '' ? 0 : ($row['Ideal Consumption Dynamic (L)']['value'] ?? 0) }},
                                                        {!! $anomaliesJson !!},
                                                        {{ $row['STEAM. DIST.']['value'] ?? 0 }}
                                                    )'
                                                        class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                        title="Lihat Detail">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </svg>
                                                    </button>
                                                    @if($hasAnomalies)
                                                    <button type="button"
                                                        onclick="showInfoModal('{{ $row['Vessel ID']['value'] }}', {!! $anomaliesJson !!})"
                                                        class="text-blue-500 hover:text-blue-600 p-1 rounded hover:bg-blue-50 transition"
                                                        title="Info Blok Warna">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                    </button>
                                                    @endif
                                                    <input type="checkbox" name="selected_rows_sea[]" value="{{ $index }}" class="form-checkbox">
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        </div>

                    @else
                        <p class="text-center py-4 mt-4">Tidak ada data untuk At Sea.</p>
                    @endif

                    @if(!$isDinamis && !empty($port_sea_data ?? null))
                        @php
                            $compactHeaders_port_sea = array_values(array_unique(array_merge($compactHeaders_port, $compactHeaders_sea)));
                        @endphp

                        <div x-data="{ compact: false, search: '' }">
                            <div class="mt-5 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                                <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">
                                    Port & Sea
                                </h2>
                                <input type="text" x-model="search" placeholder="Filter Kapal..." class="border border-gray-300 rounded-md px-3 py-1 text-sm font-normal focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-green-600">
                            </div>

                            <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-lg border border-gray-200 shadow-sm w-full">
                                <table class="min-w-full border-separate border-spacing-0 divide-y divide-gray-200 text-sm text-center">
                                    <thead class="bg-gray-50 sticky top-0 z-30">
                                        <tr>
                                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-r border-gray-200 sticky top-0 left-0 bg-gray-50 z-40 shadow-[1px_0_0_0_#e5e7eb]">
                                                Vessel ID
                                            </th>
                                            @foreach($port_sea_header as $header)
                                                @if($header !== 'Vessel ID')
                                                    @php $isCompact = in_array($header, $compactHeaders_port_sea); @endphp
                                                    <th x-show="!compact || $el.dataset.compact === 'true'"
                                                        data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                        class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">
                                                        {{ ucfirst($header) }}
                                                    </th>
                                                @endif
                                            @endforeach
                                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 border-b border-gray-200 sticky top-0 bg-gray-50 z-30">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 bg-white text-gray-700">
                                        @foreach($port_sea_data as $index => $row)
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr x-show="search === '' || '{{ strtolower(addslashes($row['Vessel ID']['port'] ?? '')) }}'.includes(search.toLowerCase())" class="text-center hover:bg-gray-50 transition-colors {{ $rowClass ?: 'bg-white' }}">
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 border-b border-r border-gray-200 sticky left-0 z-20 {{ $rowClass ? '' : 'bg-white' }} shadow-[1px_0_0_0_#e5e7eb]">
                                                    {{ $row['Vessel ID']['port'] ?? '' }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID')
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200">
                                                            @php
                                                                $valPort = $cell['port'];
                                                                $isNumPort = is_numeric($valPort);
                                                                $isIntPort = $isNumPort && floor($valPort) == $valPort;
                                                            @endphp
                                                            {{ $isNumPort ? number_format($valPort, $isIntPort ? 0 : 2, ',', '.') : $valPort }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="whitespace-nowrap px-4 py-3 text-center border-b border-gray-200">
                                                    @php
                                                        $anomaliesPort = $row['_anomalies']['port']['value'] ?? [];
                                                        $hasAnomaliesPort = count($anomaliesPort) > 0;
                                                        $anomaliesJsonPort = htmlspecialchars(json_encode($anomaliesPort), ENT_QUOTES, 'UTF-8');
                                                    @endphp
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            onclick='showDetailModal(
                                                                "{{ ($row['Vessel ID']['port'] ?? '') }} (Port)",
                                                                "Port & Sea",
                                                                0,
                                                                {{ $row['M/E HSD']['port'] ?? 0 }},
                                                                {{ $row['A/E MFO']['port'] ?? 0 }},
                                                                {{ $row['A/E HSD']['port'] ?? 0 }},
                                                                {{ $row['GENSET CONSUMPTION - HSD']['port'] ?? 0 }},
                                                                {{ $row['REEFER 20"']['port'] ?? 0 }},
                                                                {{ $row['REEFER 40"']['port'] ?? 0 }},
                                                                {{ $row['BL MFO']['port'] ?? 0 }},
                                                                {{ $row['BL HSD']['port'] ?? 0 }},
                                                                {{ $row['BL REFFER']['port'] ?? 0 }},
                                                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                                                "", "", "", 0,
                                                                "", "", "", "",
                                                                0, 0, 0, 0, 0, 0, 0,
                                                                0,
                                                                {!! $anomaliesJsonPort !!}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        @if($hasAnomaliesPort)
                                                        <button type="button"
                                                            onclick="showInfoModal('{{ ($row['Vessel ID']['port'] ?? '') }} (Port)', {!! $anomaliesJsonPort !!})"
                                                            class="text-blue-500 hover:text-blue-600 p-1 rounded hover:bg-blue-50 transition"
                                                            title="Info Blok Warna">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                        @endif
                                                        <input type="checkbox" name="selected_rows_port_sea_port[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr x-show="search === '' || '{{ strtolower(addslashes($row['Vessel ID']['sea'] ?? '')) }}'.includes(search.toLowerCase())" class="text-center {{ $rowClass ?: 'bg-white' }}">
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 z-20 {{ $rowClass ? '' : 'bg-white' }} shadow-[1px_0_0_0_#000]">
                                                    {{ $row['Vessel ID']['sea'] ?? '' }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID')
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black">
                                                            @php
                                                                $valSea = $cell['sea'];
                                                                $isNumSea = is_numeric($valSea);
                                                                $isIntSea = $isNumSea && floor($valSea) == $valSea;
                                                            @endphp
                                                            {{ $isNumSea ? number_format($valSea, $isIntSea ? 0 : 2, ',', '.') : $valSea }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    @php
                                                        $anomaliesSea = $row['_anomalies']['sea']['value'] ?? [];
                                                        $hasAnomaliesSea = count($anomaliesSea) > 0;
                                                        $anomaliesJsonSea = htmlspecialchars(json_encode($anomaliesSea), ENT_QUOTES, 'UTF-8');
                                                    @endphp
                                                    <div class="flex items-center justify-center gap-2">
                                                        <button type="button"
                                                            onclick='showDetailModal(
                                                                "{{ ($row['Vessel ID']['sea'] ?? '') }} (Sea)",
                                                                "Port & Sea",
                                                                {{ $row['M/E MFO']['sea'] ?? 0 }},
                                                                {{ $row['M/E HSD']['sea'] ?? 0 }},
                                                                {{ $row['A/E MFO']['sea'] ?? 0 }},
                                                                {{ $row['A/E HSD']['sea'] ?? 0 }},
                                                                {{ $row['GENSET CONSUMPTION - HSD']['sea'] ?? 0 }},
                                                                {{ $row['REEFER 20"']['sea'] ?? 0 }},
                                                                {{ $row['REEFER 40"']['sea'] ?? 0 }},
                                                                {{ $row['BL MFO']['sea'] ?? 0 }},
                                                                {{ $row['BL HSD']['sea'] ?? 0 }},
                                                                {{ $row['BL REFFER']['sea'] ?? 0 }},
                                                                0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                                                                "", "", "", 0,
                                                                "", "", "", "",
                                                                0, 0, 0, 0, 0, 0, 0,
                                                                {!! $anomaliesJsonSea !!}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        @if($hasAnomaliesSea)
                                                        <button type="button"
                                                            onclick="showInfoModal('{{ ($row['Vessel ID']['sea'] ?? '') }} (Sea)', {!! $anomaliesJsonSea !!})"
                                                            class="text-blue-500 hover:text-blue-600 p-1 rounded hover:bg-blue-50 transition"
                                                            title="Info Blok Warna">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                            </svg>
                                                        </button>
                                                        @endif
                                                        <input type="checkbox" name="selected_rows_port_sea_sea[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 flex justify-end">
                        <button type="submit" class="py-2 px-4 bg-green-600 text-white rounded-md">
                            Send Email
                        </button>
                    </div>
                </form>
            @else
                <p class="px-4 py-4">Tidak ada data tersedia.</p>
            @endif
        </div>
    </div>
</div>

{{-- FUEL CONSUMPTION MODAL --}}
@if(!$isDinamis)
<div id="detailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeDetailModal()"></div>

    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="bg-gray-800 px-5 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">Fuel Consumption Detail</p>
                <h3 id="modal-vessel-id" class="text-white font-bold text-lg leading-tight"></h3>
                <span id="modal-vessel-type" class="text-xs text-gray-300"></span>
                <div id="modal-extra-info" class="text-xs text-gray-400 mt-0.5"></div>
            </div>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-white transition-colors ml-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="hidden bg-white border-b border-gray-200 px-5 py-3" id="anomalies-section-wrapper">
            <button onclick="toggleDetailAnomalies()" class="flex items-center text-xs font-semibold text-blue-600 hover:text-blue-700 focus:outline-none transition-colors">
                <span>Informasi Blok Warna</span>
                <svg id="anomalies-toggle-icon" class="w-4 h-4 ml-1 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="anomalies-content" class="hidden mt-2 p-3 bg-gray-900 rounded-md border-gray-700 shadow-inner max-h-48 overflow-y-auto">
                <!--Generate JS-->
            </div>
        </div>

        <div class="flex border-b border-gray-200">
            <button onclick="switchTab('me')" id="tab-me"
                class="flex-1 py-3 text-sm font-semibold text-center uppercase border-b-2 transition-colors tab-btn
                       border-blue-600 text-blue-600">
                Main Engine (ME)
            </button>
            <button onclick="switchTab('ae')" id="tab-ae"
                class="flex-1 py-3 text-sm font-semibold text-center uppercase border-b-2 transition-colors tab-btn
                       border-transparent text-gray-500 hover:text-gray-700">
                Auxiliary Engine (AE)
            </button>
            <button onclick="switchTab('genset')" id="tab-genset"
                class="flex-1 py-3 text-sm font-semibold text-center uppercase border-b-2 transition-colors tab-btn
                       border-transparent text-gray-500 hover:text-gray-700">
                Genset
            </button>
        </div>

        <div class="px-5 py-4 flex-1 overflow-y-auto">

            {{-- ME TAB --}}
            <div id="tab-content-me">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Baseline</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL ME (L/Hour)</span>
                                <span id="me-bl-lhour" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL ME (L/Day)</span>
                                <span id="me-bl-lday" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL L/Nm</span>
                                <span id="me-bl-lnm" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Operational</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Steam Time (Hours)</span>
                                <span id="me-steam-time" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Maneuvering Time (Hours)</span>
                                <span id="me-manuev-time" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Propeller Slip (%)</span>
                                <span id="me-prop-slip" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div id="me-ship-speed-row" class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Ship Speed</span>
                                <span id="me-ship-speed" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Consumption</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">MFO</span>
                                <span id="me-mfo" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">HSD</span>
                                <span id="me-hsd" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                             <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" id="me-ideal-toggle" onclick="toggleIdealDetail()"
                                        class="text-gray-500 hover:text-gray-700">
                                        <svg id="me-ideal-arrow" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <span class="text-sm font-bold text-gray-700">Ideal Consumption (Static)</span>
                                </div>
                                <span id="me-ideal-cost" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div id="me-ideal-detail" class="hidden px-4 py-2.5 bg-yellow-100 border-t border-yellow-200">
                                <div class="text-xs text-gray-800 space-y-1">
                                    <div class="text-gray-600 italic pb-1 border-b border-yellow-200 mb-1">
                                        BL ME (L/Hour) x (Steam Time + Maneuvering Time)
                                    </div>
                                    <div id="me-ideal-calc" class="text-gray-700 font-semibold"></div>
                                </div>
                            </div>
                            <div id="me-ideal-dynamic-row" class="flex justify-between items-center px-4 py-2.5 bg-yellow-100 border-t border-yellow-200">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" id="me-ideal-dynamic-toggle" onclick="toggleIdealDynamicDetail()"
                                        class="text-gray-500 hover:text-gray-700">
                                        <svg id="me-ideal-dynamic-arrow" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <span class="text-sm font-bold text-gray-700">Ideal Consumption (Dynamic)</span>
                                </div>
                                <span id="me-ideal-dynamic" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div id="me-ideal-dynamic-detail" class="hidden px-4 py-2.5 bg-yellow-100 border-t border-yellow-200">
                                <div class="text-xs text-gray-800 space-y-1">
                                    <div class="text-gray-600 italic pb-1 border-b border-yellow-200 mb-1">
                                        BL ME Dynamic (L/Hour) x (Steam Time + Maneuvering Time)
                                    </div>
                                    <div id="me-ideal-dynamic-calc" class="text-gray-700 font-semibold"></div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-700">Total Consumption</span>
                                <span id="me-total" class="text-sm font-bold text-gray-700"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-700">Excess</span>
                                <span id="me-excess" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div id="me-lnm-card" class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">L/Nm</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL L/Nm</span>
                                <span id="me-lnm-bl" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">STEAM. DIST.</span>
                                <span id="me-steam-dist" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" id="me-lnm-toggle" onclick="toggleLnmDetail()"
                                        class="text-gray-500 hover:text-gray-700">
                                        <svg id="me-lnm-arrow" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <span class="text-sm font-bold text-gray-700">Aktual L/Nm</span>
                                </div>
                                <span id="me-lnm-aktual" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div id="me-lnm-detail" class="hidden px-4 py-2.5 bg-yellow-100 border-t border-yellow-200">
                                <div class="text-xs text-gray-800 space-y-1">
                                    <div id="me-lnm-formula" class="text-gray-600 italic pb-1 border-b border-yellow-200 mb-1">
                                        ME Fuel / Steam Distance
                                    </div>
                                    <div id="me-lnm-calc" class="text-gray-700 font-semibold"></div>
                                </div>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-700">Excess L/Nm (%)</span>
                                <span id="me-lnm-exceed" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-700">Excess L/Nm</span>
                                <span id="me-lnm-exceed-abs" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AE TAB --}}
            <div id="tab-content-ae" class="hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Baseline</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL AE (L/Hour)</span>
                                <span id="ae-bl-lhour" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL AE (L/Day)</span>
                                <span id="ae-bl-lday" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL L/Nm</span>
                                <span id="ae-bl-lnm" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL AE 1 REEFER</span>
                                <span id="ae-bl-reffer" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">BL AE 2 REEFER (PARALLEL)</span>
                                <span id="ae-bl-parallel2" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Consumption</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">MFO</span>
                                <span id="ae-mfo" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">HSD</span>
                                <span id="ae-hsd" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-700">Total Consumption</span>
                                <span id="ae-total" class="text-sm font-bold text-gray-700"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <span class="text-sm font-bold text-gray-800">Excess</span>
                                <span id="ae-excess" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100">
                                <div class="flex items-center gap-1.5">
                                    <button type="button" id="ae-tolerance-toggle" onclick="toggleToleranceDetail()"
                                        class="text-gray-500 hover:text-gray-700">
                                        <svg id="ae-tolerance-arrow" xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 transition-transform"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                    <span class="text-sm font-bold text-gray-800">Excess Tolerance</span>
                                </div>
                                <span id="ae-excess-tolerance" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div id="ae-tolerance-detail" class="hidden px-4 py-2.5 bg-yellow-100 border-t border-yellow-200">
                                <div class="text-xs text-gray-600 italic pb-1 border-b border-yellow-200 mb-1">
                                    BL AE (L/Day) + (AE Pararel Duration x BL AE (L/Hour)) - Total Consumption
                                </div>
                                <div id="ae-tolerance-calc" class="text-gray-700 font-semibold text-xs"></div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Crane</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Total Crane</span>
                                <span id="ae-crane-qty" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Crane Duration (Hours)</span>
                                <span id="ae-crane-dur" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Operational</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Maneuvering Time (Hours)</span>
                                <span id="ae-manuev-time" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">AE Pararel Duration (Hours)</span>
                                <span id="ae-pararel-dur" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">AE Load</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Load A/E 1 (KW)</span><span id="ae-load1" class="text-sm font-semibold text-gray-800"></span></div>
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Load A/E 2 (KW)</span><span id="ae-load2" class="text-sm font-semibold text-gray-800"></span></div>
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Load A/E 3 (KW)</span><span id="ae-load3" class="text-sm font-semibold text-gray-800"></span></div>
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Load A/E 4 (KW)</span><span id="ae-load4" class="text-sm font-semibold text-gray-800"></span></div>
                        </div>
                    </div>
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Reefer</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Reefer 20"</span><span id="ae-reefer20" class="text-sm font-semibold text-gray-800"></span></div>
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Reefer 40"</span><span id="ae-reefer40" class="text-sm font-semibold text-gray-800"></span></div>
                            <div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm font-bold text-gray-700">Total Reefer</span><span id="ae-reefer-total" class="text-sm font-bold text-gray-700"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- GENSET TAB --}}
            <div id="tab-content-genset" class="hidden">
                <div class="w-full">
                    <div class="rounded-lg border border-gray-200 overflow-hidden">
                        <div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Consumption</span></div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex justify-between items-center px-4 py-2.5">
                                <span class="text-sm text-gray-500">Genset Consumption HSD</span>
                                <span id="genset-hsd" class="text-sm font-semibold text-gray-800"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 px-5 py-3 bg-gray-50 max-h-28 overflow-y-auto">
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-700 uppercase tracking-wider text-xs font-bold">Remarks</span>
                    <p id="modal-remarks" class="text-gray-700 mt-1 whitespace-pre-line text-xs"></p>
                </div>
                <div>
                    <span class="text-gray-700 uppercase tracking-wider text-xs font-bold">Deck Daily Work</span>
                    <p id="modal-deck-work" class="text-gray-700 mt-1 whitespace-pre-line text-xs"></p>
                </div>
                <div>
                    <span class="text-gray-700 uppercase tracking-wider text-xs font-bold">Engine Daily Work</span>
                    <p id="modal-engine-work" class="text-gray-700 mt-1 whitespace-pre-line text-xs"></p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('success_email'))
<script>
    window.onload = function() {
        alert("{{ session('success_email') }}");
    }
</script>
@endif

@if(!$isDinamis)
<script>
    function switchTab(tab) {
        ['me','ae','genset'].forEach(t => {
            document.getElementById('tab-content-' + t).classList.add('hidden');
            document.getElementById('tab-' + t).classList.remove('border-blue-600','text-blue-600');
            document.getElementById('tab-' + t).classList.add('border-transparent','text-gray-500');
        });
        document.getElementById('tab-content-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).classList.add('border-blue-600','text-blue-600');
        document.getElementById('tab-' + tab).classList.remove('border-transparent','text-gray-500');
    }

    function toggleIdealDetail() {
        const detail = document.getElementById('me-ideal-detail');
        const arrow  = document.getElementById('me-ideal-arrow');
        detail.classList.toggle('hidden');
        arrow.classList.toggle('rotate-90');
    }
    
    function toggleIdealDynamicDetail() {
        const detail = document.getElementById('me-ideal-dynamic-detail');
        const arrow  = document.getElementById('me-ideal-dynamic-arrow');
        detail.classList.toggle('hidden');
        arrow.classList.toggle('rotate-90');
    }

    function toggleToleranceDetail() {
        const detail = document.getElementById('ae-tolerance-detail');
        const arrow  = document.getElementById('ae-tolerance-arrow');
        detail.classList.toggle('hidden');
        arrow.classList.toggle('rotate-90');
    }

    function toggleLnmDetail() {
        const detail = document.getElementById('me-lnm-detail');
        const arrow  = document.getElementById('me-lnm-arrow');
        detail.classList.toggle('hidden');
        arrow.classList.toggle('rotate-90');
    }

    function fmt(val) {
        const num = parseFloat(val) || 0;
        const isInt = Number.isInteger(num);
        return num.toLocaleString('id-ID', {
            minimumFractionDigits: isInt ? 0 : 2,
            maximumFractionDigits: isInt ? 0 : 2
        }) + ' L';
    }
    function fmtNum(val, suffix = '') {
        const num = parseFloat(val) || 0;
        const isInt = Number.isInteger(num);
        return num.toLocaleString('id-ID', {
            minimumFractionDigits: isInt ? 0 : 2,
            maximumFractionDigits: isInt ? 0 : 2
        }) + (suffix ? ' ' + suffix : '');
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showDetailModal(
        vesselId, vesselType, meMfo, meHsd, aeMfo, aeHsd, gensetHsd, reefer20, reefer40,
        blMfo, blHsd, blReffer, maneuvTime, steamTime, propSlip, craneDur, craneQty,
        loadAe1, loadAe2, loadAe3, loadAe4, aePararelDur, remarks, deckWork, engineWork,
        blAeParallel2, tanggal = '', position = '', departurePort = '', destination = '',
        blLNm = 0, lnmAktual = 0, lnmExceed = 0,
        excessAe = 0, excessAeTolerance = 0, excessMe = 0, shipSpeed = 0,
        idealDynamic = 0, anomalies = null, steamDist = 0
    ) {
        meMfo        = parseFloat(meMfo)        || 0;
        meHsd        = parseFloat(meHsd)        || 0;
        aeMfo        = parseFloat(aeMfo)        || 0;
        aeHsd        = parseFloat(aeHsd)        || 0;
        gensetHsd    = parseFloat(gensetHsd)    || 0;
        reefer20     = parseFloat(reefer20)     || 0;
        reefer40     = parseFloat(reefer40)     || 0;
        blMfo        = parseFloat(blMfo)        || 0;
        blHsd        = parseFloat(blHsd)        || 0;
        blReffer     = parseFloat(blReffer)     || 0;
        maneuvTime   = parseFloat(maneuvTime)   || 0;
        steamTime    = parseFloat(steamTime)    || 0;
        propSlip     = parseFloat(propSlip)     || 0;
        craneDur     = parseFloat(craneDur)     || 0;
        craneQty     = parseFloat(craneQty)     || 0;
        loadAe1      = parseFloat(loadAe1)      || 0;
        loadAe2      = parseFloat(loadAe2)      || 0;
        loadAe3      = parseFloat(loadAe3)      || 0;
        loadAe4      = parseFloat(loadAe4)      || 0;
        aePararelDur = parseFloat(aePararelDur) || 0;
        blLNm        = parseFloat(blLNm)        || 0;
        shipSpeed    = parseFloat(shipSpeed)    || 0;
        steamDist    = parseFloat(steamDist)    || 0;

        const meTotal   = meMfo + meHsd;
        const blMeLHour = blMfo;
        const blMeLDay  = blMfo * 24;
        const meExcess  = parseFloat(excessMe) || 0;
        const idealCost = meExcess + meTotal;

        const aeTotal   = aeMfo + aeHsd + gensetHsd;
        const blAeLHour = blHsd;
        const blAeLDay  = blHsd * 24;
        const aeExcess  = parseFloat(excessAe) || 0;
        const aeExcessTolerance = parseFloat(excessAeTolerance) || 0;

        document.getElementById('modal-vessel-id').textContent   = vesselId;
        document.getElementById('modal-vessel-type').textContent = vesselType;

        let extraLine2 = '';
        if (vesselType === 'At Port' && position) {
            extraLine2 = position;
        } else if (vesselType === 'At Sea' && (departurePort || destination)) {
            extraLine2 = (departurePort || '-') + ' to ' + (destination || '-');
        }
        document.getElementById('modal-extra-info').innerHTML =
            (tanggal ? escapeHtml(tanggal) : '') +
            (tanggal && extraLine2 ? '<br>' : '') +
            (extraLine2 ? escapeHtml(extraLine2) : '');

        document.getElementById('me-bl-lhour').textContent    = fmtNum(blMeLHour, 'L/H');
        document.getElementById('me-bl-lday').textContent     = fmtNum(blMeLDay, 'L/Day');
        document.getElementById('me-bl-lnm').textContent = fmtNum(blLNm, 'L/Nm');
        document.getElementById('ae-bl-lnm').textContent = fmtNum(blLNm, 'L/Nm');
        document.getElementById('me-steam-time').textContent  = fmtNum(steamTime, 'H');
        document.getElementById('me-manuev-time').textContent = fmtNum(maneuvTime, 'H');
        if (document.getElementById('me-ship-speed-row')) {
            if (vesselType === 'At Port') {
                document.getElementById('me-ship-speed-row').classList.add('hidden');
                document.getElementById('me-ship-speed-row').classList.remove('flex');
                if (document.getElementById('me-ideal-dynamic-row')) {
                    document.getElementById('me-ideal-dynamic-row').classList.add('hidden');
                    document.getElementById('me-ideal-dynamic-row').classList.remove('flex');
                }
            } else {
                document.getElementById('me-ship-speed-row').classList.remove('hidden');
                document.getElementById('me-ship-speed-row').classList.add('flex');
                if (document.getElementById('me-ideal-dynamic-row')) {
                    document.getElementById('me-ideal-dynamic-row').classList.remove('hidden');
                    document.getElementById('me-ideal-dynamic-row').classList.add('flex');
                }
            }
            document.getElementById('me-ship-speed').textContent = fmtNum(shipSpeed, 'Knot');
        }

        lnmAktual = parseFloat(lnmAktual) || 0;
        lnmExceed = parseFloat(lnmExceed) || 0;

        document.getElementById('me-steam-dist').textContent = fmtNum(steamDist, 'Nm');
        document.getElementById('me-lnm-bl').textContent     = fmtNum(blLNm, 'L/Nm');
        document.getElementById('me-lnm-aktual').textContent = fmtNum(lnmAktual, 'L/Nm');

        const meFuelName = meMfo > 0 ? 'ME MFO' : 'ME HSD';
        const meFuelVal  = meMfo > 0 ? meMfo : meHsd;
        const meLnmFormulaEl = document.getElementById('me-lnm-formula');
        if (meLnmFormulaEl) {
            meLnmFormulaEl.textContent = meFuelName + ' / Steam Distance';
        }
        const meLnmCalcEl = document.getElementById('me-lnm-calc');
        if (meLnmCalcEl) {
            meLnmCalcEl.textContent = fmt(meFuelVal) + ' / ' + fmtNum(steamDist, 'Nm') + ' = ' + fmtNum(lnmAktual, 'L/Nm');
        }

        document.getElementById('me-lnm-detail').classList.add('hidden');
        document.getElementById('me-lnm-arrow').classList.remove('rotate-90');

        document.getElementById('me-lnm-card').classList.toggle('hidden', vesselType !== 'At Sea');

        const lnmExceedEl = document.getElementById('me-lnm-exceed');
        lnmExceedEl.textContent = fmtNum(lnmExceed, '%');
        lnmExceedEl.className = 'text-sm font-semibold ' + (lnmExceed < 0 ? 'text-red-600 font-bold' : 'text-green-600');

        const lnmExceedAbs = blLNm - lnmAktual;
        const lnmExceedAbsEl = document.getElementById('me-lnm-exceed-abs');
        lnmExceedAbsEl.textContent = fmtNum(lnmExceedAbs, 'L/Nm');
        lnmExceedAbsEl.className = 'text-sm font-semibold ' + (lnmExceedAbs < 0 ? 'text-red-600 font-bold' : 'text-green-600');

        const mePropSlipEl = document.getElementById('me-prop-slip');
        mePropSlipEl.textContent = fmtNum(propSlip, '%');
        mePropSlipEl.className = 'text-sm font-semibold ' + (Math.abs(propSlip) >= 15 ? 'text-red-600 font-bold' : 'text-gray-800');

        document.getElementById('me-mfo').textContent                 = fmt(meMfo);
        document.getElementById('me-hsd').textContent                 = fmt(meHsd);
        document.getElementById('me-total').textContent               = fmt(meTotal);
        document.getElementById('me-ideal-cost').textContent          = fmt(idealCost);
        document.getElementById('me-ideal-dynamic').textContent       = fmt(idealDynamic);

        const totalTime = steamTime + maneuvTime;
        document.getElementById('me-ideal-calc').textContent          = fmtNum(blMeLHour) + ' x (' + fmtNum(steamTime) + ' + ' + fmtNum(maneuvTime) + ') = ' + fmt(idealCost);

        let blMeDynamicLHour = 0;
        if (totalTime > 0) {
            blMeDynamicLHour = idealDynamic / totalTime;
        }
        document.getElementById('me-ideal-dynamic-calc').textContent  = fmtNum(blMeDynamicLHour) + ' x (' + fmtNum(steamTime) + ' + ' + fmtNum(maneuvTime) + ') = ' + fmt(idealDynamic);

        document.getElementById('me-ideal-detail').classList.add('hidden');
        document.getElementById('me-ideal-arrow').classList.remove('rotate-90');
        
        document.getElementById('me-ideal-dynamic-detail').classList.add('hidden');
        document.getElementById('me-ideal-dynamic-arrow').classList.remove('rotate-90');

        document.getElementById('ae-tolerance-detail').classList.add('hidden');
        document.getElementById('ae-tolerance-arrow').classList.remove('rotate-90');

        document.getElementById('ae-tolerance-calc').textContent = fmt(blAeLDay) + ' + (' + fmtNum(aePararelDur, 'H') + ' x ' + fmt(blAeLHour) + ') - ' + fmt(aeTotal) + ' = ' + fmt(blAeLDay + (aePararelDur * blAeLHour) - aeTotal);

        const meExcessEl = document.getElementById('me-excess');
        meExcessEl.textContent = fmt(meExcess);
        meExcessEl.className = 'text-sm font-semibold ' + (meExcess < 0 ? 'text-red-600' : 'text-green-600');

        document.getElementById('ae-bl-lhour').textContent     = fmtNum(blAeLHour, 'L/H');
        document.getElementById('ae-bl-lday').textContent      = fmtNum(blAeLDay, 'L/Day');
        document.getElementById('ae-bl-reffer').textContent    = Math.round(blReffer).toLocaleString('id-ID');
        document.getElementById('ae-bl-parallel2').textContent = Math.round(parseFloat(blAeParallel2) || 0).toLocaleString('id-ID');
        document.getElementById('ae-mfo').textContent          = fmt(aeMfo);
        document.getElementById('ae-hsd').textContent          = fmt(aeHsd);
        document.getElementById('ae-total').textContent        = fmt(aeTotal);
        document.getElementById('ae-crane-dur').textContent    = fmtNum(craneDur, 'H');
        document.getElementById('ae-crane-qty').textContent    = fmtNum(craneQty);
        document.getElementById('ae-manuev-time').textContent  = fmtNum(maneuvTime, 'H');
        document.getElementById('ae-pararel-dur').textContent  = fmtNum(aePararelDur, 'H');
        document.getElementById('ae-load1').textContent        = fmtNum(loadAe1, 'KW');
        document.getElementById('ae-load2').textContent        = fmtNum(loadAe2, 'KW');
        document.getElementById('ae-load3').textContent        = fmtNum(loadAe3, 'KW');
        document.getElementById('ae-load4').textContent        = fmtNum(loadAe4, 'KW');
        document.getElementById('ae-reefer20').textContent     = Math.round(reefer20).toLocaleString('id-ID');
        document.getElementById('ae-reefer40').textContent     = Math.round(reefer40).toLocaleString('id-ID');
        document.getElementById('ae-reefer-total').textContent = Math.round(reefer20 + reefer40).toLocaleString('id-ID');

        const aeExcessEl = document.getElementById('ae-excess');
        aeExcessEl.textContent = fmt(aeExcess);
        aeExcessEl.className = 'text-sm font-semibold ' + (aeExcess < 0 ? 'text-red-600' : 'text-green-600');

        const aeExcessToleranceEl = document.getElementById('ae-excess-tolerance');
        aeExcessToleranceEl.textContent = fmt(aeExcessTolerance);
        aeExcessToleranceEl.className = 'text-sm font-semibold ' + (aeExcessTolerance < 0 ? 'text-red-600' : 'text-green-600');

        document.getElementById('genset-hsd').textContent = fmt(gensetHsd);

        document.getElementById('modal-remarks').textContent     = remarks || '-';
        document.getElementById('modal-deck-work').textContent   = deckWork || '-';
        document.getElementById('modal-engine-work').textContent = engineWork || '-';

        const anomaliesWrapper = document.getElementById('anomalies-section-wrapper');
        const anomaliesContent = document.getElementById('anomalies-content');
        const anomaliesIcon = document.getElementById('anomalies-toggle-icon');

        anomaliesContent.classList.add('hidden');
        anomaliesIcon.classList.remove('rotate-180');
        anomaliesContent.innerHTML = '';

        let hasItems = false;
        
        const colorNames = {
            'green': 'Blok Hijau (Baseline)',
            'red': 'Blok Merah (Kritis/Defisit)',
            'yellow': 'Blok Kuning (Peringatan)',
            'blue': 'Blok Biru (Info Pararel)'
        };
        
        const colorStyles = {
            'green': 'text-green-700 bg-green-100',
            'red': 'text-red-700 bg-red-100',
            'yellow': 'text-yellow-700 bg-yellow-100',
            'blue': 'text-blue-700 bg-blue-100'
        };

        if (typeof anomalies === 'object' && anomalies !== null) {
            for (const [colorKey, messages] of Object.entries(anomalies)) {
                if (messages && messages.length > 0) {
                    hasItems = true;
                    
                    const groupHeader = document.createElement('h4');
                    groupHeader.className = 'font-bold text-xs px-2 py-1 rounded inline-block mt-2 mb-1 ' + (colorStyles[colorKey] || 'bg-gray-200 text-gray-800');
                    groupHeader.textContent = colorNames[colorKey] || colorKey;
                    
                    if(anomaliesContent.children.length === 0) {
                        groupHeader.classList.remove('mt-2');
                    }
                    
                    anomaliesContent.appendChild(groupHeader);
                    
                    const ul = document.createElement('ul');
                    ul.className = 'list-disc pl-6 space-y-1 text-xs text-gray-300';
                    
                    messages.forEach(function(msg) {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        ul.appendChild(li);
                    });
                    
                    anomaliesContent.appendChild(ul);
                }
            }
        }

        if (hasItems) {
            anomaliesWrapper.classList.remove('hidden');
        } else {
            anomaliesWrapper.classList.add('hidden');
        }

        switchTab('me');
        document.getElementById('detailModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    function toggleDetailAnomalies() {
        const content = document.getElementById('anomalies-content');
        const icon = document.getElementById('anomalies-toggle-icon');
        
        if (content.classList.contains('hidden')) {
            content.classList.remove('hidden');
            icon.classList.add('rotate-180');
        } else {
            content.classList.add('hidden');
            icon.classList.remove('rotate-180');
        }
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetailModal();
    });
</script>

<div id="infoModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeInfoModal()"></div>
    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-lg mx-4 overflow-hidden">
        <div class="bg-gray-800 px-5 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">Info Blok Warna</p>
                <h3 id="modal-info-vessel" class="text-white font-bold text-lg leading-tight"></h3>
            </div>
            <button onclick="closeInfoModal()" class="text-gray-400 hover:text-white transition-colors ml-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <div id="info-container" class="px-5 py-4 max-h-[60vh] overflow-y-auto">
        </div>
    </div>
</div>

<script>
    function showInfoModal(vesselId, anomalies) {
        document.getElementById('modal-info-vessel').textContent = vesselId;
        const container = document.getElementById('info-container');
        container.innerHTML = '';

        const colorNames = {
            'green': 'Blok Hijau (Baseline)',
            'red': 'Blok Merah (Kritis/Defisit)',
            'yellow': 'Blok Kuning (Peringatan)',
            'blue': 'Blok Biru (Info Pararel)'
        };

        const colorStyles = {
            'green': 'text-green-700 bg-green-100',
            'red': 'text-red-700 bg-red-100',
            'yellow': 'text-yellow-700 bg-yellow-100',
            'blue': 'text-blue-700 bg-blue-100'
        };

        let hasItems = false;

        if (typeof anomalies === 'object' && anomalies !== null) {
            for (const [colorKey, messages] of Object.entries(anomalies)) {
                if (messages && messages.length > 0) {
                    hasItems = true;

                    const groupHeader = document.createElement('h4');
                    groupHeader.className = 'font-bold text-sm px-2 py-1 rounded inline-block mt-3 mb-2 ' + (colorStyles[colorKey] || 'bg-gray-200 text-gray-800');
                    groupHeader.textContent = colorNames[colorKey] || colorKey;

                    if(container.children.length === 0) {
                        groupHeader.classList.remove('mt-3');
                    }

                    container.appendChild(groupHeader);

                    const ul = document.createElement('ul');
                    ul.className = 'list-disc pl-6 space-y-1 text-sm text-gray-700';

                    messages.forEach(function(msg) {
                        const li = document.createElement('li');
                        li.textContent = msg;
                        ul.appendChild(li);
                    });

                    container.appendChild(ul);
                }
            }
        }

        if (!hasItems) {
            container.innerHTML = '<p class="text-sm text-gray-500">Tidak ada pesan.</p>';
        }

        document.getElementById('infoModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeInfoModal() {
        document.getElementById('infoModal').classList.add('hidden');
        document.body.style.overflow = '';
    }
</script>

@endif

@endsection
