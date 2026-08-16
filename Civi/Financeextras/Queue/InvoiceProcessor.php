<?php

namespace Civi\Financeextras\Queue;

use CRM_Queue_Service;
use CRM_Queue_Task;
use CRM_Queue_TaskContext;
use CRM_Queue_Runner;
use CRM_Utils_String;
use CRM_Core_Session;
use CRM_Core_Error;
use Civi\Financeextras\Hook\AlterMailParams\InvoiceTemplate;

/**
 * Queue-based invoice processor to handle bulk operations without memory explosion.
 * 
 * This addresses the critical memory issue where processing invoices consumes
 * 9.9GB instead of the expected 200KB, by processing items individually
 * through CiviCRM's queue system.
 */
class InvoiceProcessor {

  const QUEUE_PREFIX = 'invoice_processing_';

  /**
   * Create a queue for invoice processing.
   *
   * @param array $contributionIds Array of contribution IDs to process
   * @param array $options Optional configuration
   *
   * @return \CRM_Queue_Queue The created queue
   */
  public static function createInvoiceQueue($contributionIds, $options = []) {
    $queueName = $options['queue_name'] ?? self::QUEUE_PREFIX . CRM_Utils_String::createRandom(8);

    $queue = CRM_Queue_Service::singleton()->create([
      'type' => 'Sql',
      'name' => $queueName,
      'reset' => FALSE,
    ]);

    // Add each invoice as a separate task
    foreach ($contributionIds as $contributionId) {
      $task = new CRM_Queue_Task(
        ['Civi\Financeextras\Queue\InvoiceProcessor', 'processInvoice'],
        [$contributionId],
        "Process Invoice #{$contributionId}"
      );
      $queue->createItem($task);
    }

    CRM_Core_Error::debug_log_message(
      "Created invoice queue '{$queueName}' with " . count($contributionIds) . " items"
    );

    return $queue;
  }

  /**
   * Process a single invoice (memory-safe).
   *
   * @param \CRM_Queue_TaskContext $ctx Queue task context
   * @param int $contributionId The contribution ID to process
   *
   * @return bool TRUE on success
   */
  public static function processInvoice(CRM_Queue_TaskContext $ctx, $contributionId) {
    try {
      // Create template parameters for this specific invoice
      $templateParams = [
        'valueName' => 'contribution_invoice_receipt',
        'tplParams' => ['id' => $contributionId],
      ];

      // Process the invoice using existing InvoiceTemplate logic
      $invoiceTemplate = new InvoiceTemplate($templateParams, 'queue_processing');
      $invoiceTemplate->handle();

      // Force garbage collection after each item
      if (function_exists('gc_collect_cycles')) {
        gc_collect_cycles();
      }

      return TRUE;

    } catch (\Exception $e) {
      CRM_Core_Error::debug_log_message(
        "Error processing invoice #{$contributionId}: " . $e->getMessage()
      );
      
      // Log the error but don't fail the entire queue
      CRM_Core_Session::setStatus(
        ts('Failed to process invoice #%1: %2', [1 => $contributionId, 2 => $e->getMessage()]),
        ts('Invoice Processing Error'),
        'error'
      );

      return FALSE;
    }
  }

  /**
   * Run a queue with progress tracking.
   *
   * @param \CRM_Queue_Queue $queue The queue to run
   * @param array $options Optional configuration
   *
   * @return \CRM_Queue_Runner The queue runner
   */
  public static function runQueue($queue, $options = []) {
    $title = $options['title'] ?? ts('Processing Invoices');
    $errorMode = $options['error_mode'] ?? CRM_Queue_Runner::ERROR_CONTINUE;

    $runner = new CRM_Queue_Runner([
      'title' => $title,
      'queue' => $queue,
      'errorMode' => $errorMode,
      'onEnd' => function() use ($queue) {
        CRM_Core_Session::setStatus(
          ts('Invoice processing complete for queue: %1', [1 => $queue->getName()]),
          ts('Success'),
          'success'
        );
      },
      'onEndUrl' => $options['url_callback'] ?? NULL,
    ]);

    return $runner;
  }

  /**
   * Create and run a complete invoice processing queue.
   *
   * @param array $contributionIds Array of contribution IDs to process
   * @param array $options Optional configuration
   *
   * @return array Result with queue info and runner
   */
  public static function processInvoicesViaQueue($contributionIds, $options = []) {
    // Create the queue
    $queue = self::createInvoiceQueue($contributionIds, $options);
    
    // Create the runner
    $runner = self::runQueue($queue, $options);

    return [
      'queue' => $queue,
      'runner' => $runner,
      'queue_name' => $queue->getName(),
      'item_count' => count($contributionIds),
    ];
  }

  /**
   * Get queue status and progress information.
   *
   * @param string $queueName The queue name to check
   *
   * @return array Queue status information
   */
  public static function getQueueStatus($queueName) {
    try {
      $queue = CRM_Queue_Service::singleton()->load($queueName);
      
      $totalItems = $queue->numberOfItems();
      
      return [
        'queue_name' => $queueName,
        'total_items' => $totalItems,
        'status' => $totalItems > 0 ? 'pending' : 'complete',
      ];
    } catch (\Exception $e) {
      return [
        'queue_name' => $queueName,
        'error' => $e->getMessage(),
        'status' => 'error',
      ];
    }
  }

}