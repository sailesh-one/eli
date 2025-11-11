# (UDMS)

## Table of Contents
- [Project Overview](#project-overview)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Architecture](#architecture)
- [Setup & Installation](#setup--installation)
- [Project Structure](#project-structure)
- [Modules](#modules)
- [API Documentation](#api-documentation)
- [Security](#security)
- [Frontend Components](#frontend-components)

---

## Project Overview
A comprehensive Dealer Management System (DMS) for Used cars, providing end-to-end solutions for inventory management, sales, service operations, and business analytics.

---

## Features
- **Dashboard & Analytics**
  - Real-time sales metrics
  - Inventory status
  - KRA targets tracking
  - Performance analytics

- **Inventory Management**
  - Stock tracking
  - Vehicle details management
  - MMV (Make-Model-Variant) handling
  - Color and feature management

- **Sales Management**
  - Lead management
  - Sales pipeline tracking
  - Invoice generation
  - Exchange vehicle evaluation

- **Master Data Management**
  - Dealer information
  - User management
  - HSN codes
  - Configuration management

- **Executive Management Tools**
  - Performance metrics
  - Target tracking
  - Business analytics

---

## Technology Stack
- **Backend**
  - PHP 7.4+
  - MySQL Database
  - Composer for dependency management

- **Frontend**
  - Vue.js 3
  - Pinia for state management
  - Vue Router
  - Bootstrap 5
  - Chart.js for analytics

- **Additional Tools**
  - Chrome Headless for PDF generation
  - AWS services integration
  - Firebase for notifications
  - PHPMailer for email communications

---

## Architecture
- **Modern MVC Pattern**
  - Modular class structure in `/classes`
  - Common utilities in `/common`
  - API routes in `/apis/{version}`
  - Vue.js components in `/pages/`

- **Security Features**
  - Role-based access control (RBAC)
  - DDoS protection
  - JWT authentication
  - Input validation and sanitization

---

## Setup & Installation
1. Clone the repository
2. Install PHP dependencies:
   ```bash
   composer install
   ```
3. Configure environment settings in `common/common_config.php`
4. Set up database using provided schema
5. Install frontend dependencies:
   ```bash
   npm install
   ```
6. Build frontend assets:
   ```bash
   npm run build
   ```

---

## Project Structure
```
/apis                 # API endpoints and routing
  /v1                 # API version 1
    /admin           # Admin operations
    /auth            # Authentication
    /dashboard       # Dashboard data
    /exchange        # Vehicle exchange
    /invoice         # Invoice management
    /master-data     # Master data management
    /mmv             # Make-Model-Variant
    /my-stock        # Stock management
    
/assets              # Frontend assets
  /css              # Stylesheets
  /js               # JavaScript libraries
  /images           # Image assets
  
/classes             # Business logic classes
/common              # Shared utilities
/pages               # Frontend Vue components
  /components       # Vue components
  /layouts          # Page layouts
  /stores           # Pinia stores
  
/services            # Background services
  /crons            # Scheduled tasks
  /webhooks         # Webhook handlers
  
/vendor              # Composer dependencies
```

---

## Modules

### Core Modules
1. **Authentication & Authorization**
   - User management
   - Role-based access control
   - Session management

2. **Dashboard**
   - Sales overview
   - Inventory status
   - Performance metrics
   - KRA tracking

3. **Inventory Management**
   - Stock tracking
   - Vehicle information
   - Color and variant management
   - Stock transfer

4. **Sales Operations**
   - Lead management
   - Sales pipeline
   - Invoice generation
   - Exchange vehicle evaluation

5. **Master Data Management**
   - Dealer configuration
   - User management
   - Product catalogs
   - HSN codes

---

## API Documentation
The API is organized into logical modules under `/apis/{version}/`:

- **Authentication** (`/auth`)
  - Login/Logout
  - User management
  - Role management

- **Dashboard** (`/dashboard`)
  - Performance metrics
  - Sales analytics
  - Inventory overview

- **Master Data** (`/master-data`)
  - Dealer configuration
  - User management
  - Product catalogs

- **MMV** (`/mmv`)
  - Make-Model-Variant management
  - Vehicle specifications
  - Feature configuration

- **Stock** (`/my-stock`)
  - Inventory management
  - Stock transfer
  - Vehicle tracking

- **Exchange** (`/exchange`)
  - Vehicle evaluation
  - Exchange processing
  - Pricing calculations

- **Invoice** (`/invoice`)
  - Invoice generation
  - Payment processing
  - Document management

---

## Security
- **Authentication**: JWT-based authentication with refresh tokens
- **Authorization**: Role-based access control
- **Input Validation**: Centralized validation in `common_regex.php`
- **Security Headers**: CORS and other security headers configured
- **DDoS Protection**: Rate limiting and request filtering

---

## Frontend Components
- **Layouts**
  - Dashboard layout
  - Dealer layout
  - Executive management layout
  - Invoice layout

- **Components**
  - Status Tracker
  - Authentication components
  - Data tables
  - Charts and analytics
  - Forms and validation

---
