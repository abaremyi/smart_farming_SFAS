<?php
/** SIPIS — User Model | File: modules/Authentication/models/UserModel.php */
class UserModel {
    private PDO $db;
    private string $t = 'users';
    public function __construct(?PDO $db=null){ $this->db=$db?:Database::getConnection(); }

    /** Login lookup — joins role permissions */
    public function getUserByEmailOrPhone(string $id): ?array {
        $sql="SELECT u.*,r.name AS role_display,
               GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
              FROM {$this->t} u
              LEFT JOIN roles r ON r.id=u.role_id
              LEFT JOIN role_permissions rp ON rp.role_id=r.id
              LEFT JOIN permissions p ON p.id=rp.permission_id
              WHERE u.email=:id OR u.phone=:id OR u.username=:id
              GROUP BY u.id LIMIT 1";
        $s=$this->db->prepare($sql); $s->execute([':id'=>$id]);
        $u=$s->fetch(PDO::FETCH_ASSOC);
        if($u){ $u['permissions']=$u['perm_keys']?explode(',',$u['perm_keys']):[];
                $u['is_super_admin']=(bool)($u['is_super_admin']??false); }
        return $u?:null;
    }

    public function getUserById(int $id): ?array {
        $sql="SELECT u.*,r.name AS role_display,
               GROUP_CONCAT(DISTINCT p.`key` ORDER BY p.`key` SEPARATOR ',') AS perm_keys
              FROM {$this->t} u
              LEFT JOIN roles r ON r.id=u.role_id
              LEFT JOIN role_permissions rp ON rp.role_id=r.id
              LEFT JOIN permissions p ON p.id=rp.permission_id
              WHERE u.id=:id GROUP BY u.id";
        $s=$this->db->prepare($sql); $s->execute([':id'=>$id]);
        $u=$s->fetch(PDO::FETCH_ASSOC);
        if($u){ $u['permissions']=$u['perm_keys']?explode(',',$u['perm_keys']):[];
                $u['is_super_admin']=(bool)($u['is_super_admin']??false); }
        return $u?:null;
    }

    public function getAllUsers(array $f=[]): array {
        $sql="SELECT u.*,r.name AS role_display FROM {$this->t} u LEFT JOIN roles r ON u.role_id=r.id WHERE 1=1";
        $p=[];
        if(!empty($f['status']))  { $sql.=" AND u.account_status=:status"; $p[':status']=$f['status']; }
        if(!empty($f['role_id'])) { $sql.=" AND u.role_id=:rid";           $p[':rid']=$f['role_id']; }
        if(!empty($f['search']))  {
            $sql.=" AND (u.firstname LIKE :s OR u.lastname LIKE :s OR u.email LIKE :s OR u.phone LIKE :s)";
            $p[':s']='%'.$f['search'].'%';
        }
        $sql.=" ORDER BY u.created_at DESC";
        $s=$this->db->prepare($sql); $s->execute($p);
        return $s->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createUser(array $d): int {
        $sql="INSERT INTO {$this->t}
              (firstname,lastname,username,email,phone,phone_alt,password,
               role_id,role_name,account_status,photo,bio,gender,date_of_birth,address,
               created_by,otp_code,otp_expiry)
              VALUES
              (:fn,:ln,:un,:em,:ph,:pa,:pw,
               :ri,:rn,:as,:photo,:bio,:gen,:dob,:addr,
               :cb,:otp,:otpex)";
        $s=$this->db->prepare($sql);
        $s->execute([
            ':fn'=>$d['firstname'],':ln'=>$d['lastname'],':un'=>$d['username']??null,
            ':em'=>$d['email'],':ph'=>$d['phone']??null,':pa'=>$d['phone_alt']??null,
            ':pw'=>password_hash($d['password'],PASSWORD_BCRYPT),
            ':ri'=>$d['role_id']??2,':rn'=>$d['role_name']??'Staff',
            ':as'=>$d['account_status']??'pending',
            ':photo'=>$d['photo']??null,':bio'=>$d['bio']??null,
            ':gen'=>$d['gender']??null,':dob'=>$d['date_of_birth']??null,
            ':addr'=>$d['address']??null,':cb'=>$d['created_by']??null,
            ':otp'=>$d['otp_code']??null,':otpex'=>$d['otp_expiry']??null,
        ]);
        $id=(int)$this->db->lastInsertId();
        $this->log($id,'create','User account created');
        return $id;
    }

    public function updateUser(int $id,array $d): bool {
        $allowed=['firstname','lastname','username','email','phone','phone_alt',
                  'role_id','role_name','account_status','photo','bio','gender','date_of_birth','address'];
        $sets=[];$p=[':id'=>$id];
        foreach($allowed as $f){ if(array_key_exists($f,$d)){ $sets[]="$f=:$f"; $p[":$f"]=$d[$f]; } }
        if(!empty($d['password'])){ $sets[]="password=:password"; $p[':password']=password_hash($d['password'],PASSWORD_BCRYPT); }
        if(empty($sets)) return false;
        $ok=$this->db->prepare("UPDATE {$this->t} SET ".implode(',',$sets).",updated_at=NOW() WHERE id=:id")->execute($p);
        if($ok) $this->log($id,'update','User account updated');
        return $ok;
    }

    public function deleteUser(int $id): bool {
        $u=$this->getUserById($id);
        if($u&&$u['is_super_admin']) throw new Exception('Cannot delete super-admin');
        $this->log($id,'delete','User deleted');
        return $this->db->prepare("DELETE FROM {$this->t} WHERE id=:id")->execute([':id'=>$id]);
    }

    public function updateStatus(int $id,string $st): bool {
        $ok=$this->db->prepare("UPDATE {$this->t} SET account_status=:s,updated_at=NOW() WHERE id=:id")->execute([':s'=>$st,':id'=>$id]);
        if($ok) $this->log($id,'status_change',"Status → $st");
        return $ok;
    }

    public function updateLastLogin(int $id): void {
        $this->db->prepare("UPDATE {$this->t} SET last_login=NOW() WHERE id=:id")->execute([':id'=>$id]);
    }

    public function emailExists(string $email,?int $ex=null): bool {
        $sql="SELECT COUNT(*) FROM {$this->t} WHERE email=:e"; $p=[':e'=>$email];
        if($ex){ $sql.=" AND id!=:id"; $p[':id']=$ex; }
        $s=$this->db->prepare($sql); $s->execute($p); return (bool)$s->fetchColumn();
    }
    public function phoneExists(string $ph,?int $ex=null): bool {
        if(empty($ph)) return false;
        $sql="SELECT COUNT(*) FROM {$this->t} WHERE phone=:p"; $p=[':p'=>$ph];
        if($ex){ $sql.=" AND id!=:id"; $p[':id']=$ex; }
        $s=$this->db->prepare($sql); $s->execute($p); return (bool)$s->fetchColumn();
    }
    public function findByEmail(string $email): ?array {
        $s=$this->db->prepare("SELECT * FROM {$this->t} WHERE email=:e LIMIT 1");
        $s->execute([':e'=>$email]); return $s->fetch()?:null;
    }
    public function updateOtp(int $id,string $otp,string $exp): void {
        $this->db->prepare("UPDATE {$this->t} SET otp_code=:o,otp_expiry=:e WHERE id=:id")
                 ->execute([':o'=>$otp,':e'=>$exp,':id'=>$id]);
    }
    public function verifyOtpFromUser(int $id,string $otp): bool {
        $s=$this->db->prepare("SELECT otp_code,otp_expiry FROM {$this->t} WHERE id=:id LIMIT 1");
        $s->execute([':id'=>$id]); $r=$s->fetch();
        if(!$r) return false;
        return $r['otp_code']===$otp && strtotime($r['otp_expiry'])>time();
    }
    public function updatePassword(string $email,string $pwd): bool {
        return $this->db->prepare("UPDATE {$this->t} SET password=:p WHERE email=:e")
                        ->execute([':p'=>password_hash($pwd,PASSWORD_BCRYPT),':e'=>$email]);
    }
    public function getAllRoles(): array {
        return $this->db->query("SELECT id,name,description FROM roles ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getUserStats(): array {
        $r=$this->db->query("SELECT COUNT(*) total,
            SUM(account_status='active') active,
            SUM(account_status='pending') pending,
            SUM(account_status='inactive') inactive,
            SUM(account_status='suspended') suspended
            FROM {$this->t}")->fetch(PDO::FETCH_ASSOC);
        return $r??['total'=>0,'active'=>0,'pending'=>0,'inactive'=>0,'suspended'=>0];
    }
    public function createPasswordReset(string $email,string $otp,string $exp): void {
        $this->db->prepare("DELETE FROM password_resets WHERE email=:e")->execute([':e'=>$email]);
        $this->db->prepare("INSERT INTO password_resets (email,otp,expires_at) VALUES (:e,:o,:x)")
                 ->execute([':e'=>$email,':o'=>$otp,':x'=>$exp]);
    }
    public function verifyOtp(string $email,string $otp): bool {
        $s=$this->db->prepare("SELECT id FROM password_resets WHERE email=:e AND otp=:o AND expires_at>NOW() AND used=0 LIMIT 1");
        $s->execute([':e'=>$email,':o'=>$otp]); $r=$s->fetch();
        if($r){ $this->db->prepare("UPDATE password_resets SET used=1 WHERE id=:id")->execute([':id'=>$r['id']]); return true; }
        return false;
    }
    private function log(int $uid,string $action,string $desc): void {
        try {
            $this->db->prepare("INSERT INTO user_activity_log (user_id,action,description,ip_address) VALUES (:u,:a,:d,:ip)")
                     ->execute([':u'=>$uid,':a'=>$action,':d'=>$desc,':ip'=>$_SERVER['REMOTE_ADDR']??null]);
        } catch(Exception $e){ error_log('Log: '.$e->getMessage()); }
    }
}