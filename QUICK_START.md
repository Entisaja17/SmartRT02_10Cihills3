# QUICK START - Deploy SmartRT Online

## 📋 Checklist Sebelum Mulai

- [ ] GitHub account (gratis di github.com)
- [ ] Hosting provider (Hostinger/Domainesia/Niagahoster)
- [ ] Domain atau subdomain
- [ ] cPanel access dari hosting
- [ ] Git installed di komputer Anda

---

## ⚡ 5 STEP CEPAT

### STEP 1: GitHub Pages (Frontend)
```bash
cd c:\xampp\htdocs\SmartRT02

git init
 git add .
 git commit -m "SmartRT - Initial commit"
 git remote add origin https://github.com/entisaja17/SmartRT.git
 git branch -M main
 git push -u origin main
```

Buka di GitHub → Settings → Pages → Source: `GitHub Actions` (jika diminta)

✅ Frontend live di: `https://entisaja17.github.io/SmartRT/`

> Workflow deploy otomatis sudah ditambahkan di `.github/workflows/pages.yml`.
>
> Jika membuka aplikasi dari HP dengan koneksi data seluler, pastikan frontend menggunakan URL GitHub Pages dan backend mengarah ke API publik. Alamat lokal seperti `http://192.168.x.x/SmartRT02` hanya bekerja pada jaringan WiFi/LAN yang sama.

---

### STEP 2: Backend
1. Untuk GitHub Pages-only deployment, Anda tidak perlu backend.
2. Jika ingin menambahkan backend nanti, deploy ke hosting PHP atau Docker dengan PHP 8+.
3. Pastikan `api.php` dijalankan oleh server, bukan disajikan sebagai file statis.
4. `Dockerfile` tersedia di repo sebagai template deployment Docker + PHP.

> Catatan: GitHub Pages-only mode menggunakan data lokal/dummy di browser. Fitur server-side tidak akan tersimpan di hosting.

---

### STEP 3: Setup Database
1. Di cPanel → MySQL Databases
2. Buat database: `smart_rt`
3. Buat user: `admin` dengan password kuat
4. Assign user ke database dengan ALL PRIVILEGES

---

### STEP 4: Edit koneksi.php
Di hosting, edit file `api/koneksi.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'smart_rt');      // ← Ganti dengan DB name Anda
define('DB_USER', 'admin');  // ← Ganti dengan user Anda
define('DB_PASS', '123'); // ← Ganti dengan password Anda!
```

---

### STEP 5: Update config.js & Push
Edit file `config.js` di repository GitHub atau lokal:
```javascript
// Untuk GitHub Pages-only deployment, biarkan kosong.
const API_BASE_URL = '';
```

Push ke GitHub:
```bash
git add config.js
git commit -m "Use GitHub Pages-only mode"
git push
```

---

## ✅ Test

1. Buka frontend Anda di GitHub Pages atau buka `index.html` secara langsung di browser
2. Buka browser console (F12)
3. Ketik: `GAS_URL` → harus menunjukkan URL backend Anda
4. Coba login

---

## 🎉 DONE!

Aplikasi SmartRT sekarang ONLINE dan dapat diakses dari HP via internet data jika frontend dan backend sudah terpasang.

**Contoh URLs:**
- Frontend: `https://<your-github-username>.github.io/SmartRT02-10Cihills/`
- Backend: `https://<your-backend-domain>/api.php`

---

## 💡 Tips

- GitHub Pages otomatis deploy setiap kali push
- Update settings di cPanel jika perlu ubah database
- Keep `api/koneksi.php` credentials aman (jangan push ke GitHub!)
- Gunakan HTTPS (biasanya auto-generated Let's Encrypt)

---

## ❌ Troubleshoot

| Problem | Solution |
|---------|----------|
| API tidak connect | Check `config.js` API_BASE_URL sudah benar? |
| Login error | Check MySQL user credentials di `koneksi.php` |
| CORS error | Check `Access-Control-Allow-Origin` di `api.php` |
| Frontend blank | Check browser console (F12) untuk JS errors |

---

**Need help?** Lihat file [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) untuk detail lengkap.

Selamat! 🚀
