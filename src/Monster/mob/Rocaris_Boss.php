<?php

namespace Monster\mob;

use Monster\Monster;

class Rocaris_Boss extends PersonBase {
    const NETWORK_ID = 36;
    public $width = 0.6;
    public $height = 1.95;
    public $monster_id = 47;

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

    public function onUpdate(int $currentTick): bool {
        parent::onUpdate($currentTick);
        return true;
    }
}
