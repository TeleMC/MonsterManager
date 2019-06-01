<?php

namespace Monster;

use Core\Core;
use Core\util\Util;
use Equipments\Equipments;
use Monster\mob\Bandit_1;
use Monster\mob\Bandit_2;
use Monster\mob\Bandit_3;
use Monster\mob\Bandit_Boss;
use Monster\mob\Chicken;
use Monster\mob\Chosun_1;
use Monster\mob\Chosun_2;
use Monster\mob\Chosun_3;
use Monster\mob\Chosun_Boss;
use Monster\mob\Cobalt_1;
use Monster\mob\Cobalt_2;
use Monster\mob\Cobalt_3;
use Monster\mob\Cobalt_Boss;
use Monster\mob\Corrupt_1;
use Monster\mob\Corrupt_2;
use Monster\mob\Corrupt_3;
use Monster\mob\Corrupt_4;
use Monster\mob\Corrupt_Boss;
use Monster\mob\Cow;
use Monster\mob\Demon_1;
use Monster\mob\Demon_2;
use Monster\mob\Demon_3;
use Monster\mob\Demon_Boss;
use Monster\mob\Miner_1;
use Monster\mob\Miner_2;
use Monster\mob\Miner_3;
use Monster\mob\Miner_4;
use Monster\mob\Miner_Boss;
use Monster\mob\MonsterBase;
use Monster\mob\PersonBase;
use Monster\mob\Pig;
use Monster\mob\Prel_1;
use Monster\mob\Prel_2;
use Monster\mob\Prel_3;
use Monster\mob\Prel_Boss;
use Monster\mob\Priest_1;
use Monster\mob\Priest_2;
use Monster\mob\Priest_3;
use Monster\mob\Priest_Boss;
use Monster\mob\RedSword_1;
use Monster\mob\RedSword_2;
use Monster\mob\RedSword_3;
use Monster\mob\RedSword_4;
use Monster\mob\RedSword_Boss;
use Monster\mob\Rocaris_1;
use Monster\mob\Rocaris_2;
use Monster\mob\Rocaris_3;
use Monster\mob\Rocaris_4;
use Monster\mob\Rocaris_Boss;
use Monster\mob\Spirit_1;
use Monster\mob\Spirit_2;
use Monster\mob\Spirit_3;
use Monster\mob\Spirit_4;
use Monster\mob\Spirit_Boss;
use Monster\mob\Wolf;
use Monster\Task\SpawnTask;
use PacketEventManager\PacketEventManager;
use PartyManager\PartyManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\entity\Creature;
use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use SkillManager\SkillManager;
use UiLibrary\UiLibrary;

class Monster extends PluginBase {
    private static $instance = null;
    //public $pre = "§l§e[ §f시스템 §e]§r§e ";
    public $pre = "§e• ";
    public $monster_list = [];
    public $monster_entry = [];

    public static function getInstance() {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        @mkdir($this->getDataFolder());
        $this->saveResource("ID.yml");
        $this->saveResource("Prize.yml");
        $this->saveResource("random.yml");
        $this->saveResource("config.yml");
        $this->saveResource("skins.yml");
        $this->id = (new Config($this->getDataFolder() . "ID.yml", Config::YAML))->getAll();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->cdata = $this->config->getAll();
        $this->random = new Config($this->getDataFolder() . "random.yml", Config::YAML);
        $this->rdata = $this->random->getAll();
        $this->prize = new Config($this->getDataFolder() . "Prize.yml", Config::YAML);
        $this->pdata = $this->prize->getAll();
        $this->monster = new Config($this->getDataFolder() . "monster.yml", Config::YAML);
        $this->data = $this->monster->getAll();
        $this->mitem = new Config($this->getDataFolder() . "item.yml", Config::YAML);
        $this->idata = $this->mitem->getAll();
        $this->skin = new Config($this->getDataFolder() . "skins.yml", Config::YAML);
        $this->sdata = $this->skin->getAll();
        $this->lock = new Config($this->getDataFolder() . "lock.yml", Config::YAML, ["lock" => "false"]);
        $this->ldata = $this->lock->getAll();
        $this->core = Core::getInstance();
        $this->util = new Util($this->core);
        $this->eq = Equipments::getInstance();
        $this->ui = UiLibrary::getInstance();
        $this->packet = PacketEventManager::getInstance();
        $this->party = PartyManager::getInstance();
        $this->skill = SkillManager::getInstance();

        foreach ([
                         Chicken::Class, Pig::Class, Cow::Class, Wolf::Class,
                         Bandit_1::Class, Bandit_2::Class, Bandit_3::Class, Bandit_Boss::Class,
                         Prel_1::Class, Prel_2::Class, Prel_3::Class, Prel_Boss::Class,
                         Cobalt_1::Class, Cobalt_2::Class, Cobalt_3::Class, Cobalt_Boss::Class,
                         Miner_1::Class, Miner_2::Class, Miner_3::Class, Miner_4::Class, Miner_Boss::Class,
                         RedSword_1::Class, RedSword_2::Class, RedSword_3::Class, RedSword_4::Class, RedSword_Boss::Class,
                         Spirit_1::Class, Spirit_2::Class, Spirit_3::Class, Spirit_4::Class, Spirit_Boss::Class,
                         Chosun_1::Class, Chosun_2::Class, Chosun_3::Class, Chosun_Boss::Class,
                         Demon_1::Class, Demon_2::Class, Demon_3::Class, Demon_Boss::Class,
                         Priest_1::Class, Priest_2::Class, Priest_3::Class, Priest_Boss::Class,
                         Rocaris_1::Class, Rocaris_2::Class, Rocaris_3::Class, Rocaris_4::Class, Rocaris_Boss::Class,
                         Corrupt_1::Class, Corrupt_2::Class, Corrupt_3::Class, Corrupt_4::Class, Corrupt_Boss::Class
                 ] as $entity) {
            Entity::registerEntity($entity, true);
        }

        foreach ($this->cdata as $id => $value) {
            $info = $this->getInfoToId($id); //[최대 개체수, 리젠 기간(초), 크기, 체력, ATK, DEF, Speed]
            $this->getScheduler()->scheduleRepeatingTask(new SpawnTask($this, $this->getClassToId($id), $this->getNameToId($id), $info[2], $info[3]), ((int) $info[1] + 2) * 20);
        }
    }

    public function getInfoToId(int $id) {
        if (isset($this->cdata[$id])) return $this->cdata[$id];
        else return false;
    }

    public function getClassToId(int $id) {
        if (isset($this->id["class"][$id])) return $this->id["class"][$id];
        else return false;
    }

    public function getNameToId(int $id) {
        if (isset($this->id["name"][$id])) return $this->id["name"][$id];
        else return false;
    }

    public function onDisable() {
        foreach ($this->getServer()->getLevels() as $level) {
            foreach ($level->getEntities() as $entity) {
                if ($entity instanceof Creature && !$entity instanceof Player) {
                    //$entity->despawnFromAll();
                    $entity->addEffect(new \pocketmine\entity\EffectInstance(\pocketmine\entity\Effect::getEffect(14), 3 * 20, 1, false));
                    $entity->close();
                }
            }
        }
        unset($this->monster_list);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if ($command->getName() == "소환설정") {
            if (!$sender->isOp()) {
                $sender->sendMessage($this->pre . "권한이 없습니다.");
                return false;
            }
            if (!isset($args[0])) {
                $sender->sendMessage($this->pre . "/소환설정 <추가|제거> <몬스터 아이디|스포너 아이디> | 몬스터스폰을 설정합니다.");
                return false;
            }
            switch ($args[0]) {
                case '추가':
                    if (!isset($args[1])) {
                        $sender->sendMessage($this->pre . "/소환설정 <추가|제거> <몬스터 아이디|스포너 아이디> | 몬스터스폰을 설정합니다.");
                        return false;
                    }
                    if (!is_numeric($args[1])) {
                        $sender->sendMessage($this->pre . "몬스터 아이디는 숫자여야합니다.");
                        return false;
                    }
                    if ($this->getInfoToId($args[1]) == false) {
                        $sender->sendMessage($this->pre . "[ {$this->getNameToId($args[1])} §e](은)는 정의되지 않은 몬스터입니다.");
                        return false;
                    }
                    if (3 < $args[1] && !isset($this->sdata [$this->getNameToId($args[1])])) {
                        $sender->sendMessage($this->pre . "[ {$this->getNameToId($args[1])} §e] 의 스킨이 정의되지 않았습니다.");
                        return false;
                    }
                    $this->mode [$sender->getName()] ["생성모드"] = $this->getNameToId($args[1]);
                    $sender->sendMessage($this->pre . "[ {$this->getNameToId($args[1])} §e] 생성모드에 돌입하였습니다.");
                    $this->save();
                    return true;

                case '제거':
                    if (!isset($args[1])) {
                        $sender->sendMessage($this->pre . "/소환설정 <추가|제거> <몬스터|스포너 아이디> | 몬스터스폰을 설정합니다.");
                        return false;
                    }
                    if (!is_numeric($args[1])) {
                        $sender->sendMessage($this->pre . "스포너 아이디는 숫자여야합니다.");
                        return false;
                    }
                    if ($this->getNameToId($args[1]) == false) {
                        $sender->sendMessage($this->pre . "존재하지 않는 아이디입니다.");
                        return false;
                    }
                    if (!isset($this->data[$this->getNameToId($args[1])])) {
                        $sender->sendMessage($this->pre . "존재하지 않는 스포너입니다.");
                        return false;
                    }
                    unset($this->data[$this->getNameToId($args[1])]);
                    foreach ($this->getServer()->getLevels() as $level) {
                        foreach ($level->getEntities() as $ent) {
                            if (explode("\n", $ent->getNameTag())[0] == $this->getNameToId($args[1])) {
                                unset($this->packet->hit[$ent->getId()]);
                                //$ent->kill();
                                $ent->close();
                            }
                        }
                    }
                    $sender->sendMessage($this->pre . "성공적으로 제거하였습니다.");
                    $this->save();
                    return true;
                case '취소':
                    unset($this->mode [$sender->getName()]);
                    $sender->sendMessage($this->pre . "모든 작업을 취소하였습니다.");
                    return true;

                default:
                    $sender->sendMessage($this->pre . "/소환설정 <추가|제거> <몬스터|스포너 아이디> | 몬스터스폰을 설정합니다.");
                    return false;
            }
        }
        if ($command->getName() == "몹청소") {
            if (!$sender->isOp()) {
                $sender->sendMessage($this->pre . "권한이 없습니다.");
                return false;
            }
            $count = 0;
            foreach ($this->getServer()->getLevels() as $level) {
                foreach ($level->getEntities() as $entity) {
                    if (!$entity instanceof MonsterBase && !$entity instanceof PersonBase)
                        continue;
                    //$entity->despawnFromAll();
                    //$entity->addEffect(new \pocketmine\entity\EffectInstance(\pocketmine\entity\Effect::getEffect(14), 3*20, 1, false));
                    $entity->close();
                    $count++;
                }
            }
            unset($this->monster_list);
            /*foreach($this->monster_list as $key => $key_1){
                foreach($this->monster_list[$key] as $key_1 => $entity){
                    $entity->despawnFromAll();
                    $entity->kill();
                    $count++;
                }
            }*/
            unset($this->monster_list);
            $sender->sendMessage($this->pre . "몬스터 " . $count . "마리가 제거되었습니다.");
            return true;
        }
        if ($command->getName() == "엔티티청소") {
            if (isset($this->monster_list)) {
                foreach ($this->monster_list as $key => $value) {
                    $sender->sendMessage($this->pre . $key . "=>" . count($this->monster_list[$key]) . "마리");
                }
            }

            $count = 0;
            foreach ($this->getServer()->getLevels() as $level) {
                foreach ($level->getEntities() as $entity) {
                    if ($entity instanceof Creature && !$entity instanceof Player) {
                        $entity->close();
                        if (($entity instanceof MonsterBase || $entity instanceof PersonBase) && isset($this->monster_list[$this->getIdToName($entity->getName())][$entity->getId()])) {
                            unset($this->monster_list[$this->getIdToName($entity->getName())][$entity->getId()]);
                        }
                        $count++;
                    }
                }
            }
            unset($this->monster_list);
            $sender->sendMessage($this->pre . "생명체 " . $count . "마리가 제거되었습니다.");
            return true;
        }
        if ($command->getName() == "소환잠금") {
            if ($this->ldata["lock"] == "false") {
                $this->ldata["lock"] = "true";
                $sender->sendMessage($this->pre . "소환을 정지하였습니다.");
            } else {
                $this->ldata["lock"] = "false";
                $sender->sendMessage($this->pre . "소환을 허용하였습니다.");
            }
            return true;
        }
        if ($command->getName() == "스킨") {
            if (!$sender->isOp()) {
                $sender->sendMessage($this->pre . "권한이 없습니다.");
                return false;
            }
            if (!isset($args[0])) {
                $sender->sendMessage($this->pre . "/스킨 <몬스터 아이디> | 자신의 스킨을 몬스터의 스킨으로 설정합니다.");
                return false;
            }
            if (!is_numeric($args[0])) {
                $sender->sendMessage($this->pre . "몬스터 아이디는 숫자여야합니다.");
                return false;
            }
            if ($this->getNameToId($args[0]) == false) {
                $sender->sendMessage($this->pre . "존재하지 않는 아이디입니다.");
                return false;
            } else {
                $skin = $sender->getSkin();
                $this->sdata [$this->getNameToId($args[0])] = [];
                $this->sdata [$this->getNameToId($args[0])] [0] = base64_encode($skin->getSkinId());
                $this->sdata [$this->getNameToId($args[0])] [1] = base64_encode($skin->getSkinData());
                $this->sdata [$this->getNameToId($args[0])] [2] = base64_encode($skin->getCapeData());
                $this->sdata [$this->getNameToId($args[0])] [3] = base64_encode($skin->getGeometryName());
                $this->sdata [$this->getNameToId($args[0])] [4] = base64_encode($skin->getGeometryData());
                $this->save();
                $sender->sendMessage("{$this->pre}[ {$this->getNameToId($args[0])} §e] 의 스킨을 성공적으로 저장하였습니다.");
                return true;
            }
        }
        return true;
    }

    public function save() {
        $this->monster->setAll($this->data);
        $this->monster->save();
        $this->mitem->setAll($this->idata);
        $this->mitem->save();
        $this->skin->setAll($this->sdata);
        $this->skin->save();
        $this->lock->setAll($this->ldata);
        $this->lock->save();
    }

    public function getIdToName(string $name) {
        if (isset($this->id["id"][$name])) return $this->id["id"][$name];
        else return false;
    }

    public function MobAutoSpawn($EntityName, $name, $s, $h) {
        if ($this->ldata["lock"] == "true") return false;
        $info = $this->getInfoToName($name);
        if (!isset($this->data[$name]) or count($this->getServer()->getLevelByName($this->data[$name]["생성월드"])->getPlayers()) <= 0) return;
        if (!isset($this->monster_list[$this->getIdToName($name)])) $this->monster_list[$this->getIdToName($name)] = [];
        if (count($this->monster_list[$this->getIdToName($name)]) < $info[0]) {
            $pos = explode(":", $this->data[$name]["생성좌표"]);
            $pos = new Vector3((int) $pos[0], (int) $pos[1], (int) $pos[2]);
            $level = $this->getServer()->getLevelByName($this->data[$name]["생성월드"]);
            if (!$this->isNearbyPlayers(new AxisAlignedBB($pos->x - 12, $pos->y - 12, $pos->z - 12, $pos->x + 12, $pos->y + 12, $pos->z + 12), $level))
                return false;
            if (stripos($name, "BOSS") !== false) {
                $plus_x = 0;
                $plus_z = 0;
            } else {
                $plus_x = mt_rand(-10, 10);
                $plus_z = mt_rand(-10, 10);
            }
            $ny = $pos->y;
            for ($i = 0; $i < 20; $i++) {
                if ($level->getBlock((new Vector3($pos->x + $plus_x, $pos->y, $pos->z + $plus_z))->add(0, $i * -1, 0))->getId() !== 0) {
                    if ($this->getIdToName($name) <= 3)
                        $ny = $pos->y - $i + 1;
                    else
                        $ny = $pos->y - $i + 1;
                    break;
                }
            }
            $pos = new Vector3($pos->x + $plus_x + 0.5, $ny, $pos->z + $plus_z + 0.5);
            $this->MobCreate($EntityName, $name, $pos, $level, $s, $h);
            $this->save();
        } else {
            return;
        }
    }

    public function getInfoToName(string $name) {
        if (isset($this->cdata[$this->getIdToName($name)])) return $this->cdata[$this->getIdToName($name)];
        else return false;
    }

    public function isNearbyPlayers(AxisAlignedBB $bb, Level $level) {
        $minX = ((int) floor($bb->minX - 2)) >> 4;
        $maxX = ((int) floor($bb->maxX + 2)) >> 4;
        $minZ = ((int) floor($bb->minZ - 2)) >> 4;
        $maxZ = ((int) floor($bb->maxZ + 2)) >> 4;
        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($z = $minZ; $z <= $maxZ; ++$z) {
                foreach ($level->getChunkEntities($x, $z) as $entity) {
                    if ($entity instanceof Player && $entity->boundingBox->intersectsWith($bb))
                        return true;
                }
            }
        }
        return false;
    }

    public function MobCreate(string $EntityName, string $name, Vector3 $pos, Level $level, float $scale = 1.0, float $health) {
        if (isset($this->monster_entry[$this->getIdToName($name)])) {
            $nbt = $this->monster_entry[$this->getIdToName($name)];
            $entity = Entity::createEntity($EntityName, $level, $nbt);
            $entity->teleport($pos);
            $entity->setMaxHealth($health);
            $entity->setHealth($health);
            $entity->setNameTag($name);
            $entity->setNameTagAlwaysVisible(true);
            $entity->spawnToAll();
            $entity->setScale($scale);
            $this->monster_list[$this->getIdToName($name)][$entity->getId()] = $entity;
        } else {
            $nbt = Entity::createBaseNBT($pos, null, 1, 1);
            if ($this->getIdToName($name) >= 4 and isset($this->sdata [$name])) {
                $skinData = $this->sdata [$name];
                $nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", base64_decode($skinData[0])),
                        new ByteArrayTag("Data", base64_decode($skinData[1])),
                        new ByteArrayTag("CapeData", base64_decode($skinData[2])),
                        new StringTag("GeometryName", base64_decode($skinData[3])),
                        new ByteArrayTag("GeometryData", base64_decode($skinData[4]))
                ]));
            }
            $entity = Entity::createEntity($EntityName, $level, $nbt);
            $this->monster_entry[$this->getIdToName($name)] = $nbt;
            $entity->setMaxHealth((float) $health);
            $entity->setHealth((float) $health);
            $entity->setNameTag("{$name}");
            $entity->setNameTagAlwaysVisible(true);
            $entity->spawnToAll();
            $entity->setScale($scale);
            $this->monster_list[$this->getIdToName($name)][$entity->getId()] = $entity;
            //$this->monster_entry[$this->getIdToName($name)] = clone $entity;
        }
    }

    public function getMobEtcitem($name, $item) {
        if (!isset($this->idata [$name]) or !isset($this->idata [$name] [$item])) return false;
        else return $this->idata [$name] [$item];
    }

    public function addMobEtcitem($name, $item, $amount) {
        if (!isset($this->idata [$name])) {
            $this->idata [$name] = [];
            $this->save();
        }
        if (!isset($this->idata [$name] [$item])) {
            $this->idata [$name] [$item] = 0;
            $this->save();
        }
        $this->idata [$name] [$item] += $amount;
        $this->save();
    }

    public function reduceMobEtcitem($name, $item, $amount) {
        if (!isset($this->idata [$name]) or !isset($this->idata [$name] [$item])) return false;
        else $this->idata [$name] [$item] -= $amount;
        $this->save();
        return true;
    }

    public function EtcUI(Player $player) {
        if ($player instanceof Player) {
            $form = $this->ui->SimpleForm(function (Player $player, array $data) {
            });
            if (!isset($this->idata [$player->getName()])) {
                $a = "";
            } else {
                $a = "\n";
                foreach ($this->idata [$player->getName()] as $item => $info) {
                    $a .= "§c▶ §f{$item} : {$this->idata[$player->getName()][$item]}개\n\n";
                }
            }
            $form->setTitle("Tele Etcitem");
            $form->setContent("{$a}");
            $form->sendToPlayer($player);
        }
    }

    public function HealthBar(Entity $entity) {
        $maxhp = $entity->getMaxHealth();
        $hp = $entity->getHealth();
        $o = $maxhp / 20;
        if ($maxhp == $hp) {
            $a = "†" . str_repeat("§c§l|§r", round($maxhp / $o)) . "†";
            return $a;
        } elseif ($maxhp - $hp > 0) {
            $a = "†" . str_repeat("§c§l|§r", round($hp / $o)) . str_repeat("§l§0|§r", round($maxhp / $o - $hp / $o)) . "†";
            return $a;
        }
    }

    public function getPrize(string $name) {
        if (isset($this->pdata[$name])) return $this->pdata[$name];
        else return false;
    }

    public function getClassToName(string $name) {
        if (isset($this->id["class"][$this->getIdToName($name)])) return $this->id["class"][$this->getIdToName($name)];
        else return false;
    }

    public function getLevelToId(int $id) {
        if (isset($this->id["level"][$this->getNameToId($id)])) return $this->id["level"][$this->getNameToId($id)];
        else return false;
    }

    public function getLevelToName(string $name) {
        if (isset($this->id["level"][$name])) return $this->id["level"][$name];
        else return false;
    }
}
