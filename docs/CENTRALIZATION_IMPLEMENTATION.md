# ONEOFF-1 Centralization Implementation Guide

**Status**: Ready to Begin
**Date**: 2025-11-18
**Target**: Move generic payment logic from Stripe to Finance Extras

---

## Executive Summary

This document provides step-by-step instructions for centralizing ONEOFF-1 payment attempt tracking and utilities from the Stripe extension to Finance Extras.

**Goal**: Extract 30% of ONEOFF-1 logic (payment attempt tracking, line items, URL building) into Finance Extras for reuse across all payment processors (Stripe, GoCardless, ITAS, Deluxe).

**Estimated Time**: 5-7 days

---

## Phase 1: Finance Extras Infrastructure (Days 1-2)

### Task 1.1: Create PaymentAttempt Entity

**File**: `xml/schema/CRM/FinanceExtras/PaymentAttempt.xml`

```xml
<?xml version="1.0" encoding="iso-8859-1" ?>
<table>
  <base>CRM/FinanceExtras</base>
  <class>PaymentAttempt</class>
  <name>civicrm_payment_attempt</name>
  <comment>Generic payment attempt tracking for all processors</comment>

  <field>
    <name>id</name>
    <type>int unsigned</type>
    <required>true</required>
    <primaryKey>true</primaryKey>
    <autoincrement>true</autoincrement>
    <comment>Unique PaymentAttempt ID</comment>
  </field>

  <field>
    <name>contribution_id</name>
    <type>int unsigned</type>
    <required>true</required>
    <comment>FK to Contribution</comment>
  </field>

  <field>
    <name>contact_id</name>
    <type>int unsigned</type>
    <comment>FK to Contact</comment>
  </field>

  <field>
    <name>payment_processor_id</name>
    <type>int unsigned</type>
    <comment>FK to Payment Processor</comment>
  </field>

  <!-- Generic processor fields -->
  <field>
    <name>processor_type</name>
    <type>varchar</type>
    <length>50</length>
    <comment>stripe, gocardless, itas, deluxe</comment>
  </field>

  <field>
    <name>processor_session_id</name>
    <type>varchar</type>
    <length>256</length>
    <comment>cs_xxx (Stripe), mandate_xxx (GoCardless), etc.</comment>
  </field>

  <field>
    <name>processor_payment_id</name>
    <type>varchar</type>
    <length>256</length>
    <comment>pi_xxx (Stripe), payment_xxx (GoCardless), etc.</comment>
  </field>

  <field>
    <name>processor_reference</name>
    <type>varchar</type>
    <length>256</length>
    <comment>Additional processor reference if needed</comment>
  </field>

  <field>
    <name>status</name>
    <type>varchar</type>
    <length>25</length>
    <default>pending</default>
    <comment>pending, completed, failed, expired, cancelled</comment>
  </field>

  <field>
    <name>created_date</name>
    <type>timestamp</type>
    <default>CURRENT_TIMESTAMP</default>
    <comment>When attempt was created</comment>
  </field>

  <field>
    <name>updated_date</name>
    <type>timestamp</type>
    <default>CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP</default>
    <comment>Last updated</comment>
  </field>

  <!-- Foreign Keys -->
  <foreignKey>
    <name>contribution_id</name>
    <table>civicrm_contribution</table>
    <key>id</key>
    <onDelete>CASCADE</onDelete>
  </foreignKey>

  <foreignKey>
    <name>contact_id</name>
    <table>civicrm_contact</table>
    <key>id</key>
    <onDelete>SET NULL</onDelete>
  </foreignKey>

  <foreignKey>
    <name>payment_processor_id</name>
    <table>civicrm_payment_processor</table>
    <key>id</key>
    <onDelete>SET NULL</onDelete>
  </foreignKey>

  <!-- Indexes -->
  <index>
    <name>index_contribution_id</name>
    <fieldName>contribution_id</fieldName>
    <unique>true</unique>
  </index>

  <index>
    <name>index_processor_session_id</name>
    <fieldName>processor_session_id</fieldName>
  </index>

  <index>
    <name>index_processor_payment_id</name>
    <fieldName>processor_payment_id</fieldName>
  </index>

  <index>
    <name>index_payment_processor_id_processor_type</name>
    <fieldName>payment_processor_id</fieldName>
    <fieldName>processor_type</fieldName>
  </index>
</table>
```

**Commands**:
```bash
cd /path/to/io.compuco.financeextras
./scripts/run.sh civix                   # Generate DAO/BAO files
```

**Verify**:
- `CRM/FinanceExtras/DAO/PaymentAttempt.php` created
- `CRM/FinanceExtras/BAO/PaymentAttempt.php` created
- `sql/auto_install.sql` updated
- `sql/auto_uninstall.sql` updated

---

### Task 1.2: Add BAO Helper Methods

**File**: `CRM/FinanceExtras/BAO/PaymentAttempt.php`

Add these methods to the generated BAO class:

```php
<?php

class CRM_FinanceExtras_BAO_PaymentAttempt extends CRM_FinanceExtras_DAO_PaymentAttempt {

  /**
   * Find payment attempt by processor session ID
   *
   * @param string $sessionId
   * @param string $processorType (e.g., 'stripe', 'gocardless')
   * @return array|null
   */
  public static function findBySessionId(string $sessionId, string $processorType): ?array {
    $attempt = new self();
    $attempt->processor_session_id = $sessionId;
    $attempt->processor_type = $processorType;
    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }
    return NULL;
  }

  /**
   * Find payment attempt by processor payment ID
   *
   * @param string $paymentId
   * @param string $processorType
   * @return array|null
   */
  public static function findByPaymentId(string $paymentId, string $processorType): ?array {
    $attempt = new self();
    $attempt->processor_payment_id = $paymentId;
    $attempt->processor_type = $processorType;
    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }
    return NULL;
  }

  /**
   * Find payment attempt by contribution ID
   *
   * @param int $contributionId
   * @return array|null
   */
  public static function findByContributionId(int $contributionId): ?array {
    $attempt = new self();
    $attempt->contribution_id = $contributionId;
    if ($attempt->find(TRUE)) {
      return $attempt->toArray();
    }
    return NULL;
  }

  /**
   * Get available statuses
   *
   * @return array
   */
  public static function getStatuses(): array {
    return [
      'pending' => ts('Pending'),
      'completed' => ts('Completed'),
      'failed' => ts('Failed'),
      'expired' => ts('Expired'),
      'cancelled' => ts('Cancelled'),
    ];
  }
}
```

---

### Task 1.3: Create LineItemUtils

**File**: `Civi/FinanceExtras/Utils/LineItemUtils.php`

```php
<?php

namespace Civi\FinanceExtras\Utils;

use CRM_Core_Exception;

class LineItemUtils {

  /**
   * Fetch line items from CiviCRM contribution in standardized format
   *
   * @param int $contributionId
   * @return array [{label, unit_price_with_tax, qty, line_total, tax_amount}]
   * @throws CRM_Core_Exception
   */
  public static function fetchFromContribution(int $contributionId): array {
    $lineItems = [];

    try {
      $civiLineItems = civicrm_api3('LineItem', 'get', [
        'contribution_id' => $contributionId,
        'sequential' => 1,
      ]);

      foreach ($civiLineItems['values'] as $lineItem) {
        $lineTotal = (float) ($lineItem['line_total'] ?? 0);
        $taxAmount = (float) ($lineItem['tax_amount'] ?? 0);
        $qty = (int) $lineItem['qty'];
        $unitPriceWithTax = $qty > 0 ? ($lineTotal + $taxAmount) / $qty : 0;

        $lineItems[] = [
          'label' => $lineItem['label'],
          'unit_price_with_tax' => $unitPriceWithTax,
          'qty' => $qty,
          'line_total' => $lineTotal,
          'tax_amount' => $taxAmount,
        ];
      }

      // Fallback: use contribution total if no line items
      if (empty($lineItems)) {
        $contribution = civicrm_api3('Contribution', 'getsingle', ['id' => $contributionId]);
        $lineItems[] = [
          'label' => 'Contribution',
          'unit_price_with_tax' => (float) $contribution['total_amount'],
          'qty' => 1,
          'line_total' => (float) $contribution['total_amount'],
          'tax_amount' => 0,
        ];
      }

      return $lineItems;

    } catch (\CiviCRM_API3_Exception $e) {
      throw new CRM_Core_Exception('Failed to fetch line items: ' . $e->getMessage());
    }
  }
}
```

---

### Task 1.4: Create PaymentUrlBuilder

**File**: `Civi/FinanceExtras/Utils/PaymentUrlBuilder.php`

```php
<?php

namespace Civi\FinanceExtras\Utils;

class PaymentUrlBuilder {

  /**
   * Build success URL for payment processor redirect
   *
   * @param int $contributionId
   * @param array $params Must include: contributionPageID, qfKey
   * @param array $additionalParams Processor-specific params (e.g., {CHECKOUT_SESSION_ID})
   * @return string
   */
  public static function buildSuccessUrl(int $contributionId, array $params, array $additionalParams = []): string {
    $queryParams = [
      'id' => $params['contributionPageID'] ?? NULL,
      '_qf_ThankYou_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
    ];

    // Add processor-specific parameters
    foreach ($additionalParams as $key => $value) {
      $queryParams[$key] = $value;
    }

    return \CRM_Utils_System::url(
      'civicrm/contribute/transact',
      $queryParams,
      TRUE,  // absolute URL
      NULL,  // fragment
      FALSE  // htmlize
    );
  }

  /**
   * Build cancel URL for payment processor redirect
   *
   * @param int $contributionId
   * @param array $params Must include: contributionPageID, qfKey
   * @return string
   */
  public static function buildCancelUrl(int $contributionId, array $params): string {
    $queryParams = [
      'id' => $params['contributionPageID'] ?? NULL,
      '_qf_Main_display' => 1,
      'qfKey' => $params['qfKey'] ?? NULL,
      'cancel' => 1,
      'contribution_id' => $contributionId,  // For logging/debugging
    ];

    return \CRM_Utils_System::url(
      'civicrm/contribute/transact',
      $queryParams,
      TRUE,
      NULL,
      FALSE
    );
  }
}
```

---

### Task 1.5: Write Unit Tests

**File**: `tests/phpunit/Civi/FinanceExtras/Utils/LineItemUtilsTest.php`

```php
<?php

namespace Civi\FinanceExtras\Utils;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

class LineItemUtilsTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testFetchFromContribution() {
    // Create test contribution with line items
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => 100.00,
    ]);

    civicrm_api3('LineItem', 'create', [
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'label' => 'Test Item',
      'qty' => 2,
      'unit_price' => 45.00,
      'line_total' => 90.00,
      'tax_amount' => 10.00,
      'financial_type_id' => 'Donation',
    ]);

    // Test line item fetching
    $lineItems = LineItemUtils::fetchFromContribution($contribution['id']);

    $this->assertCount(1, $lineItems);
    $this->assertEquals('Test Item', $lineItems[0]['label']);
    $this->assertEquals(50.00, $lineItems[0]['unit_price_with_tax']); // (90 + 10) / 2
    $this->assertEquals(2, $lineItems[0]['qty']);
    $this->assertEquals(90.00, $lineItems[0]['line_total']);
    $this->assertEquals(10.00, $lineItems[0]['tax_amount']);
  }

  public function testFetchFromContributionFallback() {
    // Create contribution without line items
    $contact = civicrm_api3('Contact', 'create', [
      'contact_type' => 'Individual',
      'first_name' => 'Test',
      'last_name' => 'User',
    ]);

    $contribution = civicrm_api3('Contribution', 'create', [
      'contact_id' => $contact['id'],
      'financial_type_id' => 'Donation',
      'total_amount' => 100.00,
    ]);

    // Test fallback to contribution total
    $lineItems = LineItemUtils::fetchFromContribution($contribution['id']);

    $this->assertCount(1, $lineItems);
    $this->assertEquals('Contribution', $lineItems[0]['label']);
    $this->assertEquals(100.00, $lineItems[0]['unit_price_with_tax']);
    $this->assertEquals(1, $lineItems[0]['qty']);
  }
}
```

**File**: `tests/phpunit/Civi/FinanceExtras/Utils/PaymentUrlBuilderTest.php`

```php
<?php

namespace Civi\FinanceExtras\Utils;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

class PaymentUrlBuilderTest extends TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testBuildSuccessUrl() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $additionalParams = [
      'session_id' => '{CHECKOUT_SESSION_ID}',
    ];

    $url = PaymentUrlBuilder::buildSuccessUrl(456, $params, $additionalParams);

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('id=123', $url);
    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringContainsString('session_id=%7BCHECKOUT_SESSION_ID%7D', $url);
    $this->assertStringContainsString('_qf_ThankYou_display=1', $url);
  }

  public function testBuildCancelUrl() {
    $params = [
      'contributionPageID' => 123,
      'qfKey' => 'test_qf_key',
    ];

    $url = PaymentUrlBuilder::buildCancelUrl(456, $params);

    $this->assertStringContainsString('civicrm/contribute/transact', $url);
    $this->assertStringContainsString('id=123', $url);
    $this->assertStringContainsString('qfKey=test_qf_key', $url);
    $this->assertStringContainsString('cancel=1', $url);
    $this->assertStringContainsString('contribution_id=456', $url);
    $this->assertStringContainsString('_qf_Main_display=1', $url);
  }
}
```

**Run Tests**:
```bash
./scripts/run.sh tests
```

---

## Phase 2: Stripe Extension Migration (Days 3-5)

### Task 2.1: Update Stripe to Use PaymentAttempt

**File**: `Civi/Stripe/Service/ProcessPaymentService.php`

**Before**:
```php
$attempt = CRM_Stripe_BAO_StripeAttempt::create([
  'contribution_id' => $contributionId,
  'contact_id' => $params['contactID'],
  'payment_processor_id' => $this->_paymentProcessor['id'],
  'status' => 'pending',
]);

// Later...
CRM_Stripe_BAO_StripeAttempt::create([
  'id' => $attempt['id'],
  'stripe_checkout_session_id' => $session['id'],
]);
```

**After**:
```php
$attempt = CRM_FinanceExtras_BAO_PaymentAttempt::create([
  'contribution_id' => $contributionId,
  'contact_id' => $params['contactID'],
  'payment_processor_id' => $this->_paymentProcessor['id'],
  'processor_type' => 'stripe',
  'status' => 'pending',
]);

// Later...
CRM_FinanceExtras_BAO_PaymentAttempt::create([
  'id' => $attempt['id'],
  'processor_session_id' => $session['id'],  // Was: stripe_checkout_session_id
]);
```

### Task 2.2: Update Line Item Fetching

**Before**:
```php
// In ProcessPaymentService->buildLineItemsFromContribution()
$civiLineItems = civicrm_api3('LineItem', 'get', [
  'contribution_id' => $contributionId,
  'sequential' => 1,
]);

foreach ($civiLineItems['values'] as $lineItem) {
  // ... tax calculation logic ...
}
```

**After**:
```php
use Civi\FinanceExtras\Utils\LineItemUtils;

// In ProcessPaymentService->processCheckoutPayment()
$lineItems = LineItemUtils::fetchFromContribution($contributionId);

// Convert to Stripe format in CheckoutSessionService
$stripeLineItems = $this->buildStripeLineItems($lineItems, $currency);
```

### Task 2.3: Update URL Building

**Before**:
```php
// In ProcessPaymentService->buildSuccessUrl()
return CRM_Utils_System::url('civicrm/contribute/transact', [
  'id' => $params['contributionPageID'] ?? NULL,
  '_qf_ThankYou_display' => 1,
  'qfKey' => $params['qfKey'] ?? NULL,
  'session_id' => '{CHECKOUT_SESSION_ID}',
], TRUE, NULL, FALSE);
```

**After**:
```php
use Civi\FinanceExtras\Utils\PaymentUrlBuilder;

// In ProcessPaymentService->processCheckoutPayment()
$successUrl = PaymentUrlBuilder::buildSuccessUrl(
  $contributionId,
  $params,
  ['session_id' => '{CHECKOUT_SESSION_ID}']
);

$cancelUrl = PaymentUrlBuilder::buildCancelUrl($contributionId, $params);
```

### Task 2.4: Update Webhook Handlers

**File**: `Civi/Stripe/Service/Webhook/CheckoutSessionCompletedHandler.php`

**Before**:
```php
$attempt = CRM_Stripe_BAO_StripeAttempt::findBySessionId($sessionId);
```

**After**:
```php
$attempt = CRM_FinanceExtras_BAO_PaymentAttempt::findBySessionId($sessionId, 'stripe');
```

### Task 2.5: Data Migration Script

**File**: `CRM/Stripe/Upgrader.php`

Add upgrade method:

```php
public function upgrade_1008() {
  $this->ctx->log->info('Migrating StripeAttempt data to PaymentAttempt');

  // Copy data from civicrm_stripe_attempt to civicrm_payment_attempt
  CRM_Core_DAO::executeQuery("
    INSERT INTO civicrm_payment_attempt
      (contribution_id, contact_id, payment_processor_id, processor_type,
       processor_session_id, processor_payment_id, status, created_date, updated_date)
    SELECT
      contribution_id, contact_id, payment_processor_id, 'stripe',
      stripe_checkout_session_id, stripe_payment_intent_id, status, created_date, updated_date
    FROM civicrm_stripe_attempt
    WHERE contribution_id NOT IN (SELECT contribution_id FROM civicrm_payment_attempt)
  ");

  $this->ctx->log->info('Migration complete');
  return TRUE;
}
```

---

## Phase 3: Testing & Validation (Days 6-7)

### Task 3.1: Run All Tests

```bash
# Stripe extension tests
cd /path/to/uk.co.compucorp.stripe
./scripts/run.sh tests

# Finance Extras tests
cd /path/to/io.compuco.financeextras
./scripts/run.sh tests
```

### Task 3.2: Manual Testing Checklist

- [ ] Create one-off contribution via contribution page
- [ ] Verify PaymentAttempt record created with `processor_type='stripe'`
- [ ] Verify redirect to Stripe Checkout works
- [ ] Complete payment on Stripe
- [ ] Verify webhook updates PaymentAttempt status
- [ ] Verify contribution completed
- [ ] Test cancel flow (return to contribution page)
- [ ] Test line items with tax calculations
- [ ] Test multi-item price sets

### Task 3.3: Linting & Static Analysis

```bash
# Finance Extras
cd /path/to/io.compuco.financeextras
./scripts/lint.sh check
./scripts/run.sh phpstan-changed

# Stripe
cd /path/to/uk.co.compucorp.stripe
./scripts/lint.sh check
./scripts/run.sh phpstan-changed
```

---

## Rollout Plan

### Step 1: Deploy Finance Extras to Staging

```bash
# On staging server
cv ext:upgrade financeextras
```

### Step 2: Deploy Stripe Extension to Staging

```bash
# On staging server
cv ext:upgrade stripe
```

### Step 3: Run Data Migration

```bash
# Verify migration
cv api Extension.upgrade
```

### Step 4: Test on Staging

- Create test contributions
- Verify all flows work
- Check database for PaymentAttempt records

### Step 5: Deploy to Production

- Deploy Finance Extras first
- Deploy Stripe extension second
- Run upgrade scripts
- Monitor for errors

---

## Success Criteria

- [ ] PaymentAttempt entity created in Finance Extras
- [ ] LineItemUtils working and tested
- [ ] PaymentUrlBuilder working and tested
- [ ] Stripe extension using Finance Extras utilities
- [ ] All Stripe tests passing
- [ ] All Finance Extras tests passing
- [ ] Data migration successful
- [ ] Manual testing complete
- [ ] Linting passing
- [ ] PHPStan passing
- [ ] Deployed to staging
- [ ] Deployed to production

---

## Questions & Decisions

1. **Should we keep civicrm_stripe_attempt table?**
   - Recommendation: Yes, for 2 releases for backward compatibility
   - Add deprecation notice to StripeAttempt BAO

2. **Should Finance Extras be required in Stripe info.xml?**
   - Recommendation: Yes, add to `<requires>` section

3. **Should we add trigger_error deprecation warnings?**
   - Recommendation: Yes, when using old StripeAttempt BAO

---

**Next**: Begin with Phase 1, Task 1.1 (Create PaymentAttempt entity)
