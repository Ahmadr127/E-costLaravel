<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Kategori;

class KategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = [
            [
                'nama_kategori' => 'Konsultasi Medis',
                'deskripsi' => 'Layanan konsultasi dengan dokter spesialis',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Pemeriksaan Laboratorium',
                'deskripsi' => 'Berbagai jenis pemeriksaan laboratorium',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Radiologi',
                'deskripsi' => 'Pemeriksaan dengan sinar X, CT Scan, MRI, dll',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Tindakan Operasi',
                'deskripsi' => 'Berbagai jenis operasi dan prosedur bedah',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Fisioterapi',
                'deskripsi' => 'Layanan rehabilitasi dan fisioterapi',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Farmasi',
                'deskripsi' => 'Layanan obat-obatan dan farmasi',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Rawat Inap',
                'deskripsi' => 'Layanan perawatan pasien rawat inap',
                'is_active' => true
            ],
            [
                'nama_kategori' => 'Gawat Darurat',
                'deskripsi' => 'Layanan gawat darurat dan ICU',
                'is_active' => true
            ]
        ];

        foreach ($kategori as $data) {
            Kategori::firstOrCreate(
                ['nama_kategori' => $data['nama_kategori']],
                $data
            );
        }
    }
}
