# University Assets Management System (UAMS)

An advanced system for managing all types of assets within a university using PHP Native and MySQL with Bootstrap Responsive design.

---

## Key Features

### 1. Comprehensive Asset Management
- Support for all asset types (IT Equipment, Laboratory Equipment, Furniture, etc.)
- Multi-level categorizations
- Complete barcode and QR Code tracking
- Financial value and depreciation management

### 2. Hierarchical Location Management
- Campus → Faculty → Department → Lab/Office
- Precise tracking of each asset's location
- Support for transfers and returns between locations

### 3. Advanced Maintenance System
- Complete maintenance operations log
- Automatic notifications for upcoming maintenance
- Cost and service provider tracking

### 4. Loan and Transfer System
- Asset loans to users
- Asset transfers between locations
- Complete tracking of transfer and loan history

### 5. Reports and Statistics
- Reports by category, location, and status
- Comprehensive financial reports
- Maintenance reports

### 6. Permission System
- Multiple roles (Admin, Faculty Manager, Lab Manager, Staff)
- Role-specific permissions
- Complete activity log

---

## System Requirements

- PHP 7.4 or later
- MySQL 5.7 or later
- Apache/Nginx Web Server
- Extensions: PDO, PDO_MySQL

---

## Installation

### 1. Import Database

```bash
mysql -u root -p < database/schema.sql
```

Or use phpMyAdmin to import the `database/schema.sql` file

### 2. Configure Database Connection

Edit `config/database.php`:

```php
private $host = 'localhost';
private $dbname = 'university_assets_db';
private $username = 'root';
private $password = '';
```

### 3. Configure URL

Edit `config/config.php`:

The system auto-detects the URL, but you can manually set it if needed.

### 4. Create Upload Directories (Optional)

```bash
mkdir uploads
mkdir logs
chmod 755 uploads logs
```

---

## Default Login Credentials

- **Username:** admin
- **Password:** admin123

**⚠️ Important:** Change the default password immediately after installation!

---

## Project Structure

```
Assets Management Module/
├── config/              # Configuration files
│   ├── config.php      # Application settings
│   ├── database.php    # Database connection
│   └── functions.php    # Helper functions
├── database/           # Database files
│   └── schema.sql      # Database schema
├── includes/           # Shared files
│   ├── header.php      # Page header
│   └── footer.php      # Page footer
├── modules/            # Modules
│   ├── assets/         # Asset management
│   ├── locations/      # Location management
│   ├── maintenance/    # Maintenance management
│   ├── transfers/      # Transfers and loans
│   ├── users/          # User management
│   ├── settings/       # System settings
│   └── reports/        # Reports
├── assets/             # Static resources
│   ├── css/
│   └── js/
├── uploads/            # Uploaded files
├── logs/               # Log files
├── index.php           # Home page
├── login.php           # Login page
└── logout.php          # Logout page
```

---

## Security

### SQL Injection Protection
- Using **Prepared Statements** in all queries
- Using PDO with `PDO::ATTR_EMULATE_PREPARES => false`
- Sanitizing all inputs using `sanitizeInput()`

### XSS Protection
- Using `htmlspecialchars()` when displaying data
- Sanitizing inputs before saving

### Session Security
- `session.cookie_httponly = 1`
- `session.use_only_cookies = 1`
- Password hashing using `password_hash()`

---

## User Roles

### Admin
- Full access to all functions
- User management
- Delete assets and locations

### Faculty Manager
- Manage assets in the faculty
- View reports
- Manage maintenance

### Lab Manager
- Manage assets in the lab
- Record maintenance
- Loan assets

### Staff
- View assets
- Add new assets
- Edit assigned assets

---

## Main Functions

### Asset Management
- Add/Edit/Delete/View assets
- Advanced search and filtering
- Financial value tracking
- Warranty management

### Location Management
- Add new locations (labs, offices, rooms)
- View organizational structure
- Track assets in each location

### Maintenance Management
- Record maintenance operations
- Schedule upcoming maintenance
- Cost tracking

### Transfers and Loans
- Transfer assets between locations
- Loan assets to users
- Track active loans

### Reports
- Reports by category
- Reports by location
- Financial reports
- Maintenance reports

---

## Future Development

- [ ] Email notifications
- [ ] Barcode Scanner support
- [ ] API for integration with other systems
- [ ] Export reports to Excel/PDF
- [ ] Advanced dashboard with charts
- [ ] Mobile application

---

## Support

For help and support, please contact the development team.

---

## License

This project is intended for internal university use.

---

## Important Notes

1. **Security:** Make sure to change the default password immediately
2. **Backup:** Perform regular database backups
3. **Updates:** Monitor PHP and MySQL updates to maintain security
4. **Performance:** Use database indexing for large tables

---

## About Development

### Development Methodology

This project was built entirely using **Artificial Intelligence (AI)** technologies in software development, following best practices and modern programming standards.

### Development Process

1. **AI-Assisted Development:**
   - Using AI to write the core code
   - Applying Clean Code principles
   - Using appropriate Design Patterns
   - Implementing Security Best Practices

2. **Review and Audit:**
   - Comprehensive personal review of all screens (UI/UX)
   - Examination and analysis of business logic
   - Review of business models
   - Testing all functions and features
   - Security and performance verification

3. **Quality Assurance:**
   - Testing all scenarios
   - Ensuring requirement compliance
   - Verifying user experience
   - Security and privacy review

### Technical Features

- ✅ **Clean and organized code:** Following PSR standards and modern programming practices
- ✅ **High security:** Protection against SQL Injection and XSS
- ✅ **Optimized performance:** Optimized database queries
- ✅ **Responsive design:** Works on all devices
- ✅ **Easy maintenance:** Clear and documented code

---

**Developed by:** Mohamed Hekal  
**Using:** AI-Assisted Development  
**With comprehensive review:** Of all screens, logic, and business models  
**Version:** 1.0.0  
**Date:** 11/2025

---

---

# نظام إدارة الأصول الجامعية

نظام متقدم لإدارة جميع أنواع الأصول داخل الجامعة باستخدام PHP Native و MySQL مع تصميم Bootstrap Responsive.

---

## المميزات الرئيسية

### 1. إدارة شاملة للأصول
- دعم جميع أنواع الأصول (IT Equipment, Laboratory Equipment, Furniture, etc.)
- تصنيفات متعددة المستويات
- تتبع كامل للباركود و QR Code
- إدارة القيمة المالية والإهلاك

### 2. إدارة المواقع الهرمية
- الحرم → الكلية → القسم → المختبر/المكتب
- تتبع دقيق لموقع كل أصل
- دعم النقل والإعادة بين المواقع

### 3. نظام الصيانة المتقدم
- سجل كامل لعمليات الصيانة
- إشعارات تلقائية للصيانة القادمة
- تتبع التكلفة ومقدم الخدمة

### 4. نظام الإعارة والنقل
- إعارة الأصول للمستخدمين
- نقل الأصول بين المواقع
- تتبع كامل لسجل النقل والإعارة

### 5. التقارير والإحصائيات
- تقارير حسب التصنيف والموقع والحالة
- تقارير مالية شاملة
- تقارير الصيانة

### 6. نظام الصلاحيات
- أدوار متعددة (Admin, Faculty Manager, Lab Manager, Staff)
- صلاحيات محددة حسب الدور
- سجل كامل للأنشطة

---

## حول التطوير

### منهجية التطوير

هذا المشروع تم بناؤه بالكامل باستخدام **تقنيات الذكاء الاصطناعي (AI)** في تطوير البرمجيات، مع اتباع أفضل الممارسات والمعايير الحديثة في البرمجة.

### عملية التطوير

1. **التطوير بالذكاء الاصطناعي:**
   - استخدام AI لكتابة الكود الأساسي
   - تطبيق مبادئ البرمجة النظيفة (Clean Code)
   - استخدام Design Patterns المناسبة
   - تطبيق أفضل ممارسات الأمان (Security Best Practices)

2. **المراجعة والتدقيق:**
   - مراجعة شخصية شاملة لجميع الشاشات (UI/UX)
   - فحص وتحليل منطق العمل (Business Logic)
   - مراجعة نماذج الأعمال (Business Models)
   - اختبار جميع الوظائف والميزات
   - التحقق من الأمان والأداء

3. **ضمان الجودة:**
   - اختبار جميع السيناريوهات
   - التأكد من تطابق المتطلبات
   - التحقق من تجربة المستخدم
   - مراجعة الأمان والخصوصية

### المميزات التقنية

- ✅ **كود نظيف ومنظم:** اتباع معايير PSR وممارسات البرمجة الحديثة
- ✅ **أمان عالي:** حماية من SQL Injection و XSS
- ✅ **أداء محسّن:** استعلامات قاعدة بيانات محسّنة
- ✅ **تصميم متجاوب:** يعمل على جميع الأجهزة
- ✅ **سهولة الصيانة:** كود واضح وموثق

---

**تم التطوير بواسطة:** Mohamed Hekal  
**باستخدام:** AI-Assisted Development  
**مع مراجعة شاملة:** لجميع الشاشات والمنطق ونماذج الأعمال  
**الإصدار:** 1.0.0  
**التاريخ:** 11/2025
