<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ConsumptionEmailController extends Controller
{
    private function loadVesselEmails()
    {
        $emailFilePath = storage_path('app/email.xlsx');
        $spreadsheetEmail = IOFactory::load($emailFilePath);
        $sheetEmail = $spreadsheetEmail->getActiveSheet();
        $emailData = $sheetEmail->toArray(null, true, true, true);

        $vesselEmails = [];
        foreach (array_slice($emailData, 1) as $row) {
            $vessel = strtoupper(trim($row['A']));
            if ($vessel) {
                $vesselEmails[$vessel] = [
                    'Email Kapal' => trim($row['B'] ?? ''),
                    'Email SS/SI' => trim($row['C'] ?? ''),
                    'Email MT'    => trim($row['D'] ?? ''),
                    'Email MN'    => trim($row['E'] ?? ''),
                    'Email DGM'   => trim($row['F'] ?? ''),
                    'Email GM'    => trim($row['G'] ?? ''),
                    'Email DPA'   => trim($row['H'] ?? ''),
                    'Email SSB01' => trim($row['I'] ?? ''),
                    'OIL MGT'     => trim($row['J'] ?? '')
                ];
            }
        }

        return $vesselEmails;
    }

    private function processEmailRow($row, $sheetName, $vesselEmails)
    {
        $primaryRole = 'Email Kapal';
        $ccRoles     = ['Email SS/SI', 'Email MT', 'Email MN', 'Email DGM', 'Email GM', 'Email DPA', 'Email SSB01', 'OIL MGT'];

        $vesselName = strtoupper($row['Vessel ID']['value'] ?? 'UNKNOWN');
        $date_val   = $row['tanggal']['value'] ?? '';
        $pos_val    = $row['POSITION']['value'] ?? '';
        $dep_val    = $row['DEPARTURE PORT']['value'] ?? '';
        $des_val    = $row['DESTINATION']['value'] ?? '';

        $me_hsd_val          = floatval($row['M/E HSD']['value'] ?? 0);
        $manuvering_time_val = floatval($row['MANEUVERING TIME (HOURS)']['value'] ?? 0);
        $ae_pararel_val      = floatval($row['AE PARAREL DURATION']['value'] ?? 0);
        $crane_duration_val  = floatval($row['CRANE DURATION']['value'] ?? 0);

        $loads = [
            'LOAD A/E 1 (KW)' => floatval($row['LOAD A/E 1 (KW)']['value'] ?? 0),
            'LOAD A/E 2 (KW)' => floatval($row['LOAD A/E 2 (KW)']['value'] ?? 0),
            'LOAD A/E 3 (KW)' => floatval($row['LOAD A/E 3 (KW)']['value'] ?? 0),
            'LOAD A/E 4 (KW)' => floatval($row['LOAD A/E 4 (KW)']['value'] ?? 0),
        ];

        $refer20 = floatval($row['REEFER 20"']['value'] ?? 0);
        $refer40 = floatval($row['REEFER 40"']['value'] ?? 0);

        $activeLoads = array_filter($loads, fn($v) => $v > 0);

        $isMultipleLoadNoPararel = count($activeLoads) > 1 && $ae_pararel_val == 0;
        $isLoadDifferent         = count(array_unique($activeLoads)) > 1 && count($activeLoads) > 1;
        $isSingleLoadWithPararel = count($activeLoads) === 1 && $ae_pararel_val != 0;
        $is24HoursPararel        = $ae_pararel_val == 24;

        $mailData = [
            'vesselName'              => $vesselName,
            'sheetName'               => $sheetName,
            'date_val'                => $date_val,
            'pos_val'                 => $pos_val,
            'dep_val'                 => $dep_val,
            'des_val'                 => $des_val,
            'me_hsd_val'              => $me_hsd_val,
            'manuvering_time_val'     => $manuvering_time_val,
            'ae_pararel_val'          => $ae_pararel_val,
            'crane_duration_val'      => $crane_duration_val,
            'loads'                   => $loads,
            'refer20'                 => $refer20,
            'refer40'                 => $refer40,
            'isMultipleLoadNoPararel' => $isMultipleLoadNoPararel,
            'isLoadDifferent'         => $isLoadDifferent,
            'isSingleLoadWithPararel' => $isSingleLoadWithPararel,
            'is24HoursPararel'        => $is24HoursPararel,
            'row'                     => $row
        ];

        $toEmail = $vesselEmails[$vesselName][$primaryRole] ?? 'marulihtgl12@gmail.com';
        $ccEmails = [];

        foreach ($ccRoles as $role) {
             $email = $vesselEmails[$vesselName][$role] ?? null;
            if ($email) {
                $ccEmails[] = $email;
            }
        }

        Mail::to($toEmail)
            ->cc($ccEmails)
            ->queue(new \App\Mail\ConsumptionAnomalyMail($mailData));
    }

    public function sendEmail(Request $request)
    {
        $selectedPort = $request->input('selected_rows_port', []);
        $selectedSea  = $request->input('selected_rows_sea', []);

        $reportPort  = session('port_anomaly', []);
        $reportSea   = session('sea_anomaly', []);
        $headersPort = session('headers_port', []);
        $headersSea  = session('headers_sea', []);

        $selectedDataPort = collect($reportPort)->only($selectedPort)->values()->all();
        $selectedDataSea  = collect($reportSea)->only($selectedSea)->values()->all();

        Log::info('selectedDataSea', [
            $selectedDataSea
        ]);

        $vesselEmails = $this->loadVesselEmails();

        foreach ($selectedDataPort as $row) {
            $this->processEmailRow($row, 'At PORT', $vesselEmails);
        }

        foreach ($selectedDataSea as $row) {
            $this->processEmailRow($row, 'At SEA', $vesselEmails);
        }

        return redirect()->route('pages.consumption')
            ->with('success_email', 'All e-mails sent successfully.');
    }
}
