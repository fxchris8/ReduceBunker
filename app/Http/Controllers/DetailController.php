<?php

namespace App\Http\Controllers;

class DetailController extends Controller
{
    public function mehsd($sessionType)
    {
        $details = session("details_me_hsd_maneuvering_{$sessionType}", []);
        return view('details.mehsd', compact('details', 'sessionType'));
    }

    public function time($sessionType)
    {
        $details = session("details_time_{$sessionType}", []);
        return view('details.time', compact('details', 'sessionType'));
    }

    public function bl_mehsd($sessionType)
    {
        $rawDetails = session("details_minus_{$sessionType}", [])['SELISIH ME Maneuvering'] ?? [];

        $details = is_array($rawDetails[0] ?? null) ? $rawDetails : (empty($rawDetails) ? [] : [$rawDetails]);

        if (!empty($details)) {
            usort($details, function ($a, $b) {
                $valA = $a['selisih'] ?? 0;
                $valB = $b['selisih'] ?? 0;
                return $valA <=> $valB;
            });
        }

        return view('details.bl_mehsd', compact('details', 'sessionType'));
    }
    
    public function bl_memfo($sessionType)
    {
        $rawDetails = session("details_minus_{$sessionType}", [])['EXCESS ME MFO L/NM (%)'] ?? [];

        $details = is_array($rawDetails[0] ?? null) ? $rawDetails : (empty($rawDetails) ? [] : [$rawDetails]);

        if (!empty($details)) {
            usort($details, function ($a, $b) {
                $valA = floatval(str_replace(['%', ','], '', $a['excess_me_mfo'] ?? '0'));
                $valB = floatval(str_replace(['%', ','], '', $b['excess_me_mfo'] ?? '0'));
                return $valA <=> $valB;
            });
        }

        return view('details.bl_memfo', compact('details', 'sessionType'));
    }

    public function bl_ae($sessionType)
    {
        $rawDetails = session("details_minus_{$sessionType}", [])['EXCESS AE'] ?? [];

        $details = is_array($rawDetails[0] ?? null) ? $rawDetails : (empty($rawDetails) ? [] : [$rawDetails]);

        if (!empty($details)) {
            usort($details, function ($a, $b) {
                $valA = $a['excess_ae'] ?? 0;
                $valB = $b['excess_ae'] ?? 0;
                return $valA <=> $valB;
            });
        }

        return view('details.bl_ae', compact('details', 'sessionType'));
    }
}