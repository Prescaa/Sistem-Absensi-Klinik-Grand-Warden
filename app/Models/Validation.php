<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model untuk tabel: VALIDATION
class Validation extends Model
{
    use HasFactory;

    protected $table = 'VALIDATION';
    protected $primaryKey = 'val_id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'att_id',
        'status_validasi_otomatis',
        'status_validasi_final',
        'admin_id',
        'catatan_admin',
        'timestamp_validasi',
    ];

    /**
     * Mengubah kolom tanggal/waktu menjadi objek Carbon (date).
     */
    protected $casts = [
        'timestamp_validasi' => 'datetime',
    ];

    /**
     * Relasi: Satu Validation dimiliki oleh satu Attendance.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class, 'att_id', 'att_id');
    }
}