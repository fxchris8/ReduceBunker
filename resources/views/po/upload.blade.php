        @extends('layouts.app')

        @section('title', 'Consumption Analysis')

        @section('content')
        <div class="container mx-auto py-6 px-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-4 py-4 border-b">
                    <h1 class="text-xl font-bold">Consumption Analysis</h1>
                </div>
                <div class="p-6">
                    <form action="{{ route('upload.file') }}" method="POST" enctype="multipart/form-data" class="flex flex-col md:flex-row gap-4 mb-6">
                        <div class="flex-1">
                            @csrf
                            <select name="location" required class="w-full py-2 px-4 border rounded-md">                            
                                <option value="">Pilih Posisi</option>
                                <option value="port">At PORT</option>
                                <option value="sea">At SEA</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <input type="file" name="file" accept=".csv, .xlsx, .xls, .ods" required class="py-1 px-4 border rounded-md">
                            <button type="submit" class="py-2 px-4 bg-blue-600 text-white rounded-md">Upload</button>
                        </div>
                    </form>


                    <div class="border rounded-md">
                        @if (!empty($sheetName))
                            @php
                                $bgColor = $sheetName === 'At SEA' ? 'bg-blue-100 text-blue-700' :
                                        ($sheetName === 'At PORT' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700');
                            @endphp
                            <div class="mb-4 px-4 py-2 rounded-md text-lg font-bold inline-block {{ $bgColor }}">
                                {{ $tanggal }} {{ $sheetName }}
                            </div>
                        @endif

                        @if (!empty($tableRows))
                            <form action="{{ route('send.email') }}" method="POST">
                                @csrf
                                <div class="overflow-x-auto overflow-y-auto max-h-[560px] border rounded-md shadow-sm w-full">
                                    <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center">
                                        <thead class="bg-gray-300 sticky top-0 z-10">
                                            @foreach ($headerRows as $row)
                                                <tr>
                                                    @foreach ($row as $cell)
                                                        <th class="px-4 py-2 font-semibold text-gray-600">
                                                            {{ $cell }}
                                                        </th>
                                                    @endforeach
                                                    
                                                    @if ($loop->last)
                                                        <th class="px-4 py-2 font-semibold text-gray-600">Pilih</th>
                                                    @else
                                                        <th class="px-4 py-2"></th>
                                                    @endif
                                                </tr>
                                            @endforeach
                                        </thead>
                                        <tbody class="divide-y divide-gray-200">
                                            @foreach ($tableRows as $index => $rowData)
                                                @php
                                                    $row = $rowData['data'];
                                                    $colors = $rowData['colors'] ?? [];
                                                @endphp
                                                <tr class="odd:bg-white even:bg-gray-200">
                                                    @foreach ($row as $colIndex => $cell)
                                                        @php
                                                            $bgClass = '';
                                                            if (isset($colors[$colIndex])) {
                                                                $bgClass = $colors[$colIndex] === 'red' ? 'bg-red-200 text-red-700 font-semibold' :
                                                                        ($colors[$colIndex] === 'yellow' ? 'bg-yellow-200 text-yellow-800 font-semibold' : '');
                                                            }
                                                        @endphp
                                                        <td class="px-4 py-2 whitespace-nowrap {{ $bgClass }}">
                                                            {{ is_numeric($cell) ? number_format($cell, 2, '.', ',') : $cell }}
                                                        </td>
                                                    @endforeach
                                                    <td class="px-4 py-2 text-center">
                                                        <input type="checkbox" name="selected_rows[]" value="{{ $index }}" class="form-checkbox">
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-4 flex justify-end">
                                    <button type="submit" class="py-2 px-4 bg-green-600 text-white rounded-md">
                                        Send Email
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="text-gray-500">Sheet kosong atau tidak ditemukan.</p>
                        @endif
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

        @endsection
