<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PO;

class BunkerController extends Controller
{
    public function index()
    {
        // $purchaseOrders = collect(); // Inisialisasi koleksi kosong
        return view('menu');
    }
}
