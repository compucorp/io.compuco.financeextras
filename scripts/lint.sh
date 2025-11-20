#!/bin/bash
set -e

COMPOSE_FILE="docker-compose.lint.yml"
SERVICE_NAME="php-lint"

# Colors for output
BLUE='\033[0;34m'
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Function to run commands in the PHP container
run_in_container() {
    docker compose -f "$COMPOSE_FILE" exec -T "$SERVICE_NAME" "$@"
}

case "$1" in
    check)
        echo -e "${BLUE}🔍 Running PHP linter on changed files...${NC}"

        # Get list of changed PHP files
        CHANGED_FILES=$(git diff --diff-filter=d origin/master --name-only -- '*.php' || echo "")

        if [ -z "$CHANGED_FILES" ]; then
            echo -e "${GREEN}✅ No PHP files changed${NC}"
            exit 0
        fi

        echo "Changed files:"
        echo "$CHANGED_FILES"
        echo ""

        # Start container if not running
        docker compose -f "$COMPOSE_FILE" up -d

        # Install dependencies if needed
        if [ ! -d "bin/civicrm/coder" ]; then
            echo -e "${BLUE}📦 Installing linter dependencies...${NC}"
            run_in_container apt-get update
            run_in_container apt-get install -y git
            run_in_container bash -c "cd bin && ./install-php-linter"
        fi

        # Run phpcs on changed files
        echo "$CHANGED_FILES" | run_in_container xargs ./bin/phpcs.phar --standard=phpcs-ruleset.xml

        echo -e "${GREEN}✅ Linting complete${NC}"
        ;;

    fix)
        echo -e "${BLUE}🔧 Auto-fixing linting issues...${NC}"

        # Start container if not running
        docker compose -f "$COMPOSE_FILE" up -d

        # Install dependencies if needed
        if [ ! -d "bin/civicrm/coder" ]; then
            echo -e "${BLUE}📦 Installing linter dependencies...${NC}"
            run_in_container apt-get update
            run_in_container apt-get install -y git
            run_in_container bash -c "cd bin && ./install-php-linter"
        fi

        # Run phpcbf on all source directories
        run_in_container ./bin/phpcbf.phar --standard=phpcs-ruleset.xml CRM/ Civi/ api/ || true

        echo -e "${GREEN}✅ Auto-fix complete${NC}"
        ;;

    check-all)
        echo -e "${BLUE}🔍 Running PHP linter on all files...${NC}"

        # Start container if not running
        docker compose -f "$COMPOSE_FILE" up -d

        # Install dependencies if needed
        if [ ! -d "bin/civicrm/coder" ]; then
            echo -e "${BLUE}📦 Installing linter dependencies...${NC}"
            run_in_container apt-get update
            run_in_container apt-get install -y git
            run_in_container bash -c "cd bin && ./install-php-linter"
        fi

        # Run phpcs on all source directories
        run_in_container ./bin/phpcs.phar --standard=phpcs-ruleset.xml CRM/ Civi/ api/

        echo -e "${GREEN}✅ Linting complete${NC}"
        ;;

    stop)
        echo -e "${BLUE}🛑 Stopping linter container...${NC}"
        docker compose -f "$COMPOSE_FILE" down
        echo -e "${GREEN}✅ Stopped${NC}"
        ;;

    *)
        echo "Usage: $0 {check|fix|check-all|stop}"
        echo ""
        echo "Commands:"
        echo "  check      - Run linter on changed files (vs origin/master)"
        echo "  fix        - Auto-fix linting issues in all source files"
        echo "  check-all  - Run linter on all source files"
        echo "  stop       - Stop the linter container"
        exit 1
        ;;
esac
