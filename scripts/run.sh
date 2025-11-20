#!/bin/bash
# Helper script to run commands in the CiviCRM Docker environment

set -e

# Load environment configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/env-config.sh"

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

function usage() {
    echo "Usage: $0 <command> [args]"
    echo ""
    echo "Commands:"
    echo "  setup [--civi-version VER] [--cms-version VER]"
    echo "                               - Set up CiviCRM environment"
    echo "                                 Default: CiviCRM ${DEFAULT_CIVICRM_VERSION}, Drupal ${DEFAULT_CMS_VERSION}"
    echo "  civix                        - Run civix generate:entity-boilerplate"
    echo "  tests                        - Run all PHPUnit tests"
    echo "  test FILE                    - Run specific test file"
    echo "  phpstan                      - Run PHPStan on entire codebase"
    echo "  phpstan-changed              - Run PHPStan on changed files only (recommended)"
    echo "  shell                        - Open bash shell in the container"
    echo "  cv <args>                    - Run cv command"
    echo "  stop                         - Stop services"
    echo "  clean                        - Clean up (remove volumes)"
    echo ""
    echo "Examples:"
    echo "  $0 setup                                    # Setup with default (CiviCRM ${DEFAULT_CIVICRM_VERSION})"
    echo "  $0 setup --civi-version 5.51.3              # Setup with CiviCRM 5.51.3 (legacy civix)"
    echo "  $0 setup --civi-version 5.75.0 --cms-version 7.94"
    echo "  $0 civix                                    # Generate DAO files"
    echo "  $0 tests                                    # Run all tests"
    echo "  $0 phpstan-changed                          # Run static analysis on your changes"
    echo ""
    exit 1
}

if [ $# -eq 0 ]; then
    usage
fi

COMMAND=$1
shift

case $COMMAND in
    setup)
        echo -e "${BLUE}🚀 Starting services...${NC}"
        docker-compose -f docker-compose.test.yml up -d

        echo -e "${BLUE}⏳ Waiting for services to be ready...${NC}"
        sleep 10

        echo -e "${BLUE}🔧 Running setup script...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash /extension/scripts/setup.sh "$@"

        echo -e "${GREEN}✅ Setup complete!${NC}"
        ;;

    civix)
        echo -e "${BLUE}🔧 Running civix generate:entity-boilerplate...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            set -e
            # Install rsync if not present
            which rsync || (apt update && apt install -y rsync)

            EXT_DIR=/build/site/web/sites/all/modules/civicrm/tools/extensions/io.compuco.financeextras
            # Remove symlink/directory temporarily
            rm -rf \$EXT_DIR
            # Copy extension files
            cp -r /extension \$EXT_DIR
            # Run civix
            cd \$EXT_DIR && civix generate:entity-boilerplate --yes
            # Sync all generated files back to /extension (overwrites existing)
            rsync -av --delete \$EXT_DIR/CRM/ /extension/CRM/
            rsync -av \$EXT_DIR/sql/ /extension/sql/
            cp \$EXT_DIR/financeextras.civix.php /extension/
            # Restore symlink
            rm -rf \$EXT_DIR
            ln -sfn /extension \$EXT_DIR
        "
        echo -e "${GREEN}✅ DAO files regenerated!${NC}"
        ;;

    tests)
        echo -e "${BLUE}🗄️ Ensuring test database exists...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            echo 'CREATE DATABASE IF NOT EXISTS civicrm_test;' | mysql -u root --password=root --host=mysql
        "

        echo -e "${BLUE}🧪 Setting up test database configuration...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            FILE_PATH='/build/site/web/sites/default/civicrm.settings.php'
            # Check if TEST_DB_DSN assignment is already set (not just the conditional check)
            if ! grep -q \"\\\$GLOBALS\['_CV'\]\['TEST_DB_DSN'\] =\" \"\$FILE_PATH\"; then
                INSERT_LINE=\"\\\$GLOBALS['_CV']['TEST_DB_DSN'] = 'mysql://root:root@mysql:3306/civicrm_test?new_link=true';\"
                TMP_FILE=\$(mktemp)
                while IFS= read -r line
                do
                    echo \"\$line\" >> \"\$TMP_FILE\"
                    if [ \"\$line\" = \"<?php\" ]; then
                        echo \"\$INSERT_LINE\" >> \"\$TMP_FILE\"
                    fi
                done < \"\$FILE_PATH\"
                mv \"\$TMP_FILE\" \"\$FILE_PATH\"
                echo 'TEST_DB_DSN added successfully'
            else
                echo 'TEST_DB_DSN already set'
            fi
        "

        echo -e "${BLUE}🧪 Running all tests...${NC}"
        docker-compose -f docker-compose.test.yml exec -w /build/site/web/sites/all/modules/civicrm/tools/extensions/io.compuco.financeextras -e CIVICRM_SETTINGS=/build/site/web/sites/default/civicrm.settings.php civicrm phpunit9
        ;;

    test)
        if [ $# -eq 0 ]; then
            echo "Error: Please specify test file path"
            echo "Example: $0 test tests/phpunit/Civi/FinanceExtras/Service/PaymentAttemptServiceTest.php"
            exit 1
        fi
        TEST_FILE=$1

        echo -e "${BLUE}🗄️ Ensuring test database exists...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            echo 'CREATE DATABASE IF NOT EXISTS civicrm_test;' | mysql -u root --password=root --host=mysql
        "

        echo -e "${BLUE}🧪 Setting up test database configuration...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            FILE_PATH='/build/site/web/sites/default/civicrm.settings.php'
            # Check if TEST_DB_DSN assignment is already set (not just the conditional check)
            if ! grep -q \"\\\$GLOBALS\['_CV'\]\['TEST_DB_DSN'\] =\" \"\$FILE_PATH\"; then
                INSERT_LINE=\"\\\$GLOBALS['_CV']['TEST_DB_DSN'] = 'mysql://root:root@mysql:3306/civicrm_test?new_link=true';\"
                TMP_FILE=\$(mktemp)
                while IFS= read -r line
                do
                    echo \"\$line\" >> \"\$TMP_FILE\"
                    if [ \"\$line\" = \"<?php\" ]; then
                        echo \"\$INSERT_LINE\" >> \"\$TMP_FILE\"
                    fi
                done < \"\$FILE_PATH\"
                mv \"\$TMP_FILE\" \"\$FILE_PATH\"
                echo 'TEST_DB_DSN added successfully'
            else
                echo 'TEST_DB_DSN already set'
            fi
        "

        echo -e "${BLUE}🧪 Running test: ${TEST_FILE}${NC}"
        docker-compose -f docker-compose.test.yml exec -w /build/site/web/sites/all/modules/civicrm/tools/extensions/io.compuco.financeextras -e CIVICRM_SETTINGS=/build/site/web/sites/default/civicrm.settings.php civicrm phpunit9 "$TEST_FILE"
        ;;

    shell)
        echo -e "${BLUE}🐚 Opening shell in CiviCRM container...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash
        ;;

    cv)
        docker-compose -f docker-compose.test.yml exec -w /site/web/sites/default civicrm cv "$@"
        ;;

    phpstan)
        echo -e "${BLUE}🔍 Running PHPStan in test environment...${NC}"
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            # Download PHPStan if not present
            if [ ! -f /tmp/phpstan.phar ]; then
                echo 'Downloading PHPStan...'
                curl -sL https://github.com/phpstan/phpstan/releases/download/1.12.10/phpstan.phar -o /tmp/phpstan.phar
                chmod +x /tmp/phpstan.phar
            fi
            # Run PHPStan from the extension directory (using absolute path in phpstan.neon)
            cd /extension && php /tmp/phpstan.phar analyse -c phpstan.neon --memory-limit=1G
        "
        ;;

    phpstan-changed)
        echo -e "${BLUE}🔍 Running PHPStan on changed files only...${NC}"

        # Get list of changed PHP files (modified and new, excluding auto-generated files)
        # Exclude: DAO files, financeextras.civix.php, .mgd.php files, tests/bootstrap.php
        # Combine git diff (modified) and git status (new untracked files)
        MODIFIED_FILES=$(git diff --name-only origin/master 2>/dev/null | grep '\.php$' | grep -v '/DAO/' | grep -v 'financeextras.civix.php' | grep -v '\.mgd\.php$' | grep -v 'tests/bootstrap.php' || echo "")
        NEW_FILES=$(git status --porcelain | grep '^??' | awk '{print $2}' | grep '\.php$' | grep -v '/DAO/' | grep -v 'financeextras.civix.php' | grep -v '\.mgd\.php$' | grep -v 'tests/bootstrap.php' || echo "")
        CHANGED_FILES=$(echo "$MODIFIED_FILES $NEW_FILES" | tr '\n' ' ' | xargs)

        if [ -z "$CHANGED_FILES" ]; then
            echo -e "${GREEN}✅ No changed files to analyze${NC}"
            exit 0
        fi

        echo "Analyzing files:"
        echo "$CHANGED_FILES"
        echo ""

        # Just run the full analysis - baseline handles the rest
        docker-compose -f docker-compose.test.yml exec civicrm bash -c "
            # Download PHPStan if not present
            if [ ! -f /tmp/phpstan.phar ]; then
                echo 'Downloading PHPStan...'
                curl -sL https://github.com/phpstan/phpstan/releases/download/1.12.10/phpstan.phar -o /tmp/phpstan.phar
                chmod +x /tmp/phpstan.phar
            fi
            # Run full PHPStan analysis - baseline ignores known errors
            cd /extension && php /tmp/phpstan.phar analyse -c phpstan.neon --memory-limit=1G
        "
        ;;

    stop)
        echo -e "${BLUE}🛑 Stopping services...${NC}"
        docker-compose -f docker-compose.test.yml down
        echo -e "${GREEN}✅ Services stopped${NC}"
        ;;

    clean)
        echo -e "${BLUE}🧹 Cleaning up (removing volumes)...${NC}"
        docker-compose -f docker-compose.test.yml down -v
        echo -e "${GREEN}✅ Cleanup complete${NC}"
        ;;

    *)
        echo "Unknown command: $COMMAND"
        usage
        ;;
esac
