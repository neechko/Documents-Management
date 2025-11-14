# Pengurusan Dokumen (PHP - Native)

Instruksi singkat untuk menjalankan aplikasi pada XAMPP (Windows):

1. Letakkan folder proyek di `c:\xampp\htdocs\document management`.
2. Mulai Apache dan MySQL dari XAMPP.
3. Buat database dan tabel: import file `sql/schema.sql` menggunakan phpMyAdmin atau CLI.

   - phpMyAdmin: buka `http://localhost/phpmyadmin`, buat (atau impor) `sql/schema.sql`.
   - CLI (PowerShell):

```powershell
mysql -u root -p < "c:/xampp/htdocs/document management/sql/schema.sql"
```

4. (Opsional, disarankan) Jalankan skrip setup untuk membuat akun admin default:

```powershell
php "c:/xampp/htdocs/document management/app/setup.php"
```

   - Default username: `admin`, password: `admin123` (ubah setelah masuk).

5. Akses aplikasi via browser: `http://localhost/document%20management/public/login.php`.

6. Edit `app/config.php` jika perlu (mis. jika password MySQL berbeda).

Catatan penting & fitur tambahan:
- Sistem sekarang mendukung upload, versi dokumen, kategori bertingkat, preview PDF/gambar, pagination, pencarian, dan audit logs.
- Untuk preview Office (DOCX/XLSX/PPTX) otomatis, Anda perlu memasang LibreOffice pada mesin server.

Instal LibreOffice (Windows) — contoh langkah singkat:

```powershell
# Unduh dan install LibreOffice (manual), lalu pastikan path ke soffice.exe tersedia di:
# C:\Program Files\LibreOffice\program\soffice.exe
```

Jika LibreOffice terpasang, preview DOCX akan mencoba mengonversi ke PDF menggunakan `soffice --headless --convert-to pdf`.

Halaman penting setelah instalasi:
- Login: `public/login.php`
- Upload: `public/upload.php` (pilih dokumen yang ada untuk menambah versi atau buat dokumen baru)
- Daftar dokumen: `public/documents.php`
- Kategori CRUD: `public/categories.php`
- Audit logs: `public/audit.php`

Jika butuh bantuan menyesuaikan path LibreOffice atau mengaktifkan konversi, beri tahu saya。

````

## Quickstart: Publish locally using ngrok (recommended)

Jika Anda ingin membuat aplikasi dapat diakses dari internet tanpa mengubah konfigurasi router, gunakan `ngrok`. Langkah di bawah ini untuk Windows (PowerShell).

1. Pastikan webserver berjalan dan aplikasi dapat diakses secara lokal (contoh: `http://localhost/` atau `http://localhost:8080/`). Uji terlebih dahulu:

```powershell
# contoh cek server lokal (ubah port bila perlu)
Invoke-WebRequest -UseBasicParsing http://localhost/ -OutFile tmp.html; if ($?) { Write-Host 'OK: local site reachable' } else { Write-Host 'Local site not reachable - periksa XAMPP/Apache' }
```

2. Unduh `ngrok` untuk Windows dari https://ngrok.com/download, ekstrak `ngrok.exe` ke folder yang mudah diakses (mis. `C:\tools\ngrok.exe`).

3. Daftar akun di ngrok dan ambil `authtoken` dari dashboard (https://dashboard.ngrok.com/get-started/your-authtoken). Simpan token satu kali:

```powershell
# Jalankan satu kali untuk menyimpan token ke konfigurasi ngrok
C:\tools\ngrok.exe authtoken <YOUR_NGROK_AUTHTOKEN>
```

4. Jalankan tunnel ke port Apache (default 80) — ini menampilkan URL publik (https) yang mengarah ke laptop Anda:

```powershell
# buka tunnel HTTP ke port 80
C:\tools\ngrok.exe http 80

# jika Apache berjalan di port 8080, jalankan:
C:\tools\ngrok.exe http 8080
```

5. (Direkomendasikan) Aktifkan HTTP basic auth agar tidak sembarang orang bisa mengakses aplikasi:

```powershell
C:\tools\ngrok.exe http --auth="admin:strongpass" 80
```

6. Saya sudah menambahkan utilitas di repo:

- `scripts/start-ngrok.ps1` — helper PowerShell yang membuat/mengisi `%USERPROFILE%\\.ngrok2\\ngrok.yml` (meminta authtoken jika belum tersedia), men-start tunnel bernama `document-management`, dan otomatis membuka URL publik serta web UI (`http://127.0.0.1:4040`). Jalankan dari root repo seperti:

```powershell
.\scripts\start-ngrok.ps1 -Port 80 -Auth "admin:strongpass"
# atau bila ngrok berada di C:\tools:
.\scripts\start-ngrok.ps1 -NgrokPath "C:\tools\ngrok.exe" -Port 80 -Auth "admin:strongpass"
```

- `ngrok.yml.template` — contoh konfigurasi (tidak berisi token). Salin ke `%USERPROFILE%\\.ngrok2\\ngrok.yml` dan isi `authtoken` bila ingin konfigurasi manual.

Security checklist before exposing:

- Ubah password admin default di aplikasi (jangan gunakan `admin/admin123`).
- Gunakan `--auth` atau set `auth` di `ngrok.yml` untuk HTTP basic auth.
- Backup database sebelum membuka akses publik.
- Matikan atau amankan endpoint sensitif (hapus debug, disable dev-only features).
- Monitor `ngrok` console and logs while public.

If you want, I can add a small README section with examples of secure `ngrok.yml` entries or create a Windows scheduled task to start `ngrok` at login.
