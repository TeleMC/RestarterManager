<?php
namespace RestarterManager;

use pocketmine\command\{Command, CommandSender};
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\Task;
use pocketmine\utils\Config;

class RestarterManager extends PluginBase {
    private static $instance = null;
    //public $pre = "§l§e[ §f시스템 §e]§r§e";
    public $pre = "§e•";
    public $taskId = null;
    public $startTime = null;
    public $shutdown = false;

    public static function getInstance() {
        return self::$instance;
    }

    public function onLoad() {
        self::$instance = $this;
    }

    public function onEnable() {
        date_default_timezone_set("Asia/Seoul");
        @mkdir($this->getDataFolder());
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->data = $this->config->getAll();
        $this->startTime = \pocketmine\START_TIME;
        $this->getScheduler()->scheduleRepeatingTask(
                new class($this) extends Task {
                    public function __construct(RestarterManager $plugin) {
                        $this->plugin = $plugin;
                    }

                    public function onRun($currentTick) {
                        $this->plugin->tick();
                        $this->plugin->taskId = $this->getTaskId();
                    }
                }, 1200);
    }

    public function tick() {
        $time = microtime(true);
        if (date("h", $time) % 2 == 0 && date("i", $time) == 0 && $this->shutdown === false && $time - $this->startTime > 60) {
            $this->shutdown = true;
            $this->Delay_Restart();
        }
    }

    public function Delay_Restart() {
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->addTitle(" ", "{$this->pre}\n잠시후, 서버가 재부팅 됩니다. 5 ~ 10초후 재접속하시길 바랍니다.\n\n\n", 10, 30, 10);
        }
        $this->getServer()->getLogger()->notice("{$this->pre} 잠시후, 서버가 재부팅 됩니다.");
        $this->getScheduler()->scheduleDelayedTask(
                new class($this) extends Task {
                    public function __construct(RestarterManager $plugin) {
                        $this->plugin = $plugin;
                    }

                    public function onRun($currentTick) {
                        $this->plugin->Restart();
                    }
                }, 100);
    }

    public function Restart() {
        foreach ($this->getServer()->getLevels() as $level) {
            $level->save(true);
        }
        $this->getServer()->shutdown();
        foreach ($this->getServer()->getLevels() as $level) {
            $level->save(true);
        }
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $player->save();
            $player->kick("서버가 §a재부팅§f됩니다.\n§c5 ~ 10초§f후 §a재접속§f하시길 바랍니다.", false);
        }
    }

    public function onDisable() {
        $this->config->setAll($this->data);
        $this->config->save();
    }

    public function onCommand(CommandSender $sender, Command $cmd, $label, $args): bool {
        if ($cmd->getName() == "재부팅") {
            if (!$sender->isOp()) {
                $sender->sendMessage("{$this->pre} 권한이 없습니다.");
                return false;
            }
            if (!isset($args[0])) {
                $time = $this->getTime();
                $sender->sendMessage("--- 재부팅 도움말 1 / 1 ---");
                $sender->sendMessage("{$this->pre} /재부팅 실행 | 재부팅을 실행합니다.");
                $sender->sendMessage("{$this->pre} 재부팅까지: {$time}");
                return false;
            }
            if ($args[0] == "실행") {
                $this->Delay_Restart();
                return true;
            }
            return false;
        }
        return false;
    }

    public function getTime() {
        $time = microtime(true);
        if (date("h", $time) % 2 == 0 && date("i", $time) == 0) {
            return "0:0:0";
        } elseif (date("h", $time) % 2 == 0) {
            $minute = 60 - (int) date("i", $time) - 1;
            $second = 60 - (int) date("s", $time);
            return "1:{$minute}:{$second}";
        } else {
            $minute = 60 - (int) date("i", $time) - 1;
            $second = 60 - (int) date("s", $time);
            return "0:{$minute}:{$second}";
        }
    }
}
