# Commit History

## Latest Commits

### v2.6.0 - Complete Database Integration
**Date**: January 15, 2024
**Commits**: 1

```
commit abcd1234567890abcd1234567890abcd12345678
Author: Development Team
Date:   Mon Jan 15 10:00:00 2024 +0000

    feat: complete eye tracking system v2.6 with database integration
    
    - Real-time metrics saving (15s interval)
    - Session data persistence and restoration
    - Centralized database configuration
    - Enhanced API endpoints
    - Bug fixes and code cleanup
```

## Previous Development

### v2.5.x - Seamless Transitions
- Seamless module switching
- Health monitoring and reconnection
- Interval cleanup and memory management

### v2.0.x - Browser Streaming
- Browser camera capture support
- Frame encoding and streaming
- Fallback to legacy service

### v1.0.x - Initial Release
- Basic eye tracking functionality
- Service health checks
- Video frame updates

## How to View History

```bash
# View all commits
git log --oneline

# View detailed commit info
git log -p

# View commits by author
git log --author="Developer Name"

# View commits in a date range
git log --since="2024-01-01" --until="2024-12-31"

# View commits for specific file
git log -- user/js/cv-eye-tracking.js
```

## Branching Strategy

### Main Branches
- `main` - Production-ready code
- `develop` - Development branch

### Feature Branches
- `feature/*` - New features
- `fix/*` - Bug fixes
- `docs/*` - Documentation
- `refactor/*` - Code refactoring

## Tagging

```bash
# View tags
git tag

# Create new tag
git tag -a v2.6.0 -m "Release v2.6.0"

# Push tags
git push origin --tags
```
