# Railway MySQL Deployment Guide

## Overview

This document describes how the EyeLearn application is configured to connect to Railway MySQL database.

## Database Configuration

The application uses environment variables to configure the database connection, allowing it to work with both local development (Docker) and Railway production environments.

### Environment Variables

The following environment variables are used for database configuration:

```env
DB_HOST=caboose.proxy.rlwy.net
DB_PORT=31049
DB_USER=root
DB_PASS=NBONxXGqkEenURWCmKtTJAVywtwEduKD
DB_NAME=railway
```

### Configuration Files

1. **`.env`** (Root directory) - Main environment configuration
2. **`user/.env`** - User-specific configuration (includes Gemini API key)
3. **`.env.example`** - Template for environment variables

## Database Connection System

### How It Works

1. **Environment Loader** (`user/load_env.php`)
   - Loads environment variables from `.env` files
   - Checks both `user/.env` and root `.env`
   - Sets variables in `$_ENV`, `$_SERVER`, and via `putenv()`

2. **Database Connection** (`database/db_connection.php`)
   - Loads the environment variables
   - Reads from environment variables with fallback to defaults
   - Supports both PDO and mysqli connections
   - Priority: Docker/Railway env vars > `.env` file > localhost defaults

### Usage

```php
// Include database connection
require_once __DIR__ . '/database/db_connection.php';

// Get PDO connection
$pdo = getPDOConnection();

// Get mysqli connection
$conn = getMysqliConnection();
```

## Database Schema

The database consists of **17 tables**:

### Core Tables
- `users` - User accounts and authentication
- `modules` - Learning modules
- `module_parts` - Module sections/parts
- `module_sections` - Detailed module sections

### Quiz & Assessment Tables
- `final_quizzes` - Final assessments
- `final_quiz_questions` - Quiz questions
- `final_quiz_retakes` - Retake requests
- `checkpoint_quizzes` - Checkpoint quizzes
- `checkpoint_quiz_questions` - Checkpoint questions
- `checkpoint_quiz_results` - Quiz results
- `module_completions` - Module completion tracking

### Eye Tracking Tables
- `eye_tracking_sessions` - Tracking sessions
- `eye_tracking_analytics` - Analytics data
- `eye_tracking_data` - Raw tracking data
- `focus_events` - Focus/unfocus events
- `daily_analytics` - Daily aggregated data

### AI & Assessment Tables
- `ai_recommendations` - AI-generated feedback
- `assessments` - Assessment definitions

## Importing Schema to Railway

To import or update the database schema on Railway:

```bash
php import_schema_to_railway.php
```

This script:
- Connects to Railway MySQL
- Imports the `database/elearn_db.sql` file
- Handles large files and complex SQL statements
- Shows progress and error reporting
- Creates all tables with data

## Testing Database Connection

To verify the database connection:

```bash
php test_db_connection.php
```

This will:
- Display current configuration
- Test the connection
- List all tables and row counts

## Docker Compose Support

The `docker-compose.yml` file has been updated to support environment variables:

```yaml
environment:
  - DB_HOST=${DB_HOST:-db}
  - DB_NAME=${DB_NAME:-elearn_db}
  - DB_USER=${DB_USER:-root}
  - DB_PASS=${DB_PASS:-rootpassword}
  - DB_PORT=${DB_PORT:-3306}
```

This allows the same configuration to work for:
- **Local Development**: Uses Docker's MySQL service (default values)
- **Railway Production**: Uses environment variables from `.env`

## Switching Between Environments

### For Local Development (Docker)
Comment out Railway credentials in `.env` and let Docker Compose use defaults.

### For Railway Production
Keep Railway credentials in `.env` file.

### For XAMPP/Local PHP
The system will automatically fall back to `localhost` MySQL if Railway connection fails and the script is running on localhost.

## Security Notes

1. **Never commit `.env` files** to public repositories
2. Use `.env.example` as a template
3. Railway credentials are specific to your deployment
4. Rotate database passwords regularly

## Database Information

- **Provider**: Railway MySQL
- **Version**: MySQL 9.4.0
- **Host**: caboose.proxy.rlwy.net
- **Port**: 31049
- **Database**: railway

## Troubleshooting

### Connection Errors

If you see connection errors:

1. Verify environment variables are loaded:
   ```bash
   php -r "require 'user/load_env.php'; echo getenv('DB_HOST');"
   ```

2. Test the connection:
   ```bash
   php test_db_connection.php
   ```

3. Check Railway dashboard for database status

### Schema Issues

If tables are missing or outdated:

1. Re-import the schema:
   ```bash
   php import_schema_to_railway.php
   ```

2. Verify tables:
   ```bash
   php test_db_connection.php
   ```

## Additional Resources

- [Railway Documentation](https://docs.railway.app/)
- [MySQL 9.4 Documentation](https://dev.mysql.com/doc/)
- Project Repository: https://github.com/0322-2197-stack/EyeLearn-Env

---

**Last Updated**: 2025-11-28
**Status**: ✅ Production Ready
