# EyeLearn Cloud Deployment Guide

## Heroku Deployment (Free Tier Available)
```bash
# Install Heroku CLI
# Create Procfile
echo "web: vendor/bin/heroku-php-apache2 public/" > Procfile

# Create composer.json
{
  "require": {
    "php": "^7.4.0",
    "ext-mysqli": "*"
  }
}

# Deploy
heroku create your-eyellearn-app
heroku addons:create cleardb:ignite
git push heroku main
```

## AWS EC2 Deployment
- Launch t2.micro instance (free tier)
- Use the VPS deployment script above
- Configure security groups for web traffic

## DigitalOcean App Platform
- Connect GitHub repository
- Auto-deploy on push
- Managed database included

## Google Cloud Platform
- Use Compute Engine for VPS-like deployment
- Cloud SQL for managed MySQL
- Load balancer for scaling

## Railway/Render (Modern alternatives)
- Git-based deployment
- Automatic HTTPS
- Built-in databases
