<?php
namespace AuthRegisterSystem;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
class Main extends PluginBase
{
    private $module;
    public function onEnable():void
    {
        $this->saveDefaultConfig();
        $this->module = new AuthModule($this);
        $this->module->init();
    }
    public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args):bool
    {
        return $this->module->onCommand($sender, $cmd, $label, $args);
    }
}
