#!/bin/bash
# Setup script for Finance Extras test environment

set -e

# Load environment configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/env-config.sh"

# Parse arguments for version overrides
CIVICRM_VERSION="${DEFAULT_CIVICRM_VERSION}"
CMS_VERSION="${DEFAULT_CMS_VERSION}"

while [[ $# -gt 0 ]]; do
    case $1 in
        --civi-version)
            CIVICRM_VERSION="$2"
            shift 2
            ;;
        --cms-version)
            CMS_VERSION="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

echo "Setting up Finance Extras test environment..."
echo "CiviCRM: $CIVICRM_VERSION | Drupal: $CMS_VERSION"

# Config amp
echo "Configuring amp..."
amp config:set --mysql_dsn=mysql://root:root@mysql:3306

# Build Drupal site
echo "Building Drupal site with CiviCRM $CIVICRM_VERSION..."
civibuild create drupal-clean --civi-ver "$CIVICRM_VERSION" --cms-ver "$CMS_VERSION" --web-root "$WEB_ROOT"

# Apply CiviCRM patches if using version 5.75.0 or compatible
if [[ "$CIVICRM_VERSION" == "5.75.0" ]]; then
    echo "Applying CiviCRM core patches for version 5.75.0..."
    # Note: In CI, this is done via compucorp/apply-patch@1.0.0 action
    # For local development, patches should be applied manually if needed
fi

# Create symlink to extension
EXT_DIR="$CIVICRM_EXTENSIONS_DIR/io.compuco.financeextras"
echo "Creating symlink: $EXT_DIR -> /extension"
rm -rf "$EXT_DIR"
ln -sfn /extension "$EXT_DIR"

# Enable Finance Extras extension
echo "Enabling Finance Extras extension..."
cv en financeextras

# Setup test database
echo "Creating test database..."
echo "CREATE DATABASE IF NOT EXISTS civicrm_test;" | mysql -u root --password=root --host=mysql

# Update civicrm.settings.php with test DB DSN
echo "Configuring test database DSN..."
FILE_PATH="$CIVICRM_SETTINGS_DIR/civicrm.settings.php"
if ! grep -q "\$GLOBALS\['_CV'\]\['TEST_DB_DSN'\] =" "$FILE_PATH"; then
    INSERT_LINE="\$GLOBALS['_CV']['TEST_DB_DSN'] = 'mysql://root:root@mysql:3306/civicrm_test?new_link=true';"
    TMP_FILE=$(mktemp)
    while IFS= read -r line
    do
        echo "$line" >> "$TMP_FILE"
        if [ "$line" = "<?php" ]; then
            echo "$INSERT_LINE" >> "$TMP_FILE"
        fi
    done < "$FILE_PATH"
    mv "$TMP_FILE" "$FILE_PATH"
    echo "TEST_DB_DSN added successfully"
else
    echo "TEST_DB_DSN already configured"
fi

echo "✅ Setup complete! Finance Extras is ready for testing."
