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

## Development Workflow

**IMPORTANT: Test-Driven Development (TDD) is required for this project.**

### Before implementing new features:
1. **Always write tests first** - Create unit tests for the functionality you plan to implement
2. Run tests to confirm they fail (red phase)
3. Implement the minimum code to make tests pass (green phase)
4. Refactor if needed while keeping tests passing

### After completing implementation:
1. **Always run the full test suite** to ensure nothing is broken
2. All tests must pass before considering the implementation complete
3. If tests fail, fix the code or update tests as appropriate

## Common Development Commands

**Testing:**
```bash
# Run all PHPUnit tests (MUST be run after any code changes)
./vendor/bin/phpunit

# Run specific test file
./vendor/bin/phpunit tests/Unit/Database/DatabaseTest.php

# Run tests with coverage (if configured)
./vendor/bin/phpunit --coverage-html coverage/
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
- `src/Models/` - Data models and business logic
- `src/Controllers/` - Request handlers
- `src/Services/` - Business services
- `src/Auth/` - Authentication logic
- `config/` - Configuration files 
- `public/` - Web-accessible files
- `tests/Unit/` - Unit tests
- `tests/Integration/` - Integration tests
- `vendor/` - Composer dependencies

The application follows PSR-4 autoloading standards and includes proper error handling for database connections.

## Testing Guidelines

- All new classes must have corresponding unit tests
- Tests are located in `tests/` directory with same namespace structure as `src/`
- Use `APP_ENV=testing` environment for test execution
- Test database configuration is in `config/database_test.php`
- Mock external dependencies in unit tests
- Use real database connections for integration tests