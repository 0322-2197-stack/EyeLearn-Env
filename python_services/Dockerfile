# FastAPI Eye Tracking Service Dockerfile
FROM python:3.12-slim

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    PORT=8000

# Install system dependencies required for OpenCV + MediaPipe
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    ffmpeg \
    libsm6 \
    libxext6 \
    libgl1 \
    libglib2.0-0 \
    libgomp1 \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy dependency file first for better caching
COPY requirements.txt .

# Install Python dependencies
RUN pip install --upgrade pip && \
    pip install --no-cache-dir -r requirements.txt

# Copy the rest of the application files
COPY . .

# Make startup script executable
RUN chmod +x start.sh || true

# Expose port (Railway will set PORT env var)
EXPOSE 8000

# Use python directly (python3.12-slim uses 'python' not 'python3')
# Railway will override PORT via environment variable
# Using shell form to allow PORT variable substitution
CMD ["/bin/bash", "-c", "python -m uvicorn fastapi_service:app --host 0.0.0.0 --port ${PORT:-8000} --workers 1"]

