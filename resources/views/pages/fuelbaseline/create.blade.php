@extends('layouts.app')

@section('title', 'Tambah Baseline')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">Tambah Fuel Baseline</h1>
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

            <form method="POST" action="{{ route('fuel-baseline.store') }}" x-data="{ addNew: 'existing' }">
                @csrf

                {{-- Pilih kapal --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kapal</label>

                    <div class="flex items-center gap-4 mb-3">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" x-model="addNew" value="existing" name="_vessel_mode"> Pilih kapal yang ada
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" x-model="addNew" value="new" name="_vessel_mode"> Tambah kapal baru
                        </label>
                    </div>

                    {{-- Dropdown kapal yang sudah ada --}}
                    <div x-show="addNew === 'existing'">
                        <select name="vessel_id"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Pilih Kapal --</option>
                            @foreach($vessels as $vessel)
                                <option value="{{ $vessel->vessel_id }}" {{ old('vessel_id') == $vessel->vessel_id ? 'selected' : '' }}>
                                    {{ $vessel->vessel_id }} — {{ $vessel->vessel_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Form kapal baru --}}
                    <div x-show="addNew === 'new'" class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Kode Kapal (3 huruf)</label>
                            <input type="text" name="new_vessel_id" maxlength="3"
                                value="{{ old('new_vessel_id') }}"
                                placeholder="ABC"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full uppercase focus:ring-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Nama Kapal</label>
                            <input type="text" name="new_vessel_name"
                                value="{{ old('new_vessel_name') }}"
                                placeholder="MV Example"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                {{-- BL MFO & HSD --}}
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL MFO (L/day)</label>
                        <input type="number" step="1" name="bl_mfo"
                               value="{{ old('bl_mfo') }}"
                               class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">BL HSD (L/day)</label>
                        <input type="number" step="1" name="bl_hsd"
                               value="{{ old('bl_hsd') }}"
                               class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Speed --}}
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Speed (Knot)</label>
                    <input type="number" step="1" name="speed"
                           value="{{ old('speed') }}"
                           class="border border-gray-300 rounded-md px-4 py-2 w-64 focus:ring-2 focus:ring-blue-500">
                </div>

                {{-- Safety Stock Multiplier --}}
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Safety Stock Multiplier MFO</label>
                        <select name="ss_multiplier_mfo"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                            <option value="2" {{ old('ss_multiplier_mfo', 2) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_mfo') == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Safety Stock Multiplier HSD</label>
                        <select name="ss_multiplier_hsd"
                                class="border border-gray-300 rounded-md px-4 py-2 w-full focus:ring-2 focus:ring-blue-500">
                            <option value="2" {{ old('ss_multiplier_hsd', 2) == 2 ? 'selected' : '' }}>2x</option>
                            <option value="3" {{ old('ss_multiplier_hsd') == 3 ? 'selected' : '' }}>3x</option>
                        </select>
                    </div>
                </div>

                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 font-semibold">
                    Simpan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection