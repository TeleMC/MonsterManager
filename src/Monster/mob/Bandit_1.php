<?php

namespace Monster\mob;

use Monster\Monster;

class Bandit_1 extends PersonBase {
    const NETWORK_ID = 63;
    public $width = 0.6;
    public $height = 1.95;
    public $monster_id = 4;

    public function getSpawnPos(): array {
        if (isset(Monster::getInstance()->data[$this->getName()]["생성좌표"]))
            $pos = explode(":", Monster::getInstance()->data[$this->getName()]["생성좌표"]);
        else $pos = [0, 0, 0];
        return $pos;
    }

    public function getName(): string {
        return Monster::getInstance()->getNameToId($this->monster_id);
    }

    public function getATK(): int {
        return (Monster::getInstance()->getInfoToName($this->getName()))[4];
    }

    public function getDEF(): int {
        return (Monster::getInstance()->getInfoToName($this->getName()))[5];
    }

    public function getLv(): int {
        return Monster::getInstance()->getLevelToName($this->getName());
    }

    public function getSpeed(): float {
        return (Monster::getInstance()->getInfoToName($this->getName()))[6];
    }

    public function HealthBar() {
        $maxhp = $this->getMaxHealth();
        $hp = $this->getHealth();
        $o = $maxhp / 20;
        if ($maxhp == $hp) {
            $a = "†" . str_repeat("§c§l|§r", round($maxhp / $o)) . "†";
            return $a;
        } elseif ($maxhp - $hp > 0) {
            $a = "†" . str_repeat("§c§l|§r", round($hp / $o)) . str_repeat("§l§0|§r", round($maxhp / $o - $hp / $o)) . "†";
            return $a;
        }
    }

    public function onUpdate(int $currentTick): bool {
        parent::onUpdate($currentTick);
        return true;
    }
}
