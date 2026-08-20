@extends('layouts.app')

@section('title', 'Edit Baseline')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">
                Edit Baseline
            </h1>
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

            <form method="POST" action="{{ route('fuel-baseline.update', $fuelBaselines->vessel_id) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vessel Code</label>
                        <input type="text"
                            value="{{ $fuelBaselines->vessel->vessel_id }}"
                            disabled
                            class="border border-gray-200 bg-gray-100 text-gray-500 rounded-md px-4 py-2 w-full text-sm cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vessel Name</label>
                        <input type="text"
                            value="{{ $fuelBaselines->vessel->vessel_name }}"
                            disabled
                            class="border border-gray-200 bg-gray-100 text-gray-500 rounded-md px-4 py-2 w-full text-sm cursor-not-allowed">
                    </div>
                </div>

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Fuel Type</p>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div x-data="{ meType: '{{ in_array(old('me_fuel_type', $fuelBaselines->me_fuel_type ?? 'MFO'), ['MFO', 'HSD']) ? old('me_fuel_type', $fuelBaselines->me_fuel_type ?? 'MFO') : 'Other'}}'}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">ME Fuel Type</label>
                        <select name="me_fuel_type" x-model="meType" class="border border-gray-300 rounded-md px-4 py-2 w-full text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="MFO">MFO</option>
                            <option value="HSD">HSD</option>
                            <option value="Other">Other</option>
                        </select>
                        <div x-show="meType === 'Other'" class="mt-2">
                            <input type="text" name="me_fuel_type_other" 
                                value="{{ !in_array(old('me_fuel_type', $fuelBaselines->me_fuel_type ?? ''), ['MFO', 'HSD']) ? old('me_fuel_type_other', $fuelBaselines->me_fuel_type ?? '') : '' }}"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full text-sm focus:ring-2 focus:ring-blue-500" placeholder="Enter other fuel type">
                        </div>
                    </div>
                    <div x-data="{ aeType: '{{ in_array(old('ae_fuel_type', $fuelBaselines->ae_fuel_type ?? 'HSD'), ['MFO', 'HSD']) ? old('ae_fuel_type', $fuelBaselines->ae_fuel_type ?? 'HSD') : 'Other'}}'}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE Fuel Type</label>
                        <select name="ae_fuel_type" x-model="aeType" class="border border-gray-300 rounded-md px-4 py-2 w-full text-sm focus:ring-2 focus:ring-blue-500">
                            <option value="MFO">MFO</option>
                            <option value="HSD">HSD</option>
                            <option value="Other">Other</option>
                        </select>
                        <div x-show="aeType === 'Other'" class="mt-2">
                            <input type="text" name="ae_fuel_type_other" 
                                value="{{ !in_array(old('ae_fuel_type', $fuelBaselines->ae_fuel_type ?? ''), ['MFO', 'HSD']) ? old('ae_fuel_type_other', $fuelBaselines->ae_fuel_type ?? '') : '' }}"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full text-sm focus:ring-2 focus:ring-blue-500" placeholder="Enter other fuel type">
                        </div>
                    </div>
                </div>

                {{-- Static Baseline --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Static Baseline</p>
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ME (L/Hours)</label>
                        <input type="number" step="1" name="static_bl_me"
                            value="{{ old('static_bl_me', $fuelBaselines->static_bl_me) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE (L/Hours)</label>
                        <input type="number" step="1" name="static_bl_ae"
                            value="{{ old('static_bl_ae', $fuelBaselines->static_bl_ae) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE 1 Reefer</label>
                        <input type="number" step="1" name="bl_ae_1_reffer"
                            value="{{ old('bl_ae_1_reffer', $fuelBaselines->bl_ae_1_reffer) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">AE 2 Reefer (Parallel)</label>
                        <input type="number" step="1" name="ae_parallel_2"
                            value="{{ old('ae_parallel_2', $fuelBaselines->ae_parallel_2) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL L/Nm</label>
                        <input type="number" step="1" name="bl_l_nm"
                            value="{{ old('bl_l_nm', $fuelBaselines->bl_l_nm ?? '') }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier ME</label>
                        <input type="number" step="1" name="ss_multiplier_me"
                            value="{{ old('ss_multiplier_me', $fuelBaselines->ss_multiplier_me) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier AE</label>
                        <input type="number" step="1" name="ss_multiplier_ae"
                            value="{{ old('ss_multiplier_ae', $fuelBaselines->ss_multiplier_ae) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                {{-- Dynamic Baseline --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Dynamic Baseline</p>
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ME (L/Hours)</label>
                        <input type="number" step="1" name="dynamic_bl_me"
                            value="{{ old('dynamic_bl_me', $fuelBaselines->dynamic_bl_me) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">SS Multiplier ME</label>
                        <input type="number" step="1" name="ss_multiplier_me_dynamic"
                            value="{{ old('ss_multiplier_me_dynamic', $fuelBaselines->ss_multiplier_me_dynamic) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                </div>

                {{-- Other --}}
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Other</p>
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Density</label>
                        <input type="number" step="1" name="density"
                            value="{{ old('density', $fuelBaselines->density) }}"
                            class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Speed (Knot)</label>
                        <input type="number" step="1" name="speed"
                            value="{{ old('speed', $fuelBaselines->speed) }}"
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