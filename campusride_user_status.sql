-- =========================================================
-- CAMPUSRIDE USER ACCOUNT STATUS
-- Run this once after importing CampusRide_complete.sql.
-- =========================================================

CREATE TABLE IF NOT EXISTS user_account_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    status_name VARCHAR(30) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO user_account_status (status_name)
VALUES
('Active'),
('Pending Verification'),
('Suspended')
ON DUPLICATE KEY UPDATE status_name = status_name;

ALTER TABLE `USER`
ADD COLUMN account_status VARCHAR(30) NOT NULL DEFAULT 'Active';

ALTER TABLE `USER`
ADD CONSTRAINT fk_user_account_status
FOREIGN KEY (account_status)
REFERENCES user_account_status(status_name)
ON UPDATE CASCADE
ON DELETE RESTRICT;

-- Verify
SELECT
    u.user_id,
    u.full_name,
    u.account_status
FROM `USER` u
ORDER BY u.user_id;
