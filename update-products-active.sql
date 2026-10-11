-- Run once on an existing database. Existing products remain active.
-- For a new database, use pharmacy.sql instead.
USE price_line_pharmacy_dev;
ALTER TABLE products ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
