<?php

namespace Civi\Financeextras\Hook\Container;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ServiceContainer
 * @package Civi\Financeextras\Hook\Container
 */
class ServiceContainer {

  /**
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  private $container;

  /**
   * ServiceContainer constructor.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  public function __construct(ContainerBuilder $container) {
    $this->container = $container;
  }

  /**
   * Registers services to container.
   */
  public function register() {
    $this->container->setDefinition('service.credit_note_invoice',
      new Definition(
        \Civi\Financeextras\Service\CreditNoteInvoiceService::class,
        []
      )
    )->setAutowired(TRUE)->setPublic(TRUE);

    $this->container->setDefinition('workflow.message.credit_note_invoice',
      new Definition(
        \Civi\Financeextras\WorkflowMessage\CreditNoteInvoice::class,
        []
      )
    )->setAutowired(TRUE)->setPublic(TRUE);

    $this->container->setAlias('Civi\Financeextras\WorkflowMessage\CreditNoteInvoice', 'workflow.message.credit_note_invoice');
  }

}
