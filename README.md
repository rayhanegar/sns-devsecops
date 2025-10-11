# 🚀 SNS-DSO - DevSecOps Microblogging Platform

A Twitter-like microblogging application built with DevSecOps principles using Docker containerization.

## 📋 Table of Contents
- [Architecture](#architecture)
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Nginx Proxy Manager Setup](#nginx-proxy-manager-setup)
- [Environment Configuration](#environment-configuration)
- [API Endpoints](#api-endpoints)
- [Database Schema](#database-schema)
- [Development](#development)
- [Production Deployment](#production-deployment)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)

## 🏗️ Architecture

```
┌─────────────────────────────────────────┐
│   Nginx Proxy Manager (External)       │
│   Host: sns.devsecops                   │
│   → Routes to 172.20.0.30:80           │
└──────────────┬──────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  NGINX Web Server (sns-dso-web)          │
│  Network: proxy-network (172.20.0.30)    │
│  Network: sns-dso-internal                │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  PHP-FPM 8.2 (sns-dso-app)               │
│  Network: sns-dso-internal                │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  MariaDB 10.11 (sns-dso-db)              │
│  Network: sns-dso-internal                │
└──────────────────────────────────────────┘
```

## ✅ Prerequisites

- Docker Engine 20.10+
- Docker Compose 2.0+
- Nginx Proxy Manager (running on `proxy-network`)
- At least 2GB RAM available
- Ports 80 and 443 available (if not using NPM)

## 🚀 Quick Start

### 1. Clone and Setup

```bash
cd /home/student/sns-devsecops
```

### 2. Ensure proxy-network exists

```bash
docker network create proxy-network --subnet 172.20.0.0/16
```

### 3. Configure Environment

The `.env` file is already configured with default values:

```env
DB_NAME=sns-dso 
DB_USER=sns_user
DB_PASSWORD=devsecops-admin
DB_ROOT_PASSWORD=devsecops-admin
```

**⚠️ IMPORTANT:** Change these passwords in production!

### 4. Build and Start Containers

```bash
# Build the Docker images
docker-compose build

# Start all services
docker-compose up -d

# Check container status
docker-compose ps
```

### 5. Initialize Database

Once containers are running, initialize the database:

```bash
curl http://172.20.0.30/api/init
```

Or visit: `http://172.20.0.30/api/init`

### 6. Verify Installation

```bash
# Check health endpoint
curl http://172.20.0.30/api/health

# Or open in browser
# http://172.20.0.30/
```

## 🌐 Nginx Proxy Manager Setup

### Step 1: Access Nginx Proxy Manager

1. Open Nginx Proxy Manager web interface (usually at `http://your-server:81`)
2. Login with your credentials

### Step 2: Add Proxy Host

1. Navigate to **Hosts** → **Proxy Hosts**
2. Click **Add Proxy Host**

### Step 3: Configure Proxy Host

**Details Tab:**
- **Domain Names:** `sns.devsecops`
- **Scheme:** `http`
- **Forward Hostname/IP:** `172.20.0.30`
- **Forward Port:** `80`
- **Cache Assets:** ✅ Enabled
- **Block Common Exploits:** ✅ Enabled
- **Websockets Support:** ✅ Enabled (optional, for future features)

**SSL Tab (Optional - for HTTPS on port 443):**
- **SSL Certificate:** Request a new SSL Certificate or use existing
- **Force SSL:** ✅ Enabled (recommended)
- **HTTP/2 Support:** ✅ Enabled
- **HSTS Enabled:** ✅ Enabled

**Advanced Tab (Optional):**
```nginx
# Additional security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

# Rate limiting (optional)
limit_req_zone $binary_remote_addr zone=sns_limit:10m rate=10r/s;
limit_req zone=sns_limit burst=20 nodelay;
```

### Step 4: DNS Configuration

Add DNS entry or edit `/etc/hosts`:

```bash
# On your local machine or DNS server
echo "172.20.0.1 sns.devsecops" | sudo tee -a /etc/hosts
```

Replace `172.20.0.1` with your Nginx Proxy Manager host IP.

### Step 5: Test Access

```bash
# Test the proxy
curl -H "Host: sns.devsecops" http://172.20.0.30/

# Or visit in browser
http://sns.devsecops/
```

## ⚙️ Environment Configuration

### Environment Variables

Create or modify `.env` file:

```env
# Database Configuration
DB_NAME=sns-dso
DB_USER=sns_user
DB_PASSWORD=your_secure_password_here
DB_ROOT_PASSWORD=your_root_password_here

# Application Configuration
APP_VERSION=latest
```

### Volume Mounts

The following directories are mounted:
- `./src` → `/var/www/html` (Application code)
- `./storage` → `/var/www/html/storage` (File uploads, logs)
- `./db-data` → `/var/lib/mysql` (Database persistence)
- `./nginx/conf.d` → `/etc/nginx/conf.d` (NGINX config)

## 📡 API Endpoints

### Health Check
```bash
GET /api/health
```

Response:
```json
{
  "status": "healthy",
  "timestamp": "2025-10-05T17:45:00+00:00",
  "version": "1.0.0",
  "database": "connected"
}
```

### Initialize Database
```bash
GET /api/init
```

### Get Posts
```bash
GET /api/posts
```

### Create Post
```bash
POST /api/posts
Content-Type: application/json

{
  "user_id": 1,
  "content": "Hello, world!",
  "image_url": "https://example.com/image.jpg"
}
```

## 🗄️ Database Schema

### Tables Created:
- **users** - User accounts and profiles
- **posts** - Microblog posts
- **likes** - Post likes/reactions
- **comments** - Post comments
- **follows** - User following relationships

## 👨‍💻 Development

### View Logs

```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f sns-dso-app
docker-compose logs -f web
docker-compose logs -f sns-dso-db
```

### Access Containers

```bash
# PHP container
docker-compose exec sns-dso-app sh

# Database container
docker-compose exec sns-dso-db mysql -u sns_user -p

# NGINX container
docker-compose exec web sh
```

### Restart Services

```bash
# Restart all
docker-compose restart

# Restart specific service
docker-compose restart sns-dso-app
```

### Development Mode

To use development mode with Xdebug:

```bash
# Build with development target
docker-compose build --build-arg target=development sns-dso-app
docker-compose up -d
```

## 🚢 Production Deployment

### 1. Update Environment Variables

```bash
# Generate secure passwords
DB_PASSWORD=$(openssl rand -base64 32)
DB_ROOT_PASSWORD=$(openssl rand -base64 32)

# Update .env file
nano .env
```

### 2. Build Production Image

```bash
docker-compose build --no-cache
```

### 3. Deploy with Production Settings

```bash
# Start in detached mode
docker-compose up -d

# Check health
docker-compose ps
curl http://172.20.0.30/api/health
```

### 4. Enable HTTPS

Configure SSL in Nginx Proxy Manager as described above.

## 🔒 Security Considerations

### ✅ Implemented Security Features

1. **Network Isolation:** Internal services on `sns-dso-internal` network
2. **Environment Variables:** Secrets stored in `.env` (not in code)
3. **PHP Security:** 
   - `expose_php = Off`
   - `allow_url_include = Off`
   - Session security enabled
4. **NGINX Security:**
   - Hidden files denied
   - Sensitive files blocked
   - Rate limiting ready
5. **Database Security:**
   - Separate user credentials
   - Character set: utf8mb4
   - Prepared statements for queries

### ⚠️ Security Recommendations

1. **Change Default Passwords:** Update `.env` before production
2. **Enable SSL:** Configure HTTPS in Nginx Proxy Manager
3. **Regular Updates:** Keep Docker images updated
4. **Backup Database:** Implement regular backup strategy
5. **Monitor Logs:** Set up log aggregation and monitoring
6. **Firewall Rules:** Restrict access to necessary ports only
7. **User Authentication:** Implement proper authentication (not included in base)

## 🔧 Troubleshooting

### Container won't start

```bash
# Check logs
docker-compose logs

# Check if ports are in use
sudo netstat -tulpn | grep -E ':(80|443|3306|9000)'

# Rebuild from scratch
docker-compose down -v
docker-compose build --no-cache
docker-compose up -d
```

### Database connection failed

```bash
# Check if database is running
docker-compose ps sns-dso-db

# Check database logs
docker-compose logs sns-dso-db

# Test connection manually
docker-compose exec sns-dso-app php -r "new PDO('mysql:host=sns-dso-db;dbname=sns-dso', 'sns_user', 'devsecops-admin');"
```

### 502 Bad Gateway

```bash
# Check if PHP-FPM is running
docker-compose ps sns-dso-app

# Check NGINX configuration
docker-compose exec web nginx -t

# Restart services
docker-compose restart sns-dso-app web
```

### Nginx Proxy Manager can't reach container

```bash
# Verify proxy-network exists
docker network ls | grep proxy-network

# Check IP address
docker inspect sns-dso-web | grep IPAddress

# Verify network connection
docker network inspect proxy-network
```

### Permission Issues

```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

## 📝 Maintenance

### Backup Database

```bash
# Backup
docker-compose exec sns-dso-db mysqldump -u root -p sns-dso > backup.sql

# Restore
docker-compose exec -T sns-dso-db mysql -u root -p sns-dso < backup.sql
```

### Update Application

```bash
# Pull latest changes
git pull

# Rebuild and restart
docker-compose down
docker-compose build
docker-compose up -d
```

## 📞 Support

For issues and questions:
- Check logs: `docker-compose logs`
- Review NGINX config: `nginx/conf.d/default.conf`
- Review PHP config: `docker/php/php.prod.ini`

## 📄 License

This project is for educational and DevSecOps training purposes.

---

**Built with ❤️ for DevSecOps Learning**
