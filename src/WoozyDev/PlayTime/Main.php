<?php

/**
  * Author: WoozyDev
  */

namespace WoozyDev\PlayTime;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class Main extends PluginBase implements Listener
{

    private array $sessions;

    public function getSessions(): array
    {
        return $this->sessions;
    }

    public function setSessionData(string $uuid, array $sessionData, $join = false): void
    {
        if($join) $sessionData["session-time"] = 0;
        $this->sessions[$uuid] = $sessionData;
    }

    public function saveSession(string $uuid): void
    {
        $cfg = $this->getConfig();
        $cfg->set($uuid, $this->sessions[$uuid]);
        unset($this->sessions[$uuid]);
        $cfg->save();
        $cfg->reload();
    }

    protected function onEnable(): void
    {
        $this->sessions = array();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("PlayTime has been enabled. Coded by WoozyDev");
        $this->getScheduler()->scheduleRepeatingTask(new class($this) extends Task {
            private Main $main;

            public function __construct(Main $main)
            {
                $this->main = $main;
            }

            public function onRun(): void
            {
                foreach ($this->main->getSessions() as $uuid => $session)
                {
                    $session["session-time"] += 1;
                    $session["total-time"] += 1;
                    $this->main->setSessionData($uuid, $session);
                }
            }
        }, 20);
    }

    protected function onDisable(): void
    {
        $this->getLogger()->info("PlayTime has been disabled. Coded by WoozyDev");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if(!$sender instanceof Player) return false;

        switch (strtolower($command->getName()))
        {
            case "playtime":
                $data = $this->sessions[$this->getUUID($sender)];

                $time = $data["session-time"];
                $days = floor($time / 86400);
                $hours = floor($time / 3600);
                $minutes = floor(($time / 60) % 60);
                $seconds = floor($time % 60);

                $sender->sendMessage("§aSession Time: §b$days days, $hours hrs, $minutes mins, $seconds secs§a.");

                $time = $data["total-time"];
                $days = floor($time / 86400);
                $hours = floor($time / 3600);
                $minutes = floor(($time / 60) % 60);
                $seconds = floor($time % 60);

                $sender->sendMessage("§aTotal Time: §b$days days, $hours hrs, $minutes mins, $seconds secs§a.");
                break;
        }
        return false;
    }

    public function getUUID(Player $player): string
    {
        return $player->getUniqueId()->toString();
    }

    public function getConfig(): Config
    {
        return new Config($this->getDataFolder() . "config.yml");
    }

    public function getPlayerData(string $uuid): array
    {
        return $this->getConfig()->get($uuid, array(
            "session-time" => isset($this->sessions[$uuid]) ? $this->sessions[$uuid] : 0,
            "total-time" => 0
        ));
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        if($player instanceof Player)
        {
            $uuid = $this->getUUID($player);
            $this->setSessionData($uuid, $this->getPlayerData($uuid), true);
        }
    }

    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        if($player instanceof Player)
        {
            $this->saveSession($this->getUUID($player));
        }
    }

}