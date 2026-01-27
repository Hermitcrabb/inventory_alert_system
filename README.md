# Inventory Alert System

A Laravel-based application that monitors product inventory levels and sends **email notifications (with CC)** when stock falls below a user-defined threshold. Alert delivery is handled using Laravel’s **queue system** to ensure non-blocking, scalable processing.

Repository: [https://github.com/Hermitcrabb/inventory_alert_system](https://github.com/Hermitcrabb/inventory_alert_system)

---

## 🚀 Overview

The **Inventory Alert System** is designed to help businesses track inventory in real time and automatically notify responsible parties when stock levels become critically low. The system supports configurable thresholds per product and processes notifications asynchronously using queues.

---

## ✨ Features

* 📦 Product inventory tracking
* 🎯 User-defined low-stock alert thresholds
* 📧 Email notification system with CC support
* 🔁 Queue-based alert processing
* 🧾 Alert logging to prevent unnecessary duplicates
* 🛠 Built using standard Laravel architecture

---

## 📂 Project Structure

```
inventory_alert_system/
├── app/
│   ├── Console/
│   ├── Events/
│   ├── Http/
│   │   └── Controllers/
│   ├── Jobs/
│   ├── Listeners/
│   ├── Mail/
│   ├── Models/
│   └── Notifications/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
├── resources/
├── routes/
│   └── web.php
├── storage/
├── tests/
├── .env.example
├── artisan
├── composer.json
└── README.md
```

This structure follows standard Laravel conventions. Inventory checks, queued jobs, and notifications are implemented within the appropriate `Jobs`, `Notifications`, and `Mail` directories.

---

## ⚙️ Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/Hermitcrabb/inventory_alert_system.git
cd inventory_alert_system
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Update your database and mail settings in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_alert_system
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=alerts@example.com
MAIL_FROM_NAME="Inventory Alert System"
```

---

## 🗄️ Database Setup

Run migrations:

```bash
php artisan migrate
```

(Optional) Seed sample data:

```bash
php artisan db:seed
```

---

## 🔁 Queue-Based Alert Processing

This application uses Laravel’s queue system to process alert notifications asynchronously.

* Inventory updates trigger an alert check
* If the threshold condition is met, a **queued job** is dispatched
* The queue worker handles email delivery in the background

Run the queue worker using:

```bash
php artisan queue:work
```

This ensures inventory updates are fast and non-blocking.

---

## 📨 Email Notification Logic

* Each product defines:

  * `available_quantity`
  * `alert_threshold`

* When `available_quantity <= alert_threshold`:

  * A notification job is dispatched to the queue
  * An email alert is sent to the primary recipient
  * Additional stakeholders are included using **CC**

Duplicate alerts can be controlled by tracking the last notified quantity or alert state.

---

## 🧪 Testing

Run the test suite using:

```bash
php artisan test
```

---

## 🚧 Future Enhancements

* 📱 SMS notifications via third-party providers
* 📊 Admin dashboard for managing products and thresholds
* 📈 Alert history and reporting
* 🔐 Role-based access control

---

## 📄 License

This project is open-source and licensed under the **MIT License**.

---

## 👤 Author

**Pratham Bhandari**
GitHub: [https://github.com/Hermitcrabb](https://github.com/Hermitcrabb)
