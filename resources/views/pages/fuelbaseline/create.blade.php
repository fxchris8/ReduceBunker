@extends('layouts.app')

@section('title', 'Add Baseline')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">Add Baseline</h1>
            <a href="{{ route('fuel-baseline.index') }}"
               class="text-gray-500 hover:underline text-sm">
                &larr; Back
            </a>
        </div>

        <div class="p-6">
            @if($errors->any())
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('fuel-baseline.store') }}" x-data="{ addNew: 'existing' }">
                @csrf

                {{-- Radio toggle --}}
                <div class="flex items-center gap-4 mb-4">
                    <span class="text-sm font-medium text-gray-700">Vessel:</span>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" x-model="addNew" value="existing" name="_vessel_mode"> Select existing vessel
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" x-model="addNew" value="new" name="_vessel_mode"> Add new vessel
                    </label>
                </div>

                {{-- EXISTING --}}
                <div x-show="addNew === 'existing'" class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vessel</label>
                        <select name="vessel_id"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="">-- Select Vessel --</option>
                            @foreach($vessels as $vessel)
                                @php $hasBaseline = in_array($vessel->vessel_id, $existingVesselIds); @endphp
                                <option value="{{ $vessel->vessel_id }}"
                                        {{ old('vessel_id') == $vessel->vessel_id ? 'selected' : '' }}
                                        {{ $hasBaseline ? 'disabled' : '' }}
                                        class="{{ $hasBaseline ? 'text-gray-400 bg-gray-100' : '' }}">
                                    {{ $vessel->vessel_id }} ({{ $vessel->vessel_name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- ADD VESSEL --}}
                <div x-show="addNew === 'new'" class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vessel Code <span class="text-gray-400 font-normal">(3 chars)</span></label>
                        <input type="text" name="new_vessel_id" maxlength="3"
                            value="{{ old('new_vessel_id') }}"
                            placeholder="AKA"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full uppercase focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vessel Name</label>
                        <input type="text" name="new_vessel_name"
                            value="{{ old('new_vessel_name') }}"
                            placeholder="AKASHIA"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                {{-- Static Baseline --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Static Baseline</p>
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ME (L/Hours)</label>
                        <input type="number" step="1" name="static_bl_me"
                            value="{{ old('static_bl_me') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE (L/Hours)</label>
                        <input type="number" step="1" name="static_bl_ae"
                            value="{{ old('static_bl_ae') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE 1 Reefer</label>
                        <input type="number" step="1" name="bl_ae_1_reffer"
                            value="{{ old('bl_ae_1_reffer') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE 2 Reefer (Parallel)</label>
                        <input type="number" step="1" name="ae_parallel_2"
                            value="{{ old('ae_parallel_2') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL L/Nm</label>
                        <input type="number" step="1" name="bl_l_nm"
                            value="{{ old('bl_l_nm') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier ME</label>
                        <select name="ss_multiplier_me"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="2" {{ old('ss_multiplier_me', 2) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_me') == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier AE</label>
                        <select name="ss_multiplier_ae"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="2" {{ old('ss_multiplier_ae', 2) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_ae') == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                </div>

                {{-- Dynamic Baseline --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Dynamic Baseline</p>
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ME (L/Hours)</label>
                        <input type="number" step="1" name="dynamic_bl_me"
                            value="{{ old('dynamic_bl_me') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier ME</label>
                        <select name="ss_multiplier_me_dynamic"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="2" {{ old('ss_multiplier_me_dynamic', 2) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_me_dynamic')    == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                </div>

                {{-- Other --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Other</p>
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Density</label>
                        <input type="number" step="1" name="density"
                            value="{{ old('density') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Speed (Knot)</label>
                        <input type="number" step="1" name="speed"
                            value="{{ old('speed') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold text-sm">
                    Save
                </button>
            </form>
        </div>
    </div>
</div>
@endsection