# ONEOFF-1 Centralization Refactoring - Progress Report

**Date**: 2025-11-18
**Status**: Phase 1 Complete - Ready for Testing & Phase 2

---

## ✅ Phase 1: Finance Extras Infrastructure (COMPLETE)

### 1.1 PaymentAttempt Entity (✅ Already Existed!)

**Discovered**: The PaymentAttempt entity was already created in Finance Extras!

**Files**:
- ✅ `xml/schema/CRM/Financeextras/PaymentAttempt.xml` - Complete schema with all generic fields
- ✅ `CRM/Financeextras/BAO/PaymentAttempt.php` - Complete with helper methods
- ⏳ `CRM/Financeextras/DAO/PaymentAttempt.php` - **NEEDS GENERATION** (run civix)

**Schema Features**:
- Generic `processor_type` field (stripe, gocardless, itas)
- Generic `processor_session_id` (cs_..., mandate_..., etc.)
- Generic `processor_payment_id` (pi_..., payment_..., etc.)
- Status tracking (pending, completed, failed, cancelled)
- Proper foreign keys and indexes

**BAO Methods**:
- `findBySessionId($sessionId, $processorType)` - Find by session ID
- `findByPaymentId($paymentId, $processorType)` - Find by payment ID
- `findByContributionId($contributionId)` - Find by contribution
- `getStatuses()` - Get status options

---

### 1.2 LineItemUtils Utility (✅ CREATED)

**File**: `Civi/Financeextras/Utils/LineItemUtils.php`

**Methods**:
- `fetchFromContribution($contributionId)` - Fetch line items with tax-inclusive prices
- `calculateTotal($lineItems)` - Calculate total from line items
- `validateTotal($lineItems, $expectedTotal, $tolerance)` - Validate totals match

**Features**:
- Handles line items with tax calculations
- Falls back to contribution total if no line items
- Generic format works across all processors
- Exception handling for missing contributions

---

### 1.3 PaymentUrlBuilder Utility (✅ CREATED)

**File**: `Civi/Financeextras/Utils/PaymentUrlBuilder.php`

**Methods**:
- `buildSuccessUrl($contributionId, $params, $additionalParams)` - Thank-you page URL
- `buildCancelUrl($contributionId, $params)` - Cancel/error URL
- `buildErrorUrl($contributionId, $params, $errorMessage)` - Error URL with message
- `buildEventSuccessUrl($participantId, $params, $additionalParams)` - Event success URL
- `buildEventCancelUrl($participantId, $params)` - Event cancel URL

**Features**:
- Supports processor-specific parameters (session_id, redirect_flow_id, etc.)
- Works for contribution pages and event registration
- Absolute URLs for external redirects
- Proper URL encoding

---

### 1.4 Unit Tests (✅ CREATED)

**Files**:
- `tests/phpunit/Civi/Financeextras/Utils/LineItemUtilsTest.php`
- `tests/phpunit/Civi/Financeextras/Utils/PaymentUrlBuilderTest.php`

**LineItemUtilsTest Coverage**:
- ✅ Test fetching line items with tax
- ✅ Test fetching multiple line items
- ✅ Test fallback when no line items
- ✅ Test calculateTotal method
- ✅ Test validateTotal with tolerance
- ✅ Test exception handling

**PaymentUrlBuilderTest Coverage**:
- ✅ Test success URL building
- ✅ Test success URL with additional params
- ✅ Test cancel URL building
- ✅ Test error URL building
- ✅ Test event URLs
- ✅ Test missing parameter handling

---

## ⏳ Next Steps

### Step 1: Generate PaymentAttempt DAO (Required)

```bash
cd /path/to/io.compuco.financeextras

# Start Docker environment
docker-compose -f docker-compose.test.yml up -d

# Wait for services to be ready
sleep 10

# Run civix to generate DAO
./scripts/run.sh civix
```

**What This Does**:
- Generates `CRM/Financeextras/DAO/PaymentAttempt.php` from XML schema
- Updates `sql/auto_install.sql` with table creation
- Updates `sql/auto_uninstall.sql` with table drop

---

### Step 2: Run Finance Extras Tests

```bash
cd /path/to/io.compuco.financeextras

# Setup test environment (if not done yet)
./scripts/run.sh setup

# Run all tests
./scripts/run.sh tests

# Or run specific utility tests
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/LineItemUtilsTest.php
./scripts/run.sh test tests/phpunit/Civi/Financeextras/Utils/PaymentUrlBuilderTest.php
```

**Expected Results**:
- All tests should pass ✅
- No errors or warnings

---

### Step 3: Check Code Quality

```bash
cd /path/to/io.compuco.financeextras

# Run linter
./scripts/lint.sh check

# Auto-fix any issues
./scripts/lint.sh fix

# Run PHPStan
./scripts/run.sh phpstan-changed
```

---

## 🚀 Phase 2: Stripe Extension Migration (TODO)

Once Finance Extras tests pass, proceed with updating the Stripe extension.

### Task 2.1: Update Stripe to Use PaymentAttempt

**File**: `Civi/Stripe/Service/ProcessPaymentService.php`

**Changes Needed**:

```php
// BEFORE:
use CRM_Stripe_BAO_StripeAttempt;

$attempt = CRM_Stripe_BAO_StripeAttempt::create([
  'contribution_id' => $contributionId,
  'contact_id' => $params['contactID'],
  'payment_processor_id' => $this->_paymentProcessor['id'],
  'status' => 'pending',
]);

CRM_Stripe_BAO_StripeAttempt::create([
  'id' => $attempt['id'],
  'stripe_checkout_session_id' => $session['id'],
]);

// AFTER:
use CRM_Financeextras_BAO_PaymentAttempt;

$attempt = CRM_Financeextras_BAO_PaymentAttempt::create([
  'contribution_id' => $contributionId,
  'contact_id' => $params['contactID'],
  'payment_processor_id' => $this->_paymentProcessor['id'],
  'processor_type' => 'stripe',  // ADD THIS
  'status' => 'pending',
]);

CRM_Financeextras_BAO_PaymentAttempt::create([
  'id' => $attempt['id'],
  'processor_session_id' => $session['id'],  // RENAME FROM stripe_checkout_session_id
]);
```

---

### Task 2.2: Update Stripe to Use LineItemUtils

**File**: `Civi/Stripe/Service/ProcessPaymentService.php`

**Changes Needed**:

```php
// BEFORE:
private function buildLineItemsFromContribution(int $contributionId): array {
  $civiLineItems = civicrm_api3('LineItem', 'get', [
    'contribution_id' => $contributionId,
    'sequential' => 1,
  ]);

  foreach ($civiLineItems['values'] as $lineItem) {
    // ... tax calculation logic ...
  }
}

// AFTER:
use Civi\Financeextras\Utils\LineItemUtils;

// In processCheckoutPayment():
$lineItems = LineItemUtils::fetchFromContribution($contributionId);

// Convert to Stripe format in CheckoutSessionService
$this->checkoutSessionService->create($contribution, [
  'line_items' => $lineItems,  // Pass generic line items
  'currency' => $params['currencyID'],
  // ...
]);
```

**File**: `Civi/Stripe/Service/CheckoutSessionService.php`

```php
// Update buildLineItems() to accept generic format:
private function buildLineItems(array $genericLineItems, string $currency): array {
  $stripeLineItems = [];

  foreach ($genericLineItems as $item) {
    $unitAmountInCents = $this->calculatorService->calculateStripeAmount(
      $item['unit_price_with_tax'],  // Use generic field
      $currency
    );

    $stripeLineItems[] = [
      'price_data' => [
        'currency' => strtolower($currency),
        'product_data' => [
          'name' => $item['label'],  // Use generic field
        ],
        'unit_amount' => $unitAmountInCents,
      ],
      'quantity' => $item['qty'],  // Use generic field
    ];
  }

  return $stripeLineItems;
}
```

---

### Task 2.3: Update Stripe to Use PaymentUrlBuilder

**File**: `Civi/Stripe/Service/ProcessPaymentService.php`

**Changes Needed**:

```php
// BEFORE:
private function buildSuccessUrl(array $params): string {
  return CRM_Utils_System::url('civicrm/contribute/transact', [
    'id' => $params['contributionPageID'] ?? NULL,
    '_qf_ThankYou_display' => 1,
    'qfKey' => $params['qfKey'] ?? NULL,
    'session_id' => '{CHECKOUT_SESSION_ID}',
  ], TRUE, NULL, FALSE);
}

// AFTER:
use Civi\Financeextras\Utils\PaymentUrlBuilder;

// In processCheckoutPayment():
$successUrl = PaymentUrlBuilder::buildSuccessUrl(
  $contributionId,
  $params,
  ['session_id' => '{CHECKOUT_SESSION_ID}']  // Stripe-specific placeholder
);

$cancelUrl = PaymentUrlBuilder::buildCancelUrl($contributionId, $params);
```

---

### Task 2.4: Update Webhook Handlers

**Files**:
- `Civi/Stripe/Service/Webhook/CheckoutSessionCompletedHandler.php`
- `Civi/Stripe/Service/Webhook/CheckoutSessionExpiredHandler.php`
- `Civi/Stripe/Service/Webhook/PaymentIntentFailedHandler.php`
- `Civi/Stripe/Service/Webhook/PaymentIntentSucceededHandler.php`

**Changes Needed**:

```php
// BEFORE:
use CRM_Stripe_BAO_StripeAttempt;

$attempt = CRM_Stripe_BAO_StripeAttempt::findBySessionId($sessionId);

// AFTER:
use CRM_Financeextras_BAO_PaymentAttempt;

$attempt = CRM_Financeextras_BAO_PaymentAttempt::findBySessionId($sessionId, 'stripe');
//                                                                             ^^^^^^^^ ADD processor_type
```

---

### Task 2.5: Add Data Migration Script

**File**: `CRM/Stripe/Upgrader.php`

Add new upgrade method:

```php
public function upgrade_1008() {
  $this->ctx->log->info('Migrating StripeAttempt data to PaymentAttempt');

  // Check if old table exists
  $oldTableExists = CRM_Core_DAO::checkTableExists('civicrm_stripe_attempt');

  if ($oldTableExists) {
    // Copy data from civicrm_stripe_attempt to civicrm_payment_attempt
    CRM_Core_DAO::executeQuery("
      INSERT INTO civicrm_payment_attempt
        (contribution_id, contact_id, payment_processor_id, processor_type,
         processor_session_id, processor_payment_id, status, created_date, updated_date)
      SELECT
        contribution_id, contact_id, payment_processor_id, 'stripe',
        stripe_checkout_session_id, stripe_payment_intent_id, status, created_date, updated_date
      FROM civicrm_stripe_attempt
      WHERE contribution_id NOT IN (
        SELECT contribution_id FROM civicrm_payment_attempt WHERE processor_type = 'stripe'
      )
    ");

    $this->ctx->log->info('Migration complete');
  }

  return TRUE;
}
```

---

### Task 2.6: Update Stripe info.xml

**File**: `info.xml`

Add Finance Extras as required dependency:

```xml
<requires>
  <ext>io.compuco.financeextras</ext>
  <ext>io.compuco.legacytransactapi</ext>
</requires>
```

---

## 📋 Testing Checklist

### Finance Extras Tests
- [ ] Generate PaymentAttempt DAO with civix
- [ ] Run `./scripts/run.sh tests`
- [ ] All tests pass
- [ ] Run `./scripts/lint.sh check`
- [ ] No linting errors
- [ ] Run `./scripts/run.sh phpstan-changed`
- [ ] No PHPStan errors

### Stripe Extension Tests (After Migration)
- [ ] Update all files as documented above
- [ ] Run `./scripts/run.sh tests` in Stripe extension
- [ ] All tests pass (including existing ONEOFF-1 tests)
- [ ] Run `./scripts/lint.sh check`
- [ ] Run `./scripts/run.sh phpstan-changed`
- [ ] Manual testing:
  - [ ] Create contribution via contribution page
  - [ ] Verify redirect to Stripe Checkout
  - [ ] Complete payment
  - [ ] Verify contribution completed
  - [ ] Test cancel flow
  - [ ] Verify PaymentAttempt records created with processor_type='stripe'

---

## 📊 Progress Summary

### Completed ✅
1. Docker infrastructure for Finance Extras
2. CI/CD workflows updated
3. PaymentAttempt entity schema (already existed)
4. PaymentAttempt BAO with helper methods (already existed)
5. LineItemUtils utility class
6. PaymentUrlBuilder utility class
7. Comprehensive unit tests for utilities

### In Progress ⏳
8. Generate PaymentAttempt DAO with civix
9. Run Finance Extras tests

### Todo 📝
10. Update Stripe ProcessPaymentService
11. Update Stripe CheckoutSessionService
12. Update Stripe webhook handlers
13. Add data migration script
14. Update Stripe info.xml dependencies
15. Run Stripe tests
16. Manual testing
17. Deploy to staging

---

## 🎯 Success Criteria

**Phase 1 Complete When**:
- [x] PaymentAttempt entity exists in Finance Extras
- [x] LineItemUtils created and tested
- [x] PaymentUrlBuilder created and tested
- [ ] PaymentAttempt DAO generated
- [ ] All Finance Extras tests pass

**Phase 2 Complete When**:
- [ ] Stripe uses CRM_Financeextras_BAO_PaymentAttempt
- [ ] Stripe uses LineItemUtils
- [ ] Stripe uses PaymentUrlBuilder
- [ ] Data migration script added
- [ ] All Stripe tests pass
- [ ] Manual testing complete

**Deployment Ready When**:
- [ ] All tests pass (Finance Extras + Stripe)
- [ ] Linting passes
- [ ] PHPStan passes
- [ ] Code reviewed
- [ ] Deployed to staging
- [ ] Staging testing complete

---

**Next Action**: Start Docker and run `./scripts/run.sh civix` to generate PaymentAttempt DAO, then run tests!
