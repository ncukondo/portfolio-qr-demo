# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an Electronic Portfolio System with QR Code Demo written in PHP. It's a simple web application that demonstrates database connectivity using PostgreSQL.

**Key Architecture:**
- **Language**: PHP 8.2+
- **Database**: PostgreSQL with PDO
- **Structure**: PSR-4 autoloading with `App\` namespace mapping to `src/`
- **Entry Point**: `public/index.php` - displays system status and database connection info
- **Database Layer**: Singleton pattern in `src/Database/Database.php` with connection management

## Common Development Commands

**Testing:**
```bash
# Run PHPUnit tests
./vendor/bin/phpunit
```

**Dependency Management:**
```bash
# Install dependencies
composer install

# Update dependencies  
composer update
```

**Development Server:**
```bash
# Serve from public directory
php -S 0.0.0.0:8000 -t public/
```

## Database Configuration

Database connection is configured via environment variables in `config/database.php`:
- `DB_HOST` (default: postgres)
- `DB_NAME` (default: portfolio_db) 
- `DB_USER` (default: portfolio_user)
- `DB_PASSWORD` (default: portfolio_password)
- `DB_PORT` (default: 5432)

The Database class uses a singleton pattern and provides prepared statement support through the `query()` method.

## Code Organization

- `src/Database/` - Database abstraction layer
- `config/` - Configuration files 
- `public/` - Web-accessible files
- `vendor/` - Composer dependencies

The application follows PSR-4 autoloading standards and includes proper error handling for database connections.