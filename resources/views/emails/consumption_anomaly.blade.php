<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        table { border-collapse: collapse; text-align: center; margin-top: 20px; width: 100%; max-width: 800px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .alert { color: #d9534f; font-weight: bold; }
    </style>
</head>
<body>
    <p>Dear Capt / Chief Engineer,</p>
    
    <p>Please find attached the noon report for <b>{{ $mailData['vesselName'] }}</b></p>
    <p><b>{{ $mailData['date_val'] }} {{ $mailData['sheetName'] }}:</b></p>

    @if ($mailData['sheetName'] === 'At PORT')
        <p>Position: {{ $mailData['pos_val'] }}</p>
    @endif

    @if ($mailData['sheetName'] === 'At SEA')
        <p>Voyage from {{ $mailData['dep_val'] }} to {{ $mailData['des_val'] }}</p>
    @endif

    {{-- Anomali 1: ME HSD tanpa Manuver --}}
    @if ($mailData['me_hsd_val'] != 0 && $mailData['manuvering_time_val'] == 0)
        <p>There is an <span class="alert">ME HSD consumption of {{ $mailData['me_hsd_val'] }} liters without any recorded maneuvering time</span>.</p>
    @endif

    {{-- Anomali 2: AE Pararel Berlebih --}}
    @if (($mailData['ae_pararel_val'] - $mailData['crane_duration_val'] - $mailData['manuvering_time_val']) > 3)
        <p>There is an <span class="alert">excessive A/E Parallel duration of {{ $mailData['ae_pararel_val'] }} hours</span>, accompanied by <b>Crane usage for {{ $mailData['crane_duration_val'] }} hours</b> and a <b>maneuvering duration of {{ $mailData['manuvering_time_val'] }} hours</b>.</p>
    @endif

    {{-- Tabel Anomali Load A/E --}}
    @if ($mailData['isMultipleLoadNoPararel'] || $mailData['isLoadDifferent'] || $mailData['isSingleLoadWithPararel'] || $mailData['is24HoursPararel'])
        
        @if ($mailData['isMultipleLoadNoPararel'])
            <p>Multiple A/E Loads are active, but <b>no A/E Parallel duration is recorded</b>.</p>
        @elseif ($mailData['isLoadDifferent'])
            <p>Multiple A/E Loads are active with <b>different load values</b>.</p>
        @elseif ($mailData['isSingleLoadWithPararel'])
            <p>Only 1 A/E Load is active, but the <b>A/E Parallel duration is not 0</b>.</p>
        @elseif ($mailData['is24HoursPararel'])
            <p>The <b>A/E Parallel duration is recorded as 24 hours</b>.</p>
        @endif

        <table>
            <thead>
                <tr>
                    <th>LOAD A/E 1 (KW)</th>
                    <th>LOAD A/E 2 (KW)</th>
                    <th>LOAD A/E 3 (KW)</th>
                    <th>LOAD A/E 4 (KW)</th>
                    <th>A/E PARALLEL DURATION</th>
                    <th>REEFER 20"</th>
                    <th>REEFER 40"</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ number_format($mailData['loads']['LOAD A/E 1 (KW)'], 2) }}</td>
                    <td>{{ number_format($mailData['loads']['LOAD A/E 2 (KW)'], 2) }}</td>
                    <td>{{ number_format($mailData['loads']['LOAD A/E 3 (KW)'], 2) }}</td>
                    <td>{{ number_format($mailData['loads']['LOAD A/E 4 (KW)'], 2) }}</td>
                    <td>{{ number_format($mailData['ae_pararel_val'], 2) }}</td>
                    <td>{{ number_format($mailData['refer20'], 2) }}</td>
                    <td>{{ number_format($mailData['refer40'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Loop untuk Anomali Negatif --}}
    @php
        $analysisColumns = ['SELISIH ME Maneuvering', 'EXCESS AE', 'EXCESS ME L/NM (%)'];
        $negativeEntries = [];
        foreach ($mailData['row'] as $key => $cell) {
            if (in_array($key, $analysisColumns) && is_array($cell) && isset($cell['value']) && floatval($cell['value']) < 0) {
                $negativeEntries[] = "{$key}: {$cell['value']}";
            }
        }
    @endphp

    @foreach ($negativeEntries as $entry)
        @php
            [$columnName, $value] = explode(': ', $entry);
            $colTrimmed = trim($columnName);
        @endphp

        @if ($colTrimmed === 'SELISIH ME Maneuvering')
            <p>There is an <span class="alert">excessive ME consumption during maneuvering</span>.</p>
            <table>
                <thead>
                    <tr>
                        <th>ME HSD</th>
                        <th>Maneuvering Time (Hours)</th>
                        <th>BL ME</th>
                        <th>ME Maneuvering Cons. (L/H)</th>
                        <th>ME Maneuvering Difference</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format($mailData['row']['M/E HSD']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['MANEUVERING TIME (HOURS)']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['BL M/E Static (L/Day)']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['ME Maneuvering Cons. (L/H)']['value'] ?? 0, 2) }}</td>
                        <td><b>{{ number_format($mailData['row']['SELISIH ME Maneuvering']['value'] ?? 0, 2) }}</b></td>
                    </tr>
                </tbody>
            </table>
        @elseif ($colTrimmed === 'EXCESS AE')
            <p>There is an <span class="alert">excessive A/E fuel consumption</span>.</p>
            <table>
                <thead>
                    <tr>
                        <th>A/E MFO</th>
                        <th>A/E HSD</th>
                        <th>GENSET CONS. - HSD</th>
                        <th>BL AE (L/DAY)</th>
                        <th>AE (L/DAY)</th>
                        <th>Excess AE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format($mailData['row']['A/E MFO']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['A/E HSD']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['GENSET CONSUMPTION - HSD']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['BL A/E (L/Day)']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['AE Consumption']['value'] ?? 0, 2) }}</td>
                        <td><b>{{ number_format($mailData['row']['EXCESS AE']['value'] ?? 0, 2) }}</b></td>
                    </tr>
                </tbody>
            </table>
        @elseif ($colTrimmed === 'EXCESS ME L/NM (%)')
            <p>There is an <span class="alert">excessive ME MFO consumption based on the L/NM ratio</span>.</p>
            <table>
                <thead>
                    <tr>
                        <th>Steam Dist (NM)</th>
                        <th>Steam Time (Hours)</th>
                        <th>M/E MFO</th>
                        <th>BL L/NM</th>
                        <th>L/NM</th>
                        <th>Excess ME L/NM (%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ number_format($mailData['row']['STEAM. DIST.']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['STEAM TIME (HOUR : MINUTE)']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['M/E MFO']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['BL L/NM']['value'] ?? 0, 2) }}</td>
                        <td>{{ number_format($mailData['row']['L/NM']['value'] ?? 0, 2) }}</td>
                        <td><b>{{ number_format($mailData['row']['EXCESS ME L/NM (%)']['value'] ?? 0, 2) }}</b></td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach

    <br>
    <p>Kindly provide clarification regarding the details of the report above.</p>
    <p>Thank you for your attention and cooperation.</p>
    <br>
    <p>Best regards,<br>
    <b>Bunker Team</b></p>
</body>
</html>