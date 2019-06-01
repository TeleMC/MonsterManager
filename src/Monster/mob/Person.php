<?php

namespace Monster\mob;

class Person extends PersonBase {
    const NETWORK_ID = 36;
    public $width = 0.6;
    public $height = 1.95;

    public function getName(): string {
        return "Person";
    }

    public function onUpdate(int $currentTick): bool {
        parent::onUpdate($currentTick);
        return true;
    }
}
