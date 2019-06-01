<?php

namespace Monster\Task;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class AsyncSpawnTask extends AsyncTask {

    public function __construct(string $EntityName, string $name, Vector3 $pos, string $level, float $s, float $h) {
        //$this->plugin = $plugin;
        $this->EntityName = $EntityName;
        $this->name = $name;
        $this->pos = $pos;
        $this->level = $level;
        $this->s = $s;
        $this->h = $h;
    }

    public function onRun() {
        $this->nbt = Entity::createBaseNBT($pos, null, 1, 1);
        //Monster::getInstance()->MobCreate($this->EntityName, $this->name, $this->pos, Monster::getInstance()->getServer()->getLevelByName($this->level), $this->s, $this->h);
    }

    public function onCompletion(Server $server) {
        $servet->getPluginManager()->getPlugin("Monster")->add[$servet->getPluginManager()->getPlugin("Monster")->count++] = $this->nbt;
    }
}
