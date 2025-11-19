# Docker Development Environment for Finance Extras

This document describes the Docker-based development and testing infrastructure for the Finance Extras extension.

## Overview

The Finance Extras extension now includes a complete Docker-based testing environment that matches the Stripe extension infrastructure. This provides:

- **Isolated testing environment** - Full CiviCRM + Drupal stack in Docker
- **Unit testing** - PHPUnit 9 tests with MySQL 8.0
- **Code linting** - PHPCS with CiviCRM Drupal coding standards
- **Static analysis** - PHPStan level 9 for type safety
- **CI/CD alignment** - Local environment matches GitHub Actions workflows

## Prerequisites

- Docker Desktop installed and running
- Git command-line tools

## Quick Start

```bash
# 1. Setup test environment (one-time, takes ~5 minutes)
cd /path/to/io.compuco.financeextras
./scripts/run.sh setup

# 2. Run all tests
./scripts/run.sh tests

# 3. Run linter
./scripts/lint.sh check

# 4. Run PHPStan
./scripts/run.sh phpstan-changed
```

## Available Commands

### Testing Commands

```bash
# Setup test environment
./scripts/run.sh setup                     # Default: CiviCRM 6.4.1, Drupal 7.100
./scripts/run.sh setup --civi-version 5.75.0 --cms-version 7.94  # Custom versions

# Run tests
./scripts/run.sh tests                     # Run all PHPUnit tests
./scripts/run.sh test FILE                 # Run specific test file

# Example:
./scripts/run.sh test tests/phpunit/Civi/FinanceExtras/Service/PaymentAttemptServiceTest.php

# Shell access
./scripts/run.sh shell                     # Open bash shell in container
./scripts/run.sh cv api Contact.get        # Run cv commands

# Cleanup
./scripts/run.sh stop                      # Stop containers (preserves data)
./scripts/run.sh clean                     # Remove all containers and volumes
```

### Linting Commands

```bash
./scripts/lint.sh check                    # Lint changed files (vs origin/master)
./scripts/lint.sh check-all                # Lint all source files
./scripts/lint.sh fix                      # Auto-fix linting issues
./scripts/lint.sh stop                     # Stop linter container
```

### Static Analysis (PHPStan)

```bash
./scripts/run.sh phpstan                   # Analyze entire codebase
./scripts/run.sh phpstan-changed           # Analyze changed files only (recommended)
```

## Docker Compose Files

### docker-compose.test.yml

Full test environment with:
- **MySQL 8.0** - Database service
- **CiviCRM Buildkit** - PHP 8.0 with CiviCRM tools
- **Persistent volume** - Preserves CiviCRM site between runs

Used by: `./scripts/run.sh` commands

### docker-compose.lint.yml

Lightweight PHP 8.0 container for running PHPCS linter.

Used by: `./scripts/lint.sh` commands

### docker-compose.phpstan.yml

PHPStan static analysis container with CiviCRM core for type checking.

Used by: `./scripts/run.sh phpstan*` commands

## CI/CD Workflows

### GitHub Actions

The following workflows run automatically on pull requests:

#### .github/workflows/unit-test.yml
- Builds CiviCRM 6.4.1 + Drupal 7.100 environment
- Enables Finance Extras extension
- Runs full PHPUnit test suite
- Uses MySQL 8.0 service container

#### .github/workflows/linters.yml
- Runs PHPCS on changed PHP files
- Runs ESLint on changed JavaScript files
- Uses CiviCRM Drupal coding standards

#### .github/workflows/phpstan.yml
- Runs PHPStan level 9 static analysis
- Analyzes changed files against baseline
- Reports type errors and violations

## Configuration Files

### phpstan.neon

PHPStan configuration:
- **Level 9** - Strictest type checking
- **Baseline** - Ignores existing errors, enforces strict typing on new code
- **Paths** - Analyzes `Civi/`, `CRM/`, `tests/`
- **Exclusions** - Auto-generated DAO files, civix files

### phpunit.xml.dist

PHPUnit configuration:
- **Bootstrap** - `tests/phpunit/bootstrap.php`
- **Test suite** - `tests/phpunit/` directory
- **Listener** - CiviTestListener for test framework integration

### phpcs-ruleset.xml

PHPCS configuration:
- **Standard** - CiviCRM Drupal coding standards
- **Exclusions** - Auto-generated files

## Development Workflow

### 1. Setup Environment (First Time Only)

```bash
./scripts/run.sh setup
```

This will:
1. Start MySQL and CiviCRM containers
2. Build Drupal 7.100 + CiviCRM 6.4.1 site
3. Create symlink to extension
4. Enable Finance Extras
5. Create test database
6. Configure test database DSN

**Time**: ~5 minutes

### 2. Make Code Changes

Edit files in `Civi/`, `CRM/`, or `tests/` directories.

### 3. Run Tests

```bash
# Run all tests
./scripts/run.sh tests

# Or run specific test
./scripts/run.sh test tests/phpunit/Civi/FinanceExtras/Service/PaymentAttemptServiceTest.php
```

### 4. Check Code Quality

```bash
# Lint changed files
./scripts/lint.sh check

# Auto-fix issues
./scripts/lint.sh fix

# Run static analysis
./scripts/run.sh phpstan-changed
```

### 5. Commit and Push

```bash
git add .
git commit -m "CIVIMM-XXX: Add PaymentAttempt entity"
git push
```

GitHub Actions will automatically run tests, linting, and PHPStan on the PR.

## Troubleshooting

### "MySQL connection refused" error

Wait a few seconds for MySQL to start:
```bash
./scripts/run.sh stop
./scripts/run.sh setup
```

### "Extension not found" error

Check that setup completed successfully:
```bash
./scripts/run.sh shell
cv ext:list | grep financeextras
```

### Tests fail with "TEST_DB_DSN not set"

Re-run setup to configure test database:
```bash
./scripts/run.sh setup
```

### PHPStan errors about missing CiviCRM classes

Setup must complete before running PHPStan (needs CiviCRM core):
```bash
./scripts/run.sh setup
./scripts/run.sh phpstan-changed
```

### Linter can't find bin/phpcs.phar

Install linter dependencies:
```bash
./scripts/lint.sh check  # Automatically installs dependencies
```

### Docker volume filling up disk

Clean up old volumes:
```bash
./scripts/run.sh clean
docker system prune -af --volumes
```

## Performance Notes

### Container Startup Time

- **First setup**: ~5 minutes (downloads images, builds CiviCRM site)
- **Subsequent startups**: ~10 seconds (containers already built)

### Test Execution Time

- **Full test suite**: Varies by test count (~30 seconds for small suites)
- **Single test file**: ~5-10 seconds

### Volume Persistence

The `civicrm-site` volume persists data between container restarts. This means:
- ✅ Fast restarts (no need to rebuild site)
- ✅ Database data preserved
- ⚠️ Must run `./scripts/run.sh clean` to reset environment completely

## Advanced Usage

### Running civix Commands

Generate DAO files from XML schemas:

```bash
./scripts/run.sh civix
```

This will:
1. Copy extension to CiviCRM extensions directory
2. Run `civix generate:entity-boilerplate`
3. Sync generated files back to host

### Custom CiviCRM Versions

Test against different CiviCRM versions:

```bash
# Test with CiviCRM 5.75.0
./scripts/run.sh clean
./scripts/run.sh setup --civi-version 5.75.0 --cms-version 7.94
./scripts/run.sh tests
```

### Debugging Tests

Open a shell and run tests manually:

```bash
./scripts/run.sh shell

# Inside container:
cd /build/site/web/sites/all/modules/civicrm/tools/extensions/io.compuco.financeextras
phpunit9 --debug tests/phpunit/Civi/FinanceExtras/Service/PaymentAttemptServiceTest.php
```

### Inspecting Database

```bash
./scripts/run.sh shell

# Inside container:
mysql -u root --password=root --host=mysql civicrm

# Or for test database:
mysql -u root --password=root --host=mysql civicrm_test
```

## Comparison with Stripe Extension

Finance Extras Docker setup mirrors the Stripe extension infrastructure:

| Feature | Stripe Extension | Finance Extras | Status |
|---------|-----------------|----------------|--------|
| MySQL 8.0 | ✅ | ✅ | Aligned |
| CiviCRM 6.4.1 | ✅ | ✅ | Aligned |
| PHPUnit 9 | ✅ | ✅ | Aligned |
| PHPStan Level 9 | ✅ | ✅ | Aligned |
| Docker scripts | ✅ | ✅ | Aligned |
| CI workflows | ✅ | ✅ | Aligned |
| Stripe Mock | ✅ | ❌ | N/A (not needed) |

## Next Steps

1. **Start refactoring**: Use this infrastructure to develop and test PaymentAttempt entity
2. **Run tests continuously**: `./scripts/run.sh tests` after each change
3. **Lint before commit**: `./scripts/lint.sh check` before pushing
4. **Static analysis**: `./scripts/run.sh phpstan-changed` to catch type errors early

## Questions?

See the main README.md or contact the development team.
