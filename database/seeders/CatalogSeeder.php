<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Partner;
use App\Models\Product;
use App\Models\ProductionStage;
use App\Models\WorkShift;
use App\Models\WorkStation;
use Illuminate\Database\Seeder;

/**
 * Reference data. Every value here is synthetic and invented for the
 * prototype — no real PHM partner, product or employee record is used.
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'PROD', 'name' => 'Produksi'],
            ['code' => 'QC', 'name' => 'Quality Control'],
            ['code' => 'PPIC', 'name' => 'PPIC'],
            ['code' => 'GUDANG', 'name' => 'Gudang & Pengiriman'],
            ['code' => 'LOG', 'name' => 'Logistik'],
            ['code' => 'ADM', 'name' => 'Administrasi'],
            ['code' => 'MGMT', 'name' => 'Manajemen'],
            ['code' => 'IT', 'name' => 'Teknologi Informasi'],
        ];

        foreach ($departments as $row) {
            Department::updateOrCreate(['code' => $row['code']], $row);
        }

        $shifts = [
            ['code' => 'S1', 'name' => 'Shift 1', 'start_time' => '07:00:00', 'end_time' => '15:00:00'],
            ['code' => 'S2', 'name' => 'Shift 2', 'start_time' => '15:00:00', 'end_time' => '23:00:00'],
            ['code' => 'S3', 'name' => 'Shift 3', 'start_time' => '23:00:00', 'end_time' => '07:00:00'],
        ];

        foreach ($shifts as $row) {
            WorkShift::updateOrCreate(['code' => $row['code']], $row);
        }

        $partners = [
            ['code' => 'MTR-01', 'name' => 'CV Maju Motor Sejahtera', 'contact_person' => 'Bapak Hendra', 'phone' => '0812-0000-0001', 'address' => 'Jl. Industri Raya 12, Bekasi'],
            ['code' => 'MTR-02', 'name' => 'PT Anugerah Parts Nusantara', 'contact_person' => 'Ibu Ratna', 'phone' => '0812-0000-0002', 'address' => 'Kawasan Industri Cikarang Blok C-4'],
            ['code' => 'MTR-03', 'name' => 'Toko Sparepart Rejeki Jaya', 'contact_person' => 'Bapak Slamet', 'phone' => '0812-0000-0003', 'address' => 'Jl. Raya Solo 88, Surakarta'],
            ['code' => 'MTR-04', 'name' => 'PT Garuda Otoparts Mandiri', 'contact_person' => 'Ibu Lestari', 'phone' => '0812-0000-0004', 'address' => 'Jl. Rungkut Industri 5, Surabaya'],
        ];

        foreach ($partners as $row) {
            Partner::updateOrCreate(['code' => $row['code']], $row);
        }

        $products = [
            ['sku' => 'KNL-STD-A', 'name' => 'Knalpot Standar Motor A', 'unit' => 'pcs', 'description' => 'Knalpot standar untuk motor bebek 110cc.'],
            ['sku' => 'KNL-STD-B', 'name' => 'Knalpot Standar Motor B', 'unit' => 'pcs', 'description' => 'Knalpot standar untuk motor matic 125cc.'],
            ['sku' => 'KNL-RCG-C', 'name' => 'Knalpot Racing Motor C', 'unit' => 'pcs', 'description' => 'Knalpot racing stainless untuk motor sport 150cc.'],
            ['sku' => 'LHR-KNL-01', 'name' => 'Leher Knalpot Tipe 1', 'unit' => 'pcs', 'description' => 'Komponen leher knalpot bahan mild steel.'],
            ['sku' => 'BRK-KNL-02', 'name' => 'Braket Knalpot Tipe 2', 'unit' => 'pcs', 'description' => 'Braket dudukan knalpot hasil stamping.'],
        ];

        foreach ($products as $row) {
            Product::updateOrCreate(['sku' => $row['sku']], $row);
        }

        // Stages are data, not hardcoded logic — PHM can reorder or disable
        // these without a code change.
        $stages = [
            ['code' => 'POTONG', 'name' => 'Pemotongan', 'sequence' => 10],
            ['code' => 'LAS', 'name' => 'Pengelasan', 'sequence' => 20],
            ['code' => 'HALUS', 'name' => 'Penghalusan', 'sequence' => 30],
            ['code' => 'CAT', 'name' => 'Pengecatan', 'sequence' => 40],
            ['code' => 'RAKIT', 'name' => 'Perakitan', 'sequence' => 50],
            ['code' => 'PERIKSA', 'name' => 'Pemeriksaan Akhir', 'sequence' => 60],
        ];

        foreach ($stages as $row) {
            ProductionStage::updateOrCreate(['code' => $row['code']], $row);
        }

        $stations = [
            ['code' => 'ST-01', 'name' => 'Stasiun Pemotongan 1'],
            ['code' => 'ST-02', 'name' => 'Stasiun Pengelasan 1'],
            ['code' => 'ST-03', 'name' => 'Stasiun Pengelasan 2'],
            ['code' => 'ST-04', 'name' => 'Stasiun Penghalusan'],
            ['code' => 'ST-05', 'name' => 'Stasiun Pengecatan'],
            ['code' => 'ST-06', 'name' => 'Stasiun Perakitan'],
        ];

        foreach ($stations as $row) {
            WorkStation::updateOrCreate(['code' => $row['code']], $row);
        }
    }
}
