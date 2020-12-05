<?php

declare(strict_types=1);

namespace underwaterLiver;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\event\entity\EntityDamageEvent;



class BroadcastTask extends Task{

	/** @var Server */
	private $server;
	
	private $plugin;

	public function __construct(MainClass $plugin){
		$this->server = $plugin->getServer();
		$this->plugin = $plugin;
	}

	public function onRun(int $currentTick) : void{
        foreach($this->server->getOnlinePlayers() as $player){
			if ($player->isAlive()) {
				if($this->startsWith($player->getLevel()->getName(), "underwater")) {
					$tickDiff = 1;

					$ticks = $this->pluginGetAirSupplyTicks($player);
					$oldTicks = $ticks;
					
					//Player is OUT of water
					if($player->canBreathe()){
						$player->setBreathing(false);

						if(($respirationLevel = $player->getArmorInventory()->getHelmet()->getEnchantmentLevel(Enchantment::RESPIRATION)) <= 0 or
							lcg_value() <= (1 / ($respirationLevel + 1))
						){
							$ticks -= $tickDiff;
							if($ticks <= -20){
								$ticks = 0;
								$player->onAirExpired();

							}
						}
					//Player is IN water
					}elseif(!$player->canBreathe()){
						if($ticks < ($max = $player->getMaxAirSupplyTicks())){
							$ticks += $tickDiff * 5;
						}
						if($ticks >= $max){
							$ticks = $max;
							$player->setBreathing(true);
						}
					}
					if ($player->isAlive()) {
						$player->setAirSupplyTicks($ticks);
						$this->pluginSetAirSupplyTicks($player,$ticks);
					}
					else {
						$player->setAirSupplyTicks($player->getMaxAirSupplyTicks());
						$this->pluginSetAirSupplyTicks($player,$player->getMaxAirSupplyTicks());						
					}
				}
			}
		}
	}
	
	public function pluginGetAirSupplyTicks($player) {
		$playerName = $player->getName();
		if (!isset($this->plugin->breathingPlayer[$playerName])) {
			$this->pluginSetAirSupplyTicks($player, $player->getMaxAirSupplyTicks());
			return $player->getMaxAirSupplyTicks();
		}
		else return $this->plugin->breathingPlayer[$playerName];
	}
	
	public function pluginSetAirSupplyTicks($player,$ticks) {
		$playerName = $player->getName();
		$this->plugin->breathingPlayer[$playerName] = $ticks;
	}
	
	public function startsWith($haystack, $needle)
	{
		 $length = strlen($needle);
		 return (substr($haystack, 0, $length) === $needle);
	}
}
