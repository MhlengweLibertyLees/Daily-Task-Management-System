seed users


INSERT INTO users (full_name, email, role, password_hash)
VALUES 
('System Admin', 'admin@thaboera.co.za', 'admin', 'PASTE_ADMIN_HASH_HERE'),
('Test Member', 'member@thaboera.co.za', 'member', 'PASTE_MEMBER_HASH_HERE');
----------------------------------------------------
INSERT INTO users (full_name, email, role, password_hash)
VALUES 
('System Admin', 'admin@thaboera.co.za', 'admin', '$2y$10$p6zwW9KPu.PtCVnBGUgJI.GYJrc4QAHfa1VYzKxAQ3qeKYb9N2foq'),           
('Test Member', 'member@thaboera.co.za', 'member', '$2y$10$Z9zQADOF8VMyJwI8s/Z5v.NU9K2QYRR5w71yq.T0QHVFfGTmRcupe');