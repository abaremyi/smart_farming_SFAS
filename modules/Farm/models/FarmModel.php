<?php
/**
 * SFAS — Farm Model
 * File: modules/Farm/models/FarmModel.php
 */
class FarmModel {
    private PDO $db;

    public function __construct(PDO $db) { $this->db = $db; }

    /* ── FARMS ───────────────────────────────────────────── */

    public function getAll(array $f = []): array {
        $sql = "SELECT fm.*, u.firstname, u.lastname, u.email, u.phone,
                       COUNT(fc.id) AS crops_count
                FROM farms fm
                LEFT JOIN users u ON u.id = fm.farmer_id
                LEFT JOIN farm_crops fc ON fc.farm_id = fm.id
                WHERE 1=1";
        $p = [];
        if (!empty($f['farmer_id'])) { $sql .= " AND fm.farmer_id=:fid"; $p[':fid'] = $f['farmer_id']; }
        if (!empty($f['district']))  { $sql .= " AND fm.district=:dis";   $p[':dis'] = $f['district']; }
        if (!empty($f['search'])) {
            $sql .= " AND (fm.farm_name LIKE :s OR u.firstname LIKE :s OR u.lastname LIKE :s OR fm.sector LIKE :s)";
            $p[':s'] = '%'.$f['search'].'%';
        }
        $sql .= " GROUP BY fm.id ORDER BY fm.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT fm.*, u.firstname, u.lastname, u.email, u.phone
             FROM farms fm
             LEFT JOIN users u ON u.id = fm.farmer_id
             WHERE fm.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getByFarmer(int $farmerId): array {
        $stmt = $this->db->prepare(
            "SELECT fm.*, COUNT(fc.id) AS crops_count
             FROM farms fm
             LEFT JOIN farm_crops fc ON fc.farm_id = fm.id
             WHERE fm.farmer_id = :fid
             GROUP BY fm.id ORDER BY fm.created_at DESC"
        );
        $stmt->execute([':fid' => $farmerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO farms (farmer_id, farm_name, district, sector, cell,
             size_ha, soil_type, water_source, latitude, longitude, notes)
             VALUES (:fi,:fn,:di,:se,:ce,:sz,:st,:ws,:lat,:lon,:no)"
        );
        $stmt->execute([
            ':fi'  => $d['farmer_id'],
            ':fn'  => $d['farm_name'],
            ':di'  => $d['district']    ?? 'Nyagatare',
            ':se'  => $d['sector']      ?? null,
            ':ce'  => $d['cell']        ?? null,
            ':sz'  => $d['size_ha']     ?? null,
            ':st'  => $d['soil_type']   ?? null,
            ':ws'  => $d['water_source'] ?? 'Rain-fed',
            ':lat' => $d['latitude']    ?? null,
            ':lon' => $d['longitude']   ?? null,
            ':no'  => $d['notes']       ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $d): bool {
        $allowed = ['farm_name','district','sector','cell','size_ha','soil_type','water_source','latitude','longitude','notes'];
        $sets = []; $p = [':id' => $id];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $d)) { $sets[] = "$col=:$col"; $p[":$col"] = $d[$col]; }
        }
        if (empty($sets)) return false;
        return $this->db->prepare(
            "UPDATE farms SET ".implode(',',$sets).", updated_at=NOW() WHERE id=:id"
        )->execute($p);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM farms WHERE id=:id")->execute([':id' => $id]);
    }

    /* ── FARM CROPS ──────────────────────────────────────── */

    public function getFarmCrops(int $farmId): array {
        $stmt = $this->db->prepare(
            "SELECT fc.*, c.name AS crop_name, c.category, c.local_name
             FROM farm_crops fc
             JOIN crops c ON c.id = fc.crop_id
             WHERE fc.farm_id = :fid ORDER BY fc.planted_at DESC"
        );
        $stmt->execute([':fid' => $farmId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addFarmCrop(array $d): int {
        $stmt = $this->db->prepare(
            "INSERT INTO farm_crops (farm_id, crop_id, season, area_ha, planted_at, expected_harvest, status, notes)
             VALUES (:fi,:ci,:se,:ah,:pa,:eh,:st,:no)"
        );
        $stmt->execute([
            ':fi' => $d['farm_id'],
            ':ci' => $d['crop_id'],
            ':se' => $d['season']           ?? null,
            ':ah' => $d['area_ha']          ?? null,
            ':pa' => $d['planted_at']       ?? null,
            ':eh' => $d['expected_harvest'] ?? null,
            ':st' => $d['status']           ?? 'Growing',
            ':no' => $d['notes']            ?? null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function removeFarmCrop(int $id): bool {
        return $this->db->prepare("DELETE FROM farm_crops WHERE id=:id")->execute([':id' => $id]);
    }

    /* ── CROPS REFERENCE ─────────────────────────────────── */

    public function getAllCrops(): array {
        return $this->db->query("SELECT * FROM crops ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCropById(int $id): ?array {
        $s = $this->db->prepare("SELECT * FROM crops WHERE id=:id LIMIT 1");
        $s->execute([':id' => $id]);
        return $s->fetch() ?: null;
    }

    /* ── STATS ───────────────────────────────────────────── */

    public function getStats(): array {
        $r = $this->db->query(
            "SELECT COUNT(*) total,
             SUM(water_source='Irrigation') irrigated,
             SUM(water_source='Rain-fed') rainfed,
             AVG(size_ha) avg_size
             FROM farms"
        )->fetch(PDO::FETCH_ASSOC);
        return $r ?? ['total'=>0,'irrigated'=>0,'rainfed'=>0,'avg_size'=>0];
    }

    public function getDistinctDistricts(): array {
        return $this->db->query("SELECT DISTINCT district FROM farms WHERE district IS NOT NULL ORDER BY district")
            ->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getFarmers(): array {
        return $this->db->query(
            "SELECT id, CONCAT(firstname,' ',lastname) AS name, email FROM users WHERE role_id=3 AND account_status='active' ORDER BY firstname"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
}
