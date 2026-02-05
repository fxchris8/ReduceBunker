@extends('layouts.app')

@section('title', 'Consumption Analysis Dinamis')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Consumption Analysis Dinamis</h1>
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
                    <option value="{{ url('/consumption-analysis/statis') }}"
                        {{ request()->is('consumption-analysis/statis') ? 'selected' : '' }}>
                        Baseline Statis
                    </option>
                    <option value="{{ url('/consumption-analysis/dinamis') }}"
                        {{ request()->is('consumption-analysis/dinamis') ? 'selected' : '' }}>
                        Baseline Dinamis
                    </option>
                </select>
            </div>

            <div class="px-4 py-2 rounded-md text-lg">
                <div class="mb-4">
                    <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                        Tanggal Laporan
                    </label>
                    <input type="date" id="report_date" name="report_date"
                        class="border border-gray-300 rounded-md px-4 py-2 w-64"
                        value="{{ request('report_date', date('Y-m-d', strtotime('-1 day'))) }}"
                        onchange="location.href='{{ route('po.upload_dinamis') }}?report_date='+this.value+'&density='+document.getElementById('density').value">
                </div>       

                <div>
                    <label for="density" class="block text-sm font-medium text-gray-700 mb-2">Masukkan Density (g/L):</label>
                    <input type="number" step="any" name="density" id="density"
                        value="{{ request('density', 950) }}"
                        class="block w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-red-600 transition duration-150 ease-in-out"
                        onchange="location.href='{{ route('po.upload_dinamis') }}?report_date='+(document.getElementById('report_date').value || '{{ date('Y-m-d', strtotime('-1 day')) }}')+'&density='+this.value">
                </div>
            </div>
            
            @if(is_array($report16))
                <form action="{{ route('send.email') }}" method="POST">
                    @csrf
                    <div class="mt-5 px-4 py-2 rounded-md text-lg font-bold mb-4">
                        <h2 class="text-xl font-semibold text-blue-700 bg-blue-100 inline-block px-2 rounded">At SEA</h2>
                    </div>
                    <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-md shadow-sm w-full">
                        @if(!empty($report16))
                            @php
                                $excludedHeaders_sea = [
                                    'POSITION'
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
                                                <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">{{ ucfirst($header) }}</th>
                                            @endif
                                        @endforeach
                                        <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30">Pilih</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($colored_sea as $index => $row)
                                        <tr class="text-center odd:bg-white even:bg-gray-200">
                                            <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                                {{ $row['Vessel ID']['value'] }}
                                            </td>
                                            @foreach ($row as $colIndex => $cell)
                                                @if($colIndex !== 'Vessel ID' && !in_array($colIndex, $excludedHeaders_sea))
                                                    <td class="px-4 py-2 text-center border border-black {{ $cell['class'] }}"
                                                        title="{{ $cell['message'] }}">
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
@endsection