# EyeLearn Installation Guide

## 🚀 Quick Installation (Automated)

### Option 1: Automated Setup (Recommended)
```bash
# Clone the repository
git clone <repository-url>
cd capstone

# Run the automated setup script
python setup.py
```

The setup script will:
- ✅ Check system requirements
- ✅ Create Python virtual environment
- ✅ Install all dependencies
- ✅ Create configuration files
- ✅ Generate startup scripts
- ✅ Provide database setup instructions

---

## 🔧 Manual Installation

### Step 1: Prerequisites

#### All Platforms
- **Python 3.8+** - [Download from python.org](https://python.org)
- **XAMPP** - [Download from apachefriends.org](https://apachefriends.org)
- **Webcam** - Built-in or USB camera (720p minimum)

### Step 2: Platform-Specific Setup

#### 🪟 Windows Installation

1. **Install XAMPP**
   ```bash
   # Download XAMPP for Windows
   # Install to C:\xampp (default location)
   # Start Apache and MySQL services
   ```

2. **Install Python Dependencies**
   ```cmd
   cd capstone\python_services
   python -m venv venv
   venv\Scripts\activate
   pip install -r requirements.txt
   ```

3. **Install Visual C++ Redistributable** (if needed)
   - Download from Microsoft official site
   - Required for OpenCV and MediaPipe

#### 🍎 macOS Installation

1. **Install XAMPP**
   ```bash
   # Download XAMPP for macOS
   # Install to /Applications/XAMPP
   # Start Apache and MySQL from XAMPP Control Panel
   ```

2. **Install Xcode Command Line Tools**
   ```bash
   xcode-select --install
   ```

3. **Install Python Dependencies**
   ```bash
   cd capstone/python_services
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

#### 🐧 Linux (Ubuntu/Debian) Installation

1. **Install XAMPP**
   ```bash
   # Download XAMPP for Linux
   sudo chmod +x xampp-linux-x64-installer.run
   sudo ./xampp-linux-x64-installer.run
   sudo /opt/lampp/lampp start
   ```

2. **Install System Dependencies**
   ```bash
   sudo apt-get update
   sudo apt-get install python3-dev python3-venv cmake build-essential
   sudo apt-get install libgl1-mesa-glx libglib2.0-0 libsm6 libxext6 libxrender-dev libgomp1
   ```

3. **Install Python Dependencies**
   ```bash
   cd capstone/python_services
   python3 -m venv venv
   source venv/bin/activate
   pip install -r requirements.txt
   ```

### Step 3: Database Setup

1. **Access phpMyAdmin**
   - Open: http://localhost/phpmyadmin
   - Create database: `elearn_db`

2. **Automated Database Setup**
   ```bash
   # Copy project to XAMPP htdocs
   cp -r capstone/ /path/to/xampp/htdocs/

   # Run database setup
   # Visit: http://localhost/capstone/database_setup.php
   ```

3. **Manual Database Setup** (alternative)
   ```sql
   CREATE DATABASE elearn_db;
   USE elearn_db;
   -- Import database/elearn_db.sql if available
   ```

### Step 4: Configuration

1. **Copy Configuration Template**
   ```bash
   cp config.template.php config.php
   ```

2. **Update Database Settings** in `config.php`
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";  // Usually empty for XAMPP
   $dbname = "elearn_db";
   ```

### Step 5: Start Services

#### Windows
```cmd
# Start Eye Tracking Service
cd python_services
venv\Scripts\activate
python eye_tracking_service.py

# Or use the generated batch file
start_eyelearn.bat
```

#### macOS/Linux
```bash
# Start Eye Tracking Service
cd python_services
source venv/bin/activate
python eye_tracking_service.py

# Or use the generated shell script
./start_eyelearn.sh
```

---

## 🔍 Troubleshooting

### Common Issues

#### Python Dependencies
**Problem**: `pip install` fails
```bash
# Solutions:
pip install --upgrade pip
pip install --user -r requirements.txt
# For M1 Macs: use conda instead of pip for some packages
```

**Problem**: MediaPipe installation fails
```bash
# Solutions:
pip install mediapipe --no-deps
pip install numpy opencv-python
# Or try: conda install -c conda-forge mediapipe
```

#### Camera Issues
**Problem**: Camera not detected
- Close other applications using camera (Teams, Zoom, etc.)
- Check camera permissions in browser/system settings
- Try different USB ports for external cameras
- Update camera drivers

**Problem**: OpenCV camera errors
```python
# Test camera manually:
import cv2
cap = cv2.VideoCapture(0)
print(cap.isOpened())  # Should return True
cap.release()
```

#### Database Issues
**Problem**: Database connection fails
- Ensure MySQL is running in XAMPP
- Check database credentials in `config.php`
- Verify database `elearn_db` exists
- Check MySQL port (default: 3306)

**Problem**: Permission denied errors
```bash
# Linux: Set proper permissions
sudo chown -R www-data:www-data /opt/lampp/htdocs/capstone
sudo chmod -R 755 /opt/lampp/htdocs/capstone
```

#### Service Issues
**Problem**: Eye tracking service won't start
- Check if port 5000 is available
- Ensure Python virtual environment is activated
- Check Python path and dependencies
- Look for error messages in console

**Problem**: Flask CORS errors
```python
# Add to eye_tracking_service.py if needed:
from flask_cors import CORS
CORS(app, origins=['http://localhost'])
```

### Performance Optimization

#### For Better Eye Tracking
- **Lighting**: Ensure good, consistent lighting on face
- **Camera Position**: 18-24 inches from face, at eye level
- **Background**: Reduce background distractions
- **Browser**: Use Chrome or Edge for best camera support

#### For Better System Performance
- **RAM**: 8GB+ recommended for smooth operation
- **CPU**: Modern multi-core processor for real-time processing
- **Storage**: SSD recommended for database performance

---

## 🧪 Testing Installation

### Test Eye Tracking Service
```bash
# Test API endpoints
curl http://localhost:5000/api/health
curl http://localhost:5000/api/status
```

### Test Web Application
1. Visit: http://localhost/capstone
2. Login with: admin@admin.eyelearn / admin123
3. Test camera permissions
4. Start a learning module

### Test Database Connection
```php
<?php
// Create test_db.php in project root
include 'config.php';
echo "Database connection: " . ($conn->connect_error ? "Failed" : "Success");
?>
```

---

## 📦 Deployment Notes

### Production Deployment
1. **Security**: Change default passwords
2. **SSL**: Enable HTTPS for production
3. **Database**: Use proper MySQL credentials
4. **Logs**: Configure proper logging
5. **Backup**: Implement database backup strategy

### Environment Variables
```bash
# Optional: Use .env file for configuration
MYSQL_HOST=localhost
MYSQL_USER=root
MYSQL_PASSWORD=
MYSQL_DATABASE=elearn_db
FLASK_HOST=127.0.0.1
FLASK_PORT=5000
DEBUG_MODE=false
```

---

## 🆘 Getting Help

1. **Check Logs**: Look for error messages in:
   - XAMPP Apache/MySQL logs
   - Python console output
   - Browser developer console

2. **Diagnostic Tools**: Use built-in diagnostic pages:
   - http://localhost/capstone/focus_diagnosis.php
   - http://localhost/capstone/debug_database.php

3. **Community Support**: 
   - Check GitHub issues
   - Review documentation
   - Test with minimal setup first

---

**Installation complete! 🎉**

Access your EyeLearn platform at: http://localhost/capstone
