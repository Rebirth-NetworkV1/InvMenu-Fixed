<?php

/*
 *  ___            __  __
 * |_ _|_ ____   _|  \/  | ___ _ __  _   _
 *  | || '_ \ \ / / |\/| |/ _ \ '_ \| | | |
 *  | || | | \ V /| |  | |  __/ | | | |_| |
 * |___|_| |_|\_/ |_|  |_|\___|_| |_|\__,_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Muqsit
 * @link http://github.com/Muqsit
 *
*/

namespace muqsit\invmenu;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\plugin\Plugin;
use Rage\DivineCore\RebirthPlayer;

class InvMenuHandler implements Listener{

	/** @var Plugin|null */
	private static $registrant;

	public static function isRegistered() : bool{
		return self::$registrant instanceof Plugin;
	}

	public static function getRegistrant() : Plugin{
		return self::$registrant;
	}

	public static function register(Plugin $plugin) : void{
		if(self::isRegistered()){
			throw new \Error($plugin->getName() . "attempted to register " . self::class . " twice.");
		}

		self::$registrant = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents(new InvMenuHandler(), $plugin);
	}

	private function __construct(){
	}

	/**
	 * @param InventoryTransactionEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event): void {
    $transaction = $event->getTransaction();

    foreach ($transaction->getActions() as $action) {
        if ($action instanceof SlotChangeAction) {
            $inventory = $action->getInventory();

            if ($inventory instanceof BaseFakeInventory) {
                $menu = $inventory->getMenu();
                $listener = $menu->getListener();

                // Check for listener and validate the transaction source
                $player = $transaction->getSource();
                if ($player instanceof RebirthPlayer) {
                    $result = $listener !== null 
                        ? $listener($player, $action->getSourceItem(), $action->getTargetItem(), $action) 
                        : true;

                    if (!$result || $menu->isReadonly()) {
                        $event->setCancelled();
                    }
                } else {
                    $event->setCancelled(); // Cancel if the player isn't a RebirthPlayer
                }
                return;
            }
        }
    }
}
}

