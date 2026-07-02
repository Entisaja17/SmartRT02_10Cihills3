# 🏘️ SmartRT - Sistem Informasi Rukun Tetangga

Aplikasi web untuk manajemen Rukun Tetangga (RT) modern dengan fitur:
- 👥 Manajemen Data Warga
- 💰 Kelola Iuran & Keuangan
- 📋 Buku Kas RT
- 📢 Pengumuman & Berita
- 🆘 Sistem Keluhan Warga
- 📄 Layanan Surat Digital

---

## 🚀 Deployment

### Opsi 1: Development (Localhost)
```bash
# 1. Buka XAMPP Control Panel
# 2. Mulai Apache & MySQL
# 3. Akses: http://localhost/SmartRT02
# 4. Login: admin / 123
```

### Opsi 2: Production (Online)
📖 **Lihat file**: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

**Quick Summary:**
1. Push frontend ke GitHub repo `https://github.com/entisaja17/SmartRT`
2. Deploy frontend ke GitHub Pages
3. Untuk GitHub Pages-only deployment, biarkan `config.js` kosong dan gunakan dummy/local storage mode
4. Jika ingin backend live nanti, update `config.js` dengan domain backend

> Untuk GitHub Pages-only, aplikasi berjalan sepenuhnya di browser menggunakan data lokal/dummy. Beberapa fitur backend (database server-side) tidak akan tersimpan di server.

> Untuk backend live, gunakan deployment dengan `Dockerfile` dan server PHP.

4. Akses aplikasi dari GitHub Pages
5. Selesai! ✅

> Catatan: Jika membuka aplikasi dari HP dengan koneksi data seluler, frontend harus mengakses backend publik. Alamat lokal seperti `http://192.168.x.x/SmartRT02` hanya bekerja pada jaringan WiFi/LAN yang sama.

### GitHub Pages (Frontend)
1. GitHub Pages akan deploy otomatis melalui workflow GitHub Actions di `.github/workflows/pages.yml`
2. Setelah push ke `main`, buka GitHub → Settings → Pages → pilih `GitHub Actions` sebagai source jika diminta
3. Frontend akan tersedia di `https://entisaja17.github.io/SmartRT/`

### Backend Deployment
- Repo kini berisi `Dockerfile` untuk Docker + PHP deployment.
- `railway.toml` telah dihapus agar repo bebas dari Railway-specific config.
- Pastikan hosting PHP/ Docker:
  - menggunakan Docker build bila perlu
  - menjalankan file PHP seperti `api.php`
  - mengatur env vars `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_PORT`

---

## 📁 File Structure

```
SmartRT02/
├── index.html           # UI aplikasi (frontend)
├── kode.js              # Logika aplikasi (frontend)
├── config.js            # Konfigurasi API URL ⭐
├── api.php              # Backend API handler
├── koneksi.php          # Database connection
├── mysql_setup.sql      # SQL schema
├── DEPLOYMENT_GUIDE.md  # Panduan deployment
└── README.md            # File ini
```

---

## 🔧 Konfigurasi

### For Development (Localhost)
Tidak perlu setup apapun, `config.js` sudah default ke localhost.

### For Production (Online)
Untuk GitHub Pages-only deployment, kosongkan `config.js`:
```javascript
const API_BASE_URL = '';
```

Jika nanti Anda ingin menggunakan backend API publik, ganti dengan domain backend Anda:
```javascript
const API_BASE_URL = 'https://your-backend.example.com';
```

---

## 📦 Requirements

### Development
- XAMPP (Apache + PHP 7.4+ + MySQL)
- Modern browser (Chrome, Firefox, Edge)

### Production
- Shared hosting dengan PHP 7.4+ & MySQL 5.7+
- Domain/subdomain
- GitHub account (untuk frontend)

---

## 🔐 Security Notes

⚠️ **JANGAN:**
- ❌ Commit database credentials ke GitHub
- ❌ Share `koneksi.php` credentials
- ❌ Gunakan password yang mudah ditebak

✅ **HARUS:**
- ✅ Update `DB_PASS` dengan password kuat
- ✅ Gunakan HTTPS di production
- ✅ Regular backup database

---

## 📞 Support

Jika ada pertanyaan:
1. Check [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)
2. Check browser console (F12)
3. Check server error logs

---

**Made with ❤️ for RT Communities**

Versi: 1.0.0 | Last Updated: June 2, 2026
