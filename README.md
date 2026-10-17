# 🎓 Skillspot.in LMS

**Universal Multivendor Learning Management System**

Built with Laravel 13 · PHP 8.3 · MariaDB · Cloudflare R2 · TailwindCSS

---

## 🚀 Features

- **Multivendor Marketplace** — each vendor gets branded storefront
- **Razorpay checkout** — paid course purchases and full refunds
- **Cloudflare R2 Storage** — all assets stored globally
- **Rich Course Builder** — video, text, quiz, live sessions
- **Auto Certificates** — generated on course completion
- **Role-based Access** — Super Admin / Admin / Vendor / Student
- **Analytics Dashboard** — revenue, enrollment, completion
- **Manual seller payouts** — reviewed bank transfers recorded by an admin

---

## 🛠 Installation

```bash
git clone https://github.com/HARISALIP/Skillspot_LMS.git
cd Skillspot_LMS
composer install
cp .env.example .env
php artisan key:generate
# Configure .env with private DB, R2, mail, and Razorpay settings
php artisan migrate
php artisan storage:link
npm ci
npm run build
```

## 📁 Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 13, PHP 8.3+ |
| Database | MariaDB 10.6 |
| Storage | Cloudflare R2 (S3-compatible) |
| Frontend | Blade + TailwindCSS |
| Auth | Laravel Sanctum |
| Roles | Spatie Permission |
| Media | Spatie MediaLibrary |

## 🌐 Deployment

Use [the operations runbook](docs/OPERATIONS.md) for backups, migration checks, scheduler setup, Razorpay reconciliation, manual payouts, and release verification. Never run `db:seed` on production. The compiled `public/build` assets are required; the application no longer loads Tailwind from the CDN.
