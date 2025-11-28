#!/bin/bash
# Startup script for FastAPI service on Railway
# This ensures Python and uvicorn are found correctly

# Use python3 explicitly
exec python3 -m uvicorn fastapi_service:app --host 0.0.0.0 --port ${PORT:-8000} --workers 1

