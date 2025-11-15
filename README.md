# Sistem Absensi Klinik Grand Warden

`README.md` ini dibuat untuk proyek Sistem Absensi Karyawan berbasis foto dan geofencing untuk Klinik Grand Warden.

Sistem ini dibangun menggunakan **Laravel 10** dan dirancang untuk memvalidasi absensi karyawan tidak hanya berdasarkan waktu, tetapi juga lokasi geografis yang diverifikasi melalui data EXIF foto.

## 🚀 Fitur Utama

Sistem ini memiliki dua peran utama: **Admin** dan **Karyawan**.

### Fitur Karyawan

* **Dashboard Karyawan:** Menampilkan *widget* absensi harian dan navigasi utama.
* **Absensi Foto:** Karyawan melakukan "Absen Masuk" dan "Absen Pulang" dengan mengunggah foto.
* **Validasi Geofencing Otomatis:** Sistem secara otomatis membandingkan lokasi karyawan dengan area kerja yang terdaftar.
* **Validasi EXIF:** Sistem membaca metadata GPS (Latitude & Longitude) langsung dari file foto yang diunggah untuk memastikan keaslian lokasi.
* **Riwayat Absensi:** Karyawan dapat melihat riwayat absensi mereka.

### Fitur Admin

* **Dashboard Admin:** Panel kontrol utama untuk admin.
* **Pengaturan Geofencing:** Admin dapat mengatur titik koordinat (Latitude, Longitude) dan radius (dalam meter) untuk area absensi klinik.
* **Validasi Absensi Manual:** Admin meninjau foto yang diunggah karyawan dan dapat **Approve** (Menyetujui) atau **Reject** (Menolak) absensi tersebut.
* **Manajemen Karyawan:** Tempat untuk mengelola data karyawan.

## 💻 Tumpukan Teknologi

* **Backend:** PHP / Laravel 10
* **Frontend:** Blade Templates, Bootstrap 5, Vanilla JS
* **Database:** MySQL
* **Dependensi Kunci:** `php-exif` (diperlukan di server untuk membaca metadata GPS foto)

## ⚙️ Instalasi & Penyiapan

Berikut adalah langkah-langkah penting untuk menyiapkan proyek ini:

### 1. Konfigurasi File

* **.env:** Salin `.env.example` menjadi `.env`. Atur koneksi database Anda (DB\_DATABASE, DB\_USERNAME, DB\_PASSWORD).
* **php.ini:**
    * Pastikan ekstensi `exif` aktif. Hapus tanda titik koma (`;`) dari depan baris `extension=exif`.
    * Naikkan batas unggah file agar foto berkualitas tinggi dapat diterima:
        ```ini
        upload_max_filesize = 10M
        post_max_size = 12M
        ```
    * **PENTING:** Restart server Anda (XAMPP, Apache, dll.) setelah mengubah `php.ini`.

### 2. Perintah Terminal

Jalankan perintah ini di terminal Anda secara berurutan:

1.  **Instal Dependensi:**
    ```bash
    composer install
    npm install && npm run dev
    ```
2.  **Generate Kunci Aplikasi:**
    ```bash
    php artisan key:generate
    ```
3.  **Jalankan Migrasi (Membuat Tabel):**
    ```bash
    php artisan migrate
    ```
4.  **Jalankan Seeder (Mengisi Akun Awal):**
    ```bash
    php artisan db:seed
    ```
5.  **Buat Storage Link (Wajib untuk Foto):**
    ```bash
    php artisan storage:link
    ```
6.  **Jalankan Server:**
    ```bash
    php artisan serve
    ```

## 🗂️ Struktur Database & Alur

1.  **USER**: Menyimpan data login (`username`, `password`, `role`). Berelasi `HasOne` ke `EMPLOYEE` melalui `user_id`.
2.  **EMPLOYEE**: Menyimpan data karyawan (`nama`, `nip`). Memiliki `user_id` yang terhubung ke tabel `USER`.
3.  **WORK\_AREA**: Menyimpan `koordinat_pusat` dan `radius_geofence` klinik.
4.  **ATTENDANCE**: **Baris baru dibuat** setiap kali ada unggahan. Menyimpan `emp_id`, `waktu_unggah`, `latitude`, `longitude` (dari EXIF), dan `nama_file_foto`.
5.  **VALIDATION**: **Baris baru dibuat** setiap kali admin melakukan validasi. Menyimpan `att_id` (dari absensi), `admin_id`, dan `status_validasi` ('Approved'/'Rejected').

## 🔑 Akun Default

Akun ini dibuat oleh `php artisan db:seed`.

* **Admin:**
    * Username: `admin`
    * Password: `password`
    * Nama: Sahroni

* **Karyawan:**
    * Username: `karyawan`
    * Password: `password`
    * Nama: Mahardika
