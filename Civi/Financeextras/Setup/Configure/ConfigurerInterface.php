<?php

namespace Civi\Financeextras\Setup\Configure;

/**
 * Describes the interface for things we want to configure
 * on existing entities or for configuring certain default settings
 * during the extension installation.
 *
 */
interface ConfigurerInterface {

  /**
   * Applies the configuration.
   */
  public function apply();

}
