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
                    <div class="px-4 py-2 rounded-md text-lg">
                        <label for="report_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Awal
                        </label>
                        <input type="date" id="report_date" name="report_date"
                            class="border border-gray-300 rounded-md px-4 py-2 w-64"
                            value="{{ request('report_date', date('Y-m-d')) }}">
                    </div>
                    <div class="px-4 py-2 rounded-md text-lg">
                        <label for="next_week_date" class="block text-sm font-medium text-gray-700 mb-1">
                            Tanggal Akhir
                        </label>
                        <input type="date" id="next_week_date" name="next_week_date"
                            class="border border-gray-300 rounded-md px-4 py-2 w-64"
                            value="{{ request('next_week_date', date('Y-m-d', strtotime('+7 days'))) }}">
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <h3 class="w-full text-lg font-medium mb-2">ROB Tanker</h3>
                    <div>
                        <label for="rob_tanker_mfo" class="block text-sm font-medium text-gray-700 mb-1">
                            MFO (kL)
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
                            HSD (kL)
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
                    <div>
                        <label for="harga_mfo" class="block text-sm font-medium text-gray-700 mb-1">
                            MFO Price (per L)
                        </label>
                        <input type="number" step="any" id="harga_mfo" name="harga_mfo"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                            [appearance:textfield]
                            [&::-webkit-outer-spin-button]:appearance-none
                            [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('harga_mfo') }}"
                            required
                            placeholder="0">
                    </div>
                    <div>
                        <label for="harga_hsd" class="block text-sm font-medium text-gray-700 mb-1">
                            HSD Price (per L)
                        </label>
                        <input type="number" step="any" id="harga_hsd" name="harga_hsd"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                            [appearance:textfield]
                            [&::-webkit-outer-spin-button]:appearance-none
                            [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('harga_hsd') }}"
                            required
                            placeholder="0">
                    </div>
                </div>
                <div class="flex flex-wrap items-end gap-4 mb-4">
                    <div>
                        <label for="input_saldo_rp" class="block text-lg font-medium mb-2">
                            Input Saldo (IDR)
                        </label>
                        <input type="number" step="any" id="input_saldo_rp" name="input_saldo_rp"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48
                                [appearance:textfield]
                                [&::-webkit-outer-spin-button]:appearance-none
                                [&::-webkit-inner-spin-button]:appearance-none"
                            value="{{ request('input_saldo_rp') }}"
                            required
                            placeholder="0">
                    </div>
                    <div>
                        <label for="baseline_type" class="block text-sm font-medium text-gray-700 mb-1">Baseline</label>
                        <select name="baseline_type" id="baseline_type"
                            class="border border-gray-300 rounded-md px-4 py-2 w-48">
                            <option value="statis" {{ request('baseline_type', 'statis') === 'statis' ? 'selected' : '' }}>
                                Baseline Statis
                            </option>
                            <option value="dinamis" {{ request('baseline_type') === 'dinamis' ? 'selected' : '' }}>
                                Baseline Dinamis
                            </option>
                        </select>
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
                                    @if($header !== 'Vessel ID' && $header !== '_detail')
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
                                    $portFrom  = Str::upper(trim($row['New Current Voyage (FROM)'] ?? ''));
                                    $_robMfo   = $row['ROB MFO Arrival'] ?? $row['ROB MFO Sebelumnya'] ?? null;
                                    $_robHsd   = $row['ROB HSD Arrival'] ?? $row['ROB HSD Sebelumnya'] ?? null;
                                    $isNegativeRob = (is_numeric($_robMfo) && $_robMfo < 0) || (is_numeric($_robHsd) && $_robHsd < 0);
                                    $excludeFromMain = ['Kebutuhan MFO Next Route', 'Kebutuhan HSD Next Route', 'Isi BBM MFO', 'Isi BBM HSD', 'Keterangan', '_detail'];
                                @endphp
                                <tr class="text-center {{ $isNegativeRob ? 'bg-red-200' : '' }}"
                                     data-port="{{ $portFrom }}">
                                    <td class="px-4 py-2 text-center border border-black sticky left-0 bg-inherit z-20">
                                        {{ $row['Vessel ID'] }}
                                    </td>
                                    @foreach($headerRows as $header)
                                        @if($header !== 'Vessel ID' && $header !== '_detail')
                                            <td class="px-4 py-2 text-center border border-black">
                                                {{ in_array($header, $excludeFromMain) ? '' : (is_numeric($row[$header] ?? null) ? number_format($row[$header], 2, '.', ',') : ($row[$header] ?? '')) }}
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
                    <div class="overflow-x-auto overflow-y-auto max-h-[450px] rounded-md shadow-sm w-full">
                        <table class="table-auto w-full divide-y divide-gray-200 text-sm text-center rounded border">
                            <thead class="bg-gray-300 sticky top-0 z-20">
                                <tr>
                                    <th class="px-4 py-2 text-center border border-black">ID Vessel</th>
                                    <th class="px-4 py-2 text-center border border-black">ROB MFO</th>
                                    <th class="px-4 py-2 text-center border border-black">ROB HSD</th>
                                    <th class="px-4 py-2 text-center border border-black">Distance next voyage</th>
                                    <th class="px-4 py-2 text-center border border-black">Kebutuhan MFO</th>
                                    @if(($baselineType ?? 'statis') === 'statis')
                                    <th class="px-4 py-2 text-center border border-black">Kebutuhan HSD</th>
                                    @endif
                                    <th class="px-4 py-2 text-center border border-black">Isi BBM MFO</th>
                                    @if(($baselineType ?? 'statis') === 'statis')
                                    <th class="px-4 py-2 text-center border border-black">Isi BBM HSD</th>
                                    @endif
                                    <th class="px-4 py-2 text-center border border-black">Keterangan</th>
                                    <th class="px-4 py-2 text-center border border-black">Aksi</th>
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
                                        $isNegativeRob = (is_numeric($robMfo) && $robMfo < 0) || (is_numeric($robHsd) && $robHsd < 0);
                                    @endphp
                                    <tr class="text-center {{ $isNegativeRob ? 'bg-red-200' : '' }}"
                                        data-port="{{ $portFrom }}">
                                        <td class="px-4 py-2 text-center border border-black">{{ $row['Vessel ID'] ?? '' }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($robMfo) ? number_format($robMfo, 2, '.', ',') : $robMfo }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($robHsd) ? number_format($robHsd, 2, '.', ',') : $robHsd }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($distanceNextVoyage) ? number_format($distanceNextVoyage, 2, '.', ',') : $distanceNextVoyage }}</td>
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($kebutuhanMfo) ? number_format($kebutuhanMfo, 2, '.', ',') : $kebutuhanMfo }}</td>
                                        @if(($baselineType ?? 'statis') === 'statis')
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($kebutuhanHsd) ? number_format($kebutuhanHsd, 2, '.', ',') : $kebutuhanHsd }}</td>
                                        @endif
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($isiBbmMfo) ? number_format($isiBbmMfo, 2, '.', ',') : $isiBbmMfo }}</td>
                                        @if(($baselineType ?? 'statis') === 'statis')
                                        <td class="px-4 py-2 text-center border border-black">{{ is_numeric($isiBbmHsd) ? number_format($isiBbmHsd, 2, '.', ',') : $isiBbmHsd }}</td>
                                        @endif
                                        <td class="px-4 py-2 text-center border border-black">{{ $keterangan }}</td>
                                        <td class="px-4 py-2 text-center border border-black">
                                            @if(isset($row['_detail']))
                                                <button onclick="showDetail({{ json_encode($row['_detail']) }}, '{{ $row['Vessel ID'] }}')"
                                                    class="text-yellow-500 hover:text-yellow-600 p-1 rounded hover:bg-yellow-50 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
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

                        ['main-table-body', 'rekom-table-body'].forEach(function(tbodyId) {
                            let visibleIndex = 0;
                            document.querySelectorAll('#' + tbodyId + ' tr[data-port]').forEach(function(row) {
                                const port    = (row.dataset.port || '').toUpperCase();
                                const isJkt   = port.includes('IDJKT');
                                const isSub   = port.includes('IDSUB');
                                const visible = (isJkt && showJkt) || (isSub && showSub);
                                const isRed   = row.classList.contains('bg-red-200');

                                row.style.display = visible ? '' : 'none';

                                if (visible) {
                                    if (!isRed) {
                                        row.classList.remove('bg-white', 'bg-gray-200');
                                        row.classList.add(visibleIndex % 2 === 0 ? 'bg-white' : 'bg-gray-200');
                                        visibleIndex++;
                                    }
                                    shown++;
                                }
                            });
                        });
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
                
                <div id="detail-modal" class="fixed inset-0 z-50 hidden">
                    <div class="fixed inset-0 bg-black bg-opacity-50" onclick="closeDetail()"></div>
                    <div class="fixed inset-0 flex items-center justify-center p-4">
                        <div class="bg-white rounded-lg shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
                            <div class="bg-gray-800 text-white px-4 py-3 rounded-t-lg flex justify-between items-center">
                                <h3 id="detail-title" class="font-bold text-lg"></h3>
                                <button onclick="closeDetail()" class="text-white hover:text-red-200 text-xl">&times;</button>
                            </div>
                            <div id="detail-body" class="p-4 text-sm space-y-4"></div>
                        </div>
                    </div>
                </div>

                <script>
                function fmt(v) {
                    if (v === null || v === undefined) return '-';
                    return Number(v).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }

                function fmtRp(v) {
                    if (v === null || v === undefined) return 'Rp 0';
                    return 'Rp ' + Number(v).toLocaleString('id-ID', {minimumFractionDigits: 0, maximumFractionDigits: 0});
                }

                function showDetail(d, vessel) {
                    document.getElementById('detail-title').textContent = 'Detail Bunkering - ' + vessel;

                    let html = '';

                    // Baris 1: ROB Tanker Sebelum | Isi BBM | ROB Tanker Sesudah
                    html += '<div class="grid grid-cols-3 gap-4">';

                    html += '<div class="rounded-lg border border-gray-200 overflow-hidden">';
                    html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">ROB Tanker Sebelum Diisi</span></div>';
                    html += '<div class="divide-y divide-gray-100">';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">MFO</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.tanker_mfo_before) + ' L</span></div>';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">HSD</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.tanker_hsd_before) + ' L</span></div>';
                    html += '</div></div>';

                    html += '<div class="rounded-lg border border-gray-200 overflow-hidden">';
                    html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Jumlah Diisi</span></div>';
                    html += '<div class="divide-y divide-gray-100">';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">MFO</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.isi_bbm_mfo) + ' L</span></div>';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">HSD</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.isi_bbm_hsd) + ' L</span></div>';
                    html += '</div></div>';

                    html += '<div class="rounded-lg border border-gray-200 overflow-hidden">';
                    html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">ROB Tanker Sesudah Diisi</span></div>';
                    html += '<div class="divide-y divide-gray-100">';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">MFO</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.tanker_mfo_after) + ' L</span></div>';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">HSD</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.tanker_hsd_after) + ' L</span></div>';
                    html += '</div></div>';

                    html += '</div>';

                    // Baris 2: Saldo | Pembelian/Estimasi
                    html += '<div class="flex gap-4 mt-4 items-start">';

                    // Saldo
                    html += '<div class="rounded-lg border border-gray-200 overflow-hidden shrink-0 w-72">';
                    html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Saldo</span></div>';
                    html += '<div class="divide-y divide-gray-100">';
                    html += '<div class="flex justify-between items-center px-4 py-2.5"><span class="text-sm text-gray-500">Saldo Saat Ini</span><span class="text-sm font-semibold text-gray-800">' + fmtRp(d.sisa_saldo) + '</span></div>';
                    html += '</div></div>';

                    // Pembelian atau Estimasi
                    if (d.beli_pertamina_mfo > 0 || d.beli_pertamina_hsd > 0) {
                        if (d.saldo_tidak_cukup) {
                            html += '<div class="rounded-lg border border-red-200 overflow-hidden flex-1">';
                            html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Estimasi Pembelian dari Pertamina</span></div>';
                            html += '<div class="divide-y divide-red-100">';
                            if (d.beli_pertamina_mfo > 0) {
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-100"><span class="text-sm text-gray-800">Estimasi Beli MFO</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.beli_pertamina_mfo) + ' L</span></div>';
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-100"><span class="text-sm text-gray-800 font-bold">Estimasi Biaya MFO</span><span class="text-sm font-bold text-red-600">' + fmtRp(d.biaya_mfo) + '</span></div>';
                            }
                            if (d.beli_pertamina_hsd > 0) {
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-100"><span class="text-sm text-gray-800">Estimasi Beli HSD</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.beli_pertamina_hsd) + ' L</span></div>';
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-100"><span class="text-sm text-gray-800 font-bold">Estimasi Biaya HSD</span><span class="text-sm font-bold text-red-600">' + fmtRp(d.biaya_hsd) + '</span></div>';
                            }
                            html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-400"><span class="text-sm font-bold text-white">Total Dibutuhkan</span><span class="text-sm font-bold text-white">' + fmtRp(d.biaya_mfo + d.biaya_hsd) + '</span></div>';
                            html += '<div class="flex justify-between items-center px-4 py-2.5 bg-red-400"><span class="text-sm font-bold text-white">Kekurangan Saldo</span><span class="text-sm font-bold text-white">' + fmtRp((d.biaya_mfo + d.biaya_hsd) - d.sisa_saldo) + '</span></div>';
                            html += '</div></div>';
                        } else {
                            html += '<div class="rounded-lg border border-yellow-200 overflow-hidden flex-1">';
                            html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Pembelian dari Pertamina</span></div>';
                            html += '<div class="divide-y divide-yellow-100">';
                            html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100"><span class="text-sm text-gray-800">Saldo Awal</span><span class="text-sm font-semibold text-gray-800">' + fmtRp(d.saldo_sebelum) + '</span></div>';
                            if (d.beli_pertamina_mfo > 0) {
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100"><span class="text-sm text-gray-800">MFO</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.beli_pertamina_mfo) + ' L</span></div>';
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100"><span class="text-sm text-gray-800">Biaya MFO</span><span class="text-sm font-semibold text-red-600">- ' + fmtRp(d.biaya_mfo) + '</span></div>';
                            }
                            if (d.beli_pertamina_hsd > 0) {
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100"><span class="text-sm text-gray-800">HSD</span><span class="text-sm font-semibold text-gray-800">' + fmt(d.beli_pertamina_hsd) + ' L</span></div>';
                                html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-100"><span class="text-sm text-gray-800">Biaya HSD</span><span class="text-sm font-semibold text-red-600">- ' + fmtRp(d.biaya_hsd) + '</span></div>';
                            }
                            html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-400"><span class="text-sm font-bold text-gray-800">Total Biaya</span><span class="text-sm font-bold text-gray-800">- ' + fmtRp(d.biaya_mfo + d.biaya_hsd) + '</span></div>';
                            html += '<div class="flex justify-between items-center px-4 py-2.5 bg-yellow-400"><span class="text-sm font-bold text-gray-800">Saldo Akhir</span><span class="text-sm font-bold text-gray-800">' + fmtRp(d.sisa_saldo) + '</span></div>';
                            html += '</div></div>';
                        }
                    } else {
                        html += '<div class="rounded-lg border border-gray-200 overflow-hidden flex-1">';
                        html += '<div class="bg-gray-800 px-4 py-2"><span class="text-xs font-bold text-gray-200 uppercase tracking-wider">Pembelian dari Pertamina</span></div>';
                        html += '<div class="px-4 py-2.5"><span class="text-sm text-gray-400">Tidak ada pembelian</span></div>';
                        html += '</div>';
                    }

                    html += '</div>';

                    document.getElementById('detail-body').innerHTML = html;
                    document.getElementById('detail-modal').classList.remove('hidden');
                }

                function closeDetail() {
                    document.getElementById('detail-modal').classList.add('hidden');
                }
                </script>

            @elseif($hasFetchedPlanning ?? false)
                <p>Tidak ada data tersedia.</p>
            @endif
        </div>
    </div>
</div>

@endsection