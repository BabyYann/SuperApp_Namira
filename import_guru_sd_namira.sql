-- =================================================================
-- SCRIPT INJEKSI DATA GURU SD NAMIRA (50 GURU)
-- SuperApp Namira Database Import
-- =================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Dynamic Unit & Role Fetching (Find unit SD Namira)
SET @unit_id = (SELECT id FROM units WHERE name LIKE '%SD%' OR code = 'SD' ORDER BY id DESC LIMIT 1);
SET @role_id = (SELECT id FROM roles WHERE name = 'teacher' LIMIT 1);

-- Guru #1: Abdul Adjis Afifi
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Abdul Adjis Afifi', 'abdul@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Abdul Adjis Afifi', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'abdul@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3259201301', 'Abdul Adjis Afifi', 'L', '085204854927', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Abdul Adjis Afifi', nip = '3259201301', gender = 'L', phone = '085204854927', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #2: Anggun Happy Ananda, S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Anggun Happy Ananda, S.Pd', 'anggunhappyananda@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Anggun Happy Ananda, S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'anggunhappyananda@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3190201302', 'Anggun Happy Ananda, S.Pd', 'P', '085331362000', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Anggun Happy Ananda, S.Pd', nip = '3190201302', gender = 'P', phone = '085331362000', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #3: Hj Muthmainnah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Hj Muthmainnah', 'hj@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Hj Muthmainnah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'hj@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3175201506', 'Hj Muthmainnah', 'P', '085204610367', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Hj Muthmainnah', nip = '3175201506', gender = 'P', phone = '085204610367', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #4: Sudar
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Sudar', 'sudar@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Sudar', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'sudar@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '32201607', 'Sudar', 'L', '', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Sudar', nip = '32201607', gender = 'L', phone = '', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #5: Kholifatul Khoiriyah, S.Si
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Kholifatul Khoiriyah, S.Si', 'kholifatulk084@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Kholifatul Khoiriyah, S.Si', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'kholifatulk084@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3192201609', 'Kholifatul Khoiriyah, S.Si', 'P', '087861564895', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Kholifatul Khoiriyah, S.Si', nip = '3192201609', gender = 'P', phone = '087861564895', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #6: Hisyam Farih, S.E
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Hisyam Farih, S.E', 'anahisyam45@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Hisyam Farih, S.E', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'anahisyam45@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3291201711', 'Hisyam Farih, S.E', 'L', '082232243354', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Hisyam Farih, S.E', nip = '3291201711', gender = 'L', phone = '082232243354', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #7: Riyadhatul Badiah, S.E
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Riyadhatul Badiah, S.E', 'riyahafidz07@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Riyadhatul Badiah, S.E', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'riyahafidz07@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3195201715', 'Riyadhatul Badiah, S.E', 'P', '085259122195', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Riyadhatul Badiah, S.E', nip = '3195201715', gender = 'P', phone = '085259122195', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #8: Meylinda Kurnia Sofiyani, S.Psi
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Meylinda Kurnia Sofiyani, S.Psi', 'meylindakurnia12@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Meylinda Kurnia Sofiyani, S.Psi', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'meylindakurnia12@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3192201821', 'Meylinda Kurnia Sofiyani, S.Psi', 'P', '085234588078', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Meylinda Kurnia Sofiyani, S.Psi', nip = '3192201821', gender = 'P', phone = '085234588078', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #9: Maulidia Khoiry, S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Maulidia Khoiry, S.Pd', 'maulidiakhoiriy@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Maulidia Khoiry, S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'maulidiakhoiriy@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3199201824', 'Maulidia Khoiry, S.Pd', 'P', '082331530162', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Maulidia Khoiry, S.Pd', nip = '3199201824', gender = 'P', phone = '082331530162', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #10: Husnul Sri Maulidiah, S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Husnul Sri Maulidiah, S.Pd', 'husnulsrimaulidiah@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Husnul Sri Maulidiah, S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'husnulsrimaulidiah@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3197201933', 'Husnul Sri Maulidiah, S.Pd', 'P', '081556823582', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Husnul Sri Maulidiah, S.Pd', nip = '3197201933', gender = 'P', phone = '081556823582', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #11: Mochammad
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Mochammad', 'mochammad@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Mochammad', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'mochammad@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3260201934', 'Mochammad', 'L', '082331530162', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Mochammad', nip = '3260201934', gender = 'L', phone = '082331530162', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #12: Halimatus Sa\'diyah, S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Halimatus Sa\'diyah, S.Pd', 'halimasadiyah238@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Halimatus Sa\'diyah, S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'halimasadiyah238@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3196201935', 'Halimatus Sa\'diyah, S.Pd', 'P', '085331167567', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Halimatus Sa\'diyah, S.Pd', nip = '3196201935', gender = 'P', phone = '085331167567', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #13: Cahya Arief Khoirumah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Cahya Arief Khoirumah S.Pd', 'khoirumahcahya1104@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Cahya Arief Khoirumah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'khoirumahcahya1104@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3196202037', 'Cahya Arief Khoirumah S.Pd', 'L', '085804014742', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Cahya Arief Khoirumah S.Pd', nip = '3196202037', gender = 'L', phone = '085804014742', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #14: Dwi Arifatun Nisa\' S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Dwi Arifatun Nisa\' S.Pd', 'dwi@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Dwi Arifatun Nisa\' S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'dwi@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3196202039', 'Dwi Arifatun Nisa\' S.Pd', 'P', '082316283056', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Dwi Arifatun Nisa\' S.Pd', nip = '3196202039', gender = 'P', phone = '082316283056', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #15: Siti Anisa S.Hum
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Siti Anisa S.Hum', 'sitiianisaa456@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Siti Anisa S.Hum', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'sitiianisaa456@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3197202041', 'Siti Anisa S.Hum', 'P', '085230217949', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Siti Anisa S.Hum', nip = '3197202041', gender = 'P', phone = '085230217949', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #16: Agung Prassetiyo
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Agung Prassetiyo', 'agungprassetiyo511@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Agung Prassetiyo', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'agungprassetiyo511@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3198202142', 'Agung Prassetiyo', 'L', '085217208502', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Agung Prassetiyo', nip = '3198202142', gender = 'L', phone = '085217208502', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #17: Azkiyah Amalina S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Azkiyah Amalina S.Pd', 'azkiyahamalina79@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Azkiyah Amalina S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'azkiyahamalina79@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3197202143', 'Azkiyah Amalina S.Pd', 'P', '085335821035', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Azkiyah Amalina S.Pd', nip = '3197202143', gender = 'P', phone = '085335821035', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #18: Rosyidah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Rosyidah S.Pd', 'rosyidahnamira123@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Rosyidah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'rosyidahnamira123@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3198201744', 'Rosyidah S.Pd', 'P', '082338795422', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Rosyidah S.Pd', nip = '3198201744', gender = 'P', phone = '082338795422', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #19: Mia Nurhidayati S.E
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Mia Nurhidayati S.E', 'mianurhidayati7@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Mia Nurhidayati S.E', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'mianurhidayati7@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3198202247', 'Mia Nurhidayati S.E', 'P', '081359564307', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Mia Nurhidayati S.E', nip = '3198202247', gender = 'P', phone = '081359564307', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #20: Siti Aminatul Qomariyah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Siti Aminatul Qomariyah', 'syarifahaminatul@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Siti Aminatul Qomariyah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'syarifahaminatul@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202249', 'Siti Aminatul Qomariyah', 'P', '081233171193', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Siti Aminatul Qomariyah', nip = '3101202249', gender = 'P', phone = '081233171193', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #21: Khusnul Hotimah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Khusnul Hotimah S.Pd', 'khusnulhotimah1123@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Khusnul Hotimah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'khusnulhotimah1123@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202251', 'Khusnul Hotimah S.Pd', 'P', '081357135188', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Khusnul Hotimah S.Pd', nip = '3100202251', gender = 'P', phone = '081357135188', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #22: Nur Halimah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Nur Halimah', 'hnurhalimah091@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Nur Halimah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'hnurhalimah091@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3198202252', 'Nur Halimah', 'P', '085290443736', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Nur Halimah', nip = '3198202252', gender = 'P', phone = '085290443736', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #23: Fajar Ridwan Abilillah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Fajar Ridwan Abilillah S.Pd', 'fajar@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Fajar Ridwan Abilillah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'fajar@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202253', 'Fajar Ridwan Abilillah S.Pd', 'L', '0895630439320', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Fajar Ridwan Abilillah S.Pd', nip = '3100202253', gender = 'L', phone = '0895630439320', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #24: Halifah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Halifah', 'halifah@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Halifah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'halifah@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3272201654', 'Halifah', 'P', '', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Halifah', nip = '3272201654', gender = 'P', phone = '', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #25: Ike Nurjannah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Ike Nurjannah S.Pd', 'ikenurjannah618@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Ike Nurjannah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'ikenurjannah618@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202355', 'Ike Nurjannah S.Pd', 'P', '085608029378', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Ike Nurjannah S.Pd', nip = '3100202355', gender = 'P', phone = '085608029378', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #26: Muhammad Farid S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Muhammad Farid S.Pd', 'faridjenny24@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Muhammad Farid S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'faridjenny24@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202356', 'Muhammad Farid S.Pd', 'L', '085234789280', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Muhammad Farid S.Pd', nip = '3100202356', gender = 'L', phone = '085234789280', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #27: Rehanatil Jannah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Rehanatil Jannah', 'jannahrehanatil@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Rehanatil Jannah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'jannahrehanatil@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202357', 'Rehanatil Jannah', 'P', '082337975497', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Rehanatil Jannah', nip = '3101202357', gender = 'P', phone = '082337975497', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #28: Iva Mutma\'inah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Iva Mutma\'inah S.Pd', 'ivamutmainah.1507@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Iva Mutma\'inah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'ivamutmainah.1507@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202358', 'Iva Mutma\'inah S.Pd', 'P', '082330345815', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Iva Mutma\'inah S.Pd', nip = '3100202358', gender = 'P', phone = '082330345815', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #29: Alfina Ananda Putri S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Alfina Ananda Putri S.Pd', 'putrialnanda12@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Alfina Ananda Putri S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'putrialnanda12@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202359', 'Alfina Ananda Putri S.Pd', 'P', '082245621324', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Alfina Ananda Putri S.Pd', nip = '3101202359', gender = 'P', phone = '082245621324', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #30: Firdani Sholeh Pradana S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Firdani Sholeh Pradana S.Pd', 'dani.firdani@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Firdani Sholeh Pradana S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'dani.firdani@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3190202360', 'Firdani Sholeh Pradana S.Pd', 'L', '082335345167', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Firdani Sholeh Pradana S.Pd', nip = '3190202360', gender = 'L', phone = '082335345167', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #31: Ahmad Baidhowi S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Ahmad Baidhowi S.Pd', 'ahmadbaidhowi108@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Ahmad Baidhowi S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'ahmadbaidhowi108@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3197202461', 'Ahmad Baidhowi S.Pd', 'L', '81336535501', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Ahmad Baidhowi S.Pd', nip = '3197202461', gender = 'L', phone = '81336535501', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #32: Shofiyah Husein S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Shofiyah Husein S.Pd', 'shofiyahhusein682@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Shofiyah Husein S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'shofiyahhusein682@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202462', 'Shofiyah Husein S.Pd', 'P', '085732439937', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Shofiyah Husein S.Pd', nip = '3102202462', gender = 'P', phone = '085732439937', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #33: Abd Hannan
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Abd Hannan', 'abd@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Abd Hannan', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'abd@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3291202463', 'Abd Hannan', 'L', '082269523244', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Abd Hannan', nip = '3291202463', gender = 'L', phone = '082269523244', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #34: Yazid Mubtafi S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Yazid Mubtafi S.Pd', 'yazimubtafi7@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Yazid Mubtafi S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'yazimubtafi7@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202464', 'Yazid Mubtafi S.Pd', 'L', '083137368121', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Yazid Mubtafi S.Pd', nip = '3101202464', gender = 'L', phone = '083137368121', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #35: Intan Maufirah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Intan Maufirah', 'syfintan847@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Intan Maufirah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'syfintan847@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202465', 'Intan Maufirah', 'P', '085853664685', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Intan Maufirah', nip = '3101202465', gender = 'P', phone = '085853664685', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #36: Helmi Mufidah
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Helmi Mufidah', 'helmimufida05@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Helmi Mufidah', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'helmimufida05@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202466', 'Helmi Mufidah', 'P', '083157513651', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Helmi Mufidah', nip = '3102202466', gender = 'P', phone = '083157513651', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #37: Dandik Nofian Putra Pratama
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Dandik Nofian Putra Pratama', 'dandik@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Dandik Nofian Putra Pratama', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'dandik@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3299202467', 'Dandik Nofian Putra Pratama', 'L', '081280356087', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Dandik Nofian Putra Pratama', nip = '3299202467', gender = 'L', phone = '081280356087', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #38: Nadifah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Nadifah S.Pd', 'ndf.5403@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Nadifah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'ndf.5403@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3103202468', 'Nadifah S.Pd', 'P', '085233858252', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Nadifah S.Pd', nip = '3103202468', gender = 'P', phone = '085233858252', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #39: Mamluatul Hasanah S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Mamluatul Hasanah S.Pd', 'mamluatulhasanah1520@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Mamluatul Hasanah S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'mamluatulhasanah1520@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202469', 'Mamluatul Hasanah S.Pd', 'P', '085850797267', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Mamluatul Hasanah S.Pd', nip = '3100202469', gender = 'P', phone = '085850797267', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #40: Nur Aini Trischa Ananda
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Nur Aini Trischa Ananda', 'nurainifriscaananda@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Nur Aini Trischa Ananda', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'nurainifriscaananda@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3100202470', 'Nur Aini Trischa Ananda', 'P', '083845072546', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Nur Aini Trischa Ananda', nip = '3100202470', gender = 'P', phone = '083845072546', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #41: Hermawan Diva Ardi Wijaya
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Hermawan Diva Ardi Wijaya', 'hermawan@namira.school', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Hermawan Diva Ardi Wijaya', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'hermawan@namira.school' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202471', 'Hermawan Diva Ardi Wijaya', 'P', '083852801326', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Hermawan Diva Ardi Wijaya', nip = '3102202471', gender = 'P', phone = '083852801326', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #42: Putri Agustini S.Sos
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Putri Agustini S.Sos', 'putriagustini1303@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Putri Agustini S.Sos', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'putriagustini1303@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202472', 'Putri Agustini S.Sos', 'P', '082266837207', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Putri Agustini S.Sos', nip = '3101202472', gender = 'P', phone = '082266837207', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #43: Muhammad Syarifudin S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Muhammad Syarifudin S.Pd', 'muhammadsyarifudin032001@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Muhammad Syarifudin S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'muhammadsyarifudin032001@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3101202473', 'Muhammad Syarifudin S.Pd', 'L', '085648235862', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Muhammad Syarifudin S.Pd', nip = '3101202473', gender = 'L', phone = '085648235862', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #44: Deny Setiawan S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Deny Setiawan S.Pd', 'setiawandeny1602@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Deny Setiawan S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'setiawandeny1602@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202474', 'Deny Setiawan S.Pd', 'L', '081259894411', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Deny Setiawan S.Pd', nip = '3102202474', gender = 'L', phone = '081259894411', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #45: Hasbullah S.Pd.I
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Hasbullah S.Pd.I', 'hasbulcs1@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Hasbullah S.Pd.I', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'hasbulcs1@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202475', 'Hasbullah S.Pd.I', 'L', '085231224112', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Hasbullah S.Pd.I', nip = '3102202475', gender = 'L', phone = '085231224112', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #46: SARIF
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('SARIF', 'syarifsya726@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'SARIF', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'syarifsya726@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3105202576', 'SARIF', 'L', '083155792854', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'SARIF', nip = '3105202576', gender = 'L', phone = '083155792854', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #47: Meirinda Zahratul M. S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Meirinda Zahratul M. S.Pd', 'meirindazm@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Meirinda Zahratul M. S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'meirindazm@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202577', 'Meirinda Zahratul M. S.Pd', 'P', '081259081907', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Meirinda Zahratul M. S.Pd', nip = '3102202577', gender = 'P', phone = '081259081907', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #48: Rian Hidayad S. Kom
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Rian Hidayad S. Kom', 'rianbru18@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Rian Hidayad S. Kom', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'rianbru18@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3102202578', 'Rian Hidayad S. Kom', 'L', '082140560121', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Rian Hidayad S. Kom', nip = '3102202578', gender = 'L', phone = '082140560121', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #49: Ahmad Kamil Fadoli S.Pd
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Ahmad Kamil Fadoli S.Pd', 'kamilfadoli20@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Ahmad Kamil Fadoli S.Pd', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'kamilfadoli20@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3196202579', 'Ahmad Kamil Fadoli S.Pd', 'L', '082318246720', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Ahmad Kamil Fadoli S.Pd', nip = '3196202579', gender = 'L', phone = '082318246720', updated_at = NOW();

-- -----------------------------------------------------------------

-- Guru #50: Astutik, S.Pd.I
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Astutik, S.Pd.I', 'astutik7749@gmail.com', '$2y$12$eU7F8nQjC4XnS8gK6u4k2e.lY3kY8n3gH5jK7l9m0n1o2p3q4r5s6', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = 'Astutik, S.Pd.I', updated_at = NOW();

SET @user_id = (SELECT id FROM users WHERE email = 'astutik7749@gmail.com' LIMIT 1);

INSERT INTO model_has_roles (role_id, model_type, model_id, team_id)
VALUES (@role_id, 'App\\Models\\User', @user_id, @unit_id)
ON DUPLICATE KEY UPDATE team_id = @unit_id;

INSERT INTO teachers (user_id, unit_id, nip, full_name, gender, phone, created_at, updated_at)
VALUES (@user_id, @unit_id, '3180201380', 'Astutik, S.Pd.I', 'P', '082330221399', NOW(), NOW())
ON DUPLICATE KEY UPDATE unit_id = @unit_id, full_name = 'Astutik, S.Pd.I', nip = '3180201380', gender = 'P', phone = '082330221399', updated_at = NOW();

-- -----------------------------------------------------------------

-- Sync all imported teachers to the matching SD unit_id
UPDATE teachers SET unit_id = @unit_id WHERE nip IN ('3259201301','3190201302','3175201506','32201607','3192201609','3291201711','3195201715','3192201821','3199201824','3197201933','3260201934','3196201935','3196202037','3196202039','3197202041','3198202142','3197202143','3198201744','3198202247','3101202249','3100202251','3198202252','3100202253','3272201654','3100202355','3100202356','3101202357','3100202358','3101202359','3190202360','3197202461','3102202462','3291202463','3101202464','3101202465','3102202466','3299202467','3103202468','3100202469','3100202470','3102202471','3101202472','3101202473','3102202474','3102202475','3105202576','3102202577','3102202578','3196202579','3180201380');
UPDATE model_has_roles SET team_id = @unit_id WHERE role_id = @role_id AND model_id IN (SELECT user_id FROM teachers WHERE nip IN ('3259201301','3190201302','3175201506','32201607','3192201609','3291201711','3195201715','3192201821','3199201824','3197201933','3260201934','3196201935','3196202037','3196202039','3197202041','3198202142','3197202143','3198201744','3198202247','3101202249','3100202251','3198202252','3100202253','3272201654','3100202355','3100202356','3101202357','3100202358','3101202359','3190202360','3197202461','3102202462','3291202463','3101202464','3101202465','3102202466','3299202467','3103202468','3100202469','3100202470','3102202471','3101202472','3101202473','3102202474','3102202475','3105202576','3102202577','3102202578','3196202579','3180201380'));

SET FOREIGN_KEY_CHECKS = 1;
