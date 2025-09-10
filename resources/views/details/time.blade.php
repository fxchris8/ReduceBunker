@extends('layouts.app')

@section('title', 'Details of Excess A/E Pararel Duration')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b">
            <h2 class="text-xl font-bold">Details of Excess A/E Pararel Duration At {{ strtoupper($sessionType) }}</h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto overflow-y-auto max-h-[550px] border rounded-md shadow-sm w-full">
                <table class="table-fixed w-full border-4 border-gray-300 rounded shadow-sm divide-y divide-gray-200">
                    <thead class="bg-gray-300 sticky top-0 z-10">
                        <tr>
                            <th class="px-4 py-2 border">Vessel</th>
                            <th class="px-4 py-2 border">AE Pararel Duration</th>
                            <th class="px-4 py-2 border">Crane Duration</th>
                            <th class="px-4 py-2 border">Manuvering Time</th>
                            <th class="px-4 py-2 border">Differences</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($details as $row)
                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                <td class="px-4 py-2 border">{{ $row['vessel'] }}</td>
                                <td class="px-4 py-2 border">{{ number_format($row['ae_pararel'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 border">{{ number_format($row['crane'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 border">{{ number_format($row['maneuvering'], 2, '.', ',') }}</td>
                                <td class="px-4 py-2 border">{{ number_format($row['diff'], 2, '.', ',') }}</td>
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
