<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model untuk tabel: REPORT
class Report extends Model
{
    use HasFactory;

    protected $table = 'REPORT';
    protected $primaryKey = 'rpt_id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'emp_id',
        'periode',
        'total_hadir',
        'total_tidak_hadir',
        'file_laporan',
    ];

    /**
     * Mengubah kolom tanggal/waktu menjadi objek Carbon (date).
     */
    protected $casts = [
        'periode' => 'date',
    ];

    /**
     * Relasi: Satu Report dimiliki oleh satu Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }
}