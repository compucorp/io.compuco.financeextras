<!-- CLAUDE.md v1.0 | Last updated: 2025-11-19 -->

# 🧠 Claude Code Development Guide

This file defines how **Claude Code (Anthropic)** should be used within this project.
It acts as both:
- a **developer onboarding guide**, and
- a **context reference** for Claude when assisting in coding tasks.

Claude Code can edit files, plan changes, and run commands — but must follow all internal development standards described here.

---

## 📦 Project Overview

This is a **CiviCRM extension** that provides financial improvements including enhanced invoice numbering, credit notes with allocations, multi-company accounting, exchange rate management, and payment tracking.

**Extension Key:** `io.compuco.financeextras`
**Repository:** https://github.com/compucorp/io.compuco.financeextras
**CiviCRM Version:** 5.51+ (minimum), 6.4.1+ (development)
**PHP Version:** 7.4+ (minimum), 8.0+ (recommended)

**Key Features:**
- **Credit Notes**: Full credit note system with allocations and accounting entries
- **Multi-Company**: Owner organization support for separate accounting entities
- **Enhanced Invoicing**: Improved invoice numbering and templates
- **Exchange Rates**: Multi-currency support with historical rates
- **Payment Tracking**: PaymentAttempt entity for tracking payment processing

**Installation:**
```bash
# Install extension
composer install  # Install dev dependencies (PHPStan, PHPUnit)
cv en financeextras

# OR with Drush:
drush cei financeextras
```

---

## 0. Development Environment Setup

### Running Development Commands

All development commands use Docker-based scripts for consistency:

```bash
# Initial setup (first time only, ~5 minutes)
./scripts/run.sh setup

# Run all tests
./scripts/run.sh tests

# Run specific test file
./scripts/run.sh test tests/phpunit/CRM/Financeextras/BAO/CreditNoteTest.php

# Lint changed files
./scripts/lint.sh check

# Auto-fix linting issues
./scripts/lint.sh fix

# Run PHPStan on changed files (recommended)
./scripts/run.sh phpstan-changed

# Regenerate DAO files after schema changes
./scripts/run.sh civix

# Open shell in container
./scripts/run.sh shell

# Cleanup
./scripts/run.sh clean
```

### CiviCRM Core Reference

For development and debugging, it's helpful to have CiviCRM core code available:

```bash
# Clone CiviCRM core for reference (optional)
git clone https://github.com/civicrm/civicrm-core.git
cd civicrm-core
git checkout 6.4.1  # Match your development version
cd ..
```

**Note:** Add `civicrm-core/` to `.gitignore` if not already present.

---

## 1. Pull Request Descriptions

All PRs should follow a consistent structure:

**Required Sections:**
- **Overview**: Non-technical description of the change
- **Changes**: Bullet list of what was modified
- **Technical Details**: Implementation notes, architectural decisions
- **Testing**: How to test the changes, what was tested
- **Breaking Changes**: Any backward compatibility concerns

**When drafting PRs:**
- Reference the ticket ID in the PR title, e.g. `CIVIMM-365: Add PaymentAttempt entity`
- Keep summaries factual — avoid assumptions
- Include testing steps for reviewers

**Example PR Title Format:**
```
CIVIMM-365: Add PaymentAttempt entity for payment tracking
```

---

## 1.5. Handling Pull Request Review Feedback

When receiving PR review comments (from GitHub, Copilot, or human reviewers), **NEVER blindly implement feedback**. Always think critically and ask questions.

**Required Process:**

1. **Analyze Each Suggestion:**
   - Does this suggestion make technical sense?
   - What are the implications (database constraints, type safety, performance)?
   - Could this break existing functionality?
   - Is this consistent with the project's architecture?

2. **Ask Clarifying Questions:**
   - If unsure about the reasoning, ask the user: "Why is this change recommended?"
   - If there are trade-offs, present them: "This suggestion would fix X but might break Y - which is preferred?"
   - If the suggestion seems incorrect, explain why: "I think this might cause issues because..."

3. **Explain Your Analysis:**
   - For each change, explain WHY you're making it (or not making it)
   - Present technical reasoning (e.g., "is_null() is more precise than empty() for integer IDs because...")
   - Highlight potential issues (e.g., "Making contact_id NOT NULL would prevent ON DELETE SET NULL from working")

4. **Get Approval Before Implementing:**
   - Show the user what you plan to change
   - Wait for explicit confirmation before committing
   - Never batch commit multiple review changes without review

**Example - Good Response to Review Feedback:**

```
Reviewer: "Use empty() instead of is_null() for consistency"

Claude: Let me analyze this suggestion:

1. is_null() vs empty():
   - is_null() checks specifically for NULL
   - empty() checks for NULL, FALSE, 0, "0", "", array()

2. For credit note IDs (integers):
   - IDs are always positive integers (1, 2, 3...)
   - empty() would incorrectly treat 0 as "not set"
   - is_null() is more precise and type-safe

3. Code clarity:
   - is_null() clearly communicates intent
   - Matches CiviCRM's explicit NULL handling pattern

Should I keep is_null() for precision, or change to empty() for
consistency? What's the reasoning behind using empty() here?
```

**Red Flags - Stop and Ask Questions:**

- ❌ Changes that affect database constraints (NOT NULL, foreign keys)
- ❌ Changes to type checking logic (null checks, empty checks, isset)
- ❌ Suggestions that seem to contradict architectural decisions
- ❌ "Consistency" arguments without technical justification
- ❌ Changes that would alter existing behavior
- ❌ Automated tool suggestions (Copilot, linters) without context

**Important for Claude Code:**

- ✅ Always explain your reasoning for accepting or rejecting feedback
- ✅ Present trade-offs clearly to the user
- ✅ Ask for clarification when suggestions seem wrong
- ⚠️ Never commit without user approval
- ⚠️ Don't assume reviewers are always correct - they can be wrong too
- ✅ Your job is to provide technical analysis, not blindly follow instructions

---

## 2. Unit Testing

Unit tests are **mandatory** for all new features and bug fixes.

**Requirements:**
- Tests must be written using **PHPUnit 9** (CiviCRM extension standard)
- Store tests in `tests/phpunit/` directory, mirroring source structure
- All test classes must extend `BaseHeadlessTest`
- Never modify or skip tests just to make them pass. Fix the underlying code.

**Running Tests Locally with Docker (Recommended):**

```bash
# Setup test environment (one-time)
./scripts/run.sh setup

# Run all tests
./scripts/run.sh tests

# Run specific test file
./scripts/run.sh test tests/phpunit/CRM/Financeextras/BAO/PaymentAttemptTest.php

# Open shell for debugging
./scripts/run.sh shell
```

**Typical Workflow:**
```bash
# 1. Make code changes
# 2. Run tests
./scripts/run.sh tests

# 3. If schema changes, regenerate DAO files
./scripts/run.sh civix
./scripts/run.sh tests  # Verify tests still pass
```

**CI Workflow:**
Tests run automatically on PRs via `.github/workflows/unit-test.yml` which:
- Sets up MySQL 8.0 service container
- Builds Drupal 7.100 + CiviCRM 6.4.1 using civibuild
- Installs extension dependencies
- Runs PHPUnit tests

**Test Patterns:**
- Extend `BaseHeadlessTest` for all test classes
- Use CiviCRM API to create test data in `setUp()` methods
- Test positive, negative, and edge cases
- Clean up test data in `tearDown()` if needed

**Important for Claude Code:**
- ⚠️ Cannot run tests directly without Docker environment
- ✅ Can write test files following existing patterns
- ✅ Can review test output from CI workflows
- ✅ Suggest: "Push changes to trigger CI tests" or "Run `./scripts/run.sh tests`"

All tests must pass before commits are pushed or PRs are opened.

---

## 3. Code Linting & Style

Code must follow **CiviCRM Drupal coding standards** and pass all linting checks.

**Ruleset:** Custom ruleset defined in `phpcs-ruleset.xml` (based on Drupal standards)
- Excludes auto-generated files (DAO/, civix files, .mgd.php files)

**Running Linters Locally (Docker - Recommended):**
```bash
# Run linter on changed files (vs origin/master)
./scripts/lint.sh check

# Auto-fix linting issues
./scripts/lint.sh fix

# Run linter on all source files
./scripts/lint.sh check-all

# Stop linter container
./scripts/lint.sh stop
```

**CI Workflow:**
Linting runs automatically via `.github/workflows/linters.yml` on all PHP files changed in the PR.

**Important for Claude Code:**
- ✅ Can fix style issues based on linter output
- ✅ Can apply Drupal coding standards
- ⚠️ Always check formatting before commits
- ✅ Suggest: "Run `./scripts/lint.sh check` to verify code style"

### File Newline Requirements

**All files must end with a newline character** (POSIX standard compliance).

**Why this matters:**
- Git diffs show "No newline at end of file" warnings for files without newlines
- Many Unix tools expect files to end with newlines
- POSIX defines a line as ending with a newline character
- Prevents potential issues with concatenation and shell scripts

**Checking for missing newlines:**
```bash
# Check all files in a commit for missing newlines
git diff --check
```

**Fixing missing newlines:**
```bash
# Add newline to specific file
echo "" >> filename.ext
```

**Important for Claude Code:**
- ✅ Always ensure files end with newlines when creating or editing
- ✅ Can check for missing newlines before commits
- ⚠️ Editor settings should be configured to add trailing newlines automatically
- ✅ Verify with `git diff --check` before pushing

### Coding Style Guidelines

Follow these specific style rules to ensure consistency and pass linting checks:

**Comments:**
```php
// ✅ CORRECT: Comments on separate lines
// Calculate total: (50 * 2) + (100 * 1)
$this->assertEquals(200.00, $total);

// ❌ WRONG: Comments after statements
$this->assertEquals(200.00, $total); // (50 * 2) + (100 * 1)
```

**DocBlocks:**
```php
// ✅ CORRECT: Type-only @var tags
/**
 * @var int
 */
private $contactId;

// ❌ WRONG: Description in @var tag
/**
 * @var int Contact ID for testing
 */
private $contactId;
```

**Spacing:**
```php
// ✅ CORRECT: One space after comma
$params = ['foo' => 'bar', 'baz' => 'qux'];

// ❌ WRONG: Multiple spaces after comma
$params = ['foo' => 'bar',  'baz' => 'qux'];
```

**Array Formatting:**
```php
// ✅ CORRECT: Multi-line arrays with trailing comma
$params = [
  'contribution_id' => $this->contributionId,
  'contact_id' => $this->contactId,
  'status' => 'pending',
];

// ✅ CORRECT: Single-line for short arrays
$params = ['id' => 123, 'status' => 'active'];
```

**Method Documentation:**
```php
// ✅ CORRECT: Full DocBlock for public methods
/**
 * Creates a payment attempt record.
 *
 * @param array $params
 *   Payment attempt parameters.
 *
 * @return \CRM_Financeextras_DAO_PaymentAttempt
 *   The created payment attempt.
 *
 * @throws \CRM_Core_Exception
 *   If required parameters are missing.
 */
public static function create(array $params) {
  // ...
}
```

**Naming Conventions:**
```php
// ✅ CORRECT: CamelCase for classes, camelCase for methods
class PaymentAttempt extends CRM_Financeextras_DAO_PaymentAttempt {
  public static function findBySessionId($sessionId, $processorType) {
    // ...
  }
}

// ✅ CORRECT: UPPER_CASE for constants
const STATUS_PENDING = 'pending';
const STATUS_COMPLETED = 'completed';
```

**Important for Claude Code:**
- ✅ Never add inline comments after statements - always use separate lines
- ✅ Keep @var tags minimal (type only, no description)
- ✅ Maintain consistent spacing (one space after commas)
- ✅ Add trailing commas in multi-line arrays
- ✅ Run `./scripts/lint.sh fix` to auto-fix most style issues

---

## 3.5. Static Analysis (PHPStan)

All code must pass **PHPStan level 9** static analysis, the strictest PHP type checking available.

**Configuration:** `phpstan.neon` - Configured for Docker test environment
- **Level:** 9 (maximum strictness)
- **Baseline:** `phpstan-baseline.neon` - Contains 1792 ignored errors from legacy code
- **Approach:** Baseline captures existing errors, enforces strict typing on all future code

**What Gets Analyzed:**
- All source files in `Civi/` and `CRM/` directories
- Test files (important for quality!)
- New untracked files

**What Gets Excluded (Auto-Generated):**
- `CRM/Financeextras/DAO/*` - Generated by civix from XML schemas
- `financeextras.civix.php` - Generated by civix
- `*.mgd.php` - CiviCRM managed entity files
- `tests/bootstrap.php` - Test bootstrap configuration
- 3 legacy files with unknown interface issues

**Running PHPStan Locally (Docker - Recommended):**
```bash
# Run PHPStan on changed files only (recommended - fast)
./scripts/run.sh phpstan-changed

# Run PHPStan on entire codebase (slow - full analysis)
./scripts/run.sh phpstan
```

**Prerequisites:**
- Docker environment must be running: `./scripts/run.sh setup`
- PHPStan needs access to CiviCRM core for type information

**CI Workflow:**
PHPStan runs automatically via `.github/workflows/phpstan.yml` on all changed PHP files in the PR.

**Baseline Management:**
The baseline file captures existing type errors in legacy code. This approach:
- ✅ Allows adding PHPStan to existing codebase without fixing all legacy code
- ✅ Enforces strict typing on all new/modified code
- ✅ Prevents introduction of new type errors
- ⚠️ Baseline should only be regenerated when intentionally accepting new errors

**Important for Claude Code:**
- ✅ Can read PHPStan errors and suggest fixes
- ✅ Can add proper type hints to fix errors
- ⚠️ Always run `./scripts/run.sh phpstan-changed` before pushing
- ⚠️ Never regenerate baseline to "fix" errors - fix the code instead
- ✅ Suggest: "Run PHPStan to check type safety"

---

## 4. 🛡️ Critical Review Areas

### 🔐 Security

**Financial Data Security:**
- Never log or expose sensitive financial data (credit card details, bank account info)
- Validate all amounts and currency codes before processing
- Check for SQL injection in dynamic queries (use parameterized queries)
- Sanitize all user input before rendering (XSS prevention)
- Ensure proper authentication/authorization for financial operations

**Sensitive Data Handling:**
- Credit note amounts, allocations, and financial transactions are sensitive
- Payment processor credentials stored in `civicrm.settings.php` must never be committed
- Owner organization financial data should be properly isolated

### 🚀 Performance

- Identify N+1 query issues in credit note/contribution lookups
- Detect inefficient loops when processing bulk allocations
- Avoid unnecessary API calls and database queries
- Recommend caching for exchange rate lookups
- Review database queries in BAO classes for optimization

### 🧼 Code Quality

- Services should be focused and follow single responsibility principle
- Use meaningful names following CiviCRM conventions (`CRM_*` or `Civi\*`)
- Handle exceptions properly (use custom exception classes)
- All service methods should have proper return type declarations
- Use dependency injection for service dependencies

---

## 5. Commit Message Convention

All commits must start with the branch prefix (issue ID) followed by a short imperative description.

**Format:**
```
CIVIMM-123: Short description of change
```

**Rules:**
- Keep summaries under 72 characters
- Use present tense ("Add", "Fix", "Refactor")
- Claude must include the correct issue key when committing
- Be specific and descriptive
- **DO NOT add any AI attribution or co-authorship lines** (no "Generated with Claude Code", no "Co-Authored-By: Claude")

**Examples:**
```
CIVIMM-456: Add null check for credit note allocation
CIVIMM-789: Fix exchange rate calculation for Euro transactions
CIVIMM-101: Refactor CreditNoteService to use dependency injection
```

If Claude proposes commits automatically, it must use this exact format without any attribution footer.

---

## 6. Continuous Integration (CI)

All code must pass these workflows before merging:

| Workflow | Purpose | Local Command | CI File |
|-----------|----------|---------------|---------|
| **unit-test.yml** | PHPUnit test execution | `./scripts/run.sh tests` | `.github/workflows/unit-test.yml` |
| **linters.yml** | Code style and lint checks (PHPCS) | `./scripts/lint.sh check` | `.github/workflows/linters.yml` |
| **phpstan.yml** | Static analysis (PHPStan level 9) | `./scripts/run.sh phpstan-changed` | `.github/workflows/phpstan.yml` |

Claude must ensure that code:
- ✅ Passes **PHPUnit tests** (no test failures)
- ✅ Passes **linting** (CiviCRM Drupal standard compliance)
- ✅ Passes **PHPStan** (level 9 static analysis on changed files)

**PHPStan Notes:**
- Runs at level 9 (strictest analysis)
- Only analyzes changed files (not entire legacy codebase)
- Uses baseline approach: 1792 errors from legacy code ignored, all future code must be clean
- Local runs require full CiviCRM environment: `./scripts/run.sh setup`
- CI builds complete environment before running analysis

---

## 7. Architecture

### Code Organization

The extension uses two primary namespaces:

1. **`CRM_*` namespace** (CRM/ directory): Traditional CiviCRM architecture
   - **DAO/**: Database Access Objects (auto-generated from XML schemas in `xml/schema/`)
   - **BAO/**: Business Access Objects extending DAOs with business logic
   - **Form/**: Form controllers for credit notes, company management
   - **Page/**: Page controllers for credit note display, company listings
   - **Hook/**: Legacy hook implementations (PageRun)

2. **`Civi\Financeextras\*` namespace** (Civi/ directory): Modern service-oriented architecture
   - **Service/**: Business logic services (credit note, payment, exchange rate services)
   - **Utils/**: Utility classes for common operations
   - **Hook/**: Modern hook implementations (BuildForm, ValidateForm, PostProcess, etc.)
   - **Event/**: Custom events and event subscribers
   - **APIWrapper/**: API interceptors and response modifiers
   - **Payment/**: Payment processing logic
   - **Refund/**: Refund handling logic
   - **Token/**: Custom tokens for email templates

### Hook-Based Architecture

The extension uses a **hook handler pattern** where hooks are organized by type and registered in `financeextras.php`. Each hook handler:
- Implements a static `shouldHandle()` method to determine if it applies
- Encapsulates hook logic in a `handle()` or `run()` method
- Is registered in the main hook function (e.g., `financeextras_civicrm_buildForm`)

**Hook Categories:**
- `Civi/Financeextras/Hook/BuildForm/` - Form building hooks
- `Civi/Financeextras/Hook/ValidateForm/` - Form validation hooks
- `Civi/Financeextras/Hook/PostProcess/` - Post-form processing hooks
- `Civi/Financeextras/Hook/Post/` - Entity post-operation hooks
- `Civi/Financeextras/Hook/AlterMailParams/` - Email modification hooks
- `Civi/Financeextras/Hook/Links/` - Action links modification hooks
- `Civi/Financeextras/Hook/BatchExport/` - Batch export hooks

### Event-Driven Components

The extension uses Symfony EventDispatcher for custom events:
- Event subscribers registered in `financeextras_civicrm_config()`
- Custom events in `Civi/Financeextras/Event/`
- Event listeners in `Civi/Financeextras/Event/Listener/`

Example: `ContributionPaymentUpdatedEvent` is dispatched when contributions are created/edited.

### API Wrappers

API wrappers intercept and modify API calls:
- `Civi/Financeextras/APIWrapper/Payment.php` - Modifies payment API calls
- `Civi/Financeextras/APIWrapper/Contribution.php` - Enhances contribution API responses
- `Civi/Financeextras/APIWrapper/SearchDisplayRun.php` - Customizes search displays

Registered via `Civi::dispatcher()->addListener('civi.api.prepare|respond', ...)` in `financeextras.php`.

### Custom Entities

The extension defines several custom entities in `xml/schema/CRM/Financeextras/`:

| Entity | Purpose |
|--------|---------|
| **CreditNote** | Core credit note entity with invoice references |
| **CreditNoteLine** | Individual line items on credit notes |
| **CreditNoteAllocation** | Tracks how credit notes are allocated to contributions |
| **Company** | Multi-company support for separate accounting entities |
| **BatchOwnerOrganisation** | Links batches to owner organizations |
| **ExchangeRate** | Historical exchange rates for multi-currency |
| **PaymentAttempt** | Tracks payment processing attempts |

After modifying XML schemas, run `./scripts/run.sh civix` to regenerate DAO files.

### BAO vs Service Layer

- **BAO (Business Access Objects)**: Legacy layer in `CRM/Financeextras/BAO/`, contains entity-specific business logic
- **Service Layer**: Modern services in `Civi/Financeextras/Service/`, prefer this for new business logic
- **Utility Classes**: Stateless helpers in `Civi/Financeextras/Utils/`

When adding new functionality, prefer creating services over extending BAOs.

### Credit Note System

The credit note system is a core feature with complex business logic:
- Credit notes can be allocated to multiple contributions
- Allocations update contribution statuses
- Credit note status changes based on allocation state
- Integrates with CiviCRM's financial transaction system
- Supports refunds via payment processors

See `CRM/Financeextras/BAO/CreditNote.php` and related Hook handlers.

### Multi-Company Accounting

Owner organizations allow separate accounting entities:
- Batches can be assigned to specific owner organizations
- Financial filtering based on owner organization context
- Company entity stores additional organization metadata

See `Civi/Financeextras/Hook/SelectWhereClause/BatchList.php` for filtering logic.

---

## 8. Workflow with Claude Code

Claude Code operates in **Plan Mode** and **Execution Mode**.

**Recommended Flow:**
1. **Explain** – Ask Claude to describe the issue in its own words
2. **Plan** – Enable Plan Mode (`Shift + Tab` twice) and ask for a clear step-by-step fix plan
3. **Review** – Verify and edit Claude's plan before implementation
4. **Implement** – Disable Plan Mode and let Claude apply changes
5. **Verify** – Run linting and tests to confirm all checks pass

**Safe Commands:**
```bash
# Check git status and diff
git status
git diff

# Run linting
./scripts/lint.sh check

# Run tests
./scripts/run.sh tests

# Run PHPStan
./scripts/run.sh phpstan-changed

# Commit changes
git commit -m "CIVIMM-###: ..."
```

**Request Confirmation Before:**
- Deleting or overwriting files
- Running migrations or database changes
- Modifying auto-generated files (`financeextras.civix.php`, DAO files)
- Making changes to `xml/schema/` files (require regeneration)

---

## 9. Review & Validation

After Claude proposes code:

1. Review the diff manually
2. Run linting and tests
3. Ensure commit message format is correct (CIVIMM-###: ...)
4. Push the branch and open a PR
5. Verify CI passes (unit-test.yml, linters.yml, phpstan.yml)

If Claude generates documentation or summaries, review for accuracy before committing.

---

## 10. Developer Prompts (Examples)

| Task | Example Prompt |
|------|----------------|
| Generate tests | "Create PHPUnit tests for `CRM_Financeextras_BAO_CreditNote::create()` covering success, validation error, and allocation cases." |
| Summarize PR | "Summarize the last 3 commits into a PR description for issue CIVIMM-365." |
| Fix linting | "PHPCS reports style violations in `CreditNoteService.php`. Fix all issues according to `phpcs-ruleset.xml`." |
| Refactor | "Refactor `CreditNoteAllocation` to improve testability, preserving all logic and tests." |
| Add hook | "Create a new hook handler for `civicrm_pre` to validate credit note amounts before saving." |
| Update docs | "Add PHPDoc blocks to all public methods in `CreditNoteService` with proper type hints." |

---

## 11. Common Patterns & Best Practices

**Hook Handler Pattern:**
Create hook handlers in appropriate `Civi/Financeextras/Hook/{HookType}/` directory:
```php
class MyValidator {
  public static function shouldHandle($form, $formName): bool {
    return $formName === 'CRM_Contribute_Form_Contribution';
  }

  public function handle(): void {
    // Implementation
  }
}
```

**Event Subscribers:**
Register event subscribers in `financeextras_civicrm_config()`:
```php
Civi::dispatcher()->addSubscriber(new MyCreditNoteSubscriber());
```

**Service Registration:**
Services registered in `Civi\Financeextras\Hook\Container\ServiceContainer` using Symfony DI container.

**Adding New Entities:**
1. Create XML schema in `xml/schema/CRM/Financeextras/YourEntity.xml`
2. Run `./scripts/run.sh civix` to generate DAO and APIv4 files
3. Create BAO class in `CRM/Financeextras/BAO/YourEntity.php` if needed
4. Add business logic to BAO or create service class
5. Write tests in `tests/phpunit/`

---

## 12. Safety & Best Practices

**CRITICAL: Always Run Tests Before Committing Code Changes**

- **MANDATORY**: When modifying source code (`.php` files), run tests BEFORE committing:
  ```bash
  ./scripts/run.sh tests
  ```
- **MANDATORY**: When modifying error messages, verify affected tests expect the new message
- Tests catch issues that code review might miss (changed behavior, broken assertions, etc.)
- Pushing failing code wastes reviewer time and blocks CI

**Other Requirements:**
- Never commit code without running **tests** and **linting**
- Never remove or weaken tests to make them pass
- Always review Claude's suggestions before execution
- Always prefix commits with the issue ID (CIVIMM-###)
- Claude must never push commits automatically without human review
- Never commit `civicrm.settings.php` or any file containing sensitive data
- Never modify auto-generated files (`financeextras.civix.php`, DAO classes) manually
- If unsure, stop and consult a senior developer

**Sensitive Files (Never Commit):**
- `civicrm.settings.php` (contains configuration)
- `.env` files
- Any files with credentials or secrets

**Auto-Generated Files (Do Not Edit Manually):**
- `financeextras.civix.php` (regenerate with `civix`)
- `CRM/Financeextras/DAO/*.php` (regenerate from XML schemas)
- `sql/auto_install.sql` and `sql/auto_uninstall.sql` (auto-generated by civix)
- Files in `xml/schema/CRM/Financeextras/*.entityType.php` (auto-generated)

---

## 13. Deployment & Release Process

**Pre-Deployment Checklist:**
- ✅ All tests pass (unit-test.yml)
- ✅ Linting passes (linters.yml)
- ✅ PHPStan passes (phpstan.yml)
- ✅ Code reviewed and PR approved
- ✅ Version bumped in `info.xml` if needed
- ✅ CHANGELOG updated (if applicable)

**Release Process:**
1. Merge PR to target branch (e.g., `master`)
2. Tag release with version number
3. Create GitHub release with release notes
4. Extension can be installed via `cv` command or manually

**Important Notes:**
- Repository does NOT include `vendor/` directory in source code (gitignored)
- Production deployments install composer dependencies: `composer install --no-dev`
- Always test releases in staging environment before production

---

## 14. Pre-Merge Validation Checklist

| Check | Requirement |
|--------|-------------|
| ✅ Tests pass | PHPUnit tests all green in CI |
| ✅ Linting passes | PHPCS reports no violations |
| ✅ PHPStan passes | Level 9 static analysis clean on changed files |
| ✅ Commit prefix | Uses CIVIMM-### format |
| ✅ No sensitive data | No credentials or sensitive info in code |
| ✅ Code reviewed | At least one approval from team member |

---

## 15. CiviCRM Extension Specifics

**Extension Structure:**
- `info.xml`: Extension metadata, dependencies, version
- `financeextras.php`: Hook implementations entry point
- `financeextras.civix.php`: Auto-generated CiviX boilerplate (DO NOT EDIT)
- `xml/schema/`: Entity schema definitions
- `xml/Menu/`: Menu definitions
- `sql/`: Database schema and upgrade scripts
- `templates/`: Smarty templates for UI
- `js/`: JavaScript files
- `css/`: Stylesheets
- `ang/`: AngularJS modules

**CiviCRM Commands:**
```bash
# Enable extension
cv en financeextras

# Disable extension
cv dis financeextras

# Uninstall extension
cv ext:uninstall financeextras

# Upgrade extension
cv api Extension.upgrade

# Clear cache
cv flush
```

**Database Schema Changes:**
When modifying entities in `xml/schema/`, regenerate DAO files:

```bash
# Using the Docker test environment (RECOMMENDED - Claude Code can run this)
./scripts/run.sh civix

# This will:
# 1. Copy extension to CiviCRM extensions directory
# 2. Run civix generate:entity-boilerplate
# 3. Sync generated files back to host
```

**Important Notes for Claude Code:**
- ✅ **Can run civix via Docker** - use `./scripts/run.sh civix`
- ⚠️ Requires Docker to be running and test environment to be set up
- ✅ DAO files are **automatically regenerated** during extension installation/upgrade
- 📝 Always regenerate DAO files after modifying XML schemas
- 🔧 Test environment replicates exact CI setup

---

By following this file, **Claude Code** can act as a reliable assistant within our workflow — improving speed, not replacing review or standards.

**Happy coding with Claude Code 🚀**