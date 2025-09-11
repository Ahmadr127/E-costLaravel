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
        'kategori_id',
        'unit_cost',
        'margin',
        'tarif',
        'deskripsi',
        'is_active'
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'margin' => 'decimal:2',
        'tarif' => 'decimal:2',
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

    // Accessor untuk menghitung tarif otomatis
    public function getCalculatedTarifAttribute()
    {
        return $this->unit_cost * (1 + ($this->margin / 100));
    }

    // Mutator untuk auto-generate kode
    public function setKodeAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['kode'] = 'L' . str_pad($this->id ?? 0, 4, '0', STR_PAD_LEFT);
        } else {
            $this->attributes['kode'] = $value;
        }
    }
}
