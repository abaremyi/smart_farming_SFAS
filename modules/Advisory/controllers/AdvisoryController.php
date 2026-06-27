<?php
/**
 * SFAS — Advisory Controller
 * File: modules/Advisory/controllers/AdvisoryController.php
 */
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/AdvisoryModel.php';

class AdvisoryController {
    private AdvisoryModel $m;

    public function __construct() {
        $this->m = new AdvisoryModel(Database::getConnection());
    }

    /* ── TIPS ────────────────────────────────────────────── */

    public function listTips(array $f = []): array {
        return ['success'=>true,'data'=>$this->m->getAllTips($f)];
    }

    public function getTip(int $id): array {
        $tip = $this->m->getTipById($id);
        if (!$tip) return ['success'=>false,'message'=>'Advisory tip not found'];
        $this->m->incrementViews($id);
        return ['success'=>true,'data'=>$tip];
    }

    public function createTip(array $d, int $authorId): array {
        if (empty($d['title']))   return ['success'=>false,'message'=>'Title is required'];
        if (empty($d['content'])) return ['success'=>false,'message'=>'Content is required'];
        $d['author_id'] = $authorId;
        $id = $this->m->createTip($d);
        return ['success'=>true,'message'=>'Advisory tip created successfully','id'=>$id];
    }

    public function updateTip(int $id, array $d): array {
        $tip = $this->m->getTipById($id);
        if (!$tip) return ['success'=>false,'message'=>'Tip not found'];
        $this->m->updateTip($id, $d);
        return ['success'=>true,'message'=>'Tip updated successfully'];
    }

    public function deleteTip(int $id): array {
        $this->m->deleteTip($id);
        return ['success'=>true,'message'=>'Tip deleted'];
    }

    public function toggleTip(int $id): array {
        $tip = $this->m->getTipById($id);
        if (!$tip) return ['success'=>false,'message'=>'Tip not found'];
        $newState = $tip['is_active'] ? 0 : 1;
        $this->m->updateTip($id, ['is_active'=>$newState]);
        return ['success'=>true,'message'=>$newState ? 'Tip activated' : 'Tip deactivated', 'is_active'=>$newState];
    }

    /* ── ALERTS ──────────────────────────────────────────── */

    public function listAlerts(array $f = []): array {
        return ['success'=>true,'data'=>$this->m->getAllAlerts($f)];
    }

    public function createAlert(array $d, int $reporterId): array {
        if (empty($d['title']))       return ['success'=>false,'message'=>'Title is required'];
        if (empty($d['description'])) return ['success'=>false,'message'=>'Description is required'];
        $d['reported_by'] = $reporterId;
        $id = $this->m->createAlert($d);
        return ['success'=>true,'message'=>'Alert created','id'=>$id];
    }

    public function toggleAlert(int $id, int $active): array {
        $this->m->toggleAlert($id, $active);
        return ['success'=>true,'message'=>$active ? 'Alert activated' : 'Alert deactivated'];
    }

    public function deleteAlert(int $id): array {
        $this->m->deleteAlert($id);
        return ['success'=>true,'message'=>'Alert deleted'];
    }

    /* ── MARKET ──────────────────────────────────────────── */

    public function listPrices(array $f = []): array {
        return ['success'=>true,'data'=>$this->m->getAllPrices($f)];
    }

    public function latestPrices(): array {
        return ['success'=>true,'data'=>$this->m->getLatestPrices()];
    }

    public function addPrice(array $d, int $userId): array {
        if (empty($d['crop_id']))   return ['success'=>false,'message'=>'Crop is required'];
        if (empty($d['market']))    return ['success'=>false,'message'=>'Market name is required'];
        if (empty($d['price_rwf'])) return ['success'=>false,'message'=>'Price is required'];
        $d['updated_by'] = $userId;
        $id = $this->m->addPrice($d);
        return ['success'=>true,'message'=>'Price recorded','id'=>$id];
    }

    public function deletePrice(int $id): array {
        $this->m->deletePrice($id);
        return ['success'=>true,'message'=>'Price record deleted'];
    }

    /* ── REPORTS ─────────────────────────────────────────── */

    public function reportSummary(): array {
        return ['success'=>true,'data'=>$this->m->getReportSummary()];
    }

    public function reportByCategory(): array {
        return ['success'=>true,'data'=>$this->m->getTipsByCategory()];
    }

    public function reportAlertsBySeverity(): array {
        return ['success'=>true,'data'=>$this->m->getAlertsBySeverity()];
    }

    public function crops(): array {
        return ['success'=>true,'data'=>$this->m->getAllCrops()];
    }
}
