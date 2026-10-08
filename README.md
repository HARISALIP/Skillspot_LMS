# 🎓 Skillspot.in LMS

**Universal Multivendor Learning Management System**

Built with Laravel 11 · PHP 8.3 · MariaDB · Cloudflare R2 · TailwindCSS

---

## 🚀 Features

- **Multivendor Marketplace** — each vendor gets branded storefront
- **License-based selling** — Starter / Pro / Enterprise plans
- **Cloudflare R2 Storage** — all assets stored globally
- **Rich Course Builder** — video, text, quiz, live sessions
- **Auto Certificates** — generated on course completion
- **Role-based Access** — Super Admin / Admin / Vendor / Student
- **Analytics Dashboard** — revenue, enrollment, completion
- **White-label Branding** — per-vendor logo, domain, colors

---

## 🛠 Installation

```bash
git clone ssh://git@git.ccin.in:55552/asif/Skillspot.in.git
cd Skillspot.in
composer install
cp .env.example .env
php artisan key:generate
# Configure .env with your DB and R2 credentials
php artisan migrate --seed
php artisan storage:link
```

## 📁 Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11, PHP 8.3+ |
| Database | MariaDB 10.6 |
| Storage | Cloudflare R2 (S3-compatible) |
| Frontend | Blade + TailwindCSS |
| Auth | Laravel Sanctum |
| Roles | Spatie Permission |
| Media | Spatie MediaLibrary |

## 🌐 Deployment

Clone repo on any server, set .env, run `composer install && php artisan migrate`.
