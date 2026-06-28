<?php
/**
 * SFAS — User Model (v3 — matches actual sfas_db.sql schema exactly)
 * File: modules/Authentication/models/UserModel.php
 *
 * CHANGES vs uploaded version:
 *  1. createUser() — added role_name column (it EXISTS in sfas_db users table)
 *  2. emailExists() — fixed bug: was calling prepare().execute() then prepare() again
 *     losing the result; now uses a single prepare→execute→fetchColumn chain
 *  3. updatePhoto() — new dedicated method for photo path updates
 *  4. Everything else identical to your working version
 */
class UserModel {
    private PDO    $db;
    private string $t = 'users';

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
    }

    /* ── Auth lookups ─────────────────────────────────── */
    public function getUserByEmailOrPhone(string $id): ?array {
        $sql = "SELECT u.*, r.name AS role_display,
                GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
                FROM {$this->t} u
                LEFT JOIN roles r              ON r.id  = u.role_id
                LEFT JOIN role_permissions rp  ON rp.role_id = r.id
                LEFT JOIN permissions p        ON p.id  = rp.permission_id
                WHERE u.email=:id OR u.phone=:id OR u.username=:id
                GROUP BY u.id LIMIT 1";
        $s = $this->db->prepare($sql);
        $s->execute([':id' => $id]);
        $u = $s->fetch();
        if ($u) {
            $u['permissions']    = $u['perm_keys'] ? explode(',', $u['perm_keys']) : [];
            $u['is_super_admin'] = (bool)($u['is_super_admin'] ?? false);
        }
        return $u ?: null;
    }

    public function getUserById(int $id): ?array {
        $sql = "SELECT u.*, r.name AS role_display,
                GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
                FROM {$this->t} u
                LEFT JOIN roles r              ON r.id  = u.role_id
                LEFT JOIN role_permissions rp  ON rp.role_id = r.id
                LEFT JOIN permissions p        ON p.id  = rp.permission_id
                WHERE u.id = :id GROUP BY u.id";
        $s = $this->db->prepare($sql);
        $s->execute([':id' => $id]);
        $u = $s->fetch();
        if ($u) {
            $u['permissions']    = $u['perm_keys'] ? explode(',', $u['perm_keys']) : [];
            $u['is_super_admin'] = (bool)($u['is_super_admin'] ?? false);
        }
        return $u ?: null;
    }

    /* ── Lists ────────────────────────────────────────── */
    public function getAllUsers(array $f = []): array {
        $sql = "SELECT u.*, r.name AS role_display
                FROM {$this->t} u
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE 1=1";
        $p = [];
        if (!empty($f['status']))  { $sql .= " AND u.account_status=:status"; $p[':status'] = $f['status']; }
        if (!empty($f['role_id'])) { $sql .= " AND u.role_id=:rid";           $p[':rid']    = $f['role_id']; }
        if (!empty($f['search'])) {
            $sql .= " AND (u.firstname LIKE :s OR u.lastname LIKE :s OR u.email LIKE :s OR u.phone LIKE :s)";
            $p[':s'] = '%' . $f['search'] . '%';
        }
        $sql .= " ORDER BY u.created_at DESC";
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    /* ── CRUD ─────────────────────────────────────────── */
    public function createUser(array $d): int {
        // sfas_db users table confirmed columns (from sfas_db.sql):
        // id, firstname, lastname, username, email, phone, password,
        // role_id, role_name, is_super_admin, account_status, photo,
        // district, sector, otp_code, otp_expiry, last_login, created_by,
        // created_at, updated_at
        $sql = "INSERT INTO {$this->t}
                (firstname, lastname, username, email, phone,
                 password, role_id, role_name, account_status,
                 district, sector, created_by, otp_code, otp_expiry)
                VALUES
                (:fn, :ln, :un, :em, :ph,
                 :pw, :ri, :rn, :as,
                 :dist, :sec, :cb, :otp, :otpex)";
        $s = $this->db->prepare($sql);
        $s->execute([
            ':fn'    => $d['firstname'],
            ':ln'    => $d['lastname'],
            ':un'    => $d['username']       ?? null,
            ':em'    => $d['email'],
            ':ph'    => $d['phone']          ?? null,
            ':pw'    => password_hash($d['password'], PASSWORD_BCRYPT),
            ':ri'    => $d['role_id']        ?? 3,
            ':rn'    => $d['role_name']      ?? 'Farmer',
            ':as'    => $d['account_status'] ?? 'pending',
            ':dist'  => $d['district']       ?? null,
            ':sec'   => $d['sector']         ?? null,
            ':cb'    => $d['created_by']     ?? null,
            ':otp'   => $d['otp_code']       ?? null,
            ':otpex' => $d['otp_expiry']     ?? null,
        ]);
        $id = (int)$this->db->lastInsertId();
        $this->log($id, 'create', 'User account created');
        return $id;
    }

    public function updateUser(int $id, array $d): bool {
        $allowed = ['firstname','lastname','username','email','phone',
                    'role_id','role_name','account_status','photo','district','sector'];
        $sets = []; $p = [':id' => $id];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $d)) { $sets[] = "$f=:$f"; $p[":$f"] = $d[$f]; }
        }
        if (!empty($d['password'])) {
            $sets[]         = "password=:password";
            $p[':password'] = password_hash($d['password'], PASSWORD_BCRYPT);
        }
        if (empty($sets)) return false;
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET " . implode(',', $sets) . ", updated_at=NOW() WHERE id=:id"
        )->execute($p);
        if ($ok) $this->log($id, 'update', 'User account updated');
        return $ok;
    }

    /**
     * Dedicated photo update — avoids the full updateUser overhead.
     * Stores the relative path e.g. 'users/abc123_1.jpg'
     */
    public function updatePhoto(int $id, string $photoPath): bool {
        return $this->db->prepare(
            "UPDATE {$this->t} SET photo=:p, updated_at=NOW() WHERE id=:id"
        )->execute([':p' => $photoPath, ':id' => $id]);
    }

    public function deleteUser(int $id): bool {
        $u = $this->getUserById($id);
        if ($u && $u['is_super_admin']) throw new Exception('Cannot delete super-admin');
        $this->log($id, 'delete', 'User deleted');
        return $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool {
        $ok = $this->db->prepare(
            "UPDATE {$this->t} SET account_status=:s, updated_at=NOW() WHERE id=:id"
        )->execute([':s' => $status, ':id' => $id]);
        if ($ok) $this->log($id, 'status_change', "Status → $status");
        return $ok;
    }

    public function updateLastLogin(int $id): void {
        $this->db->prepare("UPDATE {$this->t} SET last_login=NOW() WHERE id=:id")->execute([':id' => $id]);
    }

    /* ── Checks ───────────────────────────────────────── */
    public function emailExists(string $email, ?int $exclude = null): bool {
        // FIXED: previous version called prepare() twice losing the result
        $sql = "SELECT COUNT(*) FROM {$this->t} WHERE email=:e";
        $p   = [':e' => $email];
        if ($exclude) { $sql .= " AND id!=:id"; $p[':id'] = $exclude; }
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return (bool)$s->fetchColumn();
    }

    public function phoneExists(string $ph, ?int $exclude = null): bool {
        if (empty($ph)) return false;
        $sql = "SELECT COUNT(*) FROM {$this->t} WHERE phone=:p";
        $p   = [':p' => $ph];
        if ($exclude) { $sql .= " AND id!=:id"; $p[':id'] = $exclude; }
        $s = $this->db->prepare($sql);
        $s->execute($p);
        return (bool)$s->fetchColumn();
    }

    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare("SELECT * FROM {$this->t} WHERE email=:e LIMIT 1");
        $s->execute([':e' => $email]);
        return $s->fetch() ?: null;
    }

    /* ── OTP (registration) ────────────────────────────── */
    public function updateOtp(int $id, string $otp, string $expiry): void {
        $this->db->prepare(
            "UPDATE {$this->t} SET otp_code=:o, otp_expiry=:e WHERE id=:id"
        )->execute([':o' => $otp, ':e' => $expiry, ':id' => $id]);
    }

    public function verifyOtpFromUser(int $id, string $otp): bool {
        $s = $this->db->prepare(
            "SELECT otp_code, otp_expiry FROM {$this->t} WHERE id=:id LIMIT 1"
        );
        $s->execute([':id' => $id]);
        $r = $s->fetch();
        return $r && $r['otp_code'] === $otp && strtotime($r['otp_expiry']) > time();
    }

    /* ── Password reset (forgot-password flow) ─────────── */
    public function createPasswordReset(string $email, string $otp, string $expiry): void {
        $this->db->prepare("DELETE FROM password_resets WHERE email=:e")->execute([':e' => $email]);
        $this->db->prepare(
            "INSERT INTO password_resets (email, otp, expires_at) VALUES (:e, :o, :x)"
        )->execute([':e' => $email, ':o' => $otp, ':x' => $expiry]);
    }

    public function verifyOtp(string $email, string $otp): bool {
        $s = $this->db->prepare(
            "SELECT id FROM password_resets WHERE email=:e AND otp=:o AND expires_at>NOW() AND used=0 LIMIT 1"
        );
        $s->execute([':e' => $email, ':o' => $otp]);
        return (bool)$s->fetch();
    }

    public function consumeOtpAndResetPassword(string $email, string $otp, string $newHashedPassword): bool {
        $s = $this->db->prepare(
            "SELECT id FROM password_resets WHERE email=:e AND otp=:o AND expires_at>NOW() AND used=0 LIMIT 1"
        );
        $s->execute([':e' => $email, ':o' => $otp]);
        $row = $s->fetch();
        if (!$row) return false;
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE password_resets SET used=1 WHERE id=:id")->execute([':id' => $row['id']]);
            $this->db->prepare("UPDATE {$this->t} SET password=:p WHERE email=:e")->execute([':p' => $newHashedPassword, ':e' => $email]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('consumeOtpAndResetPassword failed: ' . $e->getMessage());
            return false;
        }
    }

    /* ── Stats ────────────────────────────────────────── */
    public function getUserStats(): array {
        $r = $this->db->query(
            "SELECT COUNT(*) total,
             SUM(account_status='active')    active,
             SUM(account_status='pending')   pending,
             SUM(account_status='inactive')  inactive,
             SUM(account_status='suspended') suspended
             FROM {$this->t}"
        )->fetch();
        return $r ?? ['total'=>0,'active'=>0,'pending'=>0,'inactive'=>0,'suspended'=>0];
    }

    public function getAllRoles(): array {
        return $this->db->query("SELECT id, name, description FROM roles ORDER BY id")->fetchAll();
    }

    /* ── Audit log (sfas_db = user_activity_log) ──────── */
    private function log(int $uid, string $action, string $desc): void {
        try {
            $this->db->prepare(
                "INSERT INTO user_activity_log (user_id, action, description, ip_address)
                 VALUES (:u, :a, :d, :ip)"
            )->execute([':u' => $uid, ':a' => $action, ':d' => $desc, ':ip' => $_SERVER['REMOTE_ADDR'] ?? null]);
        } catch (Exception $e) {
            error_log('UserModel log: ' . $e->getMessage());
        }
    }
}