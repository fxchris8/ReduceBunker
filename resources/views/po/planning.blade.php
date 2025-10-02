        @extends('layouts.app')

        @section('title', 'Refueling Planning')

        @section('content')

        <div class="container mx-auto py-6 px-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-4 py-4 border-b">
                    <h1 class="text-xl font-bold">Refueling Planning</h1>
                </div>
                <div class="p-6">
                    <form action="{{ route('file.refueling') }}" method="POST" enctype="multipart/form-data" class="flex justify-center">
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

                    <div>
                        @if (!empty($tanggal))
                            <div class="mt-6 px-4 py-2 rounded-md text-lg font-bold inline-block">
                                {{ $tanggal }}
                            </div>
                        @endif

                        @if (!empty($tableRows))
                            <div class="overflow-x-auto overflow-y-auto max-h-[575px] border rounded-md shadow-sm w-full">
                                <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded">
                                    <thead class="bg-gray-300 sticky top-0 z-10">
                                        @foreach ($headerRows as $rowIndex => $row)
                                            <tr>
                                                @foreach ($row as $cell)
                                                    <th class="px-4 py-2">
                                                        {{ $cell }}
                                                    </th>
                                                @endforeach

                                                @if ($rowIndex === 0)
                                                    @for ($i = 0; $i < count($extraColumns); $i++)
                                                        <th class="px-4 py-2"></th>
                                                    @endfor
                                                @endif
                                            </tr>
                                        @endforeach
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach ($tableRows as $row)
                                            <tr class="text-center odd:bg-white even:bg-gray-200">
                                                @foreach ($row as $colIndex => $cell)
                                                    <td class="px-4 py-2 whitespace-nowrap">
                                                        {{ $colIndex === 'A' ? $cell : (is_numeric($cell) ? number_format($cell, 2, '.', ',') : $cell) }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <form action="{{ route('file.refueling.download') }}" method="POST" class="mt-4 flex justify-end">
                                @csrf
                                <input type="hidden" name="headerRows" value="{{ json_encode($headerRows) }}">
                                <input type="hidden" name="tableRows" value="{{ json_encode($tableRows) }}">
                                <button class="py-2 px-4 bg-green-600 text-white rounded-md">
                                    Download
                                </button>
                            </form>
                        @else
                            <p class="text-gray-500">Sheet kosong atau tidak ditemukan.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        @endsection 
