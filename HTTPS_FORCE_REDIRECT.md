# HTTPS Force Redirect - Dokumentasi

## 📋 Overview

Sistem ini telah dikonfigurasi untuk **otomatis redirect dari HTTP ke HTTPS** di environment production.

---

## ✅ Status Implementasi

| Komponen | Status | File |
|----------|--------|------|
| **ForceHttps Middleware** | ✅ Aktif | `app/Http/Middleware/ForceHttps.php` |
| **Global Middleware Registration** | ✅ Terdaftar | `bootstrap/app.php` |
| **URL Force Scheme** | ✅ Aktif | `app/Providers/AppServiceProvider.php` |

---

## 🔧 Cara Kerja

### 1. **Middleware ForceHttps**

File: `app/Http/Middleware/ForceHttps.php`

```php
public function handle(Request $request, Closure $next): Response
{
    // Force HTTPS in production
    if (!$request->secure() && app()->environment('production')) {
        return redirect()->secure($request->getRequestUri(), 301);
    }

    return $next($request);
}
```

**Logic:**
- Cek apakah request menggunakan HTTPS (`$request->secure()`)
- Cek apakah environment adalah production (`app()->environment('production')`)
- Jika HTTP di production → Redirect ke HTTPS dengan status code **301 (Permanent Redirect)**
- Jika sudah HTTPS atau bukan production → Lanjutkan request normal

---

### 2. **Global Middleware Registration**

File: `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware): void {
    // Force HTTPS in production
    $middleware->append(\App\Http\Middleware\ForceHttps::class);
})
```

**Fungsi:**
- Middleware ditambahkan ke **semua request** (global)
- Berjalan sebelum request diproses oleh controller

---

### 3. **URL Force Scheme**

File: `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    // Force HTTPS in production
    if (app()->environment('production')) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
    ...
}
```

**Fungsi:**
- Memastikan semua URL yang di-generate oleh Laravel menggunakan HTTPS
- Contoh: `route('dashboard')` akan generate `https://...` bukan `http://...`
- Mencegah mixed content warnings

---

## 🧪 Testing

### Test 1: HTTP Request
```bash
Request: http://paketunit.rsazra.co.id/login
Response: 302 Found
Location: https://paketunit.rsazra.co.id/login
```
✅ **BERHASIL** - Redirect ke HTTPS

### Test 2: HTTPS Request
```bash
Request: https://paketunit.rsazra.co.id/login
Response: 200 OK
```
✅ **BERHASIL** - Langsung diproses

### Test 3: Local Development
```bash
Request: http://127.0.0.1:8000/login
Response: 200 OK (No redirect)
```
✅ **BERHASIL** - Tidak redirect di local

---

## 🌍 Environment Configuration

### Production (.env)
```env
APP_ENV=production
APP_URL=https://paketunit.rsazra.co.id
```

### Local (.env)
```env
APP_ENV=local
APP_URL=http://127.0.0.1:8000
```

**PENTING:** 
- Middleware **HANYA** aktif di `APP_ENV=production`
- Di local development (APP_ENV=local), tidak ada redirect
- Ini memudahkan development tanpa perlu setup SSL certificate

---

## 📊 HTTP Status Codes

| Code | Meaning | Usage |
|------|---------|-------|
| **301** | Moved Permanently | Redirect HTTP → HTTPS (SEO friendly) |
| **302** | Found (Temporary) | Redirect sementara |

**Kenapa 301?**
- ✅ SEO friendly - Search engine tahu redirect permanent
- ✅ Browser cache redirect - Lebih cepat untuk user
- ✅ Standard untuk HTTPS redirect

---

## 🔒 Security Benefits

### 1. **Enkripsi Data**
- Semua data yang dikirim antara browser dan server dienkripsi
- Mencegah man-in-the-middle attacks
- Password, session, cookies aman

### 2. **SEO Boost**
- Google memberi ranking lebih tinggi untuk HTTPS sites
- Chrome menandai HTTP sites sebagai "Not Secure"

### 3. **Trust**
- Browser menampilkan padlock icon 🔒
- User lebih percaya dengan site

---

## 🚀 Deployment Checklist

Saat deploy ke production:

- [ ] Set `APP_ENV=production` di `.env`
- [ ] Set `APP_URL=https://your-domain.com` di `.env`
- [ ] Pastikan SSL certificate sudah terinstall di server
- [ ] Test HTTP redirect: `curl -I http://your-domain.com`
- [ ] Verify HTTPS works: `curl -I https://your-domain.com`
- [ ] Clear cache: `php artisan config:clear`
- [ ] Clear route cache: `php artisan route:clear`

---

## 🛠️ Troubleshooting

### Issue 1: Redirect Loop
**Symptom:** Browser menampilkan "Too many redirects"

**Cause:** Server proxy (nginx/apache) sudah handle HTTPS, tapi Laravel tidak tahu

**Solution:** Tambahkan di `app/Http/Middleware/TrustProxies.php`:
```php
protected $proxies = '*';
```

### Issue 2: Mixed Content Warning
**Symptom:** Browser warning tentang mixed content (HTTP resources di HTTPS page)

**Cause:** Ada asset/link yang masih menggunakan HTTP

**Solution:** 
- Pastikan `URL::forceScheme('https')` sudah aktif
- Gunakan `{{ asset() }}` helper untuk asset URLs
- Gunakan `{{ route() }}` helper untuk route URLs

### Issue 3: Tidak Redirect di Production
**Symptom:** HTTP request tidak redirect ke HTTPS

**Check:**
1. Verify `APP_ENV=production` di `.env`
2. Clear config cache: `php artisan config:clear`
3. Check middleware terdaftar: `php artisan route:list --middleware`

---

## 📝 Additional Notes

### Untuk Nginx
Jika menggunakan Nginx, bisa juga tambahkan redirect di config:

```nginx
server {
    listen 80;
    server_name paketunit.rsazra.co.id;
    return 301 https://$server_name$request_uri;
}
```

### Untuk Apache
Jika menggunakan Apache, bisa tambahkan di `.htaccess`:

```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

**Catatan:** Middleware Laravel sudah cukup, tapi redirect di web server level lebih cepat.

---

## ✅ Summary

| Fitur | Status |
|-------|--------|
| Auto HTTP → HTTPS Redirect | ✅ **AKTIF** |
| Production Only | ✅ **YA** |
| SEO Friendly (301) | ✅ **YA** |
| URL Generation HTTPS | ✅ **YA** |
| Local Development Friendly | ✅ **YA** |

**Sistem sudah siap untuk production dengan HTTPS enforcement!** 🎉

---

## 📞 Support

Jika ada masalah:
1. Check `.env` file - pastikan `APP_ENV=production`
2. Clear cache - `php artisan config:clear`
3. Check logs - `storage/logs/laravel.log`
4. Verify SSL certificate valid

---

**Last Updated:** 2026-01-13
**Version:** 1.0
