<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PO extends Model
{
    use HasFactory;

    protected $fillable =[
        'id_Po', 
        'Tanggal',
        'No_PO',
        'Kode_Shiptos',
        'nama_Shiptos',
        'Kode_Plants',
        'nama_Plants',
        'Kode_Products',
        'nama_Products',
        'Quantity', 
        'Qty_Sisa',
        'Status',
        'updated_at',
        'created_at'];
    protected $table = 'po';
    protected $primaryKey = 'id_Po'; 
    public $timestamps = false;
}
    