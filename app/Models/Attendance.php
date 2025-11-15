<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model untuk tabel: ATTENDANCE
class Attendance extends Model
{
    use HasFactory;

    protected $table = 'ATTENDANCE';
    protected $primaryKey = 'att_id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'emp_id',
        'area_id',
        'waktu_unggah',
        'latitude',
        'longitude',
        'nama_file_foto',
        'timestamp_ekstraksi',
    ];

    /**
     * Mengubah kolom tanggal/waktu menjadi objek Carbon (date).
     */
    protected $casts = [
        'waktu_unggah' => 'datetime',
        'timestamp_ekstraksi' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Relasi: Satu Attendance dimiliki oleh satu Employee.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'emp_id', 'emp_id');
    }

    /**
     * Relasi: Satu Attendance dimiliki oleh satu WorkArea.
     */
    public function workArea()
    {
        return $this->belongsTo(WorkArea::class, 'area_id', 'area_id');
    }

    /**
     * Relasi: Satu Attendance memiliki satu Validation.
     */
    public function validation()
    {
        return $this->hasOne(Validation::class, 'att_id', 'att_id');
    }
}
