# SNS-DevSecOps Refactor Summary

## Date: October 11, 2025

## Changes Made

### 1. ✅ Symbolic Link Configuration
- **Status**: Verified and working
- **Current Setup**: `src` → `/home/student/twitah-devsecops/src`
- **Details**: The src directory is now a symbolic link pointing to your developer team's repository

### 2. ✅ Nginx Configuration Updated
- **File**: `nginx/conf.d/default.conf`
- **Changes**:
  - Changed document root from `/var/www/html/public` to `/var/www/html`
  - Removed API-specific routing (old src.backup had separate api.php, new src uses MVC routing)
  - Reason: New src structure doesn't have a `public/` subdirectory; index.php is at root level

### 3. ✅ Docker Compose Database Init Path Fixed
- **File**: `docker-compose.yaml`
- **Change**: 
  - From: `./database/init:/docker-entrypoint-initdb.d:ro`
  - To: `./database:/docker-entrypoint-initdb.d:ro`
- **Reason**: SQL init file is at `./database/01-init-twitah.sql`, not in a subdirectory

### 4. ✅ Environment Variables Updated
- **File**: `.env`
- **Added**: `DB_ROOT_PASSWORD=root-devsecops-admin`
- **Reason**: Required by MariaDB container configuration

### 5. ✅ Old Artifacts Removed
- **Deleted**: `src.backup/` directory
- **Reason**: Starting fresh as requested; old implementation no longer needed

## Application Structure Comparison

### Old Structure (src.backup)
```
src.backup/
├── public/
│   ├── index.php      (main entry)
│   ├── api.php        (API endpoints)
│   ├── app.html
│   └── login.html
├── includes/
│   ├── Auth.php
│   ├── Post.php
│   └── helpers.php
└── config/
    └── database.php
```

### New Structure (current src via symlink)
```
src/
├── index.php          (main entry, MVC router)
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
├── config/
│   └── config.php
└── uploads/
```

## Key Differences
1. **Architecture**: Changed from procedural to MVC pattern
2. **Entry Point**: Single `index.php` with action-based routing instead of separate files
3. **Static Files**: Now in `views/css/` instead of `public/`
4. **Uploads**: Now in `src/uploads/` instead of external `storage/uploads/`

## Infrastructure Configuration

### Docker Compose Services
- ✅ **sns-dso-app**: PHP-FPM 8.2 application container
- ✅ **web**: Nginx reverse proxy (port 80)
- ✅ **sns-dso-db**: MariaDB 10.11 database

### Networks
- ✅ **proxy-network** (external): For external access (172.20.0.30)
- ✅ **sns-dso-internal**: Internal service communication

### Volumes
- ✅ `./src` → `/var/www/html` (application code via symlink)
- ✅ `./storage` → `/var/www/html/storage` (mounted but not used by new app)
- ✅ `./db-data` → `/var/lib/mysql` (database persistence)
- ✅ `./database` → `/docker-entrypoint-initdb.d` (database initialization)

## Validation Results

✅ Docker Compose configuration is valid  
✅ Symbolic link is properly configured  
✅ Database init file exists and is readable  
✅ Nginx configuration syntax is correct  
✅ Environment variables are complete  
✅ External proxy-network exists  

## Ready to Run

Your infrastructure is now ready to start. Run:

```bash
sudo docker compose up -d
```

### Post-Start Verification

Check container status:
```bash
sudo docker compose ps
```

Check logs:
```bash
sudo docker compose logs -f
```

Access the application:
- URL: http://sns.devsecops (or http://172.20.0.30)

### Database Information
- **Host**: sns-dso-db (internal)
- **Database**: twita_db
- **User**: sns_user
- **Password**: devsecops-admin
- **Root Password**: root-devsecops-admin

## Notes

1. **Symlink Advantage**: Changes in `/home/student/twitah-devsecops/src` will immediately reflect in the running containers
2. **Fresh Start**: Old database data in `db-data/` still exists. To start completely fresh, run:
   ```bash
   sudo docker compose down -v
   sudo rm -rf db-data/*
   sudo docker compose up -d
   ```
3. **Development Workflow**: Your team can work in the `twitah-devsecops` repository, and changes will be available in this infrastructure
4. **Storage Mount**: The `./storage` mount is still configured but not actively used by the new application

## Potential Considerations

1. **File Permissions**: Ensure the symlinked src directory has appropriate permissions for the www-data user in containers
2. **Uploads Directory**: The new app stores uploads in `src/uploads/`. These will persist in the developer repository
3. **Database Schema**: Verify that the init SQL matches the new application's requirements

---

**Status**: ✅ All configuration mismatches resolved. Ready for `sudo docker compose up -d`
