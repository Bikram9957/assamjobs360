# AssamJobs360 (Plain PHP + MySQL)

This is a plain PHP (no framework) scaffold for **Assam Government Job Portal & Mock Test Platform**.

## Requirements
- PHP 8+
- MySQL 5.7+ / MariaDB
- Apache
- Enable `mysqli` extension

## Setup
1. Create database in MySQL:
   ```sql
   CREATE DATABASE assamjobs360 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
2. Import schema:
   - Open `database/assamjobs360.sql` in phpMyAdmin and run it.
   - For an existing installation created before the job-detail update, back up the database and run `database/update_existing_install.sql` once.
3. Configure DB credentials:
   - Edit `config/config.php` and set:
     - `DB_HOST`
     - `DB_NAME`
     - `DB_USER`
     - `DB_PASS`
4. Start Apache and open:
   - Public site: `http://localhost/assamjobs360/`
   - Admin: `http://localhost/assamjobs360/admin/`

## Default admin login
- During DB import, a default admin account is seeded.
- If the seed is changed/removed, use `admin` seeding script.

## Notes
- This scaffold focuses on **working endpoints + mobile-first UI + SEO templates**.
- Full mock-test scoring logic can be implemented incrementally.

