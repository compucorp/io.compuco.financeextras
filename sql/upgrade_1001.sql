-- /*******************************************************
-- *
-- * financeextras_credit_note_line
-- *
-- * Increase the quantity table to 5 D.P.
-- *
-- *******************************************************/
ALTER TABLE `financeextras_credit_note_line` CHANGE `quantity` `quantity` DECIMAL(20,4) NULL DEFAULT NULL COMMENT 'Quantity';
