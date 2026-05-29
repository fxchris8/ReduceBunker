-- Template insert untuk master refueling_plan.
-- Ganti nilai contoh di bawah sesuai master konsumsi kapal.
-- mfo_day_at_sea dan ae_day_at_sea diisi sebagai konsumsi per hari saat at sea.
-- speed diisi dalam knot agar rumus (distance / speed) / 24 menghasilkan hari pelayaran.

INSERT INTO refueling_plan (kapal, mfo_day_at_sea, ae_day_at_sea, speed) VALUES
('AKA', 4800.00, 575.00, 10),
('APE', 18000.00, 1560.00, 10);

-- Tambahkan kapal lain dengan format yang sama:
-- ('KODE_KAPAL', mfo_day_at_sea, ae_day_at_sea, speed);
