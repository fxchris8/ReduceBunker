<?php

namespace App\Http\Controllers;

use App\Models\FuelBaseline;
use App\Models\Vessel;
use Illuminate\Http\Request;

class FuelBaselineController extends Controller
{
    public function index(Request $request)
    {
        $perPage  = $request->get('per_page', 10);
        $sortBy   = $request->get('sort_by', 'updated_at');
        $sortDir  = $request->get('sort_dir', 'desc');

        $allowedSorts = ['vessel_id', 'vessel_name', 'updated_at'];
        $allowedDirs  = ['asc', 'desc'];

        if (!in_array($sortBy, $allowedSorts)) $sortBy = 'updated_at';
        if (!in_array($sortDir, $allowedDirs))  $sortDir = 'desc';

        $search = $request->get('search', '');

        $query = FuelBaseline::with('vessel')
            ->join('vessels', 'fuel_baselines.vessel_id', '=', 'vessels.vessel_id')
            ->select('fuel_baselines.*');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('fuel_baselines.vessel_id', 'like', "%{$search}%")
                ->orWhere('vessels.vessel_name', 'like', "%{$search}%");
            });
        }

        if ($sortBy === 'vessel_name') {
            $query->orderBy('vessels.vessel_name', $sortDir);
        } elseif ($sortBy === 'vessel_id') {
            $query->orderBy('fuel_baselines.vessel_id', $sortDir);
        } else {
            $query->orderBy('fuel_baselines.updated_at', $sortDir);
        }

        if ($perPage === 'all') {
            $fuelBaselines = $query->get();
            $isPaginated   = false;
        } else {
            $fuelBaselines = $query->paginate((int) $perPage)->withQueryString();
            $isPaginated   = true;
        }


        return view('pages.fuelbaseline.index', compact('fuelBaselines', 'isPaginated', 'perPage', 'sortBy', 'sortDir', 'search'));
    }

    public function create()
    {
        $vessels = Vessel::orderBy('vessel_id')->get();
        $existingVesselIds = FuelBaseline::pluck('vessel_id')->toArray();

        return view('pages.fuelbaseline.create', compact('vessels', 'existingVesselIds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'new_vessel_id'            => 'nullable|string|size:3|uppercase|unique:vessels,vessel_id',
            'new_vessel_name'          => 'nullable|string|max:255|required_with:new_vessel_id',
            'vessel_id'                => 'nullable|exists:vessels,vessel_id',
            'static_bl_me'             => 'required|integer|min:0',
            'static_bl_ae'             => 'required|integer|min:0',
            'bl_l_nm'                  => 'nullable|integer|min:0',
            'dynamic_bl_me'            => 'required|integer|min:0',
            'ae_parallel_2'            => 'nullable|integer|min:0',
            'density'                  => 'required|integer|min:0',
            'bl_ae_1_reffer'           => 'nullable|integer|min:0',
            'speed'                    => 'required|integer|min:0',
            'ss_multiplier_me'         => 'required|in:2,3',
            'ss_multiplier_ae'         => 'required|in:2,3',
            'ss_multiplier_me_dynamic' => 'nullable|in:2,3',
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
            'vessel_id'                => $vessel_id,
            'static_bl_me'             => $validated['static_bl_me'],
            'static_bl_ae'             => $validated['static_bl_ae'],
            'bl_l_nm'                  => $validated['bl_l_nm'] ?? null,
            'dynamic_bl_me'            => $validated['dynamic_bl_me'],
            'ae_parallel_2'            => $validated['ae_parallel_2'] ?? null,
            'density'                  => $validated['density'],
            'bl_ae_1_reffer'           => $validated['bl_ae_1_reffer'] ?? null,
            'speed'                    => $validated['speed'],
            'ss_multiplier_me'         => $validated['ss_multiplier_me'],
            'ss_multiplier_ae'         => $validated['ss_multiplier_ae'],
            'ss_multiplier_me_dynamic' => $validated['ss_multiplier_me_dynamic'] ?? null,
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
            'static_bl_me'             => 'required|integer|min:0',
            'static_bl_ae'             => 'required|integer|min:0',
            'bl_l_nm'                  => 'nullable|integer|min:0',
            'dynamic_bl_me'            => 'required|integer|min:0',
            'ae_parallel_2'            => 'nullable|integer|min:0',
            'density'                  => 'required|integer|min:0',
            'bl_ae_1_reffer'           => 'nullable|integer|min:0',
            'speed'                    => 'required|integer|min:0',
            'ss_multiplier_me'         => 'required|in:2,3',
            'ss_multiplier_ae'         => 'required|in:2,3',
            'ss_multiplier_me_dynamic' => 'required|in:2,3',
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