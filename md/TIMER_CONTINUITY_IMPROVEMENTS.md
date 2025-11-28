# Eye Tracking Timer Continuity Improvements

## Overview
The eye tracking system has been enhanced to maintain timer continuity across module sections. Previously, the timer would reset when switching between sections within a module. Now, the timer shows the cumulative time spent across all sections of a module.

## Key Changes Made

### 1. Database Query Enhancement (`get_eye_tracking_data.php`)
- Added `module_total_only` parameter to support module-wide queries
- When `module_total_only=true`, the query aggregates time across all sections of a module
- Maintains backward compatibility for section-specific queries

### 2. Timer Loading Logic (`cv-eye-tracking.js`)
- Modified `loadPreviousData()` method to load total module time instead of section-specific time
- Timer now shows cumulative time across all sections when switching between sections

### 3. Display Updates
- Enhanced `updateTrackingDisplay()` to show total accumulated time (previous total + current session)
- Session time now reflects the full module progress, not just current section
- Both session-time and total-time displays show the same cumulative value

### 4. Section Switching
- Added static method `CVEyeTrackingSystem.handleSectionChange()` for seamless transitions
- Section switches now reload module total time to maintain continuity
- Enhanced existing `switchSection()` method to call `loadPreviousData()`

### 5. Navigation Integration (`Smodulepart.php`)
- Updated navigation button and section link event handlers
- Uses the new static method for better instance management
- Proper initialization flow for new pages

## How It Works

### Data Storage
- Eye tracking sessions are still stored per section in the database
- Each section maintains its own tracking records for granular analytics
- The system can query either section-specific or module-total data as needed

### Timer Display Logic
```javascript
// Before: Showed only current section time
sessionTime.textContent = this.formatTime(Math.floor(status.total_time || 0));

// After: Shows cumulative time across all sections
const currentSessionTime = Math.floor(status.total_time || 0);
const totalAccumulated = this.totalTime + currentSessionTime;
sessionTime.textContent = this.formatTime(totalAccumulated);
```

### Section Navigation Flow
1. User clicks navigation button or section link
2. `CVEyeTrackingSystem.handleSectionChange()` is called
3. Method calls `switchSection()` on existing tracker instance
4. `switchSection()` updates module/section IDs and calls `loadPreviousData()`
5. `loadPreviousData()` queries for total module time with `module_total_only=true`
6. Timer display continues showing cumulative time

## Benefits

### For Users
- **Continuous Experience**: Timer shows true time spent on the entire module
- **No Timer Resets**: Switching sections doesn't lose previous progress
- **Accurate Tracking**: Total learning time is properly accumulated

### For Analytics
- **Granular Data**: Section-specific data is still available for detailed analysis
- **Module Totals**: Easy to get overall module engagement time
- **Flexible Queries**: Can query either section-specific or module-wide data

### For Developers
- **Backward Compatibility**: Existing queries still work with section filtering
- **Clean Architecture**: Separation between data storage and display logic
- **Easy Maintenance**: Clear separation of concerns

## Testing

Use the test file `test_timer_continuity.html` to verify:

1. **Basic Functionality**
   - Start eye tracking on Section 1
   - Let it accumulate some time (e.g., 30 seconds)
   - Switch to Section 2
   - Verify timer shows accumulated time from Section 1 + new time

2. **Cross-Section Continuity**
   - Continue tracking on Section 2 for additional time
   - Switch to Section 3
   - Verify timer shows total time from Sections 1 + 2 + current time

3. **Database Persistence**
   - Refresh the page after accumulating time
   - Verify the timer loads the previous total module time correctly

## Database Schema

The system uses the existing `eye_tracking_sessions` table structure:

```sql
CREATE TABLE eye_tracking_sessions (
    id int(11) AUTO_INCREMENT PRIMARY KEY,
    user_id int(11) NOT NULL,
    module_id int(11) NOT NULL,
    section_id int(11) DEFAULT NULL,  -- Still tracks per section
    total_time_seconds int(11) DEFAULT 0,
    session_type enum('viewing','pause','resume','cv_tracking') DEFAULT 'cv_tracking',
    created_at timestamp DEFAULT CURRENT_TIMESTAMP,
    last_updated timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## API Changes

### New Query Parameter
- `GET /get_eye_tracking_data.php?module_id=1&module_total_only=true`
- Returns total time across all sections for the module

### Backward Compatibility
- `GET /get_eye_tracking_data.php?module_id=1&section_id=2` (existing behavior)
- Still returns section-specific data when needed

## Future Enhancements

1. **Real-time Sync**: Consider WebSocket updates for multi-tab scenarios
2. **Offline Support**: Cache timing data locally for offline scenarios
3. **Advanced Analytics**: Add more granular timing metrics (focus time vs. total time)
4. **User Preferences**: Allow users to choose between section-specific or module-total timer display
