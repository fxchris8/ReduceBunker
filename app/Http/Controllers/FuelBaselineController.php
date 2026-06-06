<?php

namespace App\Http\Controllers;

use App\Models\FuelBaseline;
use App\Models\Vessel;
use Illuminate\Http\Request;

class FuelBaselineController extends Controller
{
    public function index()
    {
        $fuelBaselines = FuelBaseline::with('vessel')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('pages.fuelbaseline.index', compact('fuelBaselines'));
    }

    public function create()
    {
        $vessels = Vessel::orderBy('vessel_id')->get();

        return view('pages.fuelbaseline.create', compact('vessels'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'new_vessel_id'     => 'nullable|string|size:3|uppercase|unique:vessels,vessel_id',
            'new_vessel_name'   => 'nullable|string|max:255|required_with:new_vessel_id',
            'vessel_id'         => 'nullable|exists:vessels,vessel_id',
            'bl_mfo'            => 'required|numeric|min:0',
            'bl_hsd'            => 'required|numeric|min:0',
            'speed'             => 'required|numeric|min:0',
            'ss_multiplier_mfo' => 'required|in:2,3',
            'ss_multiplier_hsd' => 'required|in:2,3',
        ]);

        if (!empty($validated['new_vessel_id'])) {
            $vessel = Vessel::create([
                'vessel_id'   => strtoupper($validated['new_vessel_id']),
                'vessel_name' => $validated['new_vessel_name'],
            ]);
            $vessel_id = $vessel->vessel_id;
        } else {
            $vessel_id = $validated['vessel_id'];
        }

        $existing = FuelBaseline::where('vessel_id', $vessel_id)->first();
        if ($existing) {
            return back()->withErrors(['vessel_id' => 'Kapal ini sudah memiliki data baseline. Gunakan fitur Edit.'])->withInput();
        }

        FuelBaseline::create([
            'vessel_id'         => $vessel_id,
            'bl_mfo'            => $validated['bl_mfo'],
            'bl_hsd'            => $validated['bl_hsd'],
            'speed'             => $validated['speed'],
            'ss_multiplier_mfo' => $validated['ss_multiplier_mfo'],
            'ss_multiplier_hsd' => $validated['ss_multiplier_hsd'],
        ]);

        return redirect()->route('fuel-baseline.index')->with('success', 'Data baseline berhasil ditambahkan.');
    }

    public function edit(FuelBaseline $fuel_baseline)
    {
        $fuel_baseline->load('vessel');

        return view('pages.fuelbaseline.edit', ['fuelBaselines' => $fuel_baseline]);
    }

    public function update(Request $request, FuelBaseline $fuel_baseline)
    {
        $validated = $request->validate([
            'bl_mfo'            => 'required|numeric|min:0',
            'bl_hsd'            => 'required|numeric|min:0',
            'speed'             => 'required|numeric|min:0',
            'ss_multiplier_mfo' => 'required|in:2,3',
            'ss_multiplier_hsd' => 'required|in:2,3',
        ]);

        $fuel_baseline->update($validated);

        return redirect()->route('fuel-baseline.index')->with('success', 'Data baseline berhasil diperbarui.');
    }

    public function destroy(FuelBaseline $fuel_baseline)
    {
        $fuel_baseline->delete();

        return redirect()->route('fuel-baseline.index')->with('success', 'Data baseline berhasil dihapus.');
    }
}