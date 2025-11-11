# (ELI)

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
