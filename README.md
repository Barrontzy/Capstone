# BSU Inventory Management System

A comprehensive PHP-based inventory management system for BSU (Batangas State University) with equipment tracking, maintenance management, and reporting capabilities.

## Features

### 🔐 Authentication & User Management
- **User Registration**: Full name, email, role (admin/technician), phone number, password
- **Secure Login**: Email and password authentication
- **Role-based Access**: Admin and technician roles with different permissions
- **User Profile Management**: View and edit personal information

### 📊 Dashboard & Analytics
- **Inventory Trends**: Monthly acquisition and disposal trends with interactive charts
- **Statistics Overview**: Total equipment, active items, maintenance status, departments
- **Recent Activity**: Latest equipment additions and maintenance alerts
- **Visual Charts**: Bar graphs and pie charts for data visualization

### 🖥️ Equipment Management
- **Equipment Categories**: Laptop PC, Desktop PC, Printer, Router, Access Point
- **CRUD Operations**: Add, view, edit, delete equipment
- **QR Code Generation**: Automatic QR code generation for each equipment
- **Search & Filter**: Filter by department, category, status, and search by name/serial
- **Detailed Information**: Serial numbers, models, brands, acquisition dates, costs

### 🏢 Department Management
- **Pre-configured Departments**:
  - College of Accountancy, Business & Economics
  - College of Arts and Sciences
  - College of Engineering
  - College of Engineering Technology (CIT)
  - College of Informatics & Computing Sciences
- **Department CRUD**: Add, edit, delete departments
- **Building & Location**: Track building and location information

### 🔧 Maintenance Management
- **Maintenance Types**: Preventive, corrective, and upgrade maintenance
- **Technician Assignment**: Assign maintenance tasks to technicians
- **Status Tracking**: Scheduled, in progress, completed, cancelled
- **Cost Tracking**: Record maintenance costs and expenses
- **Equipment Status**: Automatic status updates based on maintenance

### 📋 Task Management
- **Task Assignment**: Assign tasks to users with priorities
- **Priority Levels**: Urgent, high, medium, low
- **Status Tracking**: Pending, in progress, completed, cancelled
- **Due Date Management**: Set and track due dates
- **Overdue Alerts**: Visual indicators for overdue tasks

### 📈 Reports & Analytics
- **Complete Inventory Report**: Comprehensive equipment listing
- **Financial Summary Report**: Cost analysis and budget tracking
- **Department Analysis Report**: Equipment distribution by department
- **Maintenance & Status Report**: Maintenance records and equipment status
- **Incomplete Items Report**: Equipment with missing information
- **Acquisition Timeline Report**: Equipment purchase history
- **Export Options**: Download reports as CSV files
- **Filter Options**: Date range, department, equipment type filters

### 🔍 Search & Filter
- **Advanced Search**: Search by building, department, location
- **Multiple Filters**: Equipment type, status, department, date range
- **Sort Options**: Sort by various criteria
- **Real-time Results**: Instant search results

### 📋 Activity Log History
- **Comprehensive Tracking**: Log all user activities and system changes
- **Detailed Records**: Track equipment creation, updates, deletions
- **User Actions**: Monitor login/logout, maintenance activities, report generation
- **Filter Options**: Filter by user, action type, date range, table
- **Export Functionality**: Download activity logs as CSV files
- **Security Audit**: IP address tracking and user agent logging

### 👁️ Equipment View & Edit
- **Detailed View**: Complete equipment information with QR code display
- **Maintenance History**: View all maintenance records for equipment
- **Edit Functionality**: Update equipment details with validation
- **Activity Logging**: Track all changes with before/after values
- **Quick Actions**: Schedule maintenance, create tasks, print details
- **Responsive Design**: Mobile-friendly equipment management

### 👥 User Management (Admin Only)
- **User List**: View all registered users
- **User Details**: Comprehensive user profile with statistics
- **Role Management**: Assign admin or technician roles
- **Account Management**: Add, edit, delete user accounts
- **User Statistics**: Equipment assignments, maintenance records, task counts
- **Activity History**: View user's recent system activities
- **Password Management**: Secure password updates with validation

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **UI Framework**: Bootstrap 5.1.3
- **Icons**: Font Awesome 6.0.0
- **Charts**: Chart.js
- **QR Code**: External API (api.qrserver.com)

## Installation & Setup

### Prerequisites
- XAMPP, WAMP, or similar local server environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser

### Installation Steps

1. **Clone/Download the Project**
   ```bash
   # If using git
   git clone <repository-url>
   # Or download and extract the ZIP file
   ```

2. **Place in Web Server Directory**
   ```
   Copy the project folder to your web server directory:
   - XAMPP: C:\xampp\htdocs\BSU\
   - WAMP: C:\wamp\www\BSU\
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `inventory_db`
   - Import the `database.sql` file to create all tables and sample data

4. **Configure Database Connection**
   - Edit `includes/db.php` if needed:
   ```php
   $host = 'localhost';
   $user = 'root';
   $password = '';
   $dbname = 'inventory_db';
   ```

5. **Access the System**
   - Open your web browser
   - Navigate to: `http://localhost/BSU/` or `http://localhost/BSU/landing.php`
   - Default admin credentials:
     - Email: `admin@bsu.edu.ph`
     - Password: `password`

## Database Schema

### Tables
- **users**: User accounts and authentication
- **departments**: Department information
- **equipment_categories**: Equipment type categories
- **equipment**: Main equipment inventory
- **maintenance_records**: Maintenance history
- **tasks**: Task assignments and tracking

### Key Features
- Foreign key relationships for data integrity
- Timestamps for audit trails
- Enum fields for status and role management
- Unique constraints for emails and serial numbers

## Color Scheme

The system uses a consistent color scheme:
- **Primary**: Red (#dc3545) - Main actions and highlights
- **Secondary**: Black (#343a40) - Navigation and text
- **Accent**: Gray (#6c757d) - Secondary elements
- **Background**: White (#ffffff) - Clean interface

## Security Features

- **Password Hashing**: Bcrypt password hashing
- **Session Management**: Secure session handling
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Role-based Access**: Admin-only functions protected

## File Structure

```
BSU/
├── includes/
│   ├── db.php          # Database connection
│   └── session.php     # Session management
├── landing.php         # Landing/loading page (main entry)
├── index.php           # Login page
├── register.php        # User registration
├── dashboard.php       # Main dashboard
├── equipment.php       # Equipment management
├── departments.php     # Department management
├── maintenance.php     # Maintenance management
├── tasks.php          # Task management
├── reports.php        # Reports and analytics
├── users.php          # User management (admin)
├── profile.php        # User profile
├── logout.php         # Logout functionality
├── activity_log.php   # Activity log history
├── view_equipment.php # View equipment details
├── edit_equipment.php # Edit equipment
├── view_user.php      # View user details
├── edit_user.php      # Edit user information
├── default.php        # Default redirect
├── database.sql       # Database schema
└── README.md          # This file
```

## Usage Guide

### For Administrators
1. **Login** with admin credentials
2. **Manage Users**: Add, edit, delete user accounts
3. **Equipment Management**: Add and manage all equipment
4. **Department Management**: Manage departments and locations
5. **Generate Reports**: Create and download various reports
6. **System Monitoring**: View dashboard analytics

### For Technicians
1. **Login** with technician credentials
2. **View Assigned Tasks**: Check assigned maintenance tasks
3. **Update Maintenance**: Record maintenance activities
4. **Equipment Status**: Update equipment status
5. **View Reports**: Access relevant reports

## Default Data

The system comes with pre-configured data:
- **Default Admin**: admin@bsu.edu.ph / password
- **Departments**: All BSU colleges and departments
- **Equipment Categories**: Laptop, Desktop, Printer, Router, Access Point

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `includes/db.php`
   - Ensure MySQL service is running
   - Verify database `inventory_db` exists

2. **Page Not Found**
   - Check file permissions
   - Verify web server is running
   - Ensure files are in correct directory

3. **Login Issues**
   - Default admin: admin@bsu.edu.ph / password
   - Check if database tables are created
   - Verify session configuration

4. **QR Code Not Loading**
   - Check internet connection (uses external API)
   - Verify equipment has required data

## Support

For technical support or feature requests, please contact the development team.

## License

This project is developed for Batangas State University. All rights reserved.

---

**BSU Inventory Management System** - Streamlining equipment management for educational institutions. 