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

            <form method="GET" action="{{ route('po.planning') }}" class="mb-4" onsubmit="showPlanningLoading(this)">
                <input type="hidden" name="hit_api" value="1">

                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <!-- Tanggal Awal -->
                    <div class="px-4 py-2 rounded-md text-lg">
                        <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Awal
                        </label>
                        <input type="date" id="report_date" name="report_date"
                            class="border border-gray-300 rounded-md px-4 py-2 w-64"
                            value="{{ request('report_date', date('Y-m-d')) }}">
                    </div>

                    <!-- Tanggal Akhir -->
                    <div class="px-4 py-2 rounded-md text-lg">
                        <label for="next_week_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Akhir
                        </label>
                        <input type="date" id="next_week_date" name="next_week_date"
                            class="border border-gray-300 rounded-md px-4 py-2 w-64"
                            value="{{ request('next_week_date', date('Y-m-d', strtotime('+7 days'))) }}">
                    </div>
                </div>

                <!-- ROB Tanker -->
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <h3 class="w-full text-lg font-medium mb-2">ROB Tanker</h3>

                    <div>
                        <label for="rob_tanker_mfo" class="block text-sm font-medium text-gray-700 mb-1">
                            MFO
                        </label>
                        <input type="number" step="any" id="rob_tanker_mfo" name="rob_tanker_mfo"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                            [appearance:textfield]
                            [&::-webkit-outer-spin-button]:appearance-none
                            [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('rob_tanker_mfo') }}"
                            required
                            placeholder="0">
                    </div>

                    <div>
                        <label for="rob_tanker_hsd" class="block text-sm font-medium text-gray-700 mb-1">
                            HSD
                        </label>
                        <input type="number" step="any" id="rob_tanker_hsd" name="rob_tanker_hsd"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                            [appearance:textfield]
                            [&::-webkit-outer-spin-button]:appearance-none
                            [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('rob_tanker_hsd') }}"
                            required
                            placeholder="0">
                    </div>
                </div>

                <!-- Input Saldo -->
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <div>
                        <label for="input_saldo_rp" class="block text-lg font-medium mb-2">
                            Input Saldo
                        </label>
                        <input type="number" step="any" id="input_saldo_rp" name="input_saldo_rp"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                                [appearance:textfield]
                                [&::-webkit-outer-spin-button]:appearance-none
                                [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('input_saldo_rp') }}"
                            placeholder="0">
                    </div>
                    <button type="submit" data-loading-text="Loading..."
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow">
                        Hit API
                    </button>
                </div>
            </form>

            <script>
                function showPlanningLoading(form) {
                    const button = form.querySelector('button[type="submit"]');
                    if (!button) return;
                    button.disabled = true;
                    button.textContent = button.dataset.loadingText;
                    button.classList.add('opacity-75', 'cursor-wait');
                }
            </script>

            @if(is_array($headerRows ?? null) && count($headerRows) > 0)

                <div class="flex flex-wrap items-center gap-4 mb-3 py-2">
                    <span class="text-sm font-medium text-gray-500">Filter port:</span>

                    <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                        <input type="checkbox" id="cb-all" checked class="w-4 h-4 accent-blue-600">
                        <span class="bg-gray-100 text-xs font-medium px-2 py-0.5 rounded">ALL</span>
                    </label>

                    <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                        <input type="checkbox" id="cb-jkt" checked class="w-4 h-4 accent-blue-600">
                        <span class="bg-gray-100 text-xs font-medium px-2 py-0.5 rounded">IDJKT</span>
                    </label>

                    <label class="flex items-center gap-1.5 text-sm cursor-pointer select-none">
                        <input type="checkbox" id="cb-sub" checked class="w-4 h-4 accent-blue-600">
                        <span class="bg-gray-100 text-xs font-medium px-2 py-0.5 rounded">IDSUB</span>
                    </label>

                    <span id="port-row-count" class="ml-auto text-xs text-gray-400"></span>
                </div>

                <div class="overflow-x-auto overflow-y-auto max-h-[450px] rounded-md shadow-sm w-full">
                    <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                        <thead class="bg-gray-300 sticky top-0 z-30">
                            <tr>
                                <th class="px-4 py-2 text-center border border-black sticky top-0 left-0 bg-gray-300 z-40">
                                    Vessel ID
                                </th>
                                @foreach($headerRows as $header)
                                    @if($header !== 'Vessel ID')
                                        <th class="px-4 py-2 text-center border border-black sticky top-0 bg-gray-300 z-30
                                            {{ in_array($header, ['Pengisian HSD', 'Pengisian MFO']) ? 'bg-green-500 text-white' : '' }}">
                                            {{ ucfirst($header) }}
                                        </th>
                                    @endif
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200" id="main-table-body">
                            @foreach($report as $row)
                                @php
                                    $portFrom = Str::upper(trim($row['New Current Voyage (FROM)'] ?? ''));
                                @endphp
                                <tr class="text-center odd:bg-white even:bg-gray-200"
                                    data-port="{{ $portFrom }}">
                                    <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                        {{ $row['Vessel ID'] }}
                                    </td>
                                    @foreach($headerRows as $header)
                                        @if($header !== 'Vessel ID')
                                            <td class="px-4 py-2 text-center border border-black">
                                                {{ is_numeric($row[$header] ?? null) ? number_format($row[$header], 2, '.', ',') : ($row[$header] ?? '') }}
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-8">
                    <h2 class="text-lg font-semibold mb-3">Rekomendasi Strategi Bunkering</h2>
                    <div class="overflow-x-auto overflow-y-auto max-h-[260px] rounded-md shadow-sm w-full">
                        <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                            <thead class="bg-gray-300 sticky top-0 z-20">
                                <tr>
                                    <th class="px-4 py-2 text-center border border-black">ID Vessel</th>
                                    <th class="px-4 py-2 text-center border border-black">ROB MFO</th>
                                    <th class="px-4 py-2 text-center border border-black">ROB HSD</th>
                                    <th class="px-4 py-2 text-center border border-black">Distance next voyage</th>
                                    <th class="px-4 py-2 text-center border border-black">Kebutuhan MFO</th>
                                    <th class="px-4 py-2 text-center border border-black">Kebutuhan HSD</th>
                                    <th class="px-4 py-2 text-center border border-black">Isi BBM MFO</th>
                                    <th class="px-4 py-2 text-center border border-black">Isi BBM HSD</th>
                                    <th class="px-4 py-2 text-center border border-black">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200" id="rekom-table-body">
                                @foreach($report as $row)
                                    @php
                                        $robMfo           = $row['ROB MFO Arrival'] ?? $row['ROB MFO Berthing'] ?? $row['ROB MFO Sebelumnya'] ?? '';
                                        $robHsd           = $row['ROB HSD Arrival'] ?? $row['ROB HSD Berthing'] ?? $row['ROB HSD Sebelumnya'] ?? '';
                                        $distanceNextVoyage = $row['Jarak Next Voyage'] ?? '';
                                        $kebutuhanMfo     = $row['Kebutuhan MFO Next Route'] ?? '';
                                        $kebutuhanHsd     = $row['Kebutuhan HSD Next Route'] ?? '';
                                        $isiBbmMfo        = $row['Isi BBM MFO'] ?? '';
                                        $isiBbmHsd        = $row['Isi BBM HSD'] ?? '';
                                        $keterangan       = $row['Keterangan'] ?? '';
                                        $portFrom         = Str::upper(trim($row['New Current Voyage (FROM)'] ?? ''));
                                    @endphp
                                    <tr class="text-center odd:bg-white even:bg-gray-200"
                                        data-port="{{ $portFrom }}">
                                        <td class="px-4 py-2 text-center border border-black">{{ $row['Vessel ID'] ?? '' }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($robMfo) ? number_format($robMfo, 2, '.', ',') : $robMfo }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($robHsd) ? number_format($robHsd, 2, '.', ',') : $robHsd }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($distanceNextVoyage) ? number_format($distanceNextVoyage, 2, '.', ',') : $distanceNextVoyage }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($kebutuhanMfo) ? number_format($kebutuhanMfo, 2, '.', ',') : $kebutuhanMfo }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($kebutuhanHsd) ? number_format($kebutuhanHsd, 2, '.', ',') : $kebutuhanHsd }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($isiBbmMfo) ? number_format($isiBbmMfo, 2, '.', ',') : $isiBbmMfo }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($isiBbmHsd) ? number_format($isiBbmHsd, 2, '.', ',') : $isiBbmHsd }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ $keterangan }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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

                <script>
                (function () {
                    const cbAll     = document.getElementById('cb-all');
                    const cbJkt     = document.getElementById('cb-jkt');
                    const cbSub     = document.getElementById('cb-sub');
                    const countEl   = document.getElementById('port-row-count');

                    function getAllRows() {
                        return document.querySelectorAll(
                            '#main-table-body tr[data-port], #rekom-table-body tr[data-port]'
                        );
                    }

                    function applyFilter() {
                        const showJkt = cbJkt.checked;
                        const showSub = cbSub.checked;
                        let shown = 0;

                        getAllRows().forEach(function (row) {
                            const port    = (row.dataset.port || '').toUpperCase();
                            const isJkt   = port.includes('IDJKT');
                            const isSub   = port.includes('IDSUB');
                            const visible = (isJkt && showJkt) || (isSub && showSub);

                            row.style.display = visible ? '' : 'none';
                            if (visible) shown++;
                        });

                        if (countEl) {
                            const vesselCount = shown / 2;
                            countEl.textContent = 'Showing ' + vesselCount + ' vessel';
                        }
                    }

                    cbAll.addEventListener('change', function () {
                        cbJkt.checked = cbAll.checked;
                        cbSub.checked = cbAll.checked;
                        applyFilter();
                    });

                    function onSubChange() {
                        cbAll.checked = cbJkt.checked && cbSub.checked;
                        applyFilter();
                    }

                    cbJkt.addEventListener('change', onSubChange);
                    cbSub.addEventListener('change', onSubChange);

                    applyFilter();
                })();
                </script>

            @elseif($hasFetchedPlanning ?? false)
                <p>Tidak ada data tersedia.</p>
            @endif
        </div>
    </div>
</div>

@endsection