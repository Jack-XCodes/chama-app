# 🧾 Chama App

A lightweight, private group tracker for Chamas (savings/investment groups), allowing members to access shared documents, view personal financial statements, and monitor group-wide financial performance.

## 🎯 Overview

Chama App is designed to help traditional savings groups (Chamas) digitize their operations with a focus on simplicity and privacy. The application provides essential tools for membership management, document sharing, financial tracking, and group communications.

## ✨ Features

### 👥 Membership Management
- **User Authentication**: Secure sign up/sign in with password recovery
- **Invite-Only System**: Admin can invite members by email
- **Role-Based Access Control**: Create and assign roles with specific permissions
- **Member Management**: View member list, suspend/remove accounts
- **Single Group Focus**: Hardcoded for one group (expandable in future versions)

### 📄 Document Management
- **Document Types**: Create and manage document categories (minutes, contracts, etc.)
- **Permission-Based Access**: Role-based document visibility and actions
- **File Upload & Storage**: Secure document storage with metadata
- **Ownership Tracking**: Track document uploaders and linked users

### 💰 Financial Management

#### For Members:
- **Payment Submissions**: Submit payments with descriptions and proof (text/images)
- **Personal Statements**: View individual financial ledger
- **Group Overview**: Monitor group-wide financial position

#### For Treasurers:
- **Payment Reconciliation**: Approve/reject submitted payments
- **Transaction Recording**: Log expenses, income, and other financial activities
- **Financial Reports**: Auto-generated Balance Sheet, Cashflow, and P&L statements
- **Member Status**: Track paid-up members list

### 🔔 Notifications
- **Flexible Preferences**: Opt-in email and in-app notifications
- **Selective Alerts**: Choose notification types (documents, payments, reports)
- **Group Announcements**: Admin can broadcast messages to all members
- **Read/Unread Tracking**: Manage notification status

## 🏗 Tech Stack

- **Backend**: Laravel 11
- **Admin Panel**: Filament v3
- **Authentication**: Laravel Fortify / Filament Auth
- **Database**: MySQL/PostgreSQL
- **Storage**: Local (S3 configurable for future)
- **Notifications**: Laravel Notifications
- **Permissions**: Spatie Laravel Permission + Filament Shield
- **Reports**: Laravel Excel / DOMPDF

## 📋 Requirements

- PHP 8.2+
- Composer
- Node.js & npm
- MySQL 8.0+ or PostgreSQL 13+

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd chama-app
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node.js dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your database in `.env`**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=chama_app
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. **Run migrations and seeders**
   ```bash
   php artisan migrate --seed
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

9. **Create admin user**
   ```bash
   php artisan make:filament-user
   ```

## 🗂 Project Structure

```
chama-app/
├── app/
│   ├── Filament/           # Filament admin resources
│   ├── Http/Controllers/   # Laravel controllers
│   ├── Models/            # Eloquent models
│   └── Livewire/          # Livewire components
├── database/
│   ├── migrations/        # Database migrations
│   ├── seeders/          # Database seeders
│   └── factories/        # Model factories
├── resources/
│   ├── views/            # Blade templates
│   ├── css/              # Stylesheets
│   └── js/               # JavaScript files
└── routes/               # Application routes
```

## 🎯 Key Models

- **User**: Member information with roles and status
- **Role**: Role definitions with permissions
- **DocumentType**: Document categories with access controls
- **Document**: File storage with metadata and ownership
- **Transaction**: Financial records with reconciliation status
- **Statement**: Read-only financial summaries
- **Notification**: User notifications and preferences

## 🔐 Default Roles

- **Administrator**: Full system access, group setup, member management
- **Treasurer**: Financial management, payment reconciliation, reporting
- **Secretary**: Document management, meeting minutes
- **Member**: Basic access, payment submissions, document viewing

## 📊 Admin Dashboard Features

- Financial KPIs overview
- Payment reconciliation queue
- Member management interface
- Document access controls
- Group-wide announcements
- Financial report generation

## 🛣 Roadmap

### ✅ Version 1.0 (Current)
- [x] Basic authentication system
- [ ] Membership management
- [ ] Document management
- [ ] Financial tracking
- [ ] Notification system
- [ ] Role-based permissions

### 🔮 Future Versions
- Multiple group support
- Mobile applications
- Payment gateway integration
- Advanced accounting features
- Chat/forum functionality
- Third-party integrations

## 🧪 Testing

Run the test suite:
```bash
php artisan test
```

For specific test types:
```bash
# Feature tests
php artisan test --testsuite=Feature

# Unit tests
php artisan test --testsuite=Unit
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 💡 Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation in the `/docs` folder

## 🙏 Acknowledgments

- Built with [Laravel](https://laravel.com)
- Admin interface powered by [Filament](https://filamentphp.com)
- Permissions system by [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

---

**Note**: This is version 1.0 focused on single-group functionality. Multi-group support and advanced features are planned for future releases. 