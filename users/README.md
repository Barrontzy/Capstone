# User Management System

A comprehensive PHP-based user management system with task assignment, QR code functionality, and equipment tracking.

## Features

### User Management
- **Registration**: Full name, email, role (admin/technician), phone number, password with confirmation
- **Login**: Secure authentication with session management
- **Profile Management**: View and update user information

### Task Management (Kanban Dashboard)
- **Admin Features**:
  - Create, edit, and delete tasks
  - Assign tasks to users
  - Set priority levels (low, medium, high, urgent)
  - Set due dates
  - Link tasks to equipment
- **User Features**:
  - View assigned tasks in Kanban board
  - Update task status (pending, in progress, completed, cancelled)
  - Track task progress

### QR Code Functionality
- **Scan QR Codes**: Real-time QR code scanning using device camera
- **Upload QR Images**: Upload and process QR code images
- **Manual Input**: Manually enter QR codes
- **Equipment Tracking**: Link QR codes to equipment for tracking

### History Tracking
- **Task History**: Complete history of all task activities
- **Equipment History**: Track all equipment interactions
- **User Activity**: Monitor user actions and equipment scans

### Admin Dashboard
- **User Management**: View and manage all users
- **Task Management**: Create and assign tasks to users
- **Equipment Management**: Add and manage equipment with QR codes
- **Statistics**: View system statistics and user activity

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **QR Code**: HTML5 QR Code Scanner
- **Icons**: Font Awesome 6.0
- **Styling**: Custom CSS with red, black, gray, white color scheme

## Installation

### Prerequisites
- XAMPP, WAMP, or similar local server environment
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web browser with camera access (for QR scanning)

### Setup Instructions

1. **Clone/Download the Project**
   ```bash
   # Place the project in your web server directory
   # For XAMPP: C:\xampp\htdocs\users\
   # For WAMP: C:\wamp\www\users\
   ```

2. **Database Setup**
   - Open phpMyAdmin or your MySQL client
   - Create a new database named `user_management_system`
   - Import the `database.sql` file to create all tables

3. **Configuration**
   - Open `config/database.php`
   - Update database connection settings if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_NAME', 'user_management_system');
     define('DB_USER', 'root');
     define('DB_PASS', '');
     ```

4. **Access the Application**
   - Start your web server (Apache)
   - Open your browser and navigate to:
     ```
     http://localhost/users/
     ```

5. **Default Admin Account**
   - Email: `admin@system.com`
   - Password: `password`
   - **Important**: Change the default password after first login

## File Structure

```
users/
├── admin/
│   └── tasks.php          # Admin task management
├── assets/
│   ├── css/
│   │   └── style.css      # Main stylesheet
│   └── js/
│       └── main.js        # JavaScript functionality
├── config/
│   └── database.php       # Database configuration
├── index.php              # Main dashboard
├── login.php              # Login page
├── register.php           # Registration page
├── logout.php             # Logout functionality
├── tasks.php              # User task management
├── qr.php                 # QR code scanner
├── history.php            # History tracking
├── profile.php            # User profile
├── database.sql           # Database schema
└── README.md              # This file
```

## Usage

### For Administrators

1. **Login** with admin credentials
2. **Dashboard** shows system overview and quick actions
3. **Manage Tasks**:
   - Click "Add New Task" to create tasks
   - Assign tasks to users
   - Set priority and due dates
   - Link tasks to equipment
4. **Monitor Activity**:
   - View task progress
   - Track user activity
   - Monitor equipment usage

### For Technicians

1. **Register** or login with your account
2. **Dashboard** shows your task overview
3. **My Tasks**:
   - View assigned tasks in Kanban board
   - Update task status as you work
   - Track progress and completion
4. **QR Scanner**:
   - Scan equipment QR codes
   - Upload QR images
   - Manually enter QR codes
5. **History**:
   - View your task history
   - Track equipment interactions
6. **Profile**:
   - Update personal information
   - Change password

## Security Features

- **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
- **Session Management**: Secure session handling
- **Input Validation**: All user inputs are validated and sanitized
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: Output escaping for all displayed data

## Color Scheme

The application uses a consistent color scheme:
- **Primary Red**: `#dc3545` - Main actions and highlights
- **Black**: `#000000` - Headers and important text
- **Dark Gray**: `#343a40` - Secondary text
- **Gray**: `#6c757d` - Muted text
- **Light Gray**: `#e9ecef` - Borders and backgrounds
- **White**: `#ffffff` - Background and cards

## Browser Compatibility

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database configuration in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name and credentials

2. **QR Scanner Not Working**
   - Ensure HTTPS or localhost (camera access requires secure context)
   - Check browser permissions for camera access
   - Try using manual input as alternative

3. **Session Issues**
   - Check PHP session configuration
   - Clear browser cookies and cache
   - Restart web server

4. **File Permissions**
   - Ensure web server has read access to all files
   - Check write permissions for session storage

### Performance Tips

- Use a modern web browser for best performance
- Keep the application updated
- Regularly backup the database
- Monitor server resources

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions:
- Check the troubleshooting section above
- Review the code comments for implementation details
- Ensure all prerequisites are met

---

**Note**: This system is designed for internal use and should be deployed in a secure environment with proper access controls. 