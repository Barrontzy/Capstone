# Technician Portal - Flutter App

A comprehensive Flutter application that replicates the PHP technician management system with the same design and functionality. This app provides a modern, mobile-first interface for technicians to manage tasks, scan QR codes, view equipment history, and manage their profiles.

## Features

### 🏠 Dashboard (Kanban Board)
- **Real-time Task Management**: View tasks in a kanban-style board with three columns: Pending, In Progress, and Completed
- **Auto-refresh**: Automatically updates task status every 10 seconds
- **Task Creation**: Add new tasks with title, description, priority, and due date
- **Status Updates**: Move tasks between columns with confirmation dialogs
- **Priority System**: Color-coded priority badges (Low, Medium, High, Urgent)

### 📋 My Tasks
- **Assigned Tasks**: View all tasks assigned to the current technician
- **Task Reports**: Add progress reports and updates to tasks
- **Status Management**: Update task status with detailed tracking
- **Report History**: View all reports submitted for each task

### 📱 QR Code Scanner
- **Camera Scanner**: Scan QR codes using device camera
- **File Upload**: Upload QR code files (TXT, JSON, XML)
- **Manual Entry**: Type QR code data manually
- **Equipment Lookup**: Find equipment details by QR code
- **QR Generation**: Generate QR codes for equipment
- **Download Options**: Download QR codes in PNG, JPG, or SVG formats

### 📚 Equipment History
- **Filtered View**: Filter equipment by status and date range
- **Maintenance Records**: View maintenance history for each equipment
- **Export Functionality**: Export equipment history to CSV
- **Detailed Information**: View equipment specifications, location, and status

### 👤 Profile Management
- **Personal Information**: Update name, email, and phone number
- **Password Change**: Secure password change with current password verification
- **Statistics Dashboard**: View equipment count, task count, and maintenance records
- **Quick Actions**: Easy access to all app features

## Design Features

### 🎨 Visual Design
- **Consistent Theme**: Matches the PHP version's color scheme and design
- **Material Design 3**: Modern UI components with smooth animations
- **Responsive Layout**: Works on all screen sizes
- **Dark/Light Theme**: Support for both themes with automatic switching

### 🚀 Performance
- **State Management**: Uses Provider for efficient state management
- **Auto-refresh**: Configurable auto-refresh for real-time updates
- **Offline Support**: Caches data for offline viewing
- **Smooth Animations**: Flutter Animate for polished transitions

### 🔒 Security
- **Secure Storage**: Uses Flutter Secure Storage for sensitive data
- **Token-based Auth**: JWT token authentication
- **Input Validation**: Comprehensive form validation
- **Error Handling**: Graceful error handling with user-friendly messages

## Installation & Setup

### Prerequisites
- Flutter SDK (3.0.0 or higher)
- Dart SDK
- Android Studio / VS Code
- Android SDK (for Android development)
- Xcode (for iOS development, macOS only)

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd technician_portal
   ```

2. **Install dependencies**
   ```bash
   flutter pub get
   ```

3. **Configure API endpoints**
   - Open `lib/utils/constants.dart`
   - Update `baseUrl` to point to your PHP backend
   - Ensure your PHP backend is running and accessible

4. **Run the app**
   ```bash
   flutter run
   ```

### Backend Configuration

The Flutter app communicates with the PHP backend. Ensure your PHP backend provides these endpoints:

- `POST /api/login.php` - User authentication
- `POST /api/task_webhook.php` - Task management
- `POST /api/equipment.php` - Equipment operations
- `POST /api/profile.php` - Profile management
- `POST /api/reports.php` - Task reports

## Project Structure

```
lib/
├── main.dart                 # App entry point
├── models/                   # Data models
│   ├── user.dart
│   ├── task.dart
│   ├── equipment.dart
│   └── task_report.dart
├── providers/               # State management
│   ├── auth_provider.dart
│   ├── task_provider.dart
│   ├── equipment_provider.dart
│   └── theme_provider.dart
├── screens/                 # UI screens
│   ├── splash_screen.dart
│   ├── login_screen.dart
│   └── home_screen.dart
├── widgets/                 # Reusable widgets
│   ├── task_card.dart
│   ├── add_task_modal.dart
│   └── bottom_navigation.dart
└── utils/                   # Utilities
    ├── constants.dart
    └── theme.dart
```

## Dependencies

### Core Dependencies
- `flutter`: Flutter SDK
- `provider`: State management
- `http`: HTTP requests
- `shared_preferences`: Local storage
- `flutter_secure_storage`: Secure storage

### UI & Animation
- `flutter_animate`: Smooth animations
- `intl`: Internationalization and date formatting
- `flutter_staggered_grid_view`: Advanced grid layouts

### QR & Camera
- `qr_flutter`: QR code generation
- `mobile_scanner`: QR code scanning
- `image_picker`: File selection

### Additional Features
- `connectivity_plus`: Network connectivity
- `flutter_local_notifications`: Push notifications
- `workmanager`: Background tasks
- `permission_handler`: Device permissions

## Configuration

### API Configuration
Update the API base URL in `lib/utils/constants.dart`:

```dart
static const String baseUrl = 'http://your-server.com/bsu/technician/api';
```

### Theme Configuration
The app uses a consistent theme matching the PHP version. Colors and styles are defined in `lib/utils/constants.dart` and `lib/utils/theme.dart`.

### Auto-refresh Settings
The auto-refresh interval can be configured in `lib/utils/constants.dart`:

```dart
static const Duration autoRefreshInterval = Duration(seconds: 10);
```

## Usage

### Authentication
1. Launch the app
2. Enter your technician email and password
3. Tap "Login as Technician"
4. The app will remember your login for future sessions

### Task Management
1. **View Tasks**: The dashboard shows all tasks in a kanban board
2. **Add Task**: Tap "Add Task" to create a new task
3. **Update Status**: Tap "Start" or "Complete" buttons on task cards
4. **Auto-refresh**: Toggle auto-refresh in the app bar

### QR Code Scanning
1. Navigate to the QR tab
2. Use camera scanner, file upload, or manual entry
3. View equipment details after successful scan
4. Generate and download QR codes for equipment

### Profile Management
1. Navigate to the Profile tab
2. Update personal information
3. Change password securely
4. View statistics and quick actions

## Troubleshooting

### Common Issues

1. **Network Errors**
   - Check your internet connection
   - Verify the API base URL is correct
   - Ensure the PHP backend is running

2. **Authentication Issues**
   - Clear app data and re-login
   - Check server authentication endpoints
   - Verify user credentials

3. **QR Scanner Issues**
   - Grant camera permissions
   - Ensure QR codes are properly formatted
   - Check file upload permissions

4. **Build Issues**
   - Run `flutter clean` and `flutter pub get`
   - Check Flutter SDK version
   - Verify all dependencies are compatible

### Debug Mode
Enable debug mode for detailed logging:

```bash
flutter run --debug
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support and questions:
- Create an issue in the repository
- Contact the development team
- Check the documentation

## Version History

- **v1.0.0**: Initial release with core functionality
  - Kanban board task management
  - QR code scanning and generation
  - Equipment history tracking
  - Profile management
  - Real-time updates

---

**Note**: This Flutter app is designed to work seamlessly with the existing PHP backend. Ensure your backend provides the required API endpoints and follows the expected data format. 