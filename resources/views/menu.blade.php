@extends('layouts.app')

@section('title', 'Menu')

@section('loader')
<div id="loader" class="fixed inset-0 bg-white bg-opacity-90 flex flex-col items-center justify-center z-50 hidden">
    <div class="w-16 h-16 border-4 border-gray-300 border-t-red-600 rounded-full animate-spin"></div>
    <p class="mt-4 text-red-600 font-semibold">Loading...</p>
</div>
@endsection

@section('content')
<div class="container mx-auto py-10 px-6">
    <div class="bg-white rounded-xl shadow-lg p-8">
        <h2 class="text-2xl font-bold text-gray-700 mb-6 text-center">Main Menu</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Home -->
            <a href="{{ route('dashboard') }}" 
               class="flex items-center justify-center gap-3 py-6 px-6 text-lg bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-700 hover:to-blue-600 text-white font-semibold rounded-lg shadow-md transform hover:scale-105 transition duration-300">
                <i class="fas fa-home text-2xl"></i>
                Home / Dashboard
            </a>

            <!-- Consumption Analysis -->
            <a href="{{ route('po.upload') }}" 
               class="flex items-center justify-center gap-3 py-6 px-6 text-lg bg-gradient-to-r from-green-600 to-green-500 hover:from-green-700 hover:to-green-600 text-white font-semibold rounded-lg shadow-md transform hover:scale-105 transition duration-300">
                <i class="fas fa-chart-line text-2xl"></i>
                Consumption Analysis
            </a>

            <!-- Refueling Planning -->
            <a href="{{ route('po.planning') }}" 
               class="flex items-center justify-center gap-3 py-6 px-6 text-lg bg-gradient-to-r from-purple-600 to-purple-500 hover:from-purple-700 hover:to-purple-600 text-white font-semibold rounded-lg shadow-md transform hover:scale-105 transition duration-300">
                <i class="fas fa-gas-pump text-2xl"></i>
                Refueling Planning
            </a>

            <!-- Baseline Analysis -->
            <a href="{{ route('po.baseline') }}" 
               class="flex items-center justify-center gap-3 py-6 px-6 text-lg bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-semibold rounded-lg shadow-md transform hover:scale-105 transition duration-300">
                <i class="fas fa-balance-scale text-2xl"></i>
                Baseline Analysis
            </a>
        </div>
    </div>
</div>
@endsection
