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

This repository (`sns-devsecops`) handles the **infrastructure and deployment**, while the application code is maintained in a separate repository (`twitah-devsecops`) by the development team.

```
┌─────────────────────────────────────────────────────────┐
│  Development Repository (twitah-devsecops)              │
│  → Application code (MVC architecture)                  │
│  → Maintained by developer team                         │
└────────────────┬────────────────────────────────────────┘
                 │ (symlink)
                 ▼
┌─────────────────────────────────────────────────────────┐
│  Infrastructure Repository (sns-devsecops)              │
│  └─ src/ → symlink to twitah-devsecops/src             │
└─────────────────┬───────────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────┐
│   Nginx Proxy Manager (External)       │
│   Host: sns.devsecops.local             │
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
│  Volume: src/ (symlinked from dev repo)  │
└──────────────┬───────────────────────────┘
               │
               ▼
┌──────────────────────────────────────────┐
│  MariaDB 10.11 (sns-dso-db)              │
│  Network: sns-dso-internal                │
└──────────────────────────────────────────┘
```

### Repository Separation

- **`sns-devsecops`** (this repo): Infrastructure, Docker configs, deployment
- **`twitah-devsecops`**: Application code (MVC PHP application)
- **Symlink**: `./src` → `/home/student/twitah-devsecops/src`

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

# Verify the src symlink points to the development repository
ls -l src
# Should show: src -> /home/student/twitah-devsecops/src
```

### 2. Ensure proxy-network exists

```bash
docker network create proxy-network --subnet 172.20.0.0/16
```

### 3. Configure Environment

The `.env` file is already configured with default values:

```env
DB_HOST=sns-dso-db
DB_NAME=twita_db
DB_USER=sns_user
DB_PASSWORD=devsecops-admin
DB_ROOT_PASSWORD=devsecops-admin
```

**⚠️ IMPORTANT:** Change these passwords in production!

### 4. Clean Database (If Starting Fresh)

If you're starting fresh or encountering database permission errors, clean the old database data:

```bash
# Stop containers if running
sudo docker compose down

# Remove old database data
sudo rm -rf db-data/*

# This ensures the init SQL script runs on first start
```

### 5. Build and Start Containers

```bash
# Build the Docker images
sudo docker compose build

# Start all services
sudo docker compose up -d

# Check container status
sudo docker compose ps
```

### 6. Verify Installation

```bash
# Check the application
curl http://172.20.0.30/

# Or check via domain (after NPM setup)
curl http://sns.devsecops.local/

# View logs if there are issues
sudo docker compose logs -f
```

### 7. Troubleshooting Database Errors

If you see "Access denied for user 'sns_user'" errors:

```bash
# The database was initialized with old credentials
# Solution: Remove db-data and restart

sudo docker compose down
sudo rm -rf db-data/*
sudo docker compose up -d

# The init script (database/01-init-twitah.sql) will run automatically
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
- **Domain Names:** `sns.devsecops.local`
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
echo "172.20.0.1 sns.devsecops.local" | sudo tee -a /etc/hosts
```

Replace `172.20.0.1` with your Nginx Proxy Manager host IP.

### Step 5: Test Access

```bash
# Test the proxy
curl -H "Host: sns.devsecops.local" http://172.20.0.30/

# Or visit in browser
http://sns.devsecops.local/
```

## ⚙️ Environment Configuration

### Environment Variables

Create or modify `.env` file:

```env
# Database Configuration
DB_HOST=sns-dso-db
DB_NAME=twita_db
DB_USER=sns_user
DB_PASSWORD=your_secure_password_here
DB_ROOT_PASSWORD=your_root_password_here

# Application Configuration
APP_VERSION=latest
```

### Volume Mounts

The following directories are mounted:
- `./src` → `/var/www/html` (Application code - **symlinked from twitah-devsecops**)
- `./storage` → `/var/www/html/storage` (Storage directory - not actively used by current app)
- `./db-data` → `/var/lib/mysql` (Database persistence)
- `./database` → `/docker-entrypoint-initdb.d` (Database initialization scripts)
- `./nginx/conf.d` → `/etc/nginx/conf.d` (NGINX config)

### Application Structure

The application code is maintained in the `twitah-devsecops` repository:

```
twitah-devsecops/src/     (Development repository)
├── index.php             # Main entry point with MVC routing
├── config/
│   └── config.php        # Database configuration
├── controllers/
│   ├── AuthController.php
│   ├── TweetController.php
│   └── ProfileController.php
├── models/
│   ├── User.php
│   └── Tweet.php
├── views/
│   ├── home.php
│   ├── add.php
│   ├── profile.php
│   ├── auth/
│   ├── layout/
│   └── css/
└── uploads/              # User uploaded files
```

**Benefits of Symlink Approach:**
- Developers work in `twitah-devsecops` repository
- Infrastructure managed separately in `sns-devsecops`
- Changes in dev repo reflect immediately in running containers
- Clean separation of concerns

## 📡 Application Routes

The application uses MVC routing through `index.php?action=<action>`:

### Authentication
```bash
# Show login form
GET /?action=loginForm

# Login
POST /?action=login

# Show registration form
GET /?action=registerForm

# Register
POST /?action=register

# Logout
GET /?action=logout
```

### Tweets
```bash
# Home page (list tweets)
GET /

# Show add tweet form
GET /?action=showAdd

# Store new tweet
POST /?action=storeTweet

# Update tweet
POST /?action=updateTweet

# Delete tweet
POST /?action=deleteTweet
```

### Profile
```bash
# View profile
GET /?action=profile&username=<username>

# Update username
POST /?action=updateUsername
```

## 🗄️ Database Schema

Database is automatically initialized from `database/01-init-twitah.sql` on first container start.

### Tables:

#### users
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### tweets
```sql
CREATE TABLE tweets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    content TEXT,
    image_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### Default Test Data
- **User 1**: alice (password: password123)
- **User 2**: bob (password: qwerty)
- Sample tweets from both users

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

### 500 Internal Server Error - Database Access Denied

**Error**: "Access denied for user 'sns_user'@'%' to database 'twita_db'"

**Cause**: The database was initialized with old credentials or the `db-data` directory contains data from a previous setup.

**Solution**:
```bash
# Stop all containers
sudo docker compose down

# Remove old database data (THIS WILL DELETE ALL DATABASE DATA!)
sudo rm -rf db-data/*

# Start containers - init script will run automatically
sudo docker compose up -d

# Verify logs
sudo docker compose logs -f sns-dso-app
```

### Container won't start

```bash
# Check logs
sudo docker compose logs

# Check if ports are in use
sudo netstat -tulpn | grep -E ':(80|443|3306|9000)'

# Rebuild from scratch
sudo docker compose down -v
sudo docker compose build --no-cache
sudo docker compose up -d
```

### Database connection failed

```bash
# Check if database is running
sudo docker compose ps sns-dso-db

# Check database logs
sudo docker compose logs sns-dso-db

# Check database environment variables
sudo docker compose exec sns-dso-db env | grep MYSQL

# Test connection manually
sudo docker compose exec sns-dso-app php -r "new mysqli('sns-dso-db', 'sns_user', 'devsecops-admin', 'twita_db') or die('Connection failed');"
```

### Symlink Issues

```bash
# Verify symlink is correct
ls -la src
# Should show: src -> /home/student/twitah-devsecops/src

# If symlink is broken, recreate it
rm src
ln -s /home/student/twitah-devsecops/src src

# Verify target directory exists
ls -la /home/student/twitah-devsecops/src
```

### 502 Bad Gateway

```bash
# Check if PHP-FPM is running
sudo docker compose ps sns-dso-app

# Check NGINX configuration
sudo docker compose exec web nginx -t

# Restart services
sudo docker compose restart sns-dso-app web
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

### Permission Issues with Uploads

```bash
# The uploads directory is in the symlinked src
# Check permissions in the development repository
ls -la /home/student/twitah-devsecops/src/uploads/

# If needed, fix permissions (run from dev repo)
cd /home/student/twitah-devsecops
sudo chown -R www-data:www-data src/uploads/
sudo chmod -R 755 src/uploads/
```

### Changes in Dev Repo Not Reflecting

```bash
# Changes should reflect immediately due to symlink
# If not, check if containers are using the symlink
sudo docker compose exec sns-dso-app ls -la /var/www/html

# Restart containers to ensure fresh mount
sudo docker compose restart sns-dso-app
```

## 📝 Maintenance

### Backup Database

```bash
# Backup
sudo docker compose exec sns-dso-db mysqldump -u root -pdevsecops-admin twita_db > backup-$(date +%Y%m%d).sql

# Restore
sudo docker compose exec -T sns-dso-db mysql -u root -pdevsecops-admin twita_db < backup-20251011.sql
```

### Update Application Code

**Development Team Workflow:**
```bash
# Developers work in the twitah-devsecops repository
cd /home/student/twitah-devsecops

# Make changes to src/
# Changes are immediately available in running containers via symlink

# Commit and push
git add .
git commit -m "Your changes"
git push origin main
```

**Infrastructure Team Workflow:**
```bash
# Update infrastructure configs in sns-devsecops
cd /home/student/sns-devsecops

# Make changes to docker-compose.yaml, Dockerfile, nginx configs, etc.

# Rebuild and restart
sudo docker compose down
sudo docker compose build
sudo docker compose up -d
```

### Clean Restart

```bash
# Complete clean restart (WARNING: Deletes all data!)
sudo docker compose down -v
sudo rm -rf db-data/*

# Start fresh
sudo docker compose up -d
```

## 📞 Support

For issues and questions:
- Check logs: `sudo docker compose logs -f`
- Review NGINX config: `nginx/conf.d/default.conf`
- Review PHP config: `docker/php/php.prod.ini`
- Check database init: `database/01-init-twitah.sql`
- Verify symlink: `ls -la src`
- Check REFACTOR_SUMMARY.md for recent changes

## 📂 Repository Structure

### Infrastructure Repository (sns-devsecops)
```
sns-devsecops/
├── docker/                  # Docker configurations
│   └── php/
│       ├── php.dev.ini
│       └── php.prod.ini
├── nginx/                   # NGINX configurations
│   └── conf.d/
│       └── default.conf
├── database/                # Database initialization
│   └── 01-init-twitah.sql
├── src/ → symlink           # Symlink to twitah-devsecops/src
├── storage/                 # Storage directory (not actively used)
├── db-data/                 # Database persistence (gitignored)
├── docker-compose.yaml      # Service orchestration
├── Dockerfile               # PHP-FPM image definition
├── .env                     # Environment variables
├── README.md                # This file
├── SETUP_SUMMARY.md         # Setup documentation
└── REFACTOR_SUMMARY.md      # Recent refactor notes
```

### Development Repository (twitah-devsecops)
```
twitah-devsecops/
└── src/                     # Application code
    ├── index.php            # MVC router
    ├── config/              # Configuration
    ├── controllers/         # Business logic
    ├── models/              # Data models
    ├── views/               # HTML templates
    └── uploads/             # User uploads
```

## 📄 License

This project is for educational and DevSecOps training purposes.

---

**Built with ❤️ for DevSecOps Learning**
