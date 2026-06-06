@extends('layouts.app')

@section('title', 'Fuel Baseline')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 border-b flex items-center justify-between">
            <h1 class="text-xl font-bold">Fuel Baseline Management</h1>
            <a href="{{ route('fuel-baseline.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm font-semibold">
                + Tambah Baseline
            </a>
        </div>

        <div class="p-6">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-center border border-gray-200 rounded">
                    <thead class="bg-gray-100 text-gray-700">
                        <tr>
                            <th class="px-4 py-3 border border-gray-200">Kode Kapal</th>
                            <th class="px-4 py-3 border border-gray-200">Nama Kapal</th>
                            <th class="px-4 py-3 border border-gray-200">BL MFO (L/day)</th>
                            <th class="px-4 py-3 border border-gray-200">BL HSD (L/day)</th>
                            <th class="px-4 py-3 border border-gray-200">Speed</th>
                            <th class="px-4 py-3 border border-gray-200">Safety Stock MFO</th>
                            <th class="px-4 py-3 border border-gray-200">Safety Stock HSD</th>
                            <th class="px-4 py-3 border border-gray-200">Terakhir Diubah</th>
                            <th class="px-4 py-3 border border-gray-200">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($fuelBaselines as $baseline)
                            <tr class="odd:bg-white even:bg-gray-50 hover:bg-blue-50">
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->vessel->vessel_id }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->vessel->vessel_name }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->bl_mfo }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->bl_hsd }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    {{ $baseline->speed }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->ss_mfo }}
                                    <span class="text-xs text-gray-400 font-normal">({{ $baseline->ss_multiplier_mfo }}x)</span>
                                </td>
                                <td class="px-4 py-3 border border-gray-200 font-semibold">
                                    {{ $baseline->ss_hsd }}
                                    <span class="text-xs text-gray-400 font-normal">({{ $baseline->ss_multiplier_hsd }}x)</span>
                                </td>
                                <td class="px-4 py-3 border border-gray-200 text-gray-500 text-xs">
                                    {{ $baseline->updated_at->format('d M Y H:i') }}
                                </td>
                                <td class="px-4 py-3 border border-gray-200">
                                    <div class="flex justify-center gap-2">
                                        <a href="{{ route('fuel-baseline.edit', $baseline) }}"
                                           class="bg-yellow-400 hover:bg-yellow-500 text-white px-3 py-1 rounded text-xs font-semibold">
                                            Edit
                                        </a>
                                        <form action="{{ route('fuel-baseline.destroy', $baseline) }}" method="POST"
                                              onsubmit="return confirm('Yakin hapus baseline kapal {{ $baseline->vessel->vessel_id }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs font-semibold">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-gray-400 text-center">
                                    Belum ada data baseline. <a href="{{ route('fuel-baseline.create') }}" class="text-blue-600 underline">Tambah sekarang</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection