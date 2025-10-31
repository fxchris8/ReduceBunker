        @extends('layouts.app')

        @section('title', 'Refueling Planning')

        @section('content')

        <div class="container mx-auto py-6 px-4">
            <div class="bg-white rounded-lg shadow-md">
                <div class="bg-gray-50 px-4 py-4 border-b">
                    <h1 class="text-xl font-bold">Refueling Planning</h1>
                </div>
                
                <div class="border rounded-md p-6">
                    @if(isset($error))
                        <div class="bg-red-100 text-red-600 p-3 rounded mb-4">
                            {{ $error }}
                        </div>
                    @endif

                    <div class="flex space-x-4 mb-4">
                        <!-- Tanggal Awal -->
                        <div class="px-4 py-2 rounded-md text-lg">
                            <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Awal
                            </label>
                            <input type="date" id="report_date" name="report_date"
                                class="border border-gray-300 rounded-md px-4 py-2 w-64"
                                value="{{ request('report_date', date('Y-m-d')) }}"
                                onchange="updateDates()">
                        </div>

                        <!-- Tanggal Akhir -->
                        <div class="px-4 py-2 rounded-md text-lg">
                            <label for="next_week_date" class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Akhir
                            </label>
                            <input type="date" id="next_week_date" name="next_week_date"
                                class="border border-gray-300 rounded-md px-4 py-2 w-64"
                                value="{{ request('next_week_date', date('Y-m-d', strtotime('+7 days'))) }}"
                                onchange="updateDates()">
                        </div>
                    </div>

                    <script>
                        function updateDates() {
                            const start = document.getElementById('report_date').value;
                            const end = document.getElementById('next_week_date').value;
                            const url = `{{ route('po.planning') }}?report_date=${start}&next_week_date=${end}`;
                            window.location.href = url;
                        }
                    </script>

                    @if(is_array($headerRows) && count($headerRows) > 0)
                        <div class="overflow-x-auto overflow-y-auto max-h-[450px] rounded-md shadow-sm w-full">
                            <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                                <thead class="bg-gray-300 sticky top-0 z-10">
                                    <tr>
                                        @foreach($headerRows as $header)
                                            <th class="px-4 py-2 text-center border-2 border-black">
                                                {{ ucfirst($header) }}
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($report as $index => $row)
                                        <tr class="text-center odd:bg-white even:bg-gray-200">
                                            @foreach($headerRows as $header)
                                                <td class="px-4 py-2 text-center border-2 border-black">
                                                    {{ is_numeric($row[$header] ?? null) ? number_format($row[$header], 2, '.', ',') : ($row[$header] ?? '') }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="flex justify-end mt-4">
                            <form method="POST" action="{{ route('file.refueling.download') }}" class="mt-4">
                                @csrf
                                <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded shadow">
                                    Download
                                </button>
                            </form>
                        </div>
                    @else
                        <p>Tidak ada data tersedia.</p>
                    @endif
                </div>
            </div>
        </div>
        
        @endsection 
