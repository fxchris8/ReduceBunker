@extends('layouts.app')

@section('title', 'Menu')

@section('content')
<div class="container mx-auto py-6 px-4">
    <div class="bg-white rounded-lg shadow-md">
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/" 
                class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Home / Dashboard
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/consumption-analysis/statis" 
                class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Consumption Analysis
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/refueling-planning" 
                class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Refueling Planning
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/po" 
                class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                PO
            </a>
        </div>
        <div class="bg-gray-50 px-4 py-4 flex justify-center">
            <a href="/baseline-analysis" 
                class="w-[250px] py-4 px-6 text-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-md transition text-center block">
                Baseline Analysis
            </a>
        </div>
    </div>
</div>
@endsection