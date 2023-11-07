---- /*******************************************************
-- *
-- * Clean up the existing tables-- *
-- *******************************************************/

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `financeextras_credit_note_line`;
DROP TABLE IF EXISTS `financeextras_credit_note_allocation`;
DROP TABLE IF EXISTS `financeextras_credit_note`;
DROP TABLE IF EXISTS `financeextras_company`;
DROP TABLE IF EXISTS `financeextras_batch_owner_org`;

SET FOREIGN_KEY_CHECKS=1;
