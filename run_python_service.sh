#!/bin/bash
# Run Python eye tracking service on host (outside Docker)
# This allows direct camera access which Docker can't provide

echo "Starting Python Eye Tracking Service on host..."
echo ""
echo "Make sure you have Python installed and dependencies installed:"
echo "  pip install -r python_services/requirements.txt"
echo ""

cd "$(dirname "$0")"
python3 python_services/eye_tracking_service.py

