@extends('layouts.app')

@section('title', 'Edit Baseline')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">
                Edit Fuel Baseline — <span class="text-blue-600">{{ $fuelBaselines->vessel->vessel_id }}</span>
                <span class="text-gray-500 text-base font-normal">{{ $fuelBaselines->vessel->vessel_name }}</span>
            </h1>
            <a href="{{ route('fuel-baseline.index') }}"
               class="text-gray-500 hover:underline text-sm">
                &larr; Kembali
            </a>
        </div>

        <div class="p-6 max-w-2xl">
            @if($errors->any())
                <div class="bg-red-100 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('fuel-baseline.update', $fuelBaselines->id) }}">
                @csrf
                @method('PUT')

                {{-- BL MFO & HSD --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL MFO (L/day)</label>
                        <input type="number" step="1" name="bl_mfo"
                               value="{{ old('bl_mfo', $fuelBaselines->bl_mfo) }}"
                               class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL HSD (L/day)</label>
                        <input type="number" step="1" name="bl_hsd"
                               value="{{ old('bl_hsd', $fuelBaselines->bl_hsd) }}"
                               class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Speed --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Speed</label>
                    <input type="number" step="1" name="speed"
                           value="{{ old('speed', $fuelBaselines->speed) }}"
                           class="border border-gray-300 rounded-md px-4 py-2 w-64 focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Safety Stock Multiplier --}}
                <div class="grid grid-cols-2 gap-4 mb-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Safety Stock Multiplier MFO</label>
                        <select name="ss_multiplier_mfo"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                            <option value="2" {{ old('ss_multiplier_mfo', $fuelBaselines->ss_multiplier_mfo) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_mfo', $fuelBaselines->ss_multiplier_mfo) == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Safety Stock Multiplier HSD</label>
                        <select name="ss_multiplier_hsd"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                            <option value="2" {{ old('ss_multiplier_hsd', $fuelBaselines->ss_multiplier_hsd) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_hsd', $fuelBaselines->ss_multiplier_hsd) == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                </div>

                {{-- Info SS preview --}}
                <div class="mb-6 bg-gray-50 border border-gray-200 rounded-md px-4 py-3 text-sm text-gray-600">
                    Safety Stock saat ini:
                    <span class="font-semibold text-green-700">SS MFO = {{ number_format($fuelBaselines->ss_mfo, 2) }} L/day</span>,
                    <span class="font-semibold text-green-700">SS HSD = {{ number_format($fuelBaselines->ss_hsd, 2) }} L/day</span>
                    <span class="text-gray-400 text-xs">(akan berubah setelah disimpan)</span>
                </div>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold">
                    Perbarui
                </button>
            </form>
        </div>
    </div>
</div>
@endsection