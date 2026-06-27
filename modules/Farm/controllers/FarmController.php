<?php
/**
 * SFAS — Farm Controller
 * File: modules/Farm/controllers/FarmController.php
 */
require_once dirname(__DIR__,3).'/config/database.php';
require_once dirname(__DIR__,1).'/models/FarmModel.php';

class FarmController {
    private FarmModel $m;

    public function __construct() {
        $this->m = new FarmModel(Database::getConnection());
    }

    public function list(array $f = []): array {
        return ['success' => true, 'data' => $this->m->getAll($f)];
    }

    public function myFarms(int $farmerId): array {
        return ['success' => true, 'data' => $this->m->getByFarmer($farmerId)];
    }

    public function get(int $id): array {
        $farm = $this->m->getById($id);
        if (!$farm) return ['success' => false, 'message' => 'Farm not found'];
        $farm['crops'] = $this->m->getFarmCrops($id);
        return ['success' => true, 'data' => $farm];
    }

    public function create(array $d, object $currentUser): array {
        if (empty($d['farm_name'])) return ['success'=>false,'message'=>'Farm name is required'];
        if (empty($d['farmer_id'])) {
            // If farmer is creating their own farm
            $d['farmer_id'] = $currentUser->user_id;
        }
        $id = $this->m->create($d);
        return ['success' => true, 'message' => 'Farm registered successfully', 'id' => $id];
    }

    public function update(int $id, array $d, object $currentUser): array {
        $farm = $this->m->getById($id);
        if (!$farm) return ['success'=>false,'message'=>'Farm not found'];
        // Farmers can only edit their own farm unless admin/agronomist
        if ((int)$farm['farmer_id'] !== (int)$currentUser->user_id && !$currentUser->is_super_admin) {
            return ['success'=>false,'message'=>'You can only edit your own farms'];
        }
        $this->m->update($id, $d);
        return ['success'=>true,'message'=>'Farm updated successfully'];
    }

    public function delete(int $id, object $currentUser): array {
        $farm = $this->m->getById($id);
        if (!$farm) return ['success'=>false,'message'=>'Farm not found'];
        if ((int)$farm['farmer_id'] !== (int)$currentUser->user_id && !$currentUser->is_super_admin) {
            return ['success'=>false,'message'=>'You can only delete your own farms'];
        }
        $this->m->delete($id);
        return ['success'=>true,'message'=>'Farm deleted'];
    }

    public function addCrop(array $d): array {
        if (empty($d['farm_id']) || empty($d['crop_id'])) {
            return ['success'=>false,'message'=>'farm_id and crop_id required'];
        }
        $id = $this->m->addFarmCrop($d);
        return ['success'=>true,'message'=>'Crop added to farm','id'=>$id];
    }

    public function removeCrop(int $id): array {
        $this->m->removeFarmCrop($id);
        return ['success'=>true,'message'=>'Crop removed'];
    }

    public function crops(): array {
        return ['success'=>true,'data'=>$this->m->getAllCrops()];
    }

    public function stats(): array {
        return ['success'=>true,'data'=>$this->m->getStats()];
    }

    public function farmers(): array {
        return ['success'=>true,'data'=>$this->m->getFarmers()];
    }
}
