// Import file bootstrap.js bawaan Laravel (penting untuk backend)
import './bootstrap';

// 1. Import Bootstrap JS
import * as bootstrap from 'bootstrap';

// 2. Import jQuery dan jadikan global (agar bisa dipakai)
import $ from 'jquery';
window.$ = window.jQuery = $;

// 3. Import Leaflet JS
import 'leaflet/dist/leaflet.js';

/**
 * Fungsi untuk update jam dan tanggal real-time
 */
function updateRealTimeClock() {
    const jamElement = document.getElementById('realtime-jam');
    const tanggalElement = document.getElementById('realtime-tanggal');

    // Hanya jalankan jika elemennya ada di halaman (halaman dashboard)
    if (jamElement && tanggalElement) {
        const now = new Date();

        // Array nama hari dan bulan dalam Bahasa Indonesia
        const hari = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
        const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        // Format Jam: HH:MM
        const jam = now.getHours().toString().padStart(2, '0');
        const menit = now.getMinutes().toString().padStart(2, '0');
        
        // Format Tanggal: Hari, DD Bulan YYYY
        const namaHari = hari[now.getDay()];
        const tanggal = now.getDate();
        const namaBulan = bulan[now.getMonth()];
        const tahun = now.getFullYear();

        // Tampilkan ke HTML
        jamElement.textContent = `${jam}:${menit}`;
        tanggalElement.textContent = `${namaHari}, ${tanggal} ${namaBulan} ${tahun}`;
    }
}

// Panggil fungsi saat halaman pertama kali dimuat
document.addEventListener('DOMContentLoaded', () => {
    updateRealTimeClock();
    
    // Update jam setiap detik
    setInterval(updateRealTimeClock, 1000);
});