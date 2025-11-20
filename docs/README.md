# Finance Extras Documentation

## Development Setup Guides

- **[DOCKER_SETUP.md](DOCKER_SETUP.md)** - Docker test environment setup guide
- **[QUICK_START_CIVIX.md](QUICK_START_CIVIX.md)** - Quick start guide for civix code generation
- **[RUN_CIVIX_FIRST.md](RUN_CIVIX_FIRST.md)** - Important: Run civix before testing
- **[SETUP_COMPLETE.md](SETUP_COMPLETE.md)** - Test environment setup completion status

## Architecture & Planning

- **[CENTRALIZATION_IMPLEMENTATION.md](CENTRALIZATION_IMPLEMENTATION.md)** - Implementation plan for payment processor centralization
  - Generic payment infrastructure (PaymentAttempt, services)
  - Multi-processor architecture design
  - Migration plan from processor-specific to generic code

- **[REFACTORING_PROGRESS.md](REFACTORING_PROGRESS.md)** - Progress tracker for centralization refactoring
  - ONEOFF stories status
  - Code organization plan
  - What goes in Finance Extras vs processor extensions

## Quick Links

### Main Documentation
- [README.md](../README.md) - Main extension documentation

### Related Extension Documentation
- [Stripe Extension Documentation](../../uk.co.compucorp.stripe/docs/)

## Key Entities

### PaymentAttempt
Generic payment attempt tracking across all processors (Stripe, GoCardless, ITAS, etc.)

**Important**: PaymentAttempt requires an Api4 class file (`Civi/Api4/PaymentAttempt.php`) to be usable in code. See Stripe extension's [TEST_ENVIRONMENT_STATUS.md](../../uk.co.compucorp.stripe/docs/TEST_ENVIRONMENT_STATUS.md) for details on this requirement.
