<?php

/**
 * API wrapper for queue-based invoice processing.
 * 
 * This provides a clean API interface for the queue-based approach to solve
 * the memory explosion issue (9.9GB instead of 200KB expected).
 */

/**
 * Create a queue for processing invoices.
 *
 * @param array $params API parameters
 *
 * @return array API result with queue information
 */
function civicrm_api3_invoice_queue_create($params) {
  try {
    // Validate required parameters
    if (empty($params['contribution_ids'])) {
      throw new API_Exception('contribution_ids parameter is required');
    }

    if (!is_array($params['contribution_ids'])) {
      throw new API_Exception('contribution_ids must be an array');
    }

    // Sanitize contribution IDs
    $contributionIds = array_filter($params['contribution_ids'], 'is_numeric');
    if (empty($contributionIds)) {
      throw new API_Exception('No valid contribution IDs provided');
    }

    // Prepare options
    $options = [];
    if (!empty($params['queue_name'])) {
      $options['queue_name'] = $params['queue_name'];
    }

    // Create the queue
    $queue = \Civi\Financeextras\Queue\InvoiceProcessor::createInvoiceQueue($contributionIds, $options);

    return civicrm_api3_create_success([
      'queue_name' => $queue->getName(),
      'item_count' => count($contributionIds),
    ]);

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Specification for InvoiceQueue.create API.
 */
function _civicrm_api3_invoice_queue_create_spec(&$params) {
  $params['contribution_ids'] = [
    'api.required' => 1,
    'title' => 'Contribution IDs',
    'description' => 'Array of contribution IDs to process via queue',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['queue_name'] = [
    'title' => 'Queue Name',
    'description' => 'Custom queue name (auto-generated if not provided)',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Run an existing invoice processing queue.
 *
 * @param array $params API parameters
 *
 * @return array API result with processing information
 */
function civicrm_api3_invoice_queue_run($params) {
  try {
    // Validate required parameters
    if (empty($params['queue_name'])) {
      throw new API_Exception('queue_name parameter is required');
    }

    // Load the queue
    $queue = CRM_Queue_Service::singleton()->load($params['queue_name']);
    if (!$queue) {
      throw new API_Exception("Queue '{$params['queue_name']}' not found");
    }

    // Prepare options
    $options = [];
    if (!empty($params['title'])) {
      $options['title'] = $params['title'];
    }
    if (!empty($params['error_mode'])) {
      $errorMode = $params['error_mode'] === 'abort' 
        ? CRM_Queue_Runner::ERROR_ABORT 
        : CRM_Queue_Runner::ERROR_CONTINUE;
      $options['error_mode'] = $errorMode;
    }

    // Create and run the queue
    $runner = \Civi\Financeextras\Queue\InvoiceProcessor::runQueue($queue, $options);

    // For API calls, we'll run all items immediately
    $result = $runner->runAll();

    return civicrm_api3_create_success([
      'queue_name' => $params['queue_name'],
      'items_processed' => $result['numberOfItems'],
      'is_error' => $result['is_error'],
      'exception' => $result['exception'] ?? NULL,
    ]);

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Specification for InvoiceQueue.run API.
 */
function _civicrm_api3_invoice_queue_run_spec(&$params) {
  $params['queue_name'] = [
    'api.required' => 1,
    'title' => 'Queue Name',
    'description' => 'Name of the queue to run',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['title'] = [
    'title' => 'Progress Title',
    'description' => 'Custom title for progress display',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['error_mode'] = [
    'api.default' => 'continue',
    'title' => 'Error Mode',
    'description' => 'How to handle errors: continue or abort',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Get status of an invoice processing queue.
 *
 * @param array $params API parameters
 *
 * @return array API result with queue status
 */
function civicrm_api3_invoice_queue_getstatus($params) {
  try {
    // Validate required parameters
    if (empty($params['queue_name'])) {
      throw new API_Exception('queue_name parameter is required');
    }

    $status = \Civi\Financeextras\Queue\InvoiceProcessor::getQueueStatus($params['queue_name']);

    return civicrm_api3_create_success($status);

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Specification for InvoiceQueue.getstatus API.
 */
function _civicrm_api3_invoice_queue_getstatus_spec(&$params) {
  $params['queue_name'] = [
    'api.required' => 1,
    'title' => 'Queue Name',
    'description' => 'Name of the queue to check status for',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Process invoices using queue-based approach (all-in-one method).
 *
 * @param array $params API parameters
 *
 * @return array API result with processing information
 */
function civicrm_api3_invoice_queue_process($params) {
  try {
    // Validate required parameters
    if (empty($params['contribution_ids'])) {
      throw new API_Exception('contribution_ids parameter is required');
    }

    if (!is_array($params['contribution_ids'])) {
      throw new API_Exception('contribution_ids must be an array');
    }

    // Sanitize contribution IDs
    $contributionIds = array_filter($params['contribution_ids'], 'is_numeric');
    if (empty($contributionIds)) {
      throw new API_Exception('No valid contribution IDs provided');
    }

    // Prepare options
    $options = [];
    if (!empty($params['title'])) {
      $options['title'] = $params['title'];
    }

    // Process via queue
    $result = \Civi\Financeextras\Queue\InvoiceProcessor::processInvoicesViaQueue($contributionIds, $options);

    // Run the queue immediately for API calls
    $runResult = $result['runner']->runAll();

    return civicrm_api3_create_success([
      'queue_name' => $result['queue_name'],
      'item_count' => $result['item_count'],
      'items_processed' => $runResult['numberOfItems'],
      'is_error' => $runResult['is_error'],
      'exception' => $runResult['exception'] ?? NULL,
    ]);

  } catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Specification for InvoiceQueue.process API.
 */
function _civicrm_api3_invoice_queue_process_spec(&$params) {
  $params['contribution_ids'] = [
    'api.required' => 1,
    'title' => 'Contribution IDs',
    'description' => 'Array of contribution IDs to process via queue',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $params['title'] = [
    'title' => 'Progress Title',
    'description' => 'Custom title for progress display',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}