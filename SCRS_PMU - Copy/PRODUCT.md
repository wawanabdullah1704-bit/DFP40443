# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users
- **Pelajar (Students):** Pelajar Politeknik Mukah (PMU) yang memerlukan kenderaan sewa untuk urusan harian, outing, atau pulang ke kampung secara selamat, telus, dan patuh peraturan institusi.
- **Penyedia Kereta (Providers):** Pemilik/pengusaha kereta sewa tempatan yang ingin menyewakan kenderaan kepada pelajar dengan kawalan inventori, penjejakan tempahan, dan pengesahan status kenderaan.
- **Pegawai JHEPP (Jabatan Hal Ehwal Pelajar & Pengesahan):** Pegawai institusi yang bertanggungjawab menyemak, mengesahkan, dan meluluskan akaun pelajar serta memastikan kepatuhan kepada polisi PMU.
- **Pentadbir Sistem (Admin):** Menguruskan keseluruhan platform, pengguna, data laporan, dan integriti sistem.

## Product Purpose
SCRS PMU (Student Car Rental System PMU) adalah platform berpusat yang menghubungkan pelajar PMU dengan pembekal kereta sewa yang disahkan, dengan pengawasan dan pengesahan rasmi daripada pihak JHEPP bagi memastikan keselamatan transaksi dan pematuhan peraturan kampus.

## Positioning
Satu-satunya sistem sewa kenderaan khas kampus PMU yang menggabungkan pengesahan institusi (JHEPP verification) dengan pasaran terbuka pembekal tempatan, mengelakkan penipuan sewa kenderaan dan memastikan keselamatan pelajar.

## Operating Context
- Digunakan melalui pelayar web komputer desktop dan peranti mudah alih (responsive mobile web).
- Pengesahan pelajar melibatkan semakan kad matrik / e-mel institusi dan kelulusan pegawai JHEPP.
- Pengurusan kenderaan dan status tempahan secara langsung (real-time booking status).

## Capabilities and Constraints
- **Aliran Log Masuk / Peranan Berbilang:** Autentikasi berperingkat untuk 4 peranan (Pelajar, Pembekal, JHEPP, Admin) dari satu pintu masuk log masuk berpusat.
- **Pengurusan Tempahan & Kereta:** Penambahan kenderaan oleh pembekal, pemilihan & tempahan oleh pelajar, pengurusan status (pending, approved, rejected, completed).
- **Pengesahan JHEPP:** Semakan dokumen pelajar sebelum dibenarkan menyewa.
- **Kekangan Teknikal:** PHP Backend Native (XAMPP environment), MySQL Database, reka bentuk UI berteraskan tema Modern Clean SaaS Dashboard.

## Brand Commitments
- **Nama:** SCRS PMU (Student Car Rental System - Politeknik Mukah).
- **Bahasa & Nada:** Bahasa Melayu formal, mesra pelajar, ringkas, teratur, dan jelas.
- **Identiti Visual:** Estetika Modern Clean Minimalist (latar slate lembut `#f8fafc`, kad putih bersih dengan border halus `#e2e8f0`, bayang lembut, warna aksen biru `#2563eb`, dan tipografi moden `Plus Jakarta Sans`).

## Evidence on Hand
- Kod sumber sedia ada dalam PHP di direktori `student/`, `provider/`, `admin/`, `jhepp/`, `auth/`, dan `config/`.
- Struktur pangkalan data MySQL untuk pengurusan pengguna, kenderaan, dan tempahan.

## Product Principles
1. **Ketelusan & Keselamatan Pelajar:** Setiap tempahan dan pengesahan identiti mestilah jelas tanpa bayaran tersembunyi atau risiko penipuan.
2. **Keteraturan Aliran Kerja Setiap Peranan:** Antaramuka intuitif yang memisahkan tanggungjawab Pelajar, Pembekal, JHEPP, dan Admin secara kemas.
3. **Keringkasan & Kebersihan Visual:** Mengelakkan elemen reka bentuk yang semak, mengutamakan susun atur yang lapang, mudah dibaca, dan mesra peranti mudah alih.

## Accessibility & Inclusion
- Kontras warna yang tinggi (WCAG AA/AAA) dengan teks gelap di atas latar belakang cerah.
- Sasaran sentuhan minimum 44px untuk peranti mudah alih pelajar dan penyedia kereta.
