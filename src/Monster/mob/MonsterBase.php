<?php

namespace Monster\mob;

use Core\Core;
use Monster\Monster;
use PacketEventManager\PacketEventManager;
use pocketmine\entity\Animal;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

abstract class MonsterBase extends Animal {

    public $target = null;
    public $time = null;
    public $check_radius_duration = 20;
    public $target_find_radius = 12;
    protected $speed_factor = null;
    protected $follow_range_sq = 1.2;
    protected $jumpTicks = 0;
    protected $attack_queue = 0;
    private $punch = null;
    private $low = null;
    private $wpqkf = null;

    public function initEntity(): void {
        parent::initEntity();
    }

    public function setId(int $id) {
        $this->id = $id;
    }

    public function onUpdate(int $currentTick): bool {
        if ($this->isClosed()) return false;
        if ($this->attack_queue > 0) $this->attack_queue--;
        if ($this->check_radius_duration > 0) $this->check_radius_duration--;
        if ($this->low !== null) {
            if (time() - $this->low < 0) {
                $this->yaw = 180;
                parent::onUpdate($currentTick);
                return false;
            }
        }
        if ($this->punch !== null) {
            if (time() - $this->punch >= 1)
                $this->punch = null;
        }
        if ($this->target == null) { // 타겟이 없을때
            if ($this->isCollidedHorizontally && $this->jumpTicks == 0) {
                $this->jump();
            }
            if ($this->check_radius_duration == 0 && !$this->isNearbyPlayers()) {
                unset(PacketEventManager::getInstance()->hit[$this->getId()]);
                $this->close();
                if (isset(Monster::getInstance()->monster_list[Monster::getInstance()->getIdToName($this->getName())][$this->getId()])) {
                    unset(Monster::getInstance()->monster_list[Monster::getInstance()->getIdToName($this->getName())][$this->getId()]);
                }
                $this->check_radius_duration = 100;
            }
            parent::onUpdate($currentTick);
            return true;
        } elseif ($this->target !== null && $this->target instanceof Player && $this->level instanceof Level && $this->target->level instanceof Level && $this->level->getFolderName() == $this->target->level->getFolderName()) { //타겟이 정상적으로 존재할떄
            if ($this->target->getGamemode() == 1 || $this->target->distance($this) > 40) {
                $this->target = null;
                parent::onUpdate($currentTick);
                return false;
            }
            if ($this->isBlock()) {
                $this->jump();
            }
            if ($this->target !== null) {
                $this->followByWalking($this->target);
            }
            if ($this->attack_queue == 0) {
                if ($this->target !== null && $this->distance($this->target) <= $this->follow_range_sq) {
                    $this->attack_queue = 20;
                    if ($this->getHealth() <= 0) {
                        parent::onUpdate($currentTick);
                        return true;
                    }
                    $this->target->attack(new EntityDamageByEntityEvent($this, $this->target, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, 0));
                }
            }
        } else { //타겟 오류
            $this->target = null;
        }
        parent::onUpdate($currentTick);
        return true;

        /*if ($this->target !== null and ! $this->closed and $this->target instanceof Player) {
            if ($this->level instanceof Level and $this->target->level instanceof Level) {
                if ($this->level->getFolderName () == $this->target->level->getFolderName ()) {
                    if ($this->target instanceof Vector3 and $this instanceof Vector3) {
                        if ($this->target->getGamemode () == 1 || $this->target->distance ( $this ) > 40){
                            $this->target = null;
                        }
                    } else {
                        $this->target = null;
                    }
                        if ($this->isBlock()) {
                            $this->jump ();
                        }
                        if ($this->target !== null){
                            $this->followByWalking ( $this->target );
                        }
                    if ($this->attack_queue == 0) {
                        if ($this->target instanceof Vector3 and $this instanceof Vector3) {
                            if ($this->distance ( $this->target ) <= 1.3) {
                                $this->attack_queue = 15;
                                if ($this->target !== null)
                                  if($this->getHealth() <= 0){
                                      parent::onUpdate ( $currentTick );
                                      return true;
                                    }
                                    $this->target->attack ( new EntityDamageByEntityEvent ( $this, $this->target, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, $this->getMonsterDamage($this->target, $this) ) );
                            }
                        } else {
                            $this->target = null;
                        }
                    }
                    parent::onUpdate ( $currentTick );
                    return true;
                } else {
                    $this->target = null;
                }
            } else {
                $this->target = null;
            }
        }
        parent::onUpdate ( $currentTick );
        return false;*/
    }

    public function jump(): void {
        parent::jump();
    }

    public function isNearbyPlayers() {
        $bb = $this->boundingBox->expandedCopy($this->target_find_radius, $this->target_find_radius, $this->target_find_radius);
        $minX = ((int) floor($bb->minX - 2)) >> 4;
        $maxX = ((int) floor($bb->maxX + 2)) >> 4;
        $minZ = ((int) floor($bb->minZ - 2)) >> 4;
        $maxZ = ((int) floor($bb->maxZ + 2)) >> 4;
        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($z = $minZ; $z <= $maxZ; ++$z) {
                foreach ($this->level->getChunkEntities($x, $z) as $entity) {
                    if ($entity instanceof Player && $entity->boundingBox->intersectsWith($bb))
                        return true;
                }
            }
        }
        return false;
    }

    public function isBlock() {
        if (($this->level->getBlock($this->add(1, 0, 0))->getId() !== 0 ||
                        $this->level->getBlock($this->add(1, 0, 1))->getId() !== 0 ||
                        $this->level->getBlock($this->add(1, 0, -1))->getId() !== 0 ||
                        $this->level->getBlock($this->add(-1, 0, 0))->getId() !== 0 ||
                        $this->level->getBlock($this->add(-1, 0, 1))->getId() !== 0 ||
                        $this->level->getBlock($this->add(-1, 0, -1))->getId() !== 0 ||
                        $this->level->getBlock($this->add(0, 0, 1))->getId() !== 0 ||
                        $this->level->getBlock($this->add(0, 0, -1))->getId() !== 0
                ) &&
                $this->level->getBlock($this->add(0, -1, 0))->getId() !== 0
        ) return true;
        else return false;
    }

    public function followByWalking(Entity $target, float $xOffset = 0.0, float $yOffset = 0.0, float $zOffset = 0.0): void {
        if ($this->punch !== null)
            return;
        $pos = $this->getSpawnPos();
        $spawn = new Vector3((float) $pos[0], (float) $pos[1], (float) $pos[2]);
        if ($target !== null) {
            if ($spawn->distance($this) >= 25) {
                $this->teleport(new Vector3((float) $pos[0] + mt_rand(-3, 3), (float) $pos[1], (float) $pos[2] + mt_rand(-3, 3)));
                $this->target = null;
                return;
            }
            $x = $target->x + $xOffset - $this->x;
            $y = $target->y + $yOffset - $this->y;
            $z = $target->z + $zOffset - $this->z;
            $xz_sq = $x * $x + $z * $z;
            $xz_modulus = sqrt($xz_sq);
            if ($xz_sq < $this->follow_range_sq) {
                $this->motion->x = 0;
                $this->motion->z = 0;
            } else {
                $speed_factor = $this->getSpeed();
                $this->motion->x = $speed_factor * ($x / $xz_modulus);
                $this->motion->z = $speed_factor * ($z / $xz_modulus);
            }
            $this->yaw = rad2deg(atan2(-$x, $z));
            $this->pitch = rad2deg(-atan2($y, $xz_modulus));
            $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        }
    }

    public function getTarget() {
        return $this->target;
    }

    public function setTarget(Player $player) {
        $this->target = $player;
    }

    public function punch() {
        $this->punch = time();
    }

    public function setLow(int $time) {
        $this->low = time() + $time;
    }

    public function randMove(): void {
        if ($this->punch !== null)
            return;
        if ($this->wpqkf == null)
            $this->wpqkf = time() - 12;
        if (time() - $this->wpqkf < 5) {
            $this->move($this->motion->x, $this->motion->y, $this->motion->z);
            return;
        }
        $this->wpqkf = time();
        $pos = $this->getSpawnPos();
        if ($this->distance(new Vector3($pos[0], $pos[1], $pos[2])) > 25) {
            $x = $pos[0] - $this->x;
            $y = $pos[1] - $this->y;
            $z = $pos[2] - $this->z;
        } else {
            $rand = mt_rand(1, 4);
            if ($rand == 1) {
                $nx = 10;
                $nz = 10;
            } elseif ($rand == 2) {
                $nx = 10;
                $nz = -10;
            } elseif ($rand == 3) {
                $nx = -10;
                $nz = -10;
            } else {
                $nx = -10;
                $nz = 10;
            }
            $randPos = $this->add($nx, 0, $nz);
            $x = $randPos->x - $this->x;
            $y = $randPos->y - $this->y;
            $z = $randPos->z - $this->z;
        }
        $xz_sq = $x * $x + $z * $z;
        $xz_modulus = sqrt($xz_sq);
        $speed_factor = $this->getSpeed();
        $this->motion->x = $speed_factor * ($x / $xz_modulus);
        $this->motion->z = $speed_factor * ($z / $xz_modulus);
        $this->yaw = rad2deg(atan2(-$x, $z));
        $this->pitch = rad2deg(-atan2($y, $xz_modulus));
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
    }

    public function getMonsterDamage($player, $target) {
        $util = new \Core\util\Util(Core::getInstance());
        $CA = ($target->getATK()) / 1.5;
        $CD = 200;
        if ($util->getJob($player->getName()) == "나이트" and $util->getJob($player->getName()) == "아처") $M_DEF = $util->getDEF($name);
        else $M_DEF = $util->getMDEF($player->getName());
        $DR = 0;
        $AN = 1;
        $damage = ($CA - $M_DEF) * (100 - $DR) * $AN / 100;
        return $damage;
    }
}
