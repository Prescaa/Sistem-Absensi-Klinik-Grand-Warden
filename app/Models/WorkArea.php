<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model untuk tabel: WORK_AREA
class WorkArea extends Model
{
    use HasFactory;

    protected $table = 'WORK_AREA';
    protected $primaryKey = 'area_id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'nama_area',
        'koordinat_pusat',
        'radius_geofence',
        'jam_kerja',
    ];

    /**
     * Memberitahu Laravel untuk otomatis mengubah
     * kolom 'jam_kerja' (JSON) menjadi array PHP.
     */
    protected $casts = [
        'jam_kerja' => 'array',
        // 'koordinat_pusat' butuh library tambahan,
        // jadi kita biarkan sebagai string dulu
    ];

    /**
     * Relasi: Satu WorkArea memiliki banyak Attendance.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'area_id', 'area_id');
    }
}