# Eye Tracking System Setup Guide

## Overview
This eye tracking system monitors user attention and focus time while viewing module content. It tracks when users are actively looking at the screen and pauses the timer when their gaze or focus is not on the screen.

## Features
- ✅ Real-time attention tracking
- ✅ Automatic pause/resume based on focus
- ✅ Visual timer display
- ✅ Session persistence
- ✅ Analytics dashboard for admins
- ✅ Cross-tab detection
- ✅ Mouse movement tracking
- ✅ Keyboard activity detection

## Installation Steps

### 1. Database Setup
Run the database setup script to create the necessary tables:
```
Navigate to: http://your-domain/capstone/user/database/setup_eye_tracking.php
```

### 2. File Structure
Ensure all files are in the correct locations:
```
capstone/
├── user/
│   ├── js/
│   │   └── eye-tracking.js
│   ├── database/
│   │   ├── save_eye_tracking_data.php
│   │   ├── get_eye_tracking_data.php
│   │   └── setup_eye_tracking.php
│   ├── Smodule.php (modified)
│   └── Smodulepart.php (modified)
├── admin/
│   └── eye_tracking_analytics.php
└── database/
    └── eye_tracking_schema.sql
```

### 3. Testing
1. Navigate to any module page
2. You should see a timer widget in the bottom-right corner
3. Try switching tabs or clicking away to see the timer pause
4. Return focus to see it resume

## How It Works

### Detection Methods
1. **Page Visibility API**: Detects tab switching, window minimizing
2. **Focus/Blur Events**: Detects when window gains/loses focus
3. **Mouse Tracking**: Tracks mouse movement within content areas
4. **Scroll Detection**: Detects active scrolling behavior
5. **Keyboard Activity**: Monitors keyboard interactions

### Timer States
- 🟢 **Active**: User is focused and engaging with content
- ⏸️ **Paused**: User attention is elsewhere

### Data Storage
- Real-time session data saved every 30 seconds
- Historical data preserved for analytics
- Daily aggregations for performance insights

## Admin Analytics

Access the analytics dashboard at:
```
http://your-domain/capstone/admin/eye_tracking_analytics.php
```

### Analytics Features
- Student engagement rankings
- Module performance statistics
- Session duration analysis
- Activity timeline tracking

## Technical Implementation

### JavaScript Classes
- `EyeTrackingSystem`: Main tracking logic
- Event listeners for various attention indicators
- Automatic data persistence

### PHP Backend
- `save_eye_tracking_data.php`: Store session data
- `get_eye_tracking_data.php`: Retrieve historical data
- `eye_tracking_analytics.php`: Admin dashboard

### Database Schema
- `eye_tracking_sessions`: Raw session data
- `eye_tracking_analytics`: Aggregated analytics

## Customization Options

### Timing Settings
```javascript
// In eye-tracking.js, modify these values:
this.saveInterval = 30; // Save every 30 seconds
const inactivityTimeout = 30000; // 30 seconds inactivity
```

### Visual Customization
Modify the timer display in the `displayTimer()` method to change appearance.

### Analytics Customization
Add new metrics in the analytics dashboard by modifying the SQL queries.

## Troubleshooting

### Common Issues
1. **Timer not appearing**: Check console for JavaScript errors
2. **Data not saving**: Verify database connection and permissions
3. **Inaccurate tracking**: Check browser compatibility

### Browser Compatibility
- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Limited (no Page Visibility API on older versions)

### Performance Considerations
- Minimal impact on page performance
- Efficient event handling
- Batched data saves to reduce server requests

## Security Notes
- User authentication required for all endpoints
- SQL injection protection with prepared statements
- Session-based access control

## Future Enhancements
- Machine learning attention patterns
- Gaze tracking via webcam (WebRTC)
- Mobile app integration
- Advanced analytics with charts
- Real-time alerts for low engagement

## Support
For issues or questions, check the browser console for error messages and verify database connectivity.
