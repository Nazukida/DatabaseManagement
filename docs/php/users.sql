-- 1. Create a RESTRICTED user (For Customer, Merchant, Rider)
-- This user allows "partial information" access only.
-- It strictly CANNOT see the 'admin' table.
CREATE USER 'linli_safe'@'localhost' IDENTIFIED BY 'safe123';

-- Grant read-only access ONLY to specific public tables
GRANT SELECT ON dbms.users TO 'linli_safe'@'localhost';
GRANT SELECT ON dbms.restaurants TO 'linli_safe'@'localhost';
GRANT SELECT ON dbms.riders TO 'linli_safe'@'localhost';
-- NOTICE: We intentionally do NOT grant access to the 'admin' table here.


-- 2. Create a PRIVILEGED user (For Admin login only)
-- This user has the highest authority.
CREATE USER 'linli_admin'@'localhost' IDENTIFIED BY 'admin888';

-- Grant full access to everything
GRANT ALL PRIVILEGES ON dbms.* TO 'linli_admin'@'localhost';


-- 3. Apply changes
FLUSH PRIVILEGES;