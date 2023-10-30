-- /*******************************************************
-- *
-- * Clean up the existing tables - this section generated from drop.tpl
-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `financeextras_exchange_rate`;

SET FOREIGN_KEY_CHECKS=1;
-- /*******************************************************
-- *
-- * Create new tables
-- *
-- *******************************************************/

-- /*******************************************************
-- *
-- * financeextras_exchange_rate
-- *
-- * Exchange Rate Entity
-- *
-- *******************************************************/
CREATE TABLE `financeextras_exchange_rate` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unique ExchangeRate ID',
  `exchange_date` date COMMENT 'Exchange rate date',
  `base_currency` varchar(3) DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `conversion_currency` varchar(3) DEFAULT NULL COMMENT '3 character string, value from config setting or input via user.',
  `base_to_conversion_rate` decimal(20,2) NULL COMMENT 'The number of the converted currency to the base currency.',
  `conversion_to_base_rate` decimal(20,2) NULL COMMENT 'The number of the Base currency to the converted currency.',
  PRIMARY KEY (`id`)
)
ENGINE=InnoDB;
