@extends('layouts.app')

@section('title', 'Consumption Analysis')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h1 class="text-xl font-bold">Consumption Analysis</h1>
        </div>

        <div class="border rounded-md p-6">
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
                    onchange="window.location='{{ route('po.upload') }}?report_date='+this.value">
            </div>
            
            @if((is_array($report14)) || is_array($report16))
                <form action="{{ route('send.email') }}" method="POST">
                    @csrf
                    <div class="mt-3 px-4 py-2 rounded-md text-lg font-bold mb-4">
                        <h2 class="text-xl font-semibold text-green-700 bg-green-100 inline-block px-2 rounded">At PORT</h2>
                    </div>

                    <div class="overflow-x-auto overflow-y-auto max-h-[460px] rounded-md shadow-sm w-full">
                        @if(!empty($report14))
                            @php
                                $excludedHeaders_port = [
                                    'DEPARTURE PORT',
                                    'DESTINATION',
                                    'STEAM. DIST.',
                                    'STEAM TIME (HOUR : MINUTE)',
                                    'SHIP SPEED',
                                    'PROPELLER SLIP',
                                    'ME RPM',
                                    'BL L/NM',
                                    'L/NM',
                                    'EXCESS ME MFO L/NM (%)'
                                ];
                            @endphp

                            <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                                <thead class="bg-gray-300 sticky top-0 z-10">
                                    <tr>
                                        @foreach($headers_port as $header)
                                            @if(!in_array($header, $excludedHeaders_port))
                                                <th class="px-4 py-2 text-center border-2 border-black">{{ ucfirst($header) }}</th>
                                            @endif
                                        @endforeach
                                        <th class="px-4 py-2 border-2 border-black">Pilih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report14 as $index => $row)
                                        <tr class="text-center odd:bg-white even:bg-gray-200">
                                            @foreach ($row as $colIndex => $cell)
                                                @if(!in_array($colIndex, $excludedHeaders_port))
                                                    <td class="px-4 py-2 border-2 border-black {{ $cell['class'] }}">
                                                        {{ $colIndex === 0 ? $cell['value'] : (is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value']) }}
                                                    </td>
                                                @endif
                                            @endforeach
                                            <td class="px-4 py-2 text-center border-2 border-black">
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
                                <thead class="bg-gray-300 sticky top-0 z-10">
                                    <tr>
                                        @foreach($headers_sea as $header)
                                            @if(!in_array($header, $excludedHeaders_sea))
                                                <th class="px-4 py-2 text-center border-2 border-black">{{ ucfirst($header) }}</th>
                                            @endif
                                        @endforeach
                                        <th class="px-4 py-2 border-2 border-black">Pilih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report16 as $index => $row)
                                        <tr class="text-center odd:bg-white even:bg-gray-200">
                                            @foreach ($row as $colIndex => $cell)
                                                @if(!in_array($colIndex, $excludedHeaders_sea))
                                                    <td class="px-4 py-2 border-2 border-black {{ $cell['class'] }}">
                                                        {{ $colIndex === 0 ? $cell['value'] : (is_numeric($cell['value']) ? number_format($cell['value'], 2, '.', ',') : $cell['value']) }}
                                                    </td>
                                                @endif
                                            @endforeach
                                            <td class="px-4 py-2 text-center border-2 border-black">
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
