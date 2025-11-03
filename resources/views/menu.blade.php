@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/">
                <button
                    class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                    Home / Dashboard
                </button>
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/consumption-analysis">
                <button
                    class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                    Consumption Analysis
                </button>
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/refueling-planning">
                <button
                    class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                    Refueling Planning 
                </button>
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/po">
                <button
                    class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                    PO 
                </button>
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/baseline-analysis">
                <button
                    class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition">
                    Baseline Analysis 
                </button>
            </a>
        </div>
    </div>
</div>
@endsection
