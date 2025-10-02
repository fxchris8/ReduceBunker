<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PO;
use Shuchkin\SimpleXLSX;


class POController extends Controller
{
    public function index()
    {
        $purchaseOrders = collect();
        return view('po.po_dashboard', compact('purchaseOrders'));
    }

    public function search(Request $request)
    {
        $Po_number = $request->input('PO');

        $purchaseOrders = PO::when($Po_number, function ($query, $Po_number) {
            return $query->where('No_PO', 'like', '%' . $Po_number . '%');
        })->get();

        return response()->json($purchaseOrders);
    }

    public function create()
    {
        return view('po.create');
    }

    // Menyimpan data PO ke database
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_po' => 'required|string|max:255',
            'nama_shipto' => 'required|string|max:255',
            'kode_shipto' => 'required|string|max:255',
            'nama_product' => 'required|string|max:255',
            'kode_product' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
        ]);

        // Simpan ke database
        PO::create([
            'No_PO' => $request->kode_po,
            'nama_Shiptos' => $request->nama_shipto,
            'Kode_Shiptos' => $request->kode_shipto,
            'nama_Products' => $request->nama_product,
            'Kode_Products' => $request->kode_product,
            'Quantity' => $request->qty,
            'Qty_Sisa' => $request->qty,
            'Link' => $request->link,
        ]);

        // Redirect dengan pesan sukses
        return redirect()->route('po.po_dashboard')->with('success', 'PO berhasil disimpan!');
    }

    // Menampilkan halaman monitoring PO.
    public function edit()
    {
        $po = PO::all(); // Ambil semua data PO
        return view('po.monitoring', compact('po'));
    }

    // Mengambil data PO berdasarkan kode PO (untuk AJAX).
    public function getPO($kode_po)
    {
        $po = PO::where('No_PO', $kode_po)->first();

        if (!$po) {
            return response()->json(['error' => 'PO tidak ditemukan'], 404);
        }

        return response()->json([
            'nama_shipto' => $po->nama_Shiptos,
            'kode_shipto' => $po->Kode_Shiptos,
            'qty' => $po->Quantity,
            'qty_sisa' => $po->Qty_Sisa 
        ]);
    }

    // Memperbarui pemakaian dan mengurangi sisa QTY.
    public function update(Request $request)
    {
        $request->validate([
            'id_Po' => 'required|exists:po,id_Po',
            'qty_digunakan' => 'required|integer|min:1',
        ]);

        $po = PO::where('id_Po', $request->id_Po)->first();
        if (!$po) {
            return response()->json(['error' => 'Data PO tidak ditemukan'], 404);
        }

        if ($request->qty_digunakan > $po->Qty_Sisa) {
            return response()->json(['error' => 'Pemakaian melebihi stok tersedia!'], 400);
        }

        $po->Qty_Sisa -= $request->qty_digunakan;

        if ($po->Qty_Sisa <= 0) {
            $po->Qty_Sisa = 0;
            $po->Status = 'Closed';
        }

        $po->save();

        return response()->json(['message' => 'Data PO diperbarui!']);
    }


    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv|max:2048', // Validasi file hanya XLSX & CSV dengan ukuran maksimal 2MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $path = $file->getRealPath(); // Ambil path sementara file

            if ($extension === 'xlsx') {
                // Parsing XLSX
                if ($xlsx = SimpleXLSX::parse($path)) {
                    $rows = $xlsx->rows();
                    $this->saveData($rows);
                    return back()->with('success', 'File XLSX berhasil diproses.');
                } else {
                    return back()->with('error', 'Gagal membaca file XLSX.');
                }
            } elseif ($extension === 'csv') {
                // Parsing CSV
                $data = [];

                // Membaca file CSV
                if (($handle = fopen($path, "r")) !== FALSE) {
                    while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                        $data[] = $row;
                    }
                    fclose($handle);
                }

                // Pastikan ada data
                if (count($data) <= 1) {
                    return back()->with('error', 'File kosong atau format salah.');
                }

                // Hapus header
                unset($data[0]);

                // Simpan ke database dengan pengecekan duplikasi
                $this->saveData($data);

                return back()->with('success', 'File CSV berhasil diproses.');
            }
        }

        return back()->with('error', 'File tidak ditemukan atau format tidak didukung.');
    }

    private function saveData($rows)
    {
        foreach ($rows as $index => $row) {
            if ($index === 0) continue; 

            // dd($row); s zs
            $no_po = isset($row[2]) && trim($row[2]) !== '' ? trim($row[2]) : null;
            $kode_shiptos = isset($row[3]) && trim($row[3]) !== '' ? trim($row[3]) : null;
            $nama_shiptos = isset($row[4]) && trim($row[4]) !== '' ? trim($row[4]) : null;
            $kode_plants = isset($row[5]) && trim($row[5]) !== '' ? trim($row[5]) : null;
            $nama_plants = isset($row[6]) && trim($row[6]) !== '' ? trim($row[6]) : null;
            $kode_product = isset($row[7]) && trim($row[7]) !== '' ? trim($row[7]) : null;
            $nama_product = isset($row[8]) && trim($row[8]) !== '' ? trim($row[8]) : null;
            $quantity = isset($row[10]) && trim($row[10]) !== '' ? trim($row[10]) : null;
            $qty_sisa = isset($row[12]) && trim($row[12]) !== '' ? trim($row[12]) : null;
            $status = isset($row[15]) && trim($row[15]) !== '' ? trim($row[15]) : 'OPEN'; 

            
            // dd(compact('no_po', 'kode_product', 'nama_product', 'quantity', 'qty_sisa', 'status'));

            if ($no_po) {
                
                $existingPO = PO::where('No_PO', $no_po)->first();

                if ($existingPO) {
                    $existingPO->update([
                        'Quantity' => $quantity ?? $existingPO->Quantity,
                        'Qty_Sisa' => $qty_sisa ?? $existingPO->Qty_Sisa,
                    ]);
                } else {
                    // Jika No_PO belum ada, buat data baru
                    PO::create([
                        'No_PO' => $no_po,
                        'Kode_Shiptos' => $kode_shiptos,
                        'nama_Shiptos' => $nama_shiptos,
                        'Kode_Plants' => $kode_plants,
                        'nama_Plants' => $nama_plants,
                        'Kode_Products' => $kode_product, 
                        'nama_Products' => $nama_product,
                        'Quantity' => $quantity,
                        'Qty_Sisa' => $qty_sisa,
                        'Status' => $status,
                    ]);
                }
            }
        }
    }
}
