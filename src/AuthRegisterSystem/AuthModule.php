<?php
namespace AuthRegisterSystem;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\scheduler\PluginTask;

class AuthModule implements Listener
{
    private $plugin;
    private $__moduleConfig;
    public function __construct(PluginBase $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getCorePlugin()
    {
        return $this->plugin;
        }
    public function isEnabled()
    {
        return $this->plugin->isEnabled();
        }
    public function isDisabled()
    {
        return !$this->plugin->isEnabled();
        }
    public function getServer()
    {
        return $this->plugin->getServer();
    }
    public function getLogger()
    {
        return $this->plugin->getLogger();
    }
    public function getName()
    {
        return "AuthRegisterSystem";
        }
    public function getDataFolder(){
        return $this->plugin->getDataFolder();
    }
    public function getFile()
    {
        return $this->plugin->getFile();
        }
    public function getDescription()
    {
        return $this->plugin->getDescription();
        }
    public function saveResource($filename,$replace = false)
    {
        $this->plugin->saveResource($filename,$replace);
    }
    public function saveDefaultConfig()
    {
        $this->plugin->saveDefaultConfig();
    }
    public function reloadConfig()
    {
        $this->__moduleConfig = new \pocketmine\utils\Config($this->getDataFolder()."config.yml",\pocketmine\utils\Config::YAML);
    }
    public function saveConfig()
    {
        if($this->__moduleConfig !== null)
            {
                $this->__moduleConfig->save();
                }
    }
    public function getConfig()
    {
        if($this->__moduleConfig === null)
            {
            $this->reloadConfig();
        }
        return $this->__moduleConfig;
    }
    public $data;
    private $authenticated = [];
    public $joinData = [];
    private $loginAttempts = [];
    private $authNoticeCooldown = [];
    private $messages = [
        'register.prompt' => '&eYou are not registered. Type &6/register <password>&e to Register. &7(the password must contains minimum 6 letters or numbers)',
        'register.required_short' => '&cYou must &6/register <password>&c first.',
        'register.usage' => '&cUse: /register <password> &7(the password must contains minimum 6 letters or numbers)',
        'register.already_secured' => '&cYou are already registered. Use /login instead.',
        'register.success' => '&aRegistration successful! Your password is &6{%0}&a - don\'t forget it.',
        'login.prompt' => '&eWelcome back! Type &6/login <password>&e to continue.',
        'login.required_short' => '&cYou must &6/login <password>&c first.',
        'login.not_secured' => '&cThis account isn\'t registered yet. Use /register instead.',
        'login.wrong_password' => '&cWrong password! &7({%0} attempt(s) remaining)',
        'login.too_many_attempts' => '&cToo many failed login attempts. Please rejoin and try again.',
        'login.success' => '&aLogin successful. Welcome back!',
        'changepassword.not_registered' => '&cYou are not registered yet.',
        'changepassword.not_secured' => '&cThis account has no password set.',
        'changepassword.login_first' => '&cYou must be logged in to change your password.',
        'changepassword.mismatch' => '&cUsage: /changepassword <newpassword> <newpassword> &7(passwords must match, min. 6 characters)',
        'changepassword.success' => '&aYour password has been changed successfully.',
        'auth.not_allowed' => '&cYou cannot use this command.',
        'auth.usage' => '&cUsage: /auth remove <player>',
        'auth.not_found' => '&cNo stored authentication data was found for that player.',
        'auth.removed' => '&aAuthentication data for &6{%0}&a has been removed.',
    ];
    public function init()
    {
        @mkdir($this->getDataFolder());
        $this->data = new Config($this->getDataFolder()."players.yml",Config::YAML);
        $this->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        $this->plugin->getScheduler()->scheduleRepeatingTask(new AuthTask($this),20);
    }
    public function trans($target, $key, array $params = [])
    {
        $text = isset($this->messages[$key]) ? $this->messages[$key] : $key;
        foreach($params as $i => $p){
            $text = str_replace("{%". $i ."}",(string) $p, $text);
        }
        return
        str_replace("&","\xc2\xa7",$text);
    }
    public function checkAuthTimeout()
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
            {
            $name = strtolower($player->getName());
            if(!isset($this->joinData[$name]))
                continue;

            $stored = $this->getStoredName($name);
            $info = $stored !== null ? $this->data->get($stored) : null;
            $isRegistered = $info !== null && isset($info["password"]);
            $isAuth = $stored !== null && isset($this->authenticated[strtolower($stored)]);
            $time = time() - $this->joinData[$name]["time"];
            if(!$isRegistered)
                {
                if($time >= 60)
                    {
                    $player->kick("§cYou have been Timed out. Retry Again by Joining", false);
                }
                continue;
            }
            if(!$isAuth)
                {
                if($time >= 60)
                    {
                    $player->kick("§cYou have been Timed out. Retry Again by Joining", false);
                }
            }
        }
    }
    public function getStoredName($name)
    {
        foreach($this->data->getAll() as $stored => $info)
            {
            if(strtolower($stored) === strtolower($name))
                {
                return $stored;
            }
        }
        return null;
    }
    public function isAdministrator(CommandSender $sender)
    {
        return $sender->isOp();
    }
    private function isAuthenticated(Player $player)
    {
        $name = strtolower($player->getName());
        $stored = $this->getStoredName($name);
        if($stored === null) return false;
        return isset($this->authenticated[strtolower($stored)]);
    }
    public function onJoin(PlayerJoinEvent $event)
    {
        $player = $event->getPlayer();
        $name = strtolower($player->getName());
        unset($this->joinData[$name],
        $this->loginAttempts[$name],
        $this->authNoticeCooldown[$name]);
        $this->loginAttempts[$name] = 0;
        $this->joinData[$name]=["time"=> time()];
        $stored = $this->getStoredName($name);
        if($stored === null)
            {
            $this->data->set($player->getName(),
            ["ip" => $player->getAddress()]);
            $this->data->save();
            $this->sendOptionalRegisterMessage($player);
            return;
        }
        $info = $this->data->get($stored);
        unset($this->authenticated[strtolower($stored)]);
        if(isset($info["password"]))
            {
            $this->sendLoginMessage($player);
            return;
        }
        $this->sendOptionalRegisterMessage($player);
    }
    public function onCommand(CommandSender $sender, Command $cmd, $label,array $args)
    {
        if(!$sender instanceof Player)
            return true;
        $name = strtolower($sender->getName());
        $stored = $this->getStoredName($name);
        switch(strtolower($cmd->getName()))
        {
            case "register":
                if($stored === null)
                    return true;
                $info = $this->data->get($stored);
                if(isset($info["password"]))
                    {
                    $sender->sendMessage($this->trans($sender,"register.already_secured"));
                    return true;
                }
                if(count($args) < 1 || strlen($args[0]) < 6)
                    {
                    $sender->sendMessage($this->trans($sender,"register.usage"));
                    return true;
                }
                $info["password"] = $args[0];
                $this->data->set($stored, $info);
                $this->data->save();
                $this->authenticated[strtolower($stored)] = true;
                unset($this->joinData[$name]);
                $this->sendRegisterSuccessMessage($sender, $args[0]);
                return true;
            case "login":
                if($stored === null)
                    return true;
                $info = $this->data->get($stored);
                if(!isset($info["password"]))
                    {
                    $sender->sendMessage($this->trans($sender,"login.not_secured"));
                    return true;
                }
                if(!isset($this->loginAttempts[$name]))
                    {
                    $this->loginAttempts[$name] = 0;
                }
                if(count($args) < 1 || $args[0] !== $info["password"])
                    {
                    $this->loginAttempts[$name]++;
                    $remaining = 3 - $this->loginAttempts[$name];
                    if($remaining > 0)
                        {
                        $sender->sendMessage($this->trans($sender,"login.wrong_password",[$remaining]));
                    }
                    if($this->loginAttempts[$name] >= 3)
                        {
                        $sender->kick($this->trans($sender,"login.too_many_attempts"));
                        unset($this->loginAttempts[$name]);
                    }
                    return true;
                }
                $this->authenticated[strtolower($stored)] = true;
                unset($this->joinData[$name], $this->loginAttempts[$name]);
                $this->sendLoginSuccessMessage($sender);
                return true;
            case "changepassword":
                if($stored === null)
                    {
                    $sender->sendMessage($this->trans($sender,"changepassword.not_registered"));
                    return true;
                }
                $info = $this->data->get($stored);
                if(!isset($info["password"]))
                    {
                    $sender->sendMessage($this->trans($sender, "changepassword.not_secured"));
                    return true;
                }
                if(!isset($this->authenticated[strtolower($stored)]))
                    {
                    $sender->sendMessage($this->trans($sender, "changepassword.login_first"));
                    return true;
                }
                if(count($args) < 2 || strlen($args[0]) < 6 || $args[0] !== $args[1])
                    {
                    $sender->sendMessage($this->trans($sender,"changepassword.mismatch"));
                    return true;
                }
                $info["password"] = $args[0];
                $this->data->set($stored, $info);
                $this->data->save();
                $sender->sendMessage($this->trans($sender,"changepassword.success"));
                return true;
            case "auth":
                if(!$this->isAdministrator($sender))
                    {
                    $sender->sendMessage($this->trans($sender,"auth.not_allowed"));
                    return true;
                }
                if(count($args) < 2 || strtolower($args[0]) !=="remove")
                    {
                    $sender->sendMessage($this->trans($sender,"auth.usage"));
                    return true;
                }
                $target = $this->getStoredName(strtolower($args[1]));
                if($target === null)
                    {
                    $sender->sendMessage($this->trans($sender,"auth.not_found"));
                    return true;
                }
                $this->data->remove($target);
                $this->data->save();
                unset($this->authenticated[strtolower($target)]);
                $sender->sendMessage($this->trans($sender,"auth.removed",[$target]));
                return true;
        }
        return true;
    }
    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        if($this->isAuthenticated($player)) return;
        $from = $event->getFrom();
        $to = $event->getTo();
        if($from->getFloorX() === $to->getFloorX()
            && $from->getFloorY() === $to->getFloorY()
            && $from->getFloorZ() === $to->getFloorZ())
            {
            return;
        }
        $event->setCancelled(true);
        $player->teleport($from);
        $this->notifyAuthRequired($player);
    }
    public function onChat(PlayerChatEvent $event)
    {
        $player = $event->getPlayer();
        if(!$this->isAuthenticated($player))
            {
            $event->setCancelled(true);
            $this->notifyAuthRequired($player);
            return;
        }
        $recipients = $event->getRecipients();
        foreach($recipients as $key => $recipient)
            {
            if($recipient instanceof Player && !$this->isAuthenticated($recipient))
                {
                unset($recipients[$key]);
            }
        }
        $event->setRecipients($recipients);
    }
    public function onPacketSend(DataPacketSendEvent $event)
    {
        $packet = $event->getPacket();
        if(!($packet instanceof TextPacket))
            return;
        $player = $event->getPlayer();
        if(!($player instanceof Player))
            return;
        if($this->isAuthenticated($player))
            return;
        foreach(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $frame)
            {
            if(!isset($frame["class"]))
                continue;
            if($frame["class"] === __CLASS__)
                {
                return;
            }
        }
        $event->setCancelled(true);
    }
    public function onCommandProcess(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $message = trim($event->getMessage());
        $cmd = strtolower(explode(" ", $message)[0]);
        $name = strtolower($player->getName());
        if($this->isAuthenticated($player))
            return;
        if(in_array($cmd,
        ["/login", "/register", "/changepassword"]))
        {
            return;
        }
        $event->setCancelled(true);
        $this->notifyAuthRequired($player);
    }
    private function notifyAuthRequired(Player $player)
    {
        $name = strtolower($player->getName());
        $now = time();
        if(isset($this->authNoticeCooldown[$name])
            &&
        ($now - $this->authNoticeCooldown[$name]) < 3)
        {
            return;
        }
        $this->authNoticeCooldown[$name] = $now;
        $stored = $this->getStoredName($name);
        $info = $stored 
        !== null ? $this->data->get($stored) : null;
        $hasPassword = $info 
        !== null && isset($info["password"]);
        if(!$hasPassword)
            {
            $player->sendMessage($this->trans($player,"register.required_short"));
            return;
        }
        $player->sendMessage($this->trans($player,"login.required_short"));
    }
    public function onDamage(EntityDamageEvent $event)
    {
        if($event->getEntity() instanceof Player)
            {
            if(!$this->isAuthenticated($event->getEntity()))
                {
                $event->setCancelled(true);
            }
        }
    }
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        if(!$this->isAuthenticated($player))
            {
            $event->setCancelled(true);
            $this->notifyAuthRequired($player);
        }
    }
    public function onPlace(BlockPlaceEvent $event)
    {
        $player = $event->getPlayer();
        if(!$this->isAuthenticated($player))
            {
            $event->setCancelled(true);
            $this->notifyAuthRequired($player);
        }
    }
    public function onDrop(PlayerDropItemEvent $event)
    {
        $player = $event->getPlayer();
        if(!$this->isAuthenticated($player))
            {
            $event->setCancelled(true);
            $this->notifyAuthRequired($player);
            $player->getInventory()->sendContents($player);
        }
    }
    public function onQuit(PlayerQuitEvent $event)
    {
        $name = strtolower($event->getPlayer()->getName());
        unset(
            $this->joinData[$name],
        $this->loginAttempts[$name],
        $this->authNoticeCooldown[$name]);
        $stored = $this->getStoredName($name);
        if($stored !== null)
            {
            unset($this->authenticated[strtolower($stored)]);
        }
    }
    private function sendOptionalRegisterMessage(Player $p)
    {
        $p->sendMessage($this->trans($p,"register.prompt"));
    }
    private function sendLoginMessage(Player $p)
    {
        $p->sendMessage($this->trans($p,"login.prompt"));
    }
    private function sendRegisterSuccessMessage(Player $p, $pass)
    {
        $p->sendMessage($this->trans($p,"register.success", [$pass]));
    }
    private function sendLoginSuccessMessage(Player $p)
    {
        $p->sendMessage($this->trans($p,"login.success"));
    }
}

class AuthTask extends PluginTask
{
    private $module;
    public function __construct(AuthModule $plugin)
    {
        parent::__construct(
            $plugin->getCorePlugin()
            );
        $this->module = $plugin;
    }
    public function onRun($tick)
    {
        $this->module->checkAuthTimeout();
    }
}
