
# 📚 eTracker Extension Management System - Complete Documentation

---

## 🚦 Quick Start Guide for Professors & Testers

This section is for professors or anyone who needs to test or use the eTracker Extension Management System for the first time. Follow these steps to get started quickly:

### 1. **System Requirements**
- A modern web browser (Chrome, Firefox, Edge, Safari)
- PHP 8.2+ and MySQL 5.7+/8.0+ (for local/server setup)
- Internet connection (for cloud-hosted database)

### 2. **Getting the System**
- Download or clone the project folder to your computer.
- If you received a ZIP file, extract it to a folder (e.g., `C:/Users/YourName/Desktop/Extension`).

### 3. **Setting Up the System Locally**
1. **Install Prerequisites:**
   - Install [XAMPP](https://www.apachefriends.org/) or [WAMP](https://www.wampserver.com/) for PHP & MySQL (if not already installed).
2. **Move the Project Folder:**
   - Place the extracted folder inside your web server's `htdocs` (XAMPP) or `www` (WAMP) directory.
3. **Start Apache and MySQL:**
   - Open XAMPP/WAMP control panel and start both services.
4. **Configure Database Connection:**
   - Open `/backend/config.php` and update the database credentials if needed (see the `Environment Variables` section below).
5. **Import the Database:**
   - Open phpMyAdmin (usually at `http://localhost/phpmyadmin`).
   - Create a new database named `etracker`.
   - Import the SQL file: `/ADMIN/setup_database.sql`.
6. **Install PHP Dependencies:**
   - Open a terminal in the project folder and run: `composer install`

### 4. **Accessing the System**
- Open your browser and go to: `http://localhost/Extension/index.php`
- You will be redirected to the registration/login page.

### 5. **First Login / Creating an Account**
1. **Register as a New User:**
   - Click on Register and fill in the required details.
   - Choose your role (Faculty/Student). For admin access, you may need to update the database directly or ask the system admin.
2. **Login:**
   - Use your registered email and password to log in.
   - You will be redirected to your dashboard based on your role.

### 6. **Testing the System**
- **Admin Dashboard:** `/ADMIN/Dashboard.html` (for admin users)
- **Faculty Dashboard:** `/FACULTY/Dashboard.php`
- **Student Dashboard:** `/STUDENT/index.php`

**Tip:** If you want to test as an admin, you may need to manually set your user role to `admin` in the `users` table using phpMyAdmin.

### 7. **Basic Usage**
- **User Management:** Add, edit, or delete users from the Admin dashboard.
- **Program Management:** Create and manage programs from the Programs section.
- **Attendance:** Mark attendance using QR codes or manual entry.
- **Evaluation:** Submit and review feedback.
- **Certificates:** Generate and download certificates.
- **Reports:** View and export analytics and reports.

### 8. **Troubleshooting**
- If you see a blank page or error, check your PHP error log or `/STUDENT/debug.log`.
- Make sure your database credentials are correct in `/backend/config.php`.
- If you cannot log in, check the `users` table for your account and role.

---


## 🏛️ System Overview

The **eTracker Extension Management System** is a comprehensive web-based application designed for managing university extension services at CVSU-Imus. The system facilitates seamless interaction between students, faculty, and administrators while providing robust tracking and management capabilities for extension programs.

### 🎯 Primary Objectives
- Streamline student, stakeholders, partners & faculty registration and attendance
- Efficient program management and scheduling
- Real-time tracking and monitoring
- Automated evaluation and reporting
- Enhanced communication and notifications

---

## 🏗️ System Architecture

### Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, TailwindCSS
- **Backend**: PHP 8.2+
- **Database**: MySQL (AWS RDS)
- **Libraries**: 
  - Chart.js for data visualization
  - Font Awesome for icons
  - FPDF for PDF generation
  - QR Code generation libraries

### Database Configuration
- **Host**: extension.c5i2m2mgkbh2.ap-southeast-2.rds.amazonaws.com
- **Database**: etracker
- **Timezone**: Asia/Manila
- **Character Set**: UTF8MB4

---

## 👥 User Roles & Access Control

### 1. **Administrator**
- **Dashboard**: `/ADMIN/Dashboard.html`
- **Capabilities**:
  - Complete system oversight
  - User management (create, edit, delete users)
  - Program scheduling and management
  - Attendance monitoring
  - Evaluation management
  - Report generation and analytics
  - Document management
  - Certificate issuance
  - System notifications

### 2. **Faculty**
- **Dashboard**: `/FACULTY/Dashboard.php`
- **Capabilities**:
  - Personal profile management
  - Program creation and management
  - Attendance tracking for their programs
  - Student evaluation management
  - Certificate generation
  - Document upload and management
  - Progress reports

### 3. **Student**
- **Dashboard**: `/STUDENT/index.php`
- **Capabilities**:
  - Program enrollment and browsing
  - Attendance marking (QR code and manual)
  - Feedback submission and evaluation
  - Certificate viewing and download
  - Progress tracking and reports
  - Profile management

---


## 🔐 Authentication & Registration System

### How to Register and Log In
- Go to the main entry point: `/index.php` (or the URL provided by your admin)
- Register with your details and select your role (Faculty/Student)
- After registration, log in with your credentials
- Admin accounts may be created by the system administrator or by updating the database directly

### Entry Point
- **Main Entry**: `/index.php` → Redirects to `/register/index.html`
- **Registration Process**:
  1. Initial form with basic information
  2. Role selection (Student/Faculty)
  3. Additional profile information based on role
  4. Email verification (optional)

### Login Flow
1. User enters credentials on login page
2. Backend validates against database
3. Session creation with role-based redirection
4. Dashboard access based on user role

---


## 📋 Core Features & Modules

### 1. **User Management**
**How to Use:**
- Go to the Admin dashboard and select "Users" from the sidebar.
- Add new users, edit existing profiles, assign roles, or deactivate accounts.
**Location**: `/ADMIN/User.html`, `/ADMIN/api_users.php`

**Features**:
- User registration and profile management
- Role assignment and permissions
- User verification and activation
- Profile updates and maintenance

**Database Tables**:
- `users` - Main user information
- `faculty_info` - Extended faculty details
- `student_info` - Extended student details

### 2. **Program Management**
**How to Use:**
- Admin/Faculty: Go to the Programs section in your dashboard.
- Click "Add Program" to create a new program. Fill in the details (title, description, schedule, etc.).
- Edit or delete programs as needed.
- Students can browse and enroll in available programs from their dashboard.
**Locations**: 
- Admin: `/ADMIN/Programs.html`
- Faculty: `/FACULTY/Programs.php`
- Student: `/STUDENT/Programs.php`

**Features**:
- Program creation and scheduling
- Enrollment management
- Capacity monitoring
- Program categorization
- Status tracking (active, completed, cancelled)

**Key Files**:
- `/backend/create_program.php`
- `/backend/get_programs.php`
- `/backend/update_program.php`
- `/backend/delete_program.php`

### 3. **Attendance Tracking System**
**How to Use:**
- Faculty/Admin: Go to the Attendance section.
- For QR attendance: Generate a QR code for the session. Students scan the code using their dashboard.
- For manual attendance: Mark students as present/absent directly.
- View attendance logs and export reports as needed.
**Locations**:
- Admin: `/ADMIN/Attendance.html`
- Faculty: `/FACULTY/attendance.php`
- Student: `/STUDENT/Attendance.php`

**Features**:
- **QR Code-based Attendance**:
  - Automatic QR generation for sessions
  - Mobile-friendly scanning
  - Real-time attendance marking
- **Manual Attendance**:
  - Faculty-controlled attendance marking
  - Bulk attendance operations
- **Time-stamped Logs**:
  - Precise attendance timing
  - Location tracking (optional)
- **Attendance Reports**:
  - Individual attendance history
  - Program-wise attendance analytics
  - Export capabilities

**Key Files**:
- `/STUDENT/attendance-qr.php`
- `/STUDENT/mark_attendance.php`
- `/STUDENT/qr_attendance.php`
- `/ADMIN/api_attendance.php`

### 4. **Evaluation & Feedback System**
**How to Use:**
- Students: Go to the Feedback/Evaluation section after attending a program.
- Fill out the evaluation form and submit feedback.
- Faculty/Admin: Review feedback and generate summary reports.
**Locations**:
- Admin: `/ADMIN/Evaluation.html`
- Faculty: `/FACULTY/evaluation.php`
- Student: `/STUDENT/Feedback.php`

**Features**:
- **Multi-level Evaluations**:
  - Student evaluation of programs
  - Faculty assessment tools
  - Peer evaluation systems
- **Automated Feedback Summary**:
  - Statistical analysis of feedback
  - Trend identification
  - Performance insights
- **Evaluation Forms**:
  - Customizable evaluation criteria
  - Rating scales and comments
  - Anonymous feedback options

**Key Files**:
- `/STUDENT/submit_evaluation.php`
- `/STUDENT/submit_detailed_evaluation.php`
- `/ADMIN/api_evaluations.php`

### 5. **Certificate Management**
**How to Use:**
- Faculty/Admin: Go to the Certificates section.
- Generate certificates for participants (individually or in bulk).
- Students: Download/view certificates from your dashboard.
**Locations**:
- Admin: `/ADMIN/Certificates.html`
- Faculty: `/FACULTY/certificate.php`
- Student: `/STUDENT/certificates.php`

**Features**:
- **Automated Certificate Generation**:
  - PDF-based certificates using FPDF
  - Template customization
  - Bulk certificate generation
- **Certificate Types**:
  - Participation certificates
  - Completion certificates
  - Achievement certificates
- **Digital Verification**:
  - Unique certificate IDs
  - Verification system
  - Anti-fraud measures

**Key Files**:
- `/STUDENT/get_certificates.php`
- `/backend/fpdf.php`
- `/certificates/` directory for storage

### 6. **Document Management**
**How to Use:**
- Admin: Upload and manage documents in the Document section.
- Faculty: Upload supporting documents for programs.
- Search, view, or delete documents as needed.
**Locations**:
- Admin: `/ADMIN/Document.html`
- Faculty: `/FACULTY/upload.php`

**Features**:
- Document upload and storage
- Version control
- Access permissions
- Document categorization
- Search and retrieval

**Key Files**:
- `/ADMIN/get_documents.php`
- `/ADMIN/view_document.php`
- `/backend/delete_document.php`

### 7. **Reporting & Analytics**
**How to Use:**
- Go to the Reports section in your dashboard.
- Select the type of report (attendance, evaluation, participation, etc.).
- View charts, download PDFs, or export CSVs.
**Locations**:
- Admin: `/ADMIN/Reports.html`
- Faculty: `/FACULTY/reports.php`
- Student: `/STUDENT/Reports.php`

**Features**:
- **Dashboard Analytics**:
  - Real-time statistics
  - Visual data representation
  - Key performance indicators
- **Custom Reports**:
  - Attendance reports
  - Evaluation summaries
  - Participation analytics
  - Progress tracking
- **Export Capabilities**:
  - PDF reports
  - CSV data export
  - Scheduled reports

**Key Files**:
- `/backend/reports_api.php`
- `/backend/dashboard_stats.php`
- `/STUDENT/get_participation_report.php`

### 8. **Communication & Notifications**
**How to Use:**
- Admin/Faculty: Send announcements or notifications from the Notifications section.
- Students: View notifications in your dashboard or via email (if enabled).
**Locations**:
- Admin: `/ADMIN/Notifications.html`
- Faculty: `/FACULTY/api_notifications.php`

**Features**:
- System-wide announcements
- Program-specific notifications
- Attendance reminders
- Evaluation deadlines
- Real-time updates

**Key Files**:
- `/ADMIN/api_notifications.php`
- `/FACULTY/api_notifications.php`

---


## 🗄️ Database Schema

### Core Tables Structure

#### Users Table
```sql
users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    firstname VARCHAR(100),
    lastname VARCHAR(100),
    email VARCHAR(255) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'faculty', 'student'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
)
```

#### Programs Table
```sql
programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255),
    description TEXT,
    faculty_id INT,
    start_date DATE,
    end_date DATE,
    capacity INT,
    status ENUM('active', 'completed', 'cancelled'),
    created_at TIMESTAMP
)
```

#### Attendance Table
```sql
attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    program_id INT,
    session_id INT,
    marked_at TIMESTAMP,
    method ENUM('qr', 'manual'),
    location VARCHAR(255)
)
```

#### Evaluations Table
```sql
evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    program_id INT,
    rating INT,
    feedback TEXT,
    evaluation_type VARCHAR(50),
    submitted_at TIMESTAMP
)
```

---


## 🚀 Deployment & Configuration

### Quick Local Setup (Summary)
1. Install XAMPP/WAMP and Composer
2. Place the project folder in your web server directory
3. Import the database using `/ADMIN/setup_database.sql`
4. Update `/backend/config.php` with your database credentials
5. Run `composer install` in the project folder
6. Access the system via `http://localhost/Extension/index.php`

### Development Environment Setup

1. **Prerequisites**:
   - PHP 8.2+
   - MySQL 5.7+/8.0+
   - Web server (Apache/Nginx)
   - Composer (for dependency management)

2. **Installation Steps**:
   ```bash
   # Clone or download the project
   cd "path/to/eTracker"
   
   # Install dependencies
   composer install
   
   # Configure database connection
   # Edit /backend/config.php with your database credentials
   
   # Import database schema
   mysql -u username -p database_name < /ADMIN/setup_database.sql
   ```

### Production Deployment

#### Recommended Platforms:
1. **Railway** (Recommended for PHP + Database)
2. **Heroku** (Popular choice with add-ons)
3. **AWS** (Current database hosting)

#### Configuration Files:
- `composer.json` - PHP dependencies
- `Dockerfile` - Container configuration
- `Procfile` - Process definitions
- `railway.json` - Railway-specific settings
- `nixpacks.toml` - Build configuration

### Environment Variables (for `/backend/config.php`)
```env
DB_HOST=extension.c5i2m2mgkbh2.ap-southeast-2.rds.amazonaws.com
DB_USER=admin
DB_PASS=etrackerextension
DB_NAME=etracker
TIMEZONE=Asia/Manila
```

---

## 🔧 API Endpoints

### Authentication APIs
- `POST /register/` - User registration
- `POST /login/` - User authentication
- `POST /logout/` - Session termination

### User Management APIs
- `GET /ADMIN/api_users.php` - Get all users
- `POST /ADMIN/api_users.php` - Create new user
- `PUT /ADMIN/api_users.php` - Update user
- `DELETE /ADMIN/api_users.php` - Delete user

### Program Management APIs
- `GET /backend/get_programs.php` - Get programs
- `POST /backend/create_program.php` - Create program
- `PUT /backend/update_program.php` - Update program
- `DELETE /backend/delete_program.php` - Delete program

### Attendance APIs
- `GET /ADMIN/api_attendance.php` - Get attendance data
- `POST /STUDENT/mark_attendance.php` - Mark attendance
- `GET /STUDENT/get_attendance.php` - Get user attendance

### Evaluation APIs
- `GET /ADMIN/api_evaluations.php` - Get evaluations
- `POST /STUDENT/submit_evaluation.php` - Submit evaluation
- `GET /STUDENT/get_evaluations.php` - Get user evaluations

---


## 📱 User Interface Guide


### Dashboard Features

#### Admin Dashboard
- **Statistics Cards**: Users, programs, certificates count
- **Charts**: Attendance rates, program trends
- **Quick Actions**: Create programs, export reports
- **Notifications Panel**: System alerts and updates

#### Faculty Dashboard
- **Program Management**: Create and manage programs
- **Attendance Tracking**: Monitor student attendance
- **Evaluation Tools**: Review student feedback
- **Certificate Generation**: Issue completion certificates

#### Student Dashboard
- **Program Browser**: Discover and enroll in programs
- **Attendance Tracking**: Mark attendance via QR or manual
- **Feedback System**: Submit program evaluations
- **Progress Tracking**: View certificates and achievements


### Navigation Structure
- **Sidebar Navigation**: Role-based menu items
- **Top Bar**: Search, notifications, user profile
- **Breadcrumbs**: Current location indicator
- **Quick Actions**: Context-sensitive shortcuts

---


## 🔍 QR Code System

### Implementation Details
- **Generation**: Dynamic QR codes for each session
- **Scanning**: Mobile-friendly QR scanner
- **Validation**: Server-side attendance verification
- **Security**: Time-limited QR codes with session tokens

### Files Involved
- `/STUDENT/generate_qr_code.php` - QR generation
- `/STUDENT/qr_attendance.php` - QR scanning interface
- `/STUDENT/attendance-qr.php` - QR-based attendance marking

---


## 📊 Reporting System

### Available Reports
1. **Attendance Reports**
   - Individual attendance history
   - Program-wise attendance statistics
   - Monthly/quarterly summaries

2. **Evaluation Reports**
   - Program feedback analysis
   - Faculty performance metrics
   - Trend analysis

3. **Participation Reports**
   - Student engagement metrics
   - Program completion rates
   - Certificate issuance statistics

### Export Formats
- PDF reports with institutional branding
- CSV data for further analysis
- Real-time dashboard charts

---


## 🛡️ Security Features

### Data Protection
- **Password Hashing**: Secure password storage
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **CSRF Protection**: Token-based form security

### Access Control
- **Role-based Permissions**: Hierarchical access control
- **Session Management**: Secure session handling
- **Login Throttling**: Brute force protection
- **Data Validation**: Server-side input validation

---


## 🔧 Maintenance & Troubleshooting

### Common First-Time Issues
- **Cannot connect to database:** Check `/backend/config.php` and ensure MySQL is running.
- **Blank page or errors:** Check PHP error logs or `/STUDENT/debug.log`.
- **Cannot log in:** Make sure your user exists in the `users` table and has the correct role.

### Common Issues

#### Database Connection Issues
```php
// Check connection in /backend/config.php
if ($conn->connect_error) {
    error_log("MySQL Connection failed: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}
```

#### Session Problems
- Clear browser cache and cookies
- Check session configuration in PHP
- Verify database session storage

#### QR Code Issues
- Ensure QR generation libraries are installed
- Check camera permissions for mobile devices
- Verify QR code generation endpoints

### Log Files
- `/STUDENT/debug.log` - Student module debugging
- `/ADMIN/project_evaluations.log` - Evaluation system logs
- Server error logs for PHP errors

### Performance Optimization
- Database query optimization
- Image compression for QR codes
- CSS/JS minification
- Caching strategies for reports

---


## 📞 Support & Contact

### Technical Support
- **System Administrator**: Contact your institution's IT department
- **Database Issues**: AWS RDS support for database-related problems
- **Deployment Issues**: Refer to deployment guides in repository

### Documentation Updates
- This documentation should be updated with system changes
- Version control through Git commits
- Regular reviews for accuracy and completeness

---


## 📈 Future Enhancements

### Planned Features
- Mobile application development
- Integration with learning management systems
- Advanced analytics and AI-powered insights
- Multi-language support
- Real-time chat and communication
- Advanced certificate templates

### Scalability Considerations
- Database optimization for larger user bases
- CDN integration for better performance
- Load balancing for high traffic
- Microservices architecture migration

---


## 📄 License & Credits

### System Information
- **System Name**: eTracker Extension Management System
- **Institution**: CVSU-Imus (Cavite State University - Imus Campus)
- **Purpose**: Extension Services Management
- **Development**: Custom web application

### Third-party Libraries
- TailwindCSS for styling
- Font Awesome for icons
- Chart.js for data visualization
- FPDF for PDF generation

---

*This documentation serves as a comprehensive guide for the eTracker Extension Management System. For technical support or additional information, please contact your system administrator.*

**Last Updated**: July 8, 2025
**Version**: 1.0
**System Status**: Production Ready
