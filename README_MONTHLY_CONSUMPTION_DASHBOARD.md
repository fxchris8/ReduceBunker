# Dashboard Konsumsi Bulanan

Dokumen ini menjelaskan sinkronisasi data dan penggunaan dashboard konsumsi bulanan.

## Ringkasan

Data konsumsi dari API bunker disimpan sebagai snapshot harian pada tabel:

```text
vessel_consumption_daily
```

Setiap snapshot menyimpan data berdasarkan:

- tanggal laporan;
- Vessel ID;
- tipe sesi: `port` (report ID `14`) atau `sea` (report ID `16`).

Dashboard menggabungkan data `port` dan `sea`, lalu menjumlahkan seluruh snapshot dalam bulan yang dipilih untuk setiap kapal.

## Kolom konsumsi

Tabel dashboard menampilkan total bulanan per Vessel ID untuk:

- ME MFO
- ME HSD
- AE MFO
- AE HSD
- Boiler HSD
- Boiler MFO
- Genset Consumption

## Instalasi database

Jalankan migration untuk membuat tabel snapshot:

```sh
php artisan migrate --force
```

Untuk development lokal, `--force` dapat dihilangkan:

```sh
php artisan migrate
```

## Sinkronisasi manual

Ambil data API dan simpan snapshot untuk tanggal hari ini:

```sh
php artisan bunker:sync-daily
```

Untuk mengambil ulang data pada tanggal tertentu:

```sh
php artisan bunker:sync-daily --date=2026-09-04
```

Command memakai timezone `Asia/Jakarta`. Data untuk report ID `14` dan `16` akan diambil lalu disimpan dengan `upsert`, sehingga menjalankan ulang command untuk tanggal yang sama akan memperbarui snapshot tanpa membuat duplikasi.

## Scheduler otomatis pukul 15.00 WIB

Jadwal Laravel telah didefinisikan untuk menjalankan sinkronisasi setiap hari pukul **15.00 WIB**:

```text
php artisan bunker:sync-daily
```

Agar jadwal tersebut benar-benar berjalan, server harus memanggil scheduler Laravel setiap menit.

### Linux server (cron)

Tambahkan cron job berikut. Sesuaikan path project dan lokasi executable PHP bila diperlukan:

```cron
* * * * * cd /path/to/ReduceBunker && php artisan schedule:run >> /dev/null 2>&1
```

Contoh jika project berada di `/var/www/ReduceBunker`:

```cron
* * * * * cd /var/www/ReduceBunker && php artisan schedule:run >> /dev/null 2>&1
```

Edit cron user aktif dengan:

```sh
crontab -e
```

### Windows server (Task Scheduler)

Buat task yang berjalan setiap satu menit dengan program PHP dan argument berikut:

```text
C:\path\to\php.exe C:\path\to\ReduceBunker\artisan schedule:run
```

Atur nilai **Start in** ke direktori project:

```text
C:\path\to\ReduceBunker
```

## Memeriksa scheduler

Tampilkan seluruh jadwal Laravel:

```sh
php artisan schedule:list
```

Harus terlihat task `php artisan bunker:sync-daily` dengan jadwal `15:00`.

Untuk memeriksa task yang jatuh tempo saat ini secara manual:

```sh
php artisan schedule:run
```

Perintah ini hanya memeriksa jadwal sekali lalu selesai. Ia tidak berjalan terus-menerus, sehingga cron atau Windows Task Scheduler tetap diperlukan.

## Troubleshooting

### API timeout atau tidak dapat diakses

Jika command menampilkan error seperti berikut:

```text
cURL error 28: Failed to connect to nanika.spil.co.id port 3021
```

Periksa:

1. server dapat menjangkau `http://nanika.spil.co.id:3021`;
2. firewall, VPN, DNS, atau jaringan internal yang diperlukan sudah tersedia;
3. API bunker sedang aktif.

Jika koneksi API gagal, command berhenti dan tidak menandai sinkronisasi sebagai berhasil.

### Dashboard tidak menampilkan data

1. Pastikan migration telah dijalankan.
2. Jalankan sinkronisasi manual untuk satu tanggal.
3. Pastikan API mengembalikan data pada tanggal tersebut.
4. Buka dashboard dan pilih bulan yang sesuai dengan tanggal snapshot.
