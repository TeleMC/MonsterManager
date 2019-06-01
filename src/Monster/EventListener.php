<?php

namespace Monster;

use Monster\mob\MonsterBase;
use Monster\mob\PersonBase;
use Monster\Task\SpawnTask;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\Player;

class EventListener implements Listener {
    public function __construct(Monster $plugin) {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $ev) {
        if (!isset($this->plugin->idata [$ev->getPlayer()->getName()])) {
            $this->plugin->idata [$ev->getPlayer()->getName()] = [];
            $this->plugin->idata [$ev->getPlayer()->getName()]["닭고기"] = 0;
        }
    }

    public function onDespawn(EntityDespawnEvent $ev) {
        $entity = $ev->getEntity();
        if ($entity instanceof MonsterBase || $entity instanceof PersonBase) {
            if (isset($this->plugin->monster_list[$this->plugin->getIdToName($entity->getName())][$entity->getId()])) {
                unset($this->plugin->monster_list[$this->plugin->getIdToName($entity->getName())][$entity->getId()]);
            }
        }
    }

    public function EntityDeath(EntityDeathEvent $ev) {
        $entity = $ev->getEntity();
        if ($entity instanceof Player) return;
        if (isset($this->plugin->monster_list[$this->plugin->getIdToName($entity->getName())][$entity->getId()])) {
            unset($this->plugin->monster_list[$this->plugin->getIdToName($entity->getName())][$entity->getId()]);
        }
        $last = $entity->getLastDamageCause();
        if ($last instanceof EntityDamageByEntityEvent) {
            if (!isset($this->plugin->packet->hit[$entity->getId()])) return;
            $dmg = $this->plugin->packet->hit[$entity->getId()];
            if (!$dmg instanceof Player || !$dmg->isOnline()) return;
            $name = $dmg->getName();
            if (!$entity instanceof MonsterBase and !$entity instanceof PersonBase) return;
            if (isset($this->plugin->rdata[$this->plugin->getIdToName($entity->getName())])) {
                $rand = mt_rand(0, 100);
                foreach ($this->plugin->rdata[$this->plugin->getIdToName($entity->getName())] as $percentage => $item) {
                    if ($rand <= $percentage) {
                        if ($this->plugin->eq->isEquipment($item)) {
                            $item = $this->plugin->eq->getEquipment($item, 1, 1, 1);
                            if (!$item == null && count($item) > 0) {
                                foreach ($item as $key => $value) {
                                    if (!$dmg instanceof Player || !$dmg->isOnline())
                                        break;
                                    $dmg->getInventory()->addItem($value);
                                    $dmg->sendPopup($this->plugin->pre . "보상: {$value->getCustomName()} 1개");
                                }
                            }
                            break;
                        } else {
                            $this->plugin->addMobEtcitem($dmg->getName(), $item, 1);
                            $dmg->sendPopup($this->plugin->pre . "보상: {$item} 1개");
                            break;
                        }
                    }
                }
                $info = $this->plugin->getInfoToName($entity->getName());
                if (stripos($entity->getName(), "BOSS") === false)
                    $exp = $info[7] * 6; // 건의로 상향된 사냥 경험치
                else
                    $exp = $info[7];
                if ($this->plugin->party->isParty($name))
                    $this->plugin->party->giveExp($this->plugin->party->getParty($name), $exp, 1, $entity->getLv());
                else
                    $this->plugin->util->addExp($name, $exp, 1, $this->plugin->util->getLevel($name) - $entity->getLv());
            }
        }
    }

    public function onTouch(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        $item = $event->getItem();
        $x = $player->getX();
        $y = $player->getY();
        $z = $player->getZ();
        if (!isset($this->plugin->mode[$name])) return;
        if ($player->isOp()) {
            if (isset ($this->plugin->id["id"][$this->plugin->mode [$name] ["생성모드"]])) {
                if (isset($this->plugin->data [$this->plugin->mode [$name] ["생성모드"]])) {
                    $player->sendMessage($this->plugin->pre . "이미 존재하는 스포너입니다.");
                    return;
                }
                $info = $this->plugin->getInfoToName($this->plugin->mode [$name] ["생성모드"]);
                $this->plugin->data [$this->plugin->mode [$name] ["생성모드"]] ["생성좌표"] = "{$x}:{$y}:{$z}";
                $this->plugin->data [$this->plugin->mode [$name] ["생성모드"]] ["생성월드"] = $player->getLevel()->getName();
                $this->plugin->getScheduler()->scheduleRepeatingTask(new SpawnTask ($this->plugin, $this->plugin->getClassToName($this->plugin->mode [$name] ["생성모드"]), $this->plugin->mode [$name] ["생성모드"], $info[2], $info[3]), $info[1] * 20);
                $player->sendMessage($this->plugin->pre . "성공적으로 스포너를 설치하였습니다.");
                unset ($this->plugin->mode [$name]);
                $this->plugin->save();
            }
        }
    }
}
