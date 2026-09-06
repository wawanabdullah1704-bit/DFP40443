# SCRS PMU - Design System Documentation

## 🎨 Theme: Original Neo-Brutalism

### Overview
Sistem Tempahan Kereta Sewa Politeknik Mukah (**SCRS PMU**) menggunakan tema **Original Neo-Brutalism** dengan kontras tinggi, sempadan tebal, bayang pepejal keras 3D, fon Space Grotesk, dan palet warna bertenaga.

---

### 🌈 Color Palette

| Token | Nilai Hex | Penggunaan |
|---|---|---|
| `--black` | `#000000` | Sempadan tebal, bayang keras, teks tajuk |
| `--white` | `#ffffff` | Latar kad, modal, permukaan |
| `--yellow` | `#ffde59` | Warna aksen utama, butang utama, header sidebar, footer, badge pending |
| `--green` | `#00e676` | Status lulus (approved), kenderaan available, butang simpan |
| `--blue` | `#00e5ff` | Tindakan sekunder, butang carian/lihat |
| `--pink` | `#ff66c4` | Status ditolak (rejected), butang padam/batal, logout |
| `--orange` | `#ff914d` | Aksen tambahan |
| `--bg-color` | `#f4f4f0` | Kanvas latar belakang lembut dengan radial dot grid |

---

### 📐 Reka Bentuk & Geometri (Rounded Neo-Brutalism)

- **Sempadan (Border Thick)**: `3px solid #000000`
- **Sempadan Halus (Border Thin)**: `2px solid #000000`
- **Bayang Pepejal (Solid Drop Shadow)**: `4px 4px 0px #000000` (Besar: `6px 6px 0px`, Kecil: `2px 2px 0px`)
- **Sudut Bulat (Rounded Corners)**:
  - `--radius-xs: 4px;` (Tag mikro, checkbox)
  - `--radius-sm: 8px;` (Butang kecil, dropdown item, butang tutup)
  - `--radius-md: 12px;` (Butang standard, input borang, tab nav, banner amaran)
  - `--radius-lg: 16px;` (Kad kandungan, banner hero, kad kereta, kad statistik, kad jadual)
  - `--radius-xl: 22px;` (Modal popup, sidebar drawer, kad log masuk/daftar)
  - `--radius-full: 9999px;` (Lencana status pill, butang bujur, avatar bulat)
- **Kesan Butang Ditekan (Active Tactile)**: `transform: translate(2px, 2px); box-shadow: 0px 0px 0px #000000;`
- **Tipografi**: `Space Grotesk`, `Plus Jakarta Sans`, sans-serif

