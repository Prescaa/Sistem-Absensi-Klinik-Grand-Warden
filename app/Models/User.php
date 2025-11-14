<?php

namespace App\Models; 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// Model untuk tabel: USER
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Memberitahu Laravel untuk menggunakan tabel 'USER'.
     */
    protected $table = 'USER';

    /**
     * Memberitahu Laravel bahwa primary key-nya adalah 'user_id'.
     */
    protected $primaryKey = 'user_id';

    /**
     * Memberitahu Laravel bahwa tabel ini TIDAK punya
     * kolom 'created_at' dan 'updated_at'.
     */
    public $timestamps = false;

    /**
     * Kolom yang boleh diisi secara massal (untuk Seeder).
     */
    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'role',
    ];

    /**
     * Kolom yang disembunyikan saat diubah jadi JSON.
     */
    protected $hidden = [
        'password_hash',
    ];

    /**
     * Memberitahu sistem Auth Laravel
     * kolom mana yang berisi password.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Relasi: Satu User memiliki satu Employee.
     */
    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id', 'user_id');
    }
}