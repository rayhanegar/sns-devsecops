# 🎉 SNS-DSO Application - Complete!

## Overview
A fully functional Twitter-like microblogging platform built with DevSecOps principles.

## ✅ Completed Features

### 1. Authentication & Authorization
- ✅ User registration with validation
- ✅ Secure login with bcrypt password hashing
- ✅ Session management with httponly cookies
- ✅ Logout functionality
- ✅ Protected routes requiring authentication

### 2. Post Management
- ✅ Create posts (max 280 characters)
- ✅ View timeline feed
- ✅ Edit own posts (authorization check)
- ✅ Delete own posts (authorization check)
- ✅ Character counter with visual feedback
- ✅ Real-time post updates

### 3. Social Interactions
- ✅ Like/Unlike posts
- ✅ Like counter display
- ✅ Comment on posts
- ✅ View all comments on a post
- ✅ Delete own comments

### 4. User Interface
- ✅ Beautiful responsive design
- ✅ Login/Register page with form toggle
- ✅ Home timeline with post composer
- ✅ Post cards with author info
- ✅ Interactive like/comment buttons
- ✅ Edit post modal
- ✅ Real-time feedback messages

### 5. Security Features
- ✅ Password hashing (BCrypt with cost 12)
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (HTML escaping)
- ✅ Session security (httponly, regeneration)
- ✅ Input validation (server & client side)
- ✅ Authorization checks (owner-only operations)
- ✅ CORS headers configuration

## 🌐 How to Access

### Web Interface
1. **Login/Register**: http://sns.devsecops/login.html
2. **Main App**: http://sns.devsecops/app.html (auto-redirects if not logged in)

### API Endpoints

#### Authentication
- `POST /api/auth/register` - Register new user
- `POST /api/auth/login` - Login
- `POST /api/auth/logout` - Logout
- `GET /api/auth/me` - Get current user

#### Posts
- `GET /api/posts` - Get all posts (timeline)
- `POST /api/posts` - Create post (auth required)
- `GET /api/posts/{id}` - Get single post
- `PUT /api/posts/{id}` - Update post (owner only)
- `DELETE /api/posts/{id}` - Delete post (owner only)

#### Likes
- `POST /api/posts/{id}/like` - Like a post
- `POST /api/posts/{id}/unlike` - Unlike a post
- `GET /api/posts/{id}/likes` - Get all likes

#### Comments
- `POST /api/posts/{id}/comments` - Add comment
- `GET /api/posts/{id}/comments` - Get all comments
- `DELETE /api/comments/{id}` - Delete comment (owner only)

#### Users
- `GET /api/users/{id}` - Get user profile and posts

## 🧪 Test Users

Created during testing:
- **Username**: alice | **Password**: password123
- **Username**: bob | **Password**: password123

## 🚀 Quick Start

1. **Initialize Database** (if not done):
   ```bash
   curl http://sns.devsecops/api/init
   ```

2. **Register a New User**:
   ```bash
   curl -X POST http://sns.devsecops/api/auth/register \
     -H "Content-Type: application/json" \
     -d '{"username":"john","email":"john@example.com","password":"password123","display_name":"John Doe"}'
   ```

3. **Login**:
   ```bash
   curl -X POST http://sns.devsecops/api/auth/login \
     -H "Content-Type: application/json" \
     -c cookies.txt \
     -d '{"username":"john","password":"password123"}'
   ```

4. **Create a Post**:
   ```bash
   curl -X POST http://sns.devsecops/api/posts \
     -H "Content-Type: application/json" \
     -b cookies.txt \
     -d '{"content":"Hello SNS-DSO! 🚀"}'
   ```

5. **Access Web Interface**:
   - Open browser to: http://sns.devsecops/login.html
   - Register or login
   - Start posting!

## 📊 Architecture

```
┌─────────────────────────────────────────┐
│   Nginx Proxy Manager (172.20.0.10)    │
│   Routes: sns.devsecops → 172.20.0.30  │
└──────────────┬──────────────────────────┘
               ↓
┌──────────────────────────────────────────┐
│  NGINX Web Server (sns-dso-web)          │
│  IP: 172.20.0.30 on proxy-network        │
│  Serves: HTML, Routes /api to api.php    │
└──────────────┬───────────────────────────┘
               ↓
┌──────────────────────────────────────────┐
│  PHP-FPM 8.2 (sns-dso-app)               │
│  Classes: Auth, Post, Database           │
│  Sessions, API Logic                     │
└──────────────┬───────────────────────────┘
               ↓
┌──────────────────────────────────────────┐
│  MariaDB 10.11 (sns-dso-db)              │
│  Tables: users, posts, likes, comments   │
└──────────────────────────────────────────┘
```

## 📁 File Structure

```
/home/student/sns-devsecops/
├── src/
│   ├── config/
│   │   └── database.php         # Database connection & init
│   ├── includes/
│   │   ├── Auth.php             # Authentication class
│   │   ├── Post.php             # Post management class
│   │   └── helpers.php          # Utility functions
│   └── public/
│       ├── index.php            # Welcome/info page
│       ├── api.php              # API router
│       ├── login.html           # Login/Register page
│       └── app.html             # Main application (timeline)
├── nginx/
│   └── conf.d/
│       └── default.conf         # NGINX configuration
├── docker-compose.yaml          # Docker services
└── .env                         # Environment variables
```

## 🔐 Security Implementation

### Password Security
- BCrypt hashing with cost factor 12
- Minimum 8 characters required
- Never stored in plaintext

### Session Security
- HttpOnly cookies (XSS protection)
- Session regeneration on login
- 24-hour session timeout
- Secure session handling

### SQL Injection Prevention
- Prepared statements everywhere
- Input validation
- Type checking

### XSS Prevention
- HTML escaping in frontend
- Content Security Policy ready
- Input sanitization

### Authorization
- Owner-only edit/delete for posts
- Owner-only delete for comments
- Authentication required for protected actions

## 🎯 Features Comparison with X (Twitter)

| Feature | SNS-DSO | Notes |
|---------|---------|-------|
| User Registration | ✅ | Email, username, password |
| Login/Logout | ✅ | Session-based |
| Post Creation | ✅ | 280 char limit |
| Timeline Feed | ✅ | Chronological |
| Like Posts | ✅ | Toggle like/unlike |
| Comment on Posts | ✅ | Full threading |
| Edit Posts | ✅ | Owner only |
| Delete Posts | ✅ | Owner only |
| User Profiles | ✅ | View user's posts |
| Follow System | ⏳ | Future feature |
| Retweets | ⏳ | Future feature |
| Media Upload | ⏳ | Future feature |
| DMs | ⏳ | Future feature |

## 🚧 Future Enhancements

1. **Follow System**
   - Follow/unfollow users
   - Follower/following counts
   - Personalized timeline

2. **Media Uploads**
   - Image attachments
   - Image preview
   - File storage

3. **Advanced Features**
   - Hashtags
   - Mentions (@username)
   - Notifications
   - Direct messages
   - Search functionality

4. **Security Enhancements**
   - CSRF tokens
   - Rate limiting
   - Two-factor authentication
   - Email verification

## 📝 Notes

- Application is containerized with Docker
- Uses PHP 8.2, NGINX, MariaDB 10.11
- Follows REST API principles
- Mobile-responsive design
- Production-ready architecture

## 🎓 Learning Outcomes

This project demonstrates:
- Full-stack web development
- RESTful API design
- Authentication & Authorization
- Database design & relationships
- Security best practices
- Docker containerization
- DevSecOps principles
- Modern web UI/UX

---

**Status**: ✅ Fully Functional  
**Version**: 1.0.0  
**Last Updated**: October 5, 2025
