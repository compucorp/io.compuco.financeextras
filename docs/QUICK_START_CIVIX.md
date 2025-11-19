# Quick Start: Generate PaymentAttempt DAO

## Prerequisites

1. **Start Docker Desktop**
   - Open Docker Desktop application
   - Wait for it to fully start (whale icon should be stable)

## Generate DAO File

```bash
cd /Users/erawat/Projects/Alpha/clients/compuclient7/profiles/compuclient/modules/contrib/civicrm/ext/io.compuco.financeextras

# Start Docker services
docker-compose -f docker-compose.test.yml up -d

# Wait for services to be ready (MySQL needs time to initialize)
sleep 15

# Setup CiviCRM environment
./scripts/run.sh setup

# Generate DAO files from XML schema
./scripts/run.sh civix
```

## What This Will Do

1. **Start MySQL 8.0 and CiviCRM containers**
2. **Build CiviCRM 6.4.1 + Drupal 7.100 environment** (~5 minutes first time)
3. **Generate DAO file**: `CRM/Financeextras/DAO/PaymentAttempt.php`
4. **Update SQL scripts**:
   - `sql/auto_install.sql` - Table creation
   - `sql/auto_uninstall.sql` - Table drop

## Verify DAO Generation

```bash
# Check that DAO file was created
ls -la CRM/Financeextras/DAO/PaymentAttempt.php

# Should see the file with recent timestamp
```

## Run Tests

```bash
# Run all Finance Extras tests
./scripts/run.sh tests

# Or run specific utility tests
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/LineItemUtilsTest.php
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/PaymentUrlBuilderTest.php
```

## Expected Output

### Successful civix run:
```
🔧 Running civix generate:entity-boilerplate...
✅ DAO files regenerated!
```

### Successful tests:
```
🧪 Running all tests...
PHPUnit 9.5.x

.................................  33 / 33 (100%)

Time: 00:15.234, Memory: 128.00 MB

OK (33 tests, 85 assertions)
```

## Troubleshooting

### "MySQL connection refused"
```bash
# MySQL takes time to start, wait longer
sleep 30
./scripts/run.sh setup
```

### "civix: command not found"
```bash
# CiviCRM buildkit container includes civix
# Make sure setup completed successfully first
./scripts/run.sh shell
which civix  # Should show: /usr/local/bin/civix
```

### "Permission denied" on scripts
```bash
chmod +x ./scripts/*.sh
```

### Clean start if issues
```bash
# Remove everything and start fresh
./scripts/run.sh clean
docker-compose -f docker-compose.test.yml up -d
sleep 15
./scripts/run.sh setup
./scripts/run.sh civix
./scripts/run.sh tests
```

## What Happens Next?

Once the DAO is generated and tests pass:
1. ✅ PaymentAttempt entity is fully functional
2. ✅ LineItemUtils and PaymentUrlBuilder are tested and working
3. 🎯 Ready to proceed with **Phase 2: Stripe Extension Migration**

See `REFACTORING_PROGRESS.md` for detailed Phase 2 instructions.
