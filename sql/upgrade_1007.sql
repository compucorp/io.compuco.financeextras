-- Idempotent: only adds the column if it isn't already present.
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'civicrm_value_credit_note_ext_id'
    AND COLUMN_NAME = 'created_at'
);
SET @ddl := IF(
  @col_exists = 0,
  'ALTER TABLE `civicrm_value_credit_note_ext_id` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
  'SELECT 1'
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
