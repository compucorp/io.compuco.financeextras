<?php


class CRM_Financeextras_Page_AJAX {

  /**
   * Returns list of transactions for accounting batch (Credit notes and contributions)
   */
  public static function getFinancialTransactionsList() {
    $sortMapper = [
      0 => '',
      1 => '',
      2 => 'sort_name',
      3 => 'amount',
      4 => 'trxn_id',
      5 => 'transaction_date',
      6 => 'receive_date',
      7 => 'payment_method',
      8 => 'status',
      9 => 'name',
    ];

    $sEcho = CRM_Utils_Type::escape($_REQUEST['sEcho'], 'Integer');
    $return = isset($_REQUEST['return']) ? CRM_Utils_Type::escape($_REQUEST['return'], 'Boolean') : FALSE;
    $offset = isset($_REQUEST['iDisplayStart']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayStart'], 'Integer') : 0;
    $rowCount = isset($_REQUEST['iDisplayLength']) ? CRM_Utils_Type::escape($_REQUEST['iDisplayLength'], 'Integer') : 25;
    $sort = isset($_REQUEST['iSortCol_0']) ? CRM_Utils_Array::value(CRM_Utils_Type::escape($_REQUEST['iSortCol_0'], 'Integer'), $sortMapper) : NULL;
    $sortOrder = isset($_REQUEST['sSortDir_0']) ? CRM_Utils_Type::escape($_REQUEST['sSortDir_0'], 'String') : 'asc';
    $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
    $entityID = isset($_REQUEST['entityID']) ? CRM_Utils_Type::escape($_REQUEST['entityID'], 'String') : NULL;
    $notPresent = isset($_REQUEST['notPresent']) ? CRM_Utils_Type::escape($_REQUEST['notPresent'], 'String') : NULL;
    $statusID = isset($_REQUEST['statusID']) ? CRM_Utils_Type::escape($_REQUEST['statusID'], 'String') : NULL;
    $search = isset($_REQUEST['search']);

    $params = $_POST;
    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $returnvalues = [
      'civicrm_financial_trxn.payment_instrument_id as payment_method',
    // @custom-code start: contactID from contribution or credit note
      'COALESCE(civicrm_contribution.contact_id, financeextras_credit_note.contact_id) as contact_id',
    // @custom-code end
      'civicrm_contribution.id as contributionID',
      'contact_a.sort_name',
      'civicrm_financial_trxn.total_amount as amount',
      'civicrm_financial_trxn.trxn_id as trxn_id',
      'contact_a.contact_type',
      'contact_a.contact_sub_type',
      'civicrm_financial_trxn.trxn_date as transaction_date',
    // @custom-code start: date from contribution or credit note
      'COALESCE(civicrm_contribution.receive_date, financeextras_credit_note.date) as receive_date',
    // @custom-code end
      'civicrm_financial_type.name',
      'civicrm_financial_trxn.currency as currency',
      'civicrm_financial_trxn.status_id as status',
      'civicrm_financial_trxn.check_number as check_number',
      'civicrm_financial_trxn.card_type_id',
      'civicrm_financial_trxn.pan_truncation',
      'financeextras_credit_note.id as creditnote_id',
      'civicrm_entity_financial_trxn.entity_table as entity_table',
    ];

    $columnHeader = [
      'contact_type' => '',
      'sort_name' => ts('Contact Name'),
      'amount' => ts('Amount'),
      'trxn_id' => ts('Trxn ID'),
      'transaction_date' => ts('Transaction Date'),
      'receive_date' => ts('Contribution Date'),
      'payment_method' => ts('Payment Method'),
      'status' => ts('Status'),
      'name' => ts('Type'),
    ];

    if ($sort && $sortOrder) {
      $params['sortBy'] = $sort . ' ' . $sortOrder;
    }

    $params['page'] = ($offset / $rowCount) + 1;
    $params['rp'] = $rowCount;

    $params['context'] = $context;
    $params['offset'] = ($params['page'] - 1) * $params['rp'];
    $params['rowCount'] = $params['rp'];
    $params['sort'] = $params['sortBy'] ?? NULL;
    $params['total'] = 0;

    // get batch list
    if (isset($notPresent)) {
      $financialItem = self::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params);
      if ($search) {
        $unassignedTransactions = self::getBatchFinancialItems($entityID, $returnvalues, $notPresent, $params, TRUE);
      }
      else {
        $unassignedTransactions = self::getBatchFinancialItems($entityID, $returnvalues, $notPresent, NULL, TRUE);
      }
      while ($unassignedTransactions->fetch()) {
        $unassignedTransactionsCount[] = $unassignedTransactions->id;
      }
      if (!empty($unassignedTransactionsCount)) {
        $params['total'] = count($unassignedTransactionsCount);
      }

    }
    else {
      $financialItem = self::getBatchFinancialItems($entityID, $returnvalues, NULL, $params);
      $assignedTransactions = self::getBatchFinancialItems($entityID, $returnvalues);
      while ($assignedTransactions->fetch()) {
        $assignedTransactionsCount[] = $assignedTransactions->id;
      }
      if (!empty($assignedTransactionsCount)) {
        $params['total'] = count($assignedTransactionsCount);
      }
    }
    $financialitems = [];
    if ($statusID) {
      $batchStatuses = CRM_Core_PseudoConstant::get('CRM_Batch_DAO_Batch', 'status_id', ['labelColumn' => 'name', 'condition' => " v.value={$statusID}"]);
      $batchStatus = $batchStatuses[$statusID];
    }
    while ($financialItem->fetch()) {
      $row[$financialItem->id] = [];
      foreach ($columnHeader as $columnKey => $columnValue) {
        if ($financialItem->contact_sub_type && $columnKey == 'contact_type') {
          $row[$financialItem->id][$columnKey] = $financialItem->contact_sub_type;
          continue;
        }
        $row[$financialItem->id][$columnKey] = $financialItem->$columnKey;
        if ($columnKey == 'sort_name' && $financialItem->$columnKey && $financialItem->contact_id) {
          $url = CRM_Utils_System::url('civicrm/contact/view', "reset=1&cid=" . $financialItem->contact_id);
          $row[$financialItem->id][$columnKey] = '<a href=' . $url . '>' . $financialItem->$columnKey . '</a>';
        }
        elseif ($columnKey == 'payment_method' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Batch_BAO_Batch', 'payment_instrument_id', $financialItem->$columnKey);
          if ($row[$financialItem->id][$columnKey] == 'Check') {
            $checkNumber = $financialItem->check_number ? ' (' . $financialItem->check_number . ')' : '';
            $row[$financialItem->id][$columnKey] = $row[$financialItem->id][$columnKey] . $checkNumber;
          }
        }
        elseif ($columnKey === 'amount' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = Civi::format()->money($financialItem->$columnKey, $financialItem->currency);
        }
        elseif ($columnKey === 'transaction_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'receive_date' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Utils_Date::customFormat($financialItem->$columnKey);
        }
        elseif ($columnKey == 'status' && $financialItem->$columnKey) {
          $row[$financialItem->id][$columnKey] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $financialItem->$columnKey);
        }
      }
      if (isset($batchStatus) && in_array($batchStatus, ['Open', 'Reopened'])) {
        if (isset($notPresent)) {
          $js = "enableActions('x')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_x_" . $financialItem->id . "' name='mark_x_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            self::updateTransactionLinks($financialItem, (new CRM_Financial_Form_BatchTransaction())->links()),
            NULL,
            [
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
              'creditnoteid' => $financialItem->creditnote_id,
            ],
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
        else {
          $js = "enableActions('y')";
          $row[$financialItem->id]['check'] = "<input type='checkbox' id='mark_y_" . $financialItem->id . "' name='mark_y_" . $financialItem->id . "' value='1' onclick={$js}></input>";
          $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
            self::updateTransactionLinks($financialItem, (new CRM_Financial_Page_BatchTransaction())->links()),
            NULL,
            [
              'id' => $financialItem->id,
              'contid' => $financialItem->contributionID,
              'cid' => $financialItem->contact_id,
            ],
            ts('more'),
            FALSE,
            'financialItem.batch.row',
            'FinancialItem',
            $financialItem->id
          );
        }
      }
      else {
        $row[$financialItem->id]['check'] = NULL;
        $tempBAO = new CRM_Financial_Page_BatchTransaction();
        $links = self::updateTransactionLinks($financialItem, $tempBAO->links());
        unset($links['remove']);
        $row[$financialItem->id]['action'] = CRM_Core_Action::formLink(
          $links,
          NULL,
          [
            'id' => $financialItem->id,
            'contid' => $financialItem->contributionID,
            'cid' => $financialItem->contact_id,
          ],
          ts('more'),
          FALSE,
          'financialItem.batch.row',
          'FinancialItem',
          $financialItem->id
        );
      }
      if ($financialItem->contact_id) {
        $row[$financialItem->id]['contact_type'] = CRM_Contact_BAO_Contact_Utils::getImage(!empty($row[$financialItem->id]['contact_sub_type']) ? $row[$financialItem->id]['contact_sub_type'] : CRM_Utils_Array::value('contact_type', $row[$financialItem->id]), FALSE, $financialItem->contact_id);
      }
      // @todo: Is this right? Shouldn't it be adding to the array as we loop?
      $financialitems = $row;
    }

    $iFilteredTotal = $iTotal = $params['total'];
    $selectorElements = [
      'check',
      'contact_type',
      'sort_name',
      'amount',
      'trxn_id',
      'transaction_date',
      'receive_date',
      'payment_method',
      'status',
      'name',
      'action',
    ];

    if ($return) {
      return CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    }
    CRM_Utils_System::setHttpHeader('Content-Type', 'application/json');
    echo CRM_Utils_JSON::encodeDataTableSelector($financialitems, $sEcho, $iTotal, $iFilteredTotal, $selectorElements);
    CRM_Utils_System::civiExit();
  }

  /**
   * Retrieve financial items assigned for a batch.
   *
   * @param int $entityID
   * @param array $returnValues
   * @param bool $notPresent
   * @param array $params
   * @param bool $getCount
   *
   * @return CRM_Core_DAO
   */
  public static function getBatchFinancialItems($entityID, $returnValues, $notPresent = NULL, $params = NULL, $getCount = FALSE) {
    if (!$getCount) {
      if (!empty($params['rowCount']) &&
        $params['rowCount'] > 0
      ) {
        $limit = " LIMIT {$params['offset']}, {$params['rowCount']} ";
      }
    }
    // action is taken depending upon the mode
    $select = 'civicrm_financial_trxn.id ';
    if (!empty($returnValues)) {
      $select .= " , " . implode(' , ', $returnValues);
    }

    $orderBy = " ORDER BY civicrm_financial_trxn.id";
    if (!empty($params['sort'])) {
      $orderBy = ' ORDER BY ' . CRM_Utils_Type::escape($params['sort'], 'String');
    }

    $from = "civicrm_financial_trxn
    INNER JOIN civicrm_entity_financial_trxn ON civicrm_entity_financial_trxn.financial_trxn_id = civicrm_financial_trxn.id
    LEFT JOIN civicrm_contribution ON (civicrm_contribution.id = civicrm_entity_financial_trxn.entity_id
      AND civicrm_entity_financial_trxn.entity_table='civicrm_contribution')
    -- @custom-code start: Join the credit note table
    LEFT JOIN financeextras_credit_note ON (financeextras_credit_note.id = civicrm_entity_financial_trxn.entity_id 
      AND civicrm_entity_financial_trxn.entity_table='financeextras_credit_note')
    -- @custom-code end 
    LEFT JOIN civicrm_entity_batch ON civicrm_entity_batch.entity_table = 'civicrm_financial_trxn'
    AND civicrm_entity_batch.entity_id = civicrm_financial_trxn.id
    LEFT JOIN civicrm_financial_type ON civicrm_financial_type.id = civicrm_contribution.financial_type_id
    -- @custom-code start:  Join contact from either contribution or credit note contact ID column
    LEFT JOIN civicrm_contact contact_a ON contact_a.id = COALESCE(civicrm_contribution.contact_id, financeextras_credit_note.contact_id)
    -- @custom-code end
    LEFT JOIN civicrm_contribution_soft ON civicrm_contribution_soft.contribution_id = civicrm_contribution.id
    ";

    $searchFields = [
      'sort_name',
      'financial_type_id',
      'contribution_page_id',
      'contribution_payment_instrument_id',
      'contribution_trxn_id',
      'contribution_source',
      'contribution_currency_type',
      'contribution_pay_later',
      'contribution_recurring',
      'contribution_test',
      'contribution_thankyou_date_is_not_null',
      'contribution_receipt_date_is_not_null',
      'contribution_pcp_made_through_id',
      'contribution_pcp_display_in_roll',
      'contribution_amount_low',
      'contribution_amount_high',
      'contribution_in_honor_of',
      'contact_tags',
      'group',
      'receive_date_relative',
      'receive_date_high',
      'receive_date_low',
      'contribution_check_number',
      'contribution_status_id',
      'financial_trxn_card_type_id',
      'financial_trxn_pan_truncation',
    ];
    $values = $customJoins = [];

    // If a custom field was passed as a param,
    // we'll take it into account.
    $customSearchFields = [];
    if (!empty($params)) {
      foreach ($params as $name => $param) {
        if (substr($name, 0, 6) == 'custom') {
          $searchFields[] = $name;
        }
      }
    }

    foreach ($searchFields as $field) {
      if (isset($params[$field])) {
        $values[$field] = $params[$field];
        if ($field == 'sort_name') {
          $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id
          LEFT JOIN civicrm_email ON contact_b.id = civicrm_email.contact_id";
        }
        if ($field == 'contribution_in_honor_of') {
          $from .= " LEFT JOIN civicrm_contact contact_b ON contact_b.id = civicrm_contribution.contact_id";
        }
        if ($field == 'contact_tags') {
          $from .= " LEFT JOIN civicrm_entity_tag `civicrm_entity_tag-{$params[$field]}` ON `civicrm_entity_tag-{$params[$field]}`.entity_id = contact_a.id";
        }
        if ($field == 'group') {
          $from .= " LEFT JOIN civicrm_group_contact `civicrm_group_contact-{$params[$field]}` ON contact_a.id = `civicrm_group_contact-{$params[$field]}`.contact_id ";
        }
        if ($field == 'receive_date_relative') {
          $relativeDate = explode('.', $params[$field]);
          $date = CRM_Utils_Date::relativeToAbsolute($relativeDate[0], $relativeDate[1]);
          $values['receive_date_low'] = $date['from'];
          $values['receive_date_high'] = $date['to'];
        }

        // Add left joins as they're needed to consider
        // conditions over custom fields.
        if (substr($field, 0, 6) == 'custom') {
          $customFieldParams = ['id' => explode('_', $field)[1]];
          $customFieldDefaults = [];
          $customField = CRM_Core_BAO_CustomField::retrieve($customFieldParams, $customFieldDefaults);

          $customGroupParams = ['id' => $customField->custom_group_id];
          $customGroupDefaults = [];
          $customGroup = CRM_Core_BAO_CustomGroup::retrieve($customGroupParams, $customGroupDefaults);

          $columnName = $customField->column_name;
          $tableName = $customGroup->table_name;

          if (!array_key_exists($tableName, $customJoins)) {
            $customJoins[$tableName] = "LEFT JOIN $tableName ON $tableName.entity_id = civicrm_contribution.id";
          }
        }
      }
    }

    $searchParams = CRM_Contact_BAO_Query::convertFormValues(
      $values,
      0,
      FALSE,
      NULL,
      [
        'financial_type_id',
        'contribution_soft_credit_type_id',
        'contribution_status_id',
        'contribution_page_id',
        'financial_trxn_card_type_id',
        'contribution_payment_instrument_id',
      ]
    );
    // @todo the use of defaultReturnProperties means the search will be inefficient
    // as slow-unneeded properties are included.
    $query = new CRM_Contact_BAO_Query($searchParams,
      CRM_Contribute_BAO_Query::defaultReturnProperties(CRM_Contact_BAO_Query::MODE_CONTRIBUTE,
        FALSE
      ), NULL, FALSE, FALSE, CRM_Contact_BAO_Query::MODE_CONTRIBUTE
    );

    if (count($customJoins) > 0) {
      $from .= " " . implode(" ", $customJoins);
    }

    if (!empty($query->_where[0])) {
      $where = implode(' AND ', $query->_where[0]) . " AND civicrm_entity_batch.batch_id IS NULL ";
      // @custom-code start: Incorporate credit note transactions when the default search option is used (i.e., non-test contribution transactions).
      $where = str_replace("civicrm_contribution.is_test = 0", "(civicrm_contribution.is_test = 0 OR civicrm_entity_financial_trxn.entity_table = 'financeextras_credit_note')", $where);
      // @custom-code end
      $where = str_replace('civicrm_contribution.payment_instrument_id', 'civicrm_financial_trxn.payment_instrument_id', $where);
    }
    else {
      // @custom-code start:  Consider credit notes when calculating batch transaction summaries.
      $where = "civicrm_entity_financial_trxn.entity_table IN ('financeextras_credit_note', 'civicrm_contribution')";
      // @custom-code end
      if (!$notPresent) {
        $where .= " AND civicrm_entity_batch.batch_id = {$entityID} ";
      }
      else {
        $where .= " AND civicrm_entity_batch.batch_id IS NULL ";
      }
    }

    $sql = "
    SELECT {$select}
    FROM   {$from}
    WHERE  {$where}
          {$orderBy}
    ";

    if (isset($limit)) {
      $sql .= "{$limit}";
    }

    $result = CRM_Core_DAO::executeQuery($sql);
    return $result;
  }

  /**
   * Updates links for transaction rows.
   *
   * If the row represents a credit note,
   * it uses the credit note view link and opens it in a new tab.
   * This is necessary because the credit note pages are built with Angular,
   * and they were not opening properly in the default popup tab.
   *
   * @param \CRM_Core_DAO $row
   *   The current row.
   * @param array $links
   *   The default links for the row.
   *
   * @return array
   *   The updated links.
   */
  public static function updateTransactionLinks($row, array $links) {
    if (!empty($links['view'])  && $row->entity_table == 'financeextras_credit_note') {
      $links['view']['url'] = 'civicrm/contribution/creditnote/view';
      $links['view']['qs'] = 'reset=1&id=%%creditnoteid%%&cid=%%cid%%&action=view';
      $links['view']['fe'] = TRUE;
      $links['view']['class'] = 'no-popup';
    }

    return $links;
  }

}
