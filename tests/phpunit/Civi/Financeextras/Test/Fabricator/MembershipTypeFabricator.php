<?php

namespace Civi\Financeextras\Test\Fabricator;

class MembershipTypeFabricator extends AbstractBaseFabricator {

  protected static $entityName = 'MembershipType';

  protected static $defaultParams = [
    'duration_unit' => 'year',
    'period_type' => 'fixed',
    'duration_interval' => 1,
    'fixed_period_start_day' => 101,
    'fixed_period_rollover_day' => 1231,
    'domain_id' => 1,
    'member_of_contact_id' => 1,
    'financial_type_id' => 'Member Dues',
  ];

}
