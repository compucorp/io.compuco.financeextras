CREATE TABLE IF NOT EXISTS `financeextras_credit_note` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique CreditNote ID',
  `contact_id` int unsigned COMMENT 'FK to Contact',
  `cn_number` varchar(11),
  `date` date COMMENT 'Quotation date',
  `status_id` int unsigned NOT NULL COMMENT 'One of the values of the financeextras_credit_note_status option group',
  `reference` varchar(11),
  `currency` varchar(3) DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `description` text NULL COMMENT 'Credit note description',
  `comment` text NULL COMMENT 'Credit note comment',
  `subtotal` decimal(20,2) NULL COMMENT 'Total of all the total price fields',
  `sales_tax` decimal(20,2) NULL COMMENT 'Credit note sales tax total',
  `total_credit` decimal(20,2) NULL COMMENT 'Total value of the credit note',
  PRIMARY KEY (`id`),
  CONSTRAINT FK_financeextras_credit_note_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE CASCADE
)
ENGINE=InnoDB;
