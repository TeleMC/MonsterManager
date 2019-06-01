<?php

namespace Monster\Task;

use Monster\Monster;
use pocketmine\scheduler\Task;

class SpawnTask extends Task {
    private $plugin;
    private $mob;

    public function __construct(Monster $plugin, $a, $b, $s, $h) {
        $this->plugin = $plugin;
        $this->a = $a;
        $this->b = $b;
        $this->s = $s;
        $this->h = $h;
    }

    public function onRun($currentTick) {
        if (!isset ($this->plugin->data [$this->b])) {
            $this->plugin->getScheduler()->cancelTask($this->getTaskId());
        } else {
            $this->plugin->MobAutoSpawn($this->a, $this->b, $this->s, $this->h);
        }
    }
}
