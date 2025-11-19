# ⚠️ IMPORTANT: Run civix FIRST Before Testing

## Why This Is Critical

The `PaymentAttempt` BAO class we created extends `CRM_Financeextras_DAO_PaymentAttempt`:

```php
class CRM_Financeextras_BAO_PaymentAttempt extends CRM_Financeextras_DAO_PaymentAttempt {
  // ...
}
```

**The DAO class doesn't exist yet!** We must generate it with civix before we can:
- Run any tests
- Install the extension
- Use the PaymentAttempt entity

## What civix Will Generate

Running `civix generate:entity-boilerplate` will create:

1. **DAO Class**: `CRM/Financeextras/DAO/PaymentAttempt.php`
   - Auto-generated from XML schema
   - Contains all field definitions
   - Provides database access methods

2. **EntityType File**: `xml/schema/CRM/Financeextras/PaymentAttempt.entityType.php`
   - Defines entity metadata
   - Used by CiviCRM API

3. **SQL Install Script**: `sql/auto_install.sql` (updated)
   - Adds CREATE TABLE statement for civicrm_payment_attempt

4. **SQL Uninstall Script**: `sql/auto_uninstall.sql` (updated)
   - Adds DROP TABLE statement

## Steps to Run civix

### 1. Start Docker Desktop

**Mac**: Open Docker Desktop application from Applications
**Windows**: Open Docker Desktop from Start menu

Wait for Docker to fully start (whale icon should be stable, not animated)

### 2. Navigate to Finance Extras Directory

```bash
cd /Users/erawat/Projects/Alpha/clients/compuclient7/profiles/compuclient/modules/contrib/civicrm/ext/io.compuco.financeextras
```

### 3. Start Docker Services

```bash
docker-compose -f docker-compose.test.yml up -d
```

This starts:
- MySQL 8.0 container
- CiviCRM buildkit container (includes civix)

### 4. Wait for MySQL to Initialize

```bash
# MySQL needs time to create databases
sleep 15
```

### 5. Setup CiviCRM Environment

```bash
./scripts/run.sh setup
```

This will:
- Build Drupal 7.100 + CiviCRM 6.4.1 site (~5 minutes first time)
- Create symlink to extension
- Enable Finance Extras extension
- Create test database

### 6. Run civix

```bash
./scripts/run.sh civix
```

This will:
- Copy extension to CiviCRM extensions directory
- Run `civix generate:entity-boilerplate --yes`
- Generate DAO, EntityType, and SQL files
- Sync generated files back to host

### 7. Verify Generated Files

```bash
# Check DAO file was created
ls -la CRM/Financeextras/DAO/PaymentAttempt.php

# Check EntityType file was created
ls -la xml/schema/CRM/Financeextras/PaymentAttempt.entityType.php

# Check SQL files were updated
grep -A 20 "CREATE TABLE.*civicrm_payment_attempt" sql/auto_install.sql
```

Expected output:
```
-rw-r--r-- 1 user staff  XXXXX Nov 18 22:XX CRM/Financeextras/DAO/PaymentAttempt.php
-rw-r--r-- 1 user staff  XXXXX Nov 18 22:XX xml/schema/CRM/Financeextras/PaymentAttempt.entityType.php

CREATE TABLE `civicrm_payment_attempt` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `contribution_id` int unsigned NOT NULL,
  ...
```

## Now You Can Run Tests

Only AFTER civix completes successfully:

```bash
# Run all tests
./scripts/run.sh tests

# Or run specific tests
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/LineItemUtilsTest.php
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/PaymentUrlBuilderTest.php
```

## Troubleshooting

### Error: "Class 'CRM_Financeextras_DAO_PaymentAttempt' not found"

This means civix hasn't been run yet. Run `./scripts/run.sh civix` first.

### Error: "civix: command not found"

The CiviCRM buildkit container includes civix. Make sure:
1. Docker is running
2. Setup completed: `./scripts/run.sh setup`
3. Container is running: `docker-compose -f docker-compose.test.yml ps`

### Error: "MySQL connection refused"

MySQL is still starting. Wait longer:
```bash
docker-compose -f docker-compose.test.yml up -d
sleep 30  # Wait longer
./scripts/run.sh setup
```

### Clean Start

If you encounter issues, start fresh:
```bash
./scripts/run.sh clean
docker-compose -f docker-compose.test.yml up -d
sleep 15
./scripts/run.sh setup
./scripts/run.sh civix
./scripts/run.sh tests
```

## Complete Command Sequence

For copy-paste convenience:

```bash
# 1. Navigate to Finance Extras
cd /Users/erawat/Projects/Alpha/clients/compuclient7/profiles/compuclient/modules/contrib/civicrm/ext/io.compuco.financeextras

# 2. Start Docker services
docker-compose -f docker-compose.test.yml up -d

# 3. Wait for MySQL
sleep 15

# 4. Setup CiviCRM environment
./scripts/run.sh setup

# 5. Generate DAO and related files
./scripts/run.sh civix

# 6. Verify files were created
ls -la CRM/Financeextras/DAO/PaymentAttempt.php
ls -la xml/schema/CRM/Financeextras/PaymentAttempt.entityType.php

# 7. Run tests
./scripts/run.sh tests

# 8. Check code quality
./scripts/lint.sh check
./scripts/run.sh phpstan-changed
```

## After Success

Once civix completes and tests pass:
- ✅ PaymentAttempt entity is fully functional
- ✅ LineItemUtils and PaymentUrlBuilder are tested
- ✅ Ready for Phase 2: Stripe Extension Migration

See `REFACTORING_PROGRESS.md` for Phase 2 instructions.
