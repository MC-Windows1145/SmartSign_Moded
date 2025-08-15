<?php

namespace commandsign;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Timings;
use pocketmine\tile\Sign;
/**
 * @name SmartSign
 * @description 测试版
 * @author Wusheng233&Windows1145
 * @version 1.0.1
 * @main SmartSign\Main
 * @api 2.0.0
 */
class Main extends PluginBase implements Listener {
    public $protection;
    private $lastCommandTime = [];
	private $line2 = "plcmd" ;
    private $commandCooldown = 3; // 防刷屏冷却时间(秒)

    public function onLoad() {
        $this->getLogger()->info("反卡OP木牌正在加载");
    }

    public function onEnable() {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("反卡OP木牌已启动");
        $this->protection = $this->getServer()->getPluginManager()->getPlugin("Protection");
    }

    public function onDisable() {
        $this->getLogger()->info("反卡OP木牌已关闭");
    }

    public function onPlayerInteract(PlayerInteractEvent $event) {
        $tile = $event->getPlayer()->getLevel()->getTile($event->getBlock());
        if(!($tile instanceof Sign) || $event->isCancelled()) {
            return;
        }

        $signText = $tile->getText();
        $cmd = trim($signText[2] . $signText[3]);
        
        // 处理plcmd前后空格
        $plcmd = trim(strtolower($signText[1]));
        if($plcmd !== $this->line2 || empty($cmd)) {
            return;
        }

        $player = $event->getPlayer();
        if($this->protection !== null) {
            $this->protection->permission($event);
            if($event->isCancelled()) {
                $player->sendTip("§c你未登录,无法点击！");
                return;
            }
        }

        // 防刷屏检查
        $currentTime = microtime(true);
        $playerName = $player->getName();
        if(isset($this->lastCommandTime[$playerName]) && 
           ($currentTime - $this->lastCommandTime[$playerName]) < $this->commandCooldown) {
            $player->sendMessage("§c请等待".$this->commandCooldown."秒再试！");
            return;
        }
        $this->lastCommandTime[$playerName] = $currentTime;

        $this->getServer()->getPluginManager()->callEvent($ev = new PlayerCommandPreprocessEvent($player, "/" . $cmd));
        if($ev->isCancelled()) {
            return;
        }

        Timings::$playerCommandTimer->startTiming();
        $this->getServer()->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
        Timings::$playerCommandTimer->stopTiming();
    }
}
