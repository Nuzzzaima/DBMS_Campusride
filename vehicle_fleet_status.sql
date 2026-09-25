-- CampusRide
-- Vehicle Fleet page database support
--
-- Run this once in phpMyAdmin after importing CampusRide_complete.sql.
-- The existing vehicle records are kept. New vehicles will use
-- "Pending Inspection" until an admin approves them.

ALTER TABLE vehicle
ADD COLUMN vehicle_status VARCHAR(30) NOT NULL DEFAULT 'Pending Inspection';

-- Optional examples:
-- UPDATE vehicle SET vehicle_status = 'Approved & Active' WHERE vehicle_id = 1;
-- UPDATE vehicle SET vehicle_status = 'Suspended / Expired' WHERE vehicle_id = 2;
-- UPDATE vehicle SET vehicle_status = 'Pending Inspection' WHERE vehicle_id = 3;
