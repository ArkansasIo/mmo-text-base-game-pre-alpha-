<?php
class AdminAuth
{
    private mysqli $db;
    public ?array $admin = null;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        if (!isset($_SESSION['admin_session_token'])) {
            $uid = (int)($_SESSION['userid'] ?? 0);
            $username = (string)($_SESSION['username'] ?? '');
            if ($uid > 0 && $username !== '') {
                $shared = $this->db->prepare("SELECT a.admin_id,a.username,a.email,a.role,a.is_active FROM users u INNER JOIN admin_users a ON (a.username=u.uname OR (a.email<>'' AND a.email=u.email)) WHERE u.uid=? AND a.is_active=1 LIMIT 1");
                if ($shared) {
                    $shared->bind_param('i', $uid); $shared->execute();
                    $row = $shared->get_result()->fetch_assoc();
                    if ($row) $this->admin = $row;
                }
            }
            return;
        }
        $token = (string)$_SESSION['admin_session_token'];
        $stmt = $this->db->prepare("SELECT a.admin_id,a.username,a.email,a.role,a.is_active FROM admin_sessions s INNER JOIN admin_users a ON a.admin_id=s.admin_id WHERE s.session_id=? AND s.expires_at > NOW() AND a.is_active=1 LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $token);
            $stmt->execute();
            $result = $stmt->get_result();
            $this->admin = $result ? $result->fetch_assoc() : null;
            if ($this->admin) {
                $touch = $this->db->prepare("UPDATE admin_sessions SET last_seen_at=NOW() WHERE session_id=?");
                if ($touch) { $touch->bind_param('s', $token); $touch->execute(); }
            } else {
                unset($_SESSION['admin_session_token']);
            }
        }
    }

    public function login(string $username, string $password): bool
    {
        $stmt = $this->db->prepare("SELECT admin_id,username,email,password_hash,role,is_active FROM admin_users WHERE username=? LIMIT 1");
        if (!$stmt) return false;
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !(int)$row['is_active'] || !password_verify($password, $row['password_hash'])) return false;
        $token = bin2hex(random_bytes(32));
        $adminId = (int)$row['admin_id'];
        $session = $this->db->prepare("INSERT INTO admin_sessions (session_id,admin_id,expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 12 HOUR))");
        if (!$session) return false;
        $session->bind_param('si', $token, $adminId);
        $session->execute();
        $_SESSION['admin_session_token'] = $token;
        $this->admin = ['admin_id'=>$adminId,'username'=>$row['username'],'email'=>$row['email'],'role'=>$row['role'],'is_active'=>1];
        $touch = $this->db->prepare("UPDATE admin_users SET last_login_at=NOW() WHERE admin_id=?");
        if ($touch) { $touch->bind_param('i', $adminId); $touch->execute(); }
        return true;
    }

    public function logout(): void
    {
        if (isset($_SESSION['admin_session_token'])) {
            $stmt = $this->db->prepare("DELETE FROM admin_sessions WHERE session_id=?");
            if ($stmt) { $stmt->bind_param('s', $_SESSION['admin_session_token']); $stmt->execute(); }
            unset($_SESSION['admin_session_token']);
        }
        $this->admin = null;
    }

    public function isAuthenticated(): bool { return $this->admin !== null; }
    public function isAtLeast(string $role): bool
    {
        $levels = ['moderator'=>1,'operator'=>2,'superadmin'=>3];
        return $this->admin && ($levels[$this->admin['role']] ?? 0) >= ($levels[$role] ?? 99);
    }

    public function audit(string $action, string $module, array $details = []): void
    {
        $uid = $this->admin ? (int)$this->admin['admin_id'] : 0;
        $json = json_encode($details, JSON_UNESCAPED_SLASHES);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt = $this->db->prepare("INSERT INTO app_audit_log (uid,action_type,module_name,details_json,ip_address) VALUES (?,?,?,?,?)");
        if ($stmt) { $stmt->bind_param('issss', $uid, $action, $module, $json, $ip); $stmt->execute(); }
    }
}
?>
