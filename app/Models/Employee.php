<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model untuk tabel: EMPLOYEE
class Employee extends Model
{
    use HasFactory;

    protected $table = 'EMPLOYEE';
    protected $primaryKey = 'emp_id';
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi.
     */
    protected $fillable = [
        'user_id',
        'nama',        // ✅ INI YANG HILANG - TAMBAHKAN INI
        'nip',
        'departemen',
        'posisi',
        'status_aktif',
        'alamat',
        'no_telepon',
        'foto_profil'
    ];

    /**
     * Relasi: Satu Employee dimiliki oleh satu User.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Relasi: Satu Employee memiliki banyak Attendance.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'emp_id', 'emp_id');
    }

    /**
     * Relasi: Satu Employee memiliki banyak Report.
     */
    public function reports()
    {
        return $this->hasMany(Report::class, 'emp_id', 'emp_id');
    }
}