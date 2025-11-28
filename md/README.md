# EyeLearn - E-Learning Platform with Real-Time Eye Tracking

[![Python](https://img.shields.io/badge/Python-3.8%2B-blue.svg)](https://python.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🎯 Overview

EyeLearn is an innovative e-learning platform that combines traditional learning management with advanced real-time eye tracking technology. The system monitors student focus and attention patterns to provide comprehensive analytics for both students and administrators.

### ✨ Key Features

- **📚 Learning Management System**: Complete module-based learning platform
- **👁️ Real-Time Eye Tracking**: Advanced computer vision-based focus monitoring
- **📊 Analytics Dashboard**: Comprehensive student performance analytics
- **🎯 Focus Detection**: Real-time attention monitoring with visual feedback
- **📈 Admin Insights**: Detailed analytics for educators and administrators
- **🔒 User Management**: Secure authentication and user roles
- **📱 Responsive Design**: Works on desktop and mobile devices

## 🏗️ System Architecture

### Frontend
- **PHP/HTML/CSS/JavaScript**: Main web application
- **Chart.js**: Interactive analytics visualizations
- **Tailwind CSS**: Modern responsive styling
- **Real-time API Integration**: Live data updates

### Backend Services
- **PHP**: Web server and database operations
- **MySQL**: Primary database for user data and analytics
- **Python Flask**: Eye tracking microservice
- **MediaPipe/OpenCV**: Computer vision processing

### Eye Tracking Engine
- **MediaPipe**: Advanced facial landmark detection
- **OpenCV**: Computer vision and image processing
- **Real-time Processing**: ~30 FPS tracking with live feedback
- **Focus Analytics**: Detailed attention metrics and reporting

## 🚀 Quick Start

### Prerequisites

1. **XAMPP** (Windows/Mac/Linux)
   - Apache 2.4+
   - PHP 7.4+
   - MySQL 8.0+

2. **Python 3.8+**
   - Required for eye tracking service

3. **Webcam**
   - Built-in or external USB camera
   - Minimum 720p resolution recommended

### 🔧 Installation

#### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd capstone
```

#### Step 2: Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database called `elearn_db`
4. Import the database schema:
   ```sql
   -- Import database/elearn_db.sql
   ```

#### Step 3: Web Application Setup
1. Copy the project to your XAMPP htdocs folder:
   ```bash
   cp -r capstone/ C:/xampp/htdocs/
   ```

2. Update database configuration in `config.php`:
   ```php
   <?php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "elearn_db";
   ?>
   ```

#### Step 4: Python Environment Setup
1. Navigate to the Python services directory:
   ```bash
   cd python_services
   ```

2. Create a virtual environment (recommended):
   ```bash
   python -m venv venv
   
   # Windows
   venv\Scripts\activate
   
   # Mac/Linux
   source venv/bin/activate
   ```

3. Install Python dependencies:
   ```bash
   pip install -r requirements.txt
   ```

#### Step 5: Start the Services

1. **Start the Eye Tracking Service**:
   ```bash
   cd python_services
   python eye_tracking_service.py
   ```
   
   You should see:
   ```
   Starting Enhanced Eye Tracking Service v2.0...
   * Running on http://127.0.0.1:5000
   ```

2. **Access the Web Application**:
   - Open your browser and go to: http://localhost/capstone
   - Login with default admin credentials (see Database Setup section)

## 👥 User Accounts

### Default Admin Account
- **Username**: admin@admin.eyelearn
- **Password**: admin123

### Creating Student Accounts
1. Use the registration page: http://localhost/capstone/register.php
2. Or create accounts via the admin panel

## 🎮 Usage Guide

### For Students
1. **Login** to your account
2. **Browse Modules** available for learning
3. **Start Learning** - the eye tracking will automatically begin with a 3-second countdown
4. **Focus on the Screen** - stay within the green focus zone for optimal tracking
5. **View Your Progress** in the dashboard

### For Administrators
1. **Login** with admin credentials
2. **Access Admin Dashboard** for comprehensive analytics
3. **View Student Performance** with focus time metrics
4. **Monitor Real-time Data** with live dashboard updates
5. **Manage Students** via the student management panel

## 🔍 Eye Tracking Features

### Real-Time Monitoring
- **3-Second Countdown**: Preparation time before tracking starts
- **Focus Zones**: Visual indicators for optimal screen attention
- **Live Feedback**: Real-time focus status display
- **Session Continuity**: Continuous tracking across module sections

### Advanced Analytics
- **Focus Time Calculation**: Accurate measurement of attention periods
- **Session Tracking**: Detailed logs of learning sessions
- **Performance Metrics**: Focus percentages and engagement scores
- **Comparative Analysis**: Gender-based and module-based insights

### Quality Assurance
- **Data Filtering**: Removes invalid sessions (< 30 seconds or > 2 hours)
- **Smooth Detection**: Advanced algorithms prevent false positives
- **Error Recovery**: Automatic camera reconnection and error handling

## 📊 API Endpoints

### Eye Tracking Service (Port 5000)
- `POST /api/start_tracking` - Start eye tracking session
- `POST /api/stop_tracking` - Stop current session
- `GET /api/status` - Get current tracking status
- `GET /api/frame` - Get current camera frame
- `GET /api/metrics` - Get detailed analytics
- `POST /api/switch_section` - Change section without stopping tracking

### Web Application APIs
- `GET /admin/database/get_dashboard_data.php` - Admin dashboard data
- `GET /admin/database/get_students.php` - Student management data
- Various other endpoints for user management and analytics

## 🛠️ Troubleshooting

### Common Issues

#### Camera Not Working
1. **Check Camera Permissions**: Ensure browser/system has camera access
2. **Close Other Applications**: Teams, Zoom, etc. might be using the camera
3. **Try Different Browsers**: Chrome/Edge work best for camera access
4. **Restart Services**: Stop and restart the Python eye tracking service

#### Eye Tracking Not Accurate
1. **Check Lighting**: Ensure good lighting on your face
2. **Position Camera**: Camera should be at eye level, 18-24 inches away
3. **Stay in Focus Zone**: Keep your gaze within the green tracking area
4. **Clean Data**: Use the diagnostic tools to clean invalid sessions

#### Database Connection Issues
1. **Verify MySQL**: Ensure MySQL service is running in XAMPP
2. **Check Credentials**: Verify database configuration in `config.php`
3. **Database Exists**: Ensure `elearn_db` database is created and populated

#### Python Service Issues
1. **Check Dependencies**: Ensure all requirements are installed
2. **Port Conflicts**: Make sure port 5000 is not in use
3. **Camera Drivers**: Update camera drivers if needed
4. **Python Version**: Ensure Python 3.8+ is installed

## 🔧 Development

### Project Structure
```
capstone/
├── admin/                 # Admin panel and dashboard
├── user/                  # Student interface
├── python_services/       # Eye tracking service
├── database/             # Database scripts and backups
├── config.php            # Database configuration
├── index.php             # Main entry point
└── README.md             # This file
```

### Adding New Features
1. **Web Features**: Add to PHP files in respective directories
2. **Eye Tracking Features**: Modify `python_services/eye_tracking_service.py`
3. **Database Changes**: Update schema and migration scripts
4. **API Extensions**: Add new endpoints to Flask service or PHP APIs

### Testing
1. **Focus Diagnosis**: Visit `http://localhost/capstone/focus_diagnosis.php`
2. **Data Cleaning**: Use the diagnostic tools for data quality checks
3. **API Testing**: Test individual endpoints with Postman or curl

## 📋 System Requirements

### Minimum Requirements
- **OS**: Windows 10, macOS 10.14, Ubuntu 18.04
- **RAM**: 4GB (8GB recommended)
- **Storage**: 2GB free space
- **Camera**: 720p webcam
- **Browser**: Chrome 80+, Firefox 75+, Edge 80+

### Recommended Specifications
- **RAM**: 8GB or more
- **Camera**: 1080p webcam with good low-light performance
- **Lighting**: Consistent lighting on user's face
- **Internet**: Stable connection for real-time features

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For issues and support:
1. Check the troubleshooting section above
2. Review system logs in XAMPP and Python console
3. Use the diagnostic tools provided in the system
4. Create an issue in the repository

## 🙏 Acknowledgments

- **MediaPipe**: Google's framework for building perception pipelines
- **OpenCV**: Computer vision library
- **Chart.js**: Beautiful JavaScript charts
- **Tailwind CSS**: Utility-first CSS framework

---

**Built with ❤️ for modern education technology**
