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
                <form method="GET" action="{{ route('po.upload') }}" class="flex items-center gap-3">
                    <input type="date" id="report_date" name="report_date"
                        class="border border-gray-300 rounded-md px-4 py-2 w-64"
                        value="{{ request('report_date') }}">
                    <button type="submit"
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Tampilkan
                    </button>
                </form>
            </div>
            
            @if((is_array($report14)) || is_array($report16))
                <form action="{{ route('send.email') }}" method="POST">
                    @csrf
                    <div x-data="{ compact: true }">
                        <div class="mt-3 px-4 py-2 rounded-md text-lg font-bold mb-4 flex justify-between items-center">
                            <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">
                                At PORT
                            </h2>
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
                                        'SHIP SPEED','PROPELLER SLIP','ME RPM','BL L/NM','L/NM','EXCESS ME MFO L/NM (%)',
                                        'BL MFO','BL HSD','BL REFFER'
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
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($report14 as $index => $row)
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr class="text-center {{ $rowClass ?: 'odd:bg-white even:bg-gray-200' }}">
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['value'] }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID' && $colIndex !== '_row_class' && !in_array($colIndex, $excludedHeaders_port))
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_port); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black {{ $rowClass ? '' : $cell['class'] }}">
                                                            {{ is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
                                                    <div class="flex items-center justify-center gap-2">
                                                        {{-- Tombol Detail --}}
                                                        <button type="button"
                                                            onclick='showDetailModal(
                                                                "{{ $row['Vessel ID']['value'] }}",
                                                                "At Port",
                                                                0,
                                                                {{ $row['M/E HSD']['value'] ?? 0 }},
                                                                {{ $row['A/E MFO']['value'] ?? 0 }},
                                                                {{ $row['A/E HSD']['value'] ?? 0 }},
                                                                {{ $row['GENSET CONSUMPTION - HSD']['value'] ?? 0 }},
                                                                {{ $row['REEFER 20"']['value'] ?? 0 }},
                                                                {{ $row['REEFER 40"']['value'] ?? 0 }},
                                                                {{ $row['BL MFO']['value'] ?? 0 }},
                                                                {{ $row['BL HSD']['value'] ?? 0 }},
                                                                {{ $row['BL REFFER']['value'] ?? 0 }}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        {{-- Checkbox Select --}}
                                                        <input type="checkbox" name="selected_rows_port[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
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
                                    $excludedHeaders_sea = ['POSITION', 'BL MFO', 'BL HSD', 'BL REFFER'];
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
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($report16 as $index => $row)
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr class="text-center {{ $rowClass ?: 'odd:bg-white even:bg-gray-200' }}">
                                                <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                    {{ $row['Vessel ID']['value'] }}
                                                </td>
                                                @foreach ($row as $colIndex => $cell)
                                                    @if($colIndex !== 'Vessel ID' && $colIndex !== '_row_class' && !in_array($colIndex, $excludedHeaders_sea))
                                                        @php $isCompact = in_array($colIndex, $compactHeaders_sea); @endphp
                                                        <td x-show="!compact || $el.dataset.compact === 'true'"
                                                            data-compact="{{ $isCompact ? 'true' : 'false' }}"
                                                            class="px-4 py-2 text-center border border-black {{ $rowClass ? '' : $cell['class'] }}">
                                                            {{ is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value'] }}
                                                        </td>
                                                    @endif
                                                @endforeach
                                                <td class="px-4 py-2 text-center border border-black">
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
                                                                {{ $row['BL REFFER']['value'] ?? 0 }}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        {{-- Checkbox Select --}}
                                                        <input type="checkbox" name="selected_rows_sea[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
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
                                            <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($port_sea_data as $index => $row)
                                            {{-- Baris Port --}}
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr class="text-center {{ $rowClass ?: 'odd:bg-white even:bg-gray-200' }}">
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
                                                                {{ $row['BL REFFER']['port'] ?? 0 }}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        <input type="checkbox" name="selected_rows_port_sea_port[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
                                                </td>
                                            </tr>
                                            {{-- Baris Sea --}}
                                            @php $rowClass = $row['_row_class']['value'] ?? ''; @endphp
                                            <tr class="text-center {{ $rowClass ?: 'odd:bg-white even:bg-gray-200' }}">
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
                                                                {{ $row['BL REFFER']['sea'] ?? 0 }}
                                                            )'
                                                            class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition"
                                                            title="Lihat Detail">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                        </button>
                                                        <input type="checkbox" name="selected_rows_port_sea_sea[]" value="{{ $index }}" class="form-checkbox">
                                                    </div>
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

{{-- FUEL CONSUMPTION MODAL --}}
<div id="detailModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeDetailModal()"></div>

    <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 overflow-hidden">
        <div class="bg-gray-800 px-5 py-4 flex items-center justify-between">
            <div>
                <p class="text-xs text-gray-400 uppercase tracking-widest mb-0.5">Fuel Consumption Detail</p>
                <h3 id="modal-vessel-id" class="text-white font-bold text-lg leading-tight"></h3>
                <span id="modal-vessel-type" class="text-xs text-gray-300"></span>
            </div>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-white transition-colors ml-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-5 py-4 grid grid-cols-2 gap-4">
            <div class="rounded-lg border border-gray-300 overflow-hidden">
                <div class="bg-gray-800 px-4 py-2">
                    <span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Baseline</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">BL MFO</span>
                        <span id="modal-bl-mfo" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">BL HSD</span>
                        <span id="modal-bl-hsd" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">BL Reefer</span>
                        <span id="modal-bl-reffer" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                </div>
            </div>
            <div class="rounded-lg border border-gray-300 overflow-hidden">
                <div class="bg-gray-800 px-4 py-2">
                    <span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Auxiliary Engine (AE)</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">AE MFO</span>
                        <span id="modal-ae-mfo" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">AE HSD</span>
                        <span id="modal-ae-hsd" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">Genset Consumption HSD</span>
                        <span id="modal-genset-hsd" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm font-bold text-gray-700">Total AE</span>
                        <span id="modal-ae-total" class="text-sm font-bold text-gray-700"></span>
                    </div>
                </div>
            </div>
            <div class="rounded-lg border border-gray-300 overflow-hidden">
                <div class="bg-gray-800 px-4 py-2">
                    <span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Main Engine (ME)</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">ME MFO</span>
                        <span id="modal-me-mfo" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">ME HSD</span>
                        <span id="modal-me-hsd" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm font-bold text-gray-700">Total ME</span>
                        <span id="modal-me-total" class="text-sm font-bold text-gray-700"></span>
                    </div>
                </div>
            </div>
            <div class="rounded-lg border border-gray-300 overflow-hidden">
                <div class="bg-gray-800 px-4 py-2">
                    <span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Reefer</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">Reefer 20"</span>
                        <span id="modal-reefer20" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">Reefer 40"</span>
                        <span id="modal-reefer40" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm font-bold text-gray-700">Total Reefer</span>
                        <span id="modal-reefer-total" class="text-sm font-bold text-gray-700"></span>
                    </div>
                </div>
            </div>
            <div class="rounded-lg border border-gray-800 overflow-hidden col-span-2">
                <div class="bg-gray-800 px-4 py-2">
                    <span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Total Fuel Consumption</span>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">MFO</span>
                        <span id="modal-total-mfo" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5">
                        <span class="text-sm text-gray-500">HSD</span>
                        <span id="modal-total-hsd" class="text-sm font-semibold text-gray-800"></span>
                    </div>
                    <div class="flex justify-between items-center px-4 py-2.5 bg-gray-800">
                        <span class="text-sm font-bold text-white">Total Fuel</span>
                        <span id="modal-total-fuel" class="text-sm font-bold text-white"></span>
                    </div>
                </div>
            </div>

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

        const form = document.getElementById("emailForm");
        if (form) {
            form.addEventListener("submit", function() {
                showLoader();
            });
        }
    });

    function fmt(val) {
        const num = parseFloat(val) || 0;
        return num.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' L';
    }

    function showDetailModal(vesselId, vesselType, meMfo, meHsd, aeMfo, aeHsd, gensetHsd, reefer20, reefer40, blMfo, blHsd, blReffer) {
        meMfo     = parseFloat(meMfo)     || 0;
        meHsd     = parseFloat(meHsd)     || 0;
        aeMfo     = parseFloat(aeMfo)     || 0;
        aeHsd     = parseFloat(aeHsd)     || 0;
        gensetHsd = parseFloat(gensetHsd) || 0;
        reefer20  = parseFloat(reefer20)  || 0;
        reefer40  = parseFloat(reefer40)  || 0;
        blMfo     = parseFloat(blMfo)     || 0;
        blHsd     = parseFloat(blHsd)     || 0;
        blReffer  = parseFloat(blReffer)  || 0;

        const aeTotal   = aeMfo + aeHsd + gensetHsd;
        const meTotal   = meMfo + meHsd;
        const totalMfo  = aeMfo + meMfo;
        const totalHsd  = aeHsd + meHsd + gensetHsd;
        const totalFuel = totalMfo + totalHsd;
        const totalReefer = reefer20 + reefer40;

        document.getElementById('modal-vessel-id').textContent   = vesselId;
        document.getElementById('modal-vessel-type').textContent = vesselType;

        document.getElementById('modal-bl-mfo').textContent    = fmt(blMfo);
        document.getElementById('modal-bl-hsd').textContent    = fmt(blHsd);
        document.getElementById('modal-bl-reffer').textContent = blReffer.toLocaleString('id-ID');

        document.getElementById('modal-ae-mfo').textContent    = fmt(aeMfo);
        document.getElementById('modal-ae-hsd').textContent    = fmt(aeHsd);
        document.getElementById('modal-genset-hsd').textContent = fmt(gensetHsd);
        document.getElementById('modal-ae-total').textContent  = fmt(aeTotal);

        document.getElementById('modal-me-mfo').textContent   = fmt(meMfo);
        document.getElementById('modal-me-hsd').textContent   = fmt(meHsd);
        document.getElementById('modal-me-total').textContent = fmt(meTotal);

        document.getElementById('modal-reefer20').textContent     = reefer20.toLocaleString('id-ID');
        document.getElementById('modal-reefer40').textContent     = reefer40.toLocaleString('id-ID');
        document.getElementById('modal-reefer-total').textContent = totalReefer.toLocaleString('id-ID');

        document.getElementById('modal-total-mfo').textContent  = fmt(totalMfo);
        document.getElementById('modal-total-hsd').textContent  = fmt(totalHsd);
        document.getElementById('modal-total-fuel').textContent = fmt(totalFuel);

        document.getElementById('detailModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeDetailModal() {
        document.getElementById('detailModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeDetailModal();
    });
</script>
@endsection