<?php
/**
 * SFAS — Advisory Model
 * File: modules/Advisory/models/AdvisoryModel.php
 */
class AdvisoryModel {
    private PDO $db;

    public function __construct(PDO $db) { $this->db = $db; }

    /* ── ADVISORY TIPS ───────────────────────────────────── */

    public function getAllTips(array $f = []): array {
        $sql = "SELECT t.*, c.name AS crop_name,
                       u.firstname AS author_first, u.lastname AS author_last
                FROM advisory_tips t
                LEFT JOIN crops c ON c.id = t.crop_id
                LEFT JOIN users u ON u.id = t.author_id
                WHERE 1=1";
        $p = [];
        if (isset($f['is_active'])) { $sql .= " AND t.is_active=:ia"; $p[':ia'] = $f['is_active']; }
        if (!empty($f['category'])) { $sql .= " AND t.category=:cat"; $p[':cat'] = $f['category']; }
        if (!empty($f['crop_id']))  { $sql .= " AND t.crop_id=:cr";   $p[':cr']  = $f['crop_id']; }
        if (!empty($f['district'])) { $sql .= " AND (t.district=:di OR t.district IS NULL)"; $p[':di'] = $f['district']; }
        if (!empty($f['search']))   {
            $sql .= " AND (t.title LIKE :s OR t.content LIKE :s)";
            $p[':s'] = '%'.$f['search'].'%';
        }
        $sql .= " ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTipById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT t.*, c.name AS crop_name, u.firstname AS author_first, u.lastname AS author_last
             FROM advisory_tips t
             LEFT JOIN crops c ON c.id=t.crop_id
             LEFT JOIN users u ON u.id=t.author_id
             WHERE t.id=:id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function createTip(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO advisory_tips (title,content,category,crop_id,season,district,author_id,is_active)
             VALUES (:ti,:co,:ca,:cr,:se,:di,:au,:ia)"
        );
        $stmt->execute([
            ':ti' => $d['title'],
            ':co' => $d['content'],
            ':ca' => $d['category'] ?? 'General',
            ':cr' => $d['crop_id']  ?? null,
            ':se' => $d['season']   ?? null,
            ':di' => $d['district'] ?? null,
            ':au' => $d['author_id'] ?? null,
            ':ia' => $d['is_active'] ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateTip(int $id, array $d): bool {
        $allowed = ['title','content','category','crop_id','season','district','is_active'];
        $sets = []; $p = [':id' => $id];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $d)) { $sets[] = "$col=:$col"; $p[":$col"] = $d[$col]; }
        }
        if (empty($sets)) return false;
        return $this->db->prepare(
            "UPDATE advisory_tips SET ".implode(',',$sets).",updated_at=NOW() WHERE id=:id"
        )->execute($p);
    }

    public function deleteTip(int $id): bool {
        return $this->db->prepare("DELETE FROM advisory_tips WHERE id=:id")->execute([':id'=>$id]);
    }

    public function incrementViews(int $id): void {
        $this->db->prepare("UPDATE advisory_tips SET views=views+1 WHERE id=:id")->execute([':id'=>$id]);
    }

    /* ── PEST ALERTS ─────────────────────────────────────── */

    public function getAllAlerts(array $f = []): array {
        $sql = "SELECT a.*, c.name AS crop_name FROM pest_alerts a
                LEFT JOIN crops c ON c.id=a.crop_id WHERE 1=1";
        $p = [];
        if (isset($f['is_active'])) { $sql .= " AND a.is_active=:ia"; $p[':ia'] = $f['is_active']; }
        if (!empty($f['severity'])) { $sql .= " AND a.severity=:sv";  $p[':sv'] = $f['severity']; }
        if (!empty($f['crop_id'])) { $sql .= " AND a.crop_id=:cr";   $p[':cr'] = $f['crop_id']; }
        $sql .= " ORDER BY FIELD(a.severity,'Critical','High','Medium','Low'), a.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createAlert(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO pest_alerts (title,description,pest_name,crop_id,severity,district,sector,reported_by,is_active)
             VALUES (:ti,:de,:pn,:cr,:sv,:di,:se,:rb,:ia)"
        );
        $stmt->execute([
            ':ti' => $d['title'],
            ':de' => $d['description'],
            ':pn' => $d['pest_name']   ?? null,
            ':cr' => $d['crop_id']     ?? null,
            ':sv' => $d['severity']    ?? 'Medium',
            ':di' => $d['district']    ?? null,
            ':se' => $d['sector']      ?? null,
            ':rb' => $d['reported_by'] ?? null,
            ':ia' => $d['is_active']   ?? 1,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function toggleAlert(int $id, int $active): bool {
        return $this->db->prepare("UPDATE pest_alerts SET is_active=:ia WHERE id=:id")
            ->execute([':ia'=>$active,':id'=>$id]);
    }

    public function deleteAlert(int $id): bool {
        return $this->db->prepare("DELETE FROM pest_alerts WHERE id=:id")->execute([':id'=>$id]);
    }

    /* ── MARKET PRICES ───────────────────────────────────── */

    public function getAllPrices(array $f = []): array {
        $sql = "SELECT mp.*, c.name AS crop_name, c.local_name
                FROM market_prices mp
                JOIN crops c ON c.id=mp.crop_id
                WHERE 1=1";
        $p = [];
        if (!empty($f['crop_id']))  { $sql .= " AND mp.crop_id=:cr";  $p[':cr']  = $f['crop_id']; }
        if (!empty($f['district'])) { $sql .= " AND mp.district=:di"; $p[':di']  = $f['district']; }
        if (!empty($f['date']))     { $sql .= " AND mp.price_date=:dt"; $p[':dt'] = $f['date']; }
        $sql .= " ORDER BY mp.price_date DESC, mp.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLatestPrices(): array {
        return $this->db->query(
            "SELECT mp.*, c.name AS crop_name FROM market_prices mp
             JOIN crops c ON c.id=mp.crop_id
             WHERE mp.price_date = (SELECT MAX(price_date) FROM market_prices m2 WHERE m2.crop_id=mp.crop_id)
             GROUP BY mp.crop_id, mp.market
             ORDER BY c.name, mp.market"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addPrice(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO market_prices (crop_id,market,district,price_rwf,unit,price_date,source,updated_by)
             VALUES (:ci,:mk,:di,:pr,:un,:dt,:so,:ub)"
        );
        $stmt->execute([
            ':ci' => $d['crop_id'],
            ':mk' => $d['market'],
            ':di' => $d['district']   ?? null,
            ':pr' => $d['price_rwf'],
            ':un' => $d['unit']       ?? 'kg',
            ':dt' => $d['price_date'] ?? date('Y-m-d'),
            ':so' => $d['source']     ?? 'Field Survey',
            ':ub' => $d['updated_by'] ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deletePrice(int $id): bool {
        return $this->db->prepare("DELETE FROM market_prices WHERE id=:id")->execute([':id'=>$id]);
    }

    /* ── STATS ───────────────────────────────────────────── */

    public function getTipStats(): array {
        $r = $this->db->query(
            "SELECT COUNT(*) total, SUM(is_active) active,
             COUNT(DISTINCT category) categories, SUM(views) total_views
             FROM advisory_tips"
        )->fetch();
        return $r ?? ['total'=>0,'active'=>0,'categories'=>0,'total_views'=>0];
    }

    public function getAllCrops(): array {
        return $this->db->query("SELECT id,name,local_name FROM crops ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ── REPORTS DATA ────────────────────────────────────── */

    public function getReportSummary(): array {
        $tips     = $this->db->query("SELECT COUNT(*) FROM advisory_tips WHERE is_active=1")->fetchColumn();
        $alerts   = $this->db->query("SELECT COUNT(*) FROM pest_alerts   WHERE is_active=1")->fetchColumn();
        $farms    = $this->db->query("SELECT COUNT(*) FROM farms")->fetchColumn();
        $farmers  = $this->db->query("SELECT COUNT(*) FROM users WHERE role_id=3 AND account_status='active'")->fetchColumn();
        $aiChats  = $this->db->query("SELECT COUNT(*) FROM ai_chat_logs WHERE role='user'")->fetchColumn();
        $prices   = $this->db->query("SELECT COUNT(*) FROM market_prices")->fetchColumn();
        return compact('tips','alerts','farms','farmers','aiChats','prices');
    }

    public function getTipsByCategory(): array {
        return $this->db->query(
            "SELECT category, COUNT(*) AS total FROM advisory_tips WHERE is_active=1 GROUP BY category ORDER BY total DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAlertsBySeverity(): array {
        return $this->db->query(
            "SELECT severity, COUNT(*) AS total FROM pest_alerts WHERE is_active=1 GROUP BY severity"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentActivity(int $limit=10): array {
        return $this->db->query(
            "SELECT 'tip' AS type, title AS label, created_at FROM advisory_tips
             UNION ALL
             SELECT 'alert', title, created_at FROM pest_alerts
             ORDER BY created_at DESC LIMIT $limit"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
