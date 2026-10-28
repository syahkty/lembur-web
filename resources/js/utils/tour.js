import { driver } from 'driver.js';
import 'driver.js/dist/driver.css';
import { router } from '@inertiajs/vue3';

/**
 * Konfigurasi langkah-langkah tur interaktif multi-halaman
 */
export function getTourSteps(page, role) {
    if (role === 'admin') {
        switch (page) {
            case 'dashboard':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman Dashboard (SPL)',
                            description: 'Halaman utama untuk memantau instruksi lembur harian. Kita akan mempelajari fitur di halaman ini terlebih dahulu, lalu berpindah ke LRLK, Kelola Akun, Kelola Stasiun, dan Cetak.'
                        }
                    },
                    {
                        element: '#tour-filter-tanggal',
                        popover: {
                            title: 'Filter Tanggal',
                            description: 'Memilih tanggal untuk menampilkan data Surat Perintah Lembur (SPL) pada hari yang ditentukan.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-cetak',
                        popover: {
                            title: 'Tombol Cetak SPL',
                            description: 'Mencetak dokumen fisik formulir SPL (PROS-HCM-02) sesuai tanggal terpilih lengkap dengan pengesahan digital.',
                            side: 'left'
                        }
                    },
                    {
                        element: '#tour-rekap',
                        popover: {
                            title: 'Tabel Rekapitulasi SPL',
                            description: 'Menampilkan seluruh instruksi lembur karyawan pada tanggal terpilih, rincian jam kerja, status persetujuan, tanda tangan digital, foto bukti, dan opsi revisi/hapus.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-btn-lrlk',
                        popover: {
                            title: 'Lanjut ke Halaman LRLK ➔',
                            description: 'Penjelasan Dashboard selesai. Klik tombol di bawah ini untuk berpindah ke halaman Laporan Realisasi Lembur Karyawan (LRLK).',
                            side: 'bottom',
                            doneBtnText: 'Buka Halaman LRLK ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('lembur.lrlk', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'lrlk':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman LRLK (Realisasi)',
                            description: 'Halaman ini menyajikan rekapitulasi realisasi lembur yang otomatis dihimpun dari data SPL yang telah disetujui (ACC) oleh karyawan.'
                        }
                    },
                    {
                        element: '#tour-lrlk-filter',
                        popover: {
                            title: 'Filter Tanggal LRLK',
                            description: 'Pilih tanggal untuk melihat rekapitulasi realisasi kerja lembur pada hari tersebut.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-lrlk-stats',
                        popover: {
                            title: 'Ringkasan Statistik Realisasi',
                            description: 'Menampilkan akumulasi jam lembur seluruh karyawan, jumlah orang yang lembur, dan total estimasi upah lembur harian.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-lrlk-cetak',
                        popover: {
                            title: 'Tombol Cetak LRLK',
                            description: 'Mencetak dokumen formulir fisik LRLK resmi (PROS-HCM-02) sesuai tanggal yang dipilih.',
                            side: 'left'
                        }
                    },
                    {
                        element: '#tour-lrlk-tabel',
                        popover: {
                            title: 'Tabel Rincian Realisasi',
                            description: 'Rincian data per karyawan: stasiun kerja, jam mulai & selesai aktual, jam istirahat, dan total jam lembur yang dibayar.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-nav-akun',
                        popover: {
                            title: 'Lanjut ke Kelola Akun ➔',
                            description: 'Penjelasan LRLK selesai. Klik tombol di bawah ini untuk berpindah ke halaman Kelola Akun & Pembagian Shift.',
                            side: 'bottom',
                            doneBtnText: 'Buka Kelola Akun ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('admin.akun', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'akun':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman Kelola Akun',
                            description: 'Halaman ini digunakan untuk mengelola data akun pengguna sistem SILEKA, penempatan stasiun kerja, dan pembagian shift kerja regu.'
                        }
                    },
                    {
                        element: '#tour-akun-tambah',
                        popover: {
                            title: 'Tombol Tambah Akun',
                            description: 'Membuka formulir pendaftaran akun baru untuk Karyawan, Mandor, Admin, atau akun Penandatangan dokumen.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-akun-tabs',
                        popover: {
                            title: 'Tab Shift & Penandatangan',
                            description: 'Menyaring daftar akun berdasarkan Shift 1 (Mandor A), Shift 2 (Mandor B), atau akun Penandatangan.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-akun-tabel',
                        popover: {
                            title: 'Tabel Daftar Akun',
                            description: 'Menampilkan nama, NIK/No Employe, stasiun, role, serta tombol aksi untuk edit data, atur tarif upah, atau hapus akun.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-nav-stasiun',
                        popover: {
                            title: 'Lanjut ke Kelola Stasiun ➔',
                            description: 'Penjelasan Kelola Akun selesai. Klik tombol di bawah ini untuk berpindah ke halaman Kelola Stasiun pabrik.',
                            side: 'bottom',
                            doneBtnText: 'Buka Kelola Stasiun ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('admin.stasiun', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'stasiun':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman Kelola Stasiun',
                            description: 'Halaman untuk mengatur master data stasiun kerja operasional pabrik kelapa sawit (seperti Boiler, Threshing, Clarification, Engine Room, dll).'
                        }
                    },
                    {
                        element: '#tour-stasiun-tambah',
                        popover: {
                            title: 'Tombol Tambah Stasiun',
                            description: 'Menambahkan stasiun kerja atau unit operasional baru ke dalam sistem pabrik.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-stasiun-tabel',
                        popover: {
                            title: 'Tabel Master Stasiun',
                            description: 'Daftar kode dan nama stasiun kerja aktif yang terhubung dengan penugasan karyawan pada form SPL dan LRLK.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-nav-cetak',
                        popover: {
                            title: 'Lanjut ke Cetak Dokumen ➔',
                            description: 'Penjelasan Kelola Stasiun selesai. Klik tombol di bawah ini untuk melihat halaman Pracetak Dokumen resmi.',
                            side: 'bottom',
                            doneBtnText: 'Buka Halaman Cetak ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('lembur.print', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'print':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman Cetak Dokumen',
                            description: 'Halaman pracetak format A4 standar fisik SPL (PROS-HCM-02) yang siap dicetak ke printer fisik atau disimpan sebagai PDF.'
                        }
                    },
                    {
                        element: '#tour-print-toolbar',
                        popover: {
                            title: 'Bilah Alat Cetak (Floating Toolbar)',
                            description: 'Bilah kontrol untuk mengganti tanggal dokumen, beralih ke Cetak LRLK, atau mencetak langsung ke printer.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-print-lembar',
                        popover: {
                            title: 'Lembar Fisik Presisi A4',
                            description: 'Tampilan formulir presisi A4 Portrait dengan 36 baris karyawan, tanda tangan digital, serta kolom pengesahan penandatangan.',
                            side: 'left'
                        }
                    },
                    {
                        popover: {
                            title: 'Tur Panduan Selesai 🎉',
                            description: 'Selamat! Anda telah mempelajari seluruh alur kerja sistem SILEKA dari Dashboard, LRLK, Kelola Akun, Kelola Stasiun, hingga Cetak Dokumen.',
                            doneBtnText: 'Selesai & Ke Dashboard',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dashboard'));
                            }
                        }
                    }
                ];
        }
    } else if (role === 'mandor') {
        switch (page) {
            case 'dashboard':
                return [
                    {
                        popover: {
                            title: 'Panduan Mandor: Dashboard',
                            description: 'Pantau status lembur regu Anda dan kelola instruksi kerja lembur harian.'
                        }
                    },
                    {
                        element: '#tour-filter-tanggal',
                        popover: {
                            title: 'Filter Tanggal',
                            description: 'Pilih tanggal untuk melihat jadwal atau riwayat lembur anggota regu Anda.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-rekap',
                        popover: {
                            title: 'Tabel Lembur Regu',
                            description: 'Memeriksa persetujuan (ACC), tanda tangan digital karyawan, foto dokumentasi, atau merevisi instruksi yang ditolak.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-buat-spl',
                        popover: {
                            title: 'Lanjut ke Buat SPL ➔',
                            description: 'Penjelasan Dashboard selesai. Klik tombol di bawah ini untuk melihat formulir pembuatan SPL.',
                            side: 'bottom',
                            doneBtnText: 'Buka Buat SPL ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('lembur.create', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'create':
                return [
                    {
                        popover: {
                            title: 'Panduan: Formulir Buat SPL',
                            description: 'Formulir untuk mengatur penugasan lembur harian atau mencatat status kehadiran anggota regu Anda.'
                        }
                    },
                    {
                        element: '#tour-create-tgl',
                        popover: {
                            title: 'Tanggal Lembur',
                            description: 'Pilih tanggal pelaksanaan kerja lembur untuk regu Anda.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-create-preset',
                        popover: {
                            title: 'Form Isi Cepat (Preset Kolektif)',
                            description: 'Fitur untuk mengisi jam lembur dan jenis pekerjaan secara serentak ke semua anggota regu yang lembur, tanpa perlu menginput satu per satu.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-create-shortcuts',
                        popover: {
                            title: 'Pilihan Shortcut Cepat',
                            description: 'Pilihan template jam lembur umum (seperti 16:00-19:00 atau lembur malam 2 sesi). Klik salah satu untuk mengisi form preset secara instan.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-create-sesi2',
                        popover: {
                            title: 'Aktivasi Sesi 2 (Lembur Lanjutan)',
                            description: 'Centang opsi ini jika pekerjaan lembur berlanjut ke sesi malam atau dini hari. Sistem akan menghitung total akumulasi jam secara otomatis.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-create-apply',
                        popover: {
                            title: 'Tombol Terapkan ke Semua Lembur',
                            description: 'Menyalin jam dan pekerjaan dari form isi cepat ini ke seluruh anggota regu yang berstatus lembur dengan satu kali klik.',
                            side: 'left'
                        }
                    },
                    {
                        element: '#tour-create-tabel',
                        popover: {
                            title: 'Tabel Anggota Regu & Penyesuaian',
                            description: 'Periksa atau ubah jam per karyawan jika ada yang berbeda. Untuk anggota yang tidak hadir lembur, pilih status Cuti atau HK (Hari Kerja biasa).',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-create-simpan',
                        popover: {
                            title: 'Simpan & Terbitkan SPL',
                            description: 'Menyimpan dan menerbitkan formulir SPL resmi ke sistem agar anggota regu dapat melakukan verifikasi dan ACC tanda tangan.',
                            side: 'top'
                        }
                    },
                    {
                        popover: {
                            title: 'Lanjut ke Halaman LRLK ➔',
                            description: 'Penjelasan formulir selesai. Klik tombol di bawah ini untuk melihat pemantauan realisasi lembur di halaman LRLK.',
                            doneBtnText: 'Buka Halaman LRLK ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('lembur.lrlk', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'lrlk':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman LRLK (Realisasi)',
                            description: 'Di sini Anda dapat melihat rekapitulasi jam kerja realisasi lembur regu Anda.'
                        }
                    },
                    {
                        element: '#tour-lrlk-stats',
                        popover: {
                            title: 'Ringkasan Jam Regu',
                            description: 'Total jam lembur aktual yang terlaksana oleh regu Anda pada tanggal terpilih.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-lrlk-tabel',
                        popover: {
                            title: 'Tabel Realisasi Lembur',
                            description: 'Daftar anggota regu yang melaksanakan lembur beserta jam kerja riil.',
                            side: 'top'
                        }
                    },
                    {
                        popover: {
                            title: 'Tur Panduan Selesai 🎉',
                            description: 'Anda telah menyelesaikan pengenalan seluruh alur kerja Mandor di sistem SILEKA.',
                            doneBtnText: 'Selesai & Ke Dashboard',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dashboard'));
                            }
                        }
                    }
                ];
        }
    } else if (['asisten', 'askep', 'ktu', 'administratur', 'hrga'].includes(role)) {
        switch (page) {
            case 'dokumen':
                return [
                    {
                        popover: {
                            title: 'Panduan: Dokumen Lembur',
                            description: 'Halaman kerja bagi penandatangan untuk memeriksa instruksi SPL, realisasi LRLK, dan membubuhkan tanda tangan digital.'
                        }
                    },
                    {
                        element: '#tour-dokumen-tanggal',
                        popover: {
                            title: 'Filter Tanggal',
                            description: 'Pilih tanggal lembur yang ingin diperiksa datanya.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-dokumen-ttd-btn',
                        popover: {
                            title: 'Tombol Tanda Tangan',
                            description: 'Membuka canvas digital untuk membuat atau memperbarui tanda tangan digital Anda.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-dokumen-status-ttd',
                        popover: {
                            title: 'Status Tanda Tangan Aktif',
                            description: 'Menampilkan pratinjau tanda tangan digital Anda yang otomatis terpasang pada lembar cetak fisik SPL & LRLK.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-dokumen-tabs',
                        popover: {
                            title: 'Tab Dokumen SPL & LRLK',
                            description: 'Beralih antara melihat daftar rincian penugasan SPL atau rekapitulasi realisasi jam kerja LRLK.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-dokumen-tabel',
                        popover: {
                            title: 'Tabel Rincian Lembur',
                            description: 'Memeriksa nama karyawan, stasiun, jenis pekerjaan, durasi jam lembur, dan status persetujuan karyawan.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-dokumen-preview-btn',
                        popover: {
                            title: 'Lanjut ke Preview SPL ➔',
                            description: 'Klik tombol di bawah ini untuk melihat pratinjau lembar cetak fisik SPL yang telah dilengkapi tanda tangan digital Anda.',
                            side: 'left',
                            doneBtnText: 'Buka Preview SPL ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dokumen.preview.spl', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'preview_spl':
                return [
                    {
                        popover: {
                            title: 'Panduan: Preview SPL Format Cetak',
                            description: 'Pratinjau lembar fisik formulir SPL (PROS-HCM-02) berukuran A4 yang siap divalidasi dan diarsipkan.'
                        }
                    },
                    {
                        element: '#tour-print-toolbar',
                        popover: {
                            title: 'Bilah Navigasi Dokumen',
                            description: 'Bilah alat untuk memilih tanggal dokumen, kembali ke menu utama dokumen, atau beralih ke formulir LRLK.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-print-lembar',
                        popover: {
                            title: 'Lembar SPL Format Fisik A4',
                            description: 'Menampilkan format presisi fisik lembar lembur pabrik lengkap dengan 36 baris karyawan dan kolom tanda tangan digital pejabat berwenang.',
                            side: 'left'
                        }
                    },
                    {
                        popover: {
                            title: 'Lanjut ke Preview LRLK ➔',
                            description: 'Penjelasan Preview SPL selesai. Klik tombol di bawah ini untuk melihat pratinjau lembar Laporan Realisasi Kerja Lembur (LRLK).',
                            doneBtnText: 'Buka Preview LRLK ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dokumen.preview.lrlk', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'preview_lrlk':
                return [
                    {
                        popover: {
                            title: 'Panduan: Preview LRLK Format Cetak',
                            description: 'Pratinjau lembar fisik formulir LRLK (PROS-HCM-02) yang memuat rekapitulasi jam realisasi lembur per stasiun kerja.'
                        }
                    },
                    {
                        element: '#tour-print-lrlk-toolbar',
                        popover: {
                            title: 'Bilah Navigasi LRLK',
                            description: 'Bilah kontrol untuk mengganti tanggal atau beralih kembali ke formulir SPL.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-print-lrlk-lembar',
                        popover: {
                            title: 'Lembar LRLK Format Fisik A4',
                            description: 'Menampilkan rekapitulasi jam realisasi lembur seluruh stasiun serta kolom pengesahan Kabag HRGA, KTU, dan Administratur.',
                            side: 'left'
                        }
                    },
                    {
                        popover: {
                            title: 'Tur Panduan Selesai 🎉',
                            description: 'Selamat! Anda telah mempelajari seluruh alur peninjauan dan pengesahan dokumen SPL & LRLK di sistem SILEKA.',
                            doneBtnText: 'Selesai & Ke Dokumen',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dokumen.index'));
                            }
                        }
                    }
                ];
        }
    } else {
        // Karyawan
        switch (page) {
            case 'dashboard':
                return [
                    {
                        popover: {
                            title: 'Panduan Karyawan: Dashboard',
                            description: 'Di sini Anda dapat memeriksa dan menyetujui instruksi lembur dari mandor.'
                        }
                    },
                    {
                        element: '#tour-filter-tanggal',
                        popover: {
                            title: 'Filter Tanggal',
                            description: 'Pilih tanggal untuk melihat instruksi lembur pada hari tersebut.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-tabel-spl',
                        popover: {
                            title: 'Tabel Instruksi & ACC',
                            description: 'Periksa jam dan jenis pekerjaan. Klik tombol ACC & TTD untuk menyetujui dengan tanda tangan digital, atau Tolak jika data tidak sesuai.',
                            side: 'top'
                        }
                    },
                    {
                        element: '#tour-nav-riwayat',
                        popover: {
                            title: 'Lanjut ke Riwayat Lembur ➔',
                            description: 'Penjelasan Dashboard selesai. Klik tombol di bawah ini untuk melihat riwayat lembur bulanan dan estimasi upah Anda.',
                            side: 'bottom',
                            doneBtnText: 'Buka Riwayat Lembur ➔',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('lembur.riwayat', { tour: 'true' }));
                            }
                        }
                    }
                ];

            case 'riwayat':
                return [
                    {
                        popover: {
                            title: 'Panduan: Halaman Riwayat Lembur',
                            description: 'Melihat seluruh riwayat jam kerja lembur yang telah Anda kerjakan beserta akumulasi upahnya.'
                        }
                    },
                    {
                        element: '#tour-riwayat-bulan',
                        popover: {
                            title: 'Filter Bulan',
                            description: 'Pilih periode bulan untuk melihat total lembur Anda pada bulan tersebut.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-riwayat-stats',
                        popover: {
                            title: 'Ringkasan Jam & Upah',
                            description: 'Menampilkan total jam lembur aktual, jam dibayar (PP 35/2021), serta akumulasi upah lembur yang akan Anda terima.',
                            side: 'bottom'
                        }
                    },
                    {
                        element: '#tour-riwayat-tabel',
                        popover: {
                            title: 'Tabel Riwayat Lembur',
                            description: 'Rincian riwayat per tanggal, jam masuk-pulang, mandor yang menugaskan, dan status persetujuan.',
                            side: 'top'
                        }
                    },
                    {
                        popover: {
                            title: 'Tur Panduan Selesai 🎉',
                            description: 'Selamat! Anda telah memahami cara memeriksa instruksi lembur, menyetujui (ACC), dan melihat riwayat lembur Anda.',
                            doneBtnText: 'Selesai & Ke Dashboard',
                            onDoneClick: (el, step, opts) => {
                                opts.driver.destroy();
                                router.visit(route('dashboard'));
                            }
                        }
                    }
                ];
        }
    }

    return [];
}

/**
 * Otomatis periksa parameter ?tour=true di URL dan jalankan tour halaman jika ada
 */
export function runTourIfRequested(page, role) {
    if (typeof window === 'undefined') return;

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tour') !== 'true') return;

    // Bersihkan parameter dari URL agar tidak berjalan ulang saat halaman di-refresh manual
    window.history.replaceState({}, document.title, window.location.pathname);

    // Jalankan setelah DOM ter-render
    setTimeout(() => {
        startTour(page, role);
    }, 200);
}

/**
 * Jalankan tur driver.js untuk halaman dan role tertentu
 */
export function startTour(page, role) {
    const rawSteps = getTourSteps(page, role);
    if (!rawSteps || rawSteps.length === 0) return;

    // Filter elemen yang ada di layar
    const activeSteps = rawSteps.filter(step => {
        if (!step.element) return true;
        const el = document.querySelector(step.element);
        return el && (el.offsetWidth > 0 || el.offsetHeight > 0 || el.getClientRects().length > 0);
    });

    if (activeSteps.length === 0) return;

    const driverObj = driver({
        showProgress: true,
        animate: true,
        nextBtnText: 'Lanjut ➔',
        prevBtnText: '⬅ Kembali',
        doneBtnText: 'Selesai',
        steps: activeSteps,
    });

    driverObj.drive();
}
