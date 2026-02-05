@extends('layouts.app')

@section('title', 'Details of ME MFO Consumption > BL')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h2 class="text-xl font-bold">Details of ME MFO Consumption > BL At {{ strtoupper($sessionType) }}</h2>
        </div>
        <div class="border rounded-md p-6">
            <div class="overflow-x-auto overflow-y-auto max-h-[550px] rounded-md shadow-sm w-full">
                <table class="table-fixed w-full divide-y divide-gray-200 text-sm text-center rounded border">
                    <thead class="bg-gray-300 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 text-center border border-black">Vessel</th>
                            <th class="px-4 py-2 text-center border border-black">Steam Distance (Miles)</th>
                            <th class="px-4 py-2 text-center border border-black">Steam Time (Hour)</th>
                            <th class="px-4 py-2 text-center border border-black">ME MFO</th>
                            <th class="px-4 py-2 text-center border border-black">BL L/Nm</th>
                            <th class="px-4 py-2 text-center border border-black">L/NM</th>
                            <th class="px-4 py-2 text-center border border-black">EXCESS ME MFO L/NM (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $row)
                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                <td class="px-4 py-2 text-center border border-black">{{ $row['vessel'] }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['steam_distance'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['steam_time'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['me_mfo'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['bl_l_nm'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['l_nm'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 text-center border border-black">{{ number_format($row['excess_me_mfo_l_nm'], 0, '.', ',') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button
                onclick="window.history.back()"
                class="mt-6 w-[100px] py-2 px-4 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                Return
            </button>
        </div>
    </div>
</div>

@endsection
