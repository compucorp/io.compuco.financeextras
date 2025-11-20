#!/bin/bash
# Centralized environment configuration for CiviCRM development environments

# Default CiviCRM and CMS versions
export DEFAULT_CIVICRM_VERSION="6.4.1"
export DEFAULT_CMS_VERSION="7.100"


# Legacy civix environment (for reference - only needed if civix has issues with newer versions)
export LEGACY_CIVIX_CIVICRM_VERSION="5.51.3"
export LEGACY_CIVIX_CMS_VERSION="7.94"

# Common paths
export WEB_ROOT="/build/site"
export CIVICRM_EXTENSIONS_DIR="$WEB_ROOT/web/sites/all/modules/civicrm/tools/extensions"
export CIVICRM_SETTINGS_DIR="$WEB_ROOT/web/sites/default"
