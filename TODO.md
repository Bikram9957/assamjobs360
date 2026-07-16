# TODO - Admin Forget Password (Security Focused)

- [x] Step 1: Create DB migration/ensure-table for password reset tokens in `lib/db.php`.
- [x] Step 2: Add secure reset token helpers (generate/verify) in `lib/security.php`.
- [x] Step 3: Add UI+logic page `admin/forgot_password.php` (username-based, token displayed once, generic messages, rate limit, CSRF).
- [x] Step 4: Add UI+logic page `admin/reset_password.php` (selector+token+new password, strict validation, rate limit, CSRF).
- [x] Step 5: Update `admin/login.php` to include “Forgot password?” link.
- [ ] Step 6: Manual security testing checklist (enumeration prevention, rate limits, token reuse, expiry, CSRF).


