-- Import Data Guru SMP Namira
-- Password default user: NIY (jika ada) atau 'guru123'

-- 1. Fajar Adi Pamungkas, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Fajar Adi Pamungkas, S.Pd.', 'pamungkas.fajar26@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'pamungkas.fajar26@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Fajar Adi Pamungkas, S.Pd.', '91891909', 'L', '082338398026', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 2. Rima Kesuma, S.Si.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Rima Kesuma, S.Si.', 'ummififa31@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'ummififa31@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Rima Kesuma, S.Si.', '91821903', 'P', '082280091982', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 3. Erna Junaidah, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Erna Junaidah, S.Pd.', 'ernaidah@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'ernaidah@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Erna Junaidah, S.Pd.', '91921904', 'P', '082301281263', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 4. Anik Darwati, S.Pd.I.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Anik Darwati, S.Pd.I.', 'anikdarwati48@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'anikdarwati48@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Anik Darwati, S.Pd.I.', '91861913', 'P', '085236418848', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 5. Wahyu Agus Heriadi
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Wahyu Agus Heriadi', 'heriadi15agustus@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'heriadi15agustus@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Wahyu Agus Heriadi', '92721910', 'L', '081336237717', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 6. Muriyanto
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Muriyanto', 'muriyanto@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'muriyanto@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Muriyanto', '92591919', 'L', '085253611643', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 7. Sinta Pratiwi, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Sinta Pratiwi, S.Pd.', 'shentapratiwi@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'shentapratiwi@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Sinta Pratiwi, S.Pd.', '91951917', 'P', '085704072802', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 8. Kartika Wulan, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Kartika Wulan, S.Pd.', 'kartikawulan2015@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'kartikawulan2015@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Kartika Wulan, S.Pd.', '91931918', 'P', '085649131573', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 9. R. Budiono
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('R. Budiono', 'r.budiono1967@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'r.budiono1967@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'R. Budiono', '92672021', 'L', '082228654141', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 10. Ghufronuddaroini
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Ghufronuddaroini', 'ghufron.n2020@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'ghufron.n2020@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Ghufronuddaroini', '92892022', 'L', '085234361347', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 11. Darwin Djeni, S.Pd, M.Sc.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Darwin Djeni, S.Pd, M.Sc.', 'darwindjeni15@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'darwindjeni15@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Darwin Djeni, S.Pd, M.Sc.', '91902027', 'L', '082264605878', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 12. Alfido Fauzy Zakaria, S.Pd., M.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Alfido Fauzy Zakaria, S.Pd., M.Pd.', 'alfidofauzy@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'alfidofauzy@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Alfido Fauzy Zakaria, S.Pd., M.Pd.', '91942028', 'L', '081559837707', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 13. Dandi Pratama Putrawattimena
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Dandi Pratama Putrawattimena', 'dp0513463@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'dp0513463@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Dandi Pratama Putrawattimena', '92992133', 'L', '0895334324850', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 14. Dimas Eko Cahyono, M.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Dimas Eko Cahyono, M.Pd.', 'samsamid38@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'samsamid38@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Dimas Eko Cahyono, M.Pd.', '91982135', 'L', '085290088187', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 15. Nurul Kurniyasih, S.Kom.I.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Nurul Kurniyasih, S.Kom.I.', 'kurniasihnurul2016@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'kurniasihnurul2016@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Nurul Kurniyasih, S.Kom.I.', '91932134', 'P', '085875499144', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 16. Muhammad Fathur Rozi, S.Pd., M.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Muhammad Fathur Rozi, S.Pd., M.Pd.', 'rozi8917@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'rozi8917@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Muhammad Fathur Rozi, S.Pd., M.Pd.', '91972137', 'L', '085334222252', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 17. Rizki Dwi Karunia Sari, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Rizki Dwi Karunia Sari, S.Pd.', 'rizkidks23@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'rizkidks23@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Rizki Dwi Karunia Sari, S.Pd.', '91992138', 'L', '0895632660986', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 18. Nadia Pramadianti, S.M.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Nadia Pramadianti, S.M.', 'npramadianti83@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'npramadianti83@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Nadia Pramadianti, S.M.', '92982139', 'P', '085294893887', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 19. Natasha Dwike Yuni Astutik, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Natasha Dwike Yuni Astutik, S.Pd.', 'natashadwikeyuni@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'natashadwikeyuni@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Natasha Dwike Yuni Astutik, S.Pd.', '91962242', 'P', '08980586411', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 20. Kartika Ardi Chumairoh, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Kartika Ardi Chumairoh, S.Pd.', 'kartikachumairoh@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'kartikachumairoh@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Kartika Ardi Chumairoh, S.Pd.', '91982243', 'P', '081327691026', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 21. Ma\'sum Ali Ridlwan, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Ma\'sum Ali Ridlwan, S.Pd.', 'maksumridlwan74@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'maksumridlwan74@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Ma\'sum Ali Ridlwan, S.Pd.', '91922244', 'L', '08887056492', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 22. Siti Hanifah, S.Pd.I.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Siti Hanifah, S.Pd.I.', 'sitihanifah.17lj@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'sitihanifah.17lj@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Siti Hanifah, S.Pd.I.', '91922247', 'P', '082318320550', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 23. Laila Nur Hamidah, M.Pd.I.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Laila Nur Hamidah, M.Pd.I.', 'ayla.hamidah@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'ayla.hamidah@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Laila Nur Hamidah, M.Pd.I.', '91922248', 'P', '085791903154', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 24. Anya Veda Eine Putri, S.Tr. Sos.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Anya Veda Eine Putri, S.Tr. Sos.', 'bknamiraanya@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'bknamiraanya@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Anya Veda Eine Putri, S.Tr. Sos.', '91992246', 'P', '081333291265', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 25. Imam Thobroni
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Imam Thobroni', 'imamroni720@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'imamroni720@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Imam Thobroni', '91942352', 'L', '085334573375', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 26. Ahmad Shiddiq, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Ahmad Shiddiq, S.Pd.', 'asibnuhusain@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'asibnuhusain@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Ahmad Shiddiq, S.Pd.', '91982350', 'L', '089602007321', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 27. Faizah Ulinnuha, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Faizah Ulinnuha, S.Pd.', 'faizahu9@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'faizahu9@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Faizah Ulinnuha, S.Pd.', '91972353', 'P', '089514539031', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 28. Dimas Sidianto
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Dimas Sidianto', 'dimas@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'dimas@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Dimas Sidianto', '92752355', 'L', '085819758211', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 29. Robin Hedra Jaya, S.Sn.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Robin Hedra Jaya, S.Sn.', 'rhendra.jaya93@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'rhendra.jaya93@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Robin Hedra Jaya, S.Sn.', '91932354', 'L', '082233110775', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 30. M. Irfani
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('M. Irfani', 'fanni030899@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'fanni030899@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'M. Irfani', '91992353', 'L', '082140503787', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 31. Rofianto, S.Kom
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Rofianto, S.Kom', 'antorofi48@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'antorofi48@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Rofianto, S.Kom', '91932356', 'L', '082244695970', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 32. Siti Maria Ulfa, S.Pd.I.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Siti Maria Ulfa, S.Pd.I.', 'sitimariaulfa338@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'sitimariaulfa338@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Siti Maria Ulfa, S.Pd.I.', '91922457', 'P', '085707546704', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 33. Diana Nuriyah
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Diana Nuriyah', 'nd3221823@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'nd3221823@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Diana Nuriyah', '91032458', 'P', '082131007760', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 34. Hikal Fikri
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Hikal Fikri', 'hikalf19@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'hikalf19@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Hikal Fikri', '91042459', 'L', '0881036175600', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 35. Sela Agustin, S.E.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Sela Agustin, S.E.', 'sela@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'sela@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Sela Agustin, S.E.', '91982460', 'P', '082139220521', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 36. Holil Abdullatif, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Holil Abdullatif, S.Pd.', 'holilabdullatif100@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'holilabdullatif100@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Holil Abdullatif, S.Pd.', '91972461', 'L', '085204581497', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 37. Rosyidatul Ainia, S.Pd.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Rosyidatul Ainia, S.Pd.', 'ainiarosyidatul03@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'ainiarosyidatul03@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Rosyidatul Ainia, S.Pd.', '91022462', 'P', '085745620031', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 38. Sulthaan Randy Zhaafirzi
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Sulthaan Randy Zhaafirzi', 'sulthaan@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'sulthaan@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Sulthaan Randy Zhaafirzi', '92222463', 'L', '', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 39. Muhammad Zhavrilo Zahirzi
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Muhammad Zhavrilo Zahirzi', 'muhammad@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'muhammad@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Muhammad Zhavrilo Zahirzi', '92222464', 'L', '', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 40. Izzatul Maula
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Izzatul Maula', 'izzatul@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'izzatul@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Izzatul Maula', '91982566', 'P', '081913855027', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 41. Nailatul Ilmi, S.Psi.
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Nailatul Ilmi, S.Psi.', 'nailatul@namira.school', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'nailatul@namira.school' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Nailatul Ilmi, S.Psi.', '91032567', 'P', '082257083244', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

-- 42. Wachid Hasyim
INSERT INTO users (name, email, password, created_at, updated_at) 
VALUES ('Wachid Hasyim', 'hwachsym003@gmail.com', '$2y$12$e6y.q0hR9V1sQJ3HqW/H2.kRzP8xZ/YVb9Q0k1gZ2YVb9Q0k1gZ2', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE name = VALUES(name);

SET @u_id = (SELECT id FROM users WHERE email = 'hwachsym003@gmail.com' LIMIT 1);
SET @smp_id = (SELECT id FROM units WHERE code = 'SMP' OR name LIKE '%SMP%' LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id) 
VALUES (@role_id, 'App\\Models\\User', @u_id, @smp_id) 
ON DUPLICATE KEY UPDATE team_id = @smp_id;

INSERT INTO teachers (user_id, unit_id, full_name, nip, gender, phone, created_at, updated_at) 
VALUES (@u_id, @smp_id, 'Wachid Hasyim', '91972568', 'L', '081331017757', NOW(), NOW()) 
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), nip = VALUES(nip), gender = VALUES(gender), phone = VALUES(phone);

