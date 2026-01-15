<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Layanan extends Model
{
    use HasFactory;

    protected $table = 'layanan';

    protected $fillable = [
        'kode',
        'jenis_pemeriksaan',
        'tarif_master',
        'kategori_id',
        'unit_cost',
        'deskripsi',
        'is_active'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }


    // Mutator untuk menyimpan kode - TIDAK auto-generate di sini
    // Auto-generate hanya dilakukan via boot() setelah model tersimpan
    public function setKodeAttribute($value)
    {
        // Simpan nilai apa adanya - jangan auto-generate di mutator
        // karena $this->id belum tersedia saat create
        $this->attributes['kode'] = $value ?: null;
    }

    /**
     * Boot method untuk auto-generate kode setelah model tersimpan
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate kode setelah record dibuat, jika kode masih kosong
        static::created(function ($layanan) {
            if (empty($layanan->kode)) {
                // Generate kode berdasarkan ID yang sudah ada
                $layanan->kode = 'L' . str_pad($layanan->id, 4, '0', STR_PAD_LEFT);
                $layanan->saveQuietly(); // Gunakan saveQuietly untuk menghindari infinite loop
            }
        });
    }
}
