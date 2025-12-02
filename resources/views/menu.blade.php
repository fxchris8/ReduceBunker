@extends('layouts.app')

@section('title', 'Menu')

@section('loader')
<div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
    <div class="w-16 h-16 border-4 border-gray-300 border-t-red-300 rounded-full animate-spin"></div>
    <p class="mt-4 text-red-600 font-semibold">Loading...</p>
</div>
@endsection

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/" class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Home / Dashboard
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/consumption-analysis/statis" class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Consumption Analysis
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/refueling-planning" class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Refueling Planning
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/baseline-analysis" class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Baseline Analysis
            </a>
        </div>
    </div>
</div>
@endsection