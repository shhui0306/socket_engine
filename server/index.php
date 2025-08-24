<?php 
// Include models
foreach (glob("Model/general/*.php") as $filename)
{
    include $filename;
}
foreach (glob("Model/*.php") as $filename)
{
    include $filename;
}
foreach (glob("Model/custom/*.php") as $filename)
{
    include $filename;
}


// Incude Libraries
foreach (glob("Library/general/*.php") as $filename)
{
    include $filename;
}
foreach (glob("Library/*.php") as $filename)
{
    include $filename;
}
foreach (glob("Library/custom/*.php") as $filename)
{
    include $filename;
}

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use Workerman\Timer;

require_once __DIR__ . '/vendor/autoload.php';

//global library
$key = new Library\keyLibrary();
$serverLib = new Library\serverLibrary();

// Listen port 2021 for socket.io client
$io = new SocketIO(2021);
$io->on('connection', function ($socket) use ($io) {

    $socket->on("init",function($token)use($socket,$io){
        global $key, $serverLib;
        $decryptedData = json_decode($key->emailDec($token),true);
        if (isset($decryptedData['action'])){
            switch($decryptedData['action']){
                case "init": $result = $serverLib->init($decryptedData,$socket->id); break;
                default: $result=[["action"=>"emit", "name"=>"init", "data"=>["action"=>"", "data"=>"",  "message"=>"", "error"=>"Invalid action.", "devError"=>"[init] invalid action"]]]; break;
            }
        }
        emitAction($result,$io,$socket);
    });

    //idea sample
    $socket->on("room",function($token)use($socket,$io){
        global $key, $serverLib;
        if (is_array($token)){
            // non encrypted array
            $decryptedData = $token;
            $result = [];
            if (isset($decryptedData['action'])){
            switch($decryptedData['action']){
                case 'getRoom': $result = $serverLib->getRoom($decryptedData); break;
                case 'getRoomPlayerList': $result = $serverLib->getRoomPlayerList($socket->id); break;
                default: $result=[["action"=>"emit", "name"=>"room", "data"=>["action"=>"", "data"=>"",  "message"=>"", "error"=>"Invalid action.", "devError"=>"[room] invalid action ".$decryptedData['action']]]]; break;
                }
            }
        } else{
            // encrypted string
            $decryptedData = json_decode($key->resultDec($token),true);
            $result = [];
            if (isset($decryptedData['action'])){
            switch($decryptedData['action']){
                case 'addRoom': $result = $serverLib->addRoom($decryptedData,$socket->id); break; 
                case 'joinRoom': $result = $serverLib->joinRoom($decryptedData,$socket->id); break;
                case 'getRoom': $result = $serverLib->getRoom($decryptedData); break;
                case 'getRoomPlayerList': $result = $serverLib->getRoomPlayerList($socket->id); break;
                case 'playerReadyStart': $result = $serverLib->playerReadyStart($decryptedData,$socket->id); break;
                default: $result=[["action"=>"emit", "name"=>"room", "data"=>["action"=>"", "data"=>"",  "message"=>"", "error"=>"Invalid action.", "devError"=>"[room] invalid action ".$decryptedData['action']]]]; break;
                }
            }
        }
        emitAction($result,$io,$socket);
    });

    $socket->on("game",function($token)use($socket,$io){
        global $key, $serverLib;
        $result = [];
        if (is_array($token)){
            $decryptedData = $token;
            if (isset($decryptedData['action'])){
                switch($decryptedData['action']){
                    default: $result =  $serverLib->switchGameAction($decryptedData,$socket->id);
                }
            }
        }else{
            $decryptedData = json_decode($key->resultDec($token),true);
            if (isset($decryptedData['action'])){
                switch($decryptedData['action']){
                    default: $result =  $serverLib->switchGameAction($decryptedData,$socket->id);
                }
            }
        }
        
        emitAction($result,$io,$socket);
    });

    $socket->on("interact",function($token)use($socket,$io){
        global $key, $serverLib;
        $result = [];
        if (is_array($token)){
            $decryptedData = $token;
            if (isset($decryptedData['action'])){
                switch($decryptedData['action']){
                    case "manageInteractResult": $result = $serverLib->manageInteractResult($decryptedData,$socket->id); break;
                }
            }
        }else{
            $decryptedData = json_decode($key->resultDec($token),true);
            if (isset($decryptedData['action'])){
                switch($decryptedData['action']){
                    case "manageInteractResult": $result = $serverLib->manageInteractResult($decryptedData,$socket->id); break;
                }
            }
        }
        emitAction($result,$io,$socket);
    });

    $socket->on("chat",function($token)use($socket,$io){
        global $serverLib;
        $result = [];
        if (is_array($token)){
            $decryptedData = $token;
            if (isset($decryptedData['action'])){
                switch($decryptedData['action']){
                    case "sendChat": $result = $serverLib->sendChat($decryptedData,$socket->id); break;
                    default: break;
                }
            }
        }
        emitAction($result,$io,$socket);
    });

    $socket->on("disconnect",function()use($socket,$io){
        global $serverLib;
        $result = $serverLib->disconnect($socket->id);
        emitAction($result,$io,$socket);
    });

});

Worker::runAll();




//------------------ socket functions --------------------------
// execute emit and socket instructions
function emitAction($instructions,$io,$socket){
    if (is_array($instructions)){
        foreach ($instructions as $m){
            // perform instruction
            if (!isset($m["action"])) $m["action"]="";
            if (!isset($m["data"])) $m["data"]="";
            switch ($m["action"]){
                case "emit": $socket->emit($m["name"],$m["data"]); break; //emit to user
                case "emitTo": $io->to($m["to"])->emit($m["name"],$m["data"]); break; //emit to specific room / user
                case "emitGlobal": $io->emit($m["name"],$m["data"]); break; //global emit
                case "join": $socket->join($m["name"]); break; //join room
                case "leave": $socket->leave($m["name"]); break; //join room
                case "disconnect": $socket->disconnect(); break; //disconnects socket from server
                case "setCountdown": setCountdown($m["data"],$io,$socket); break; // set countdown
                case "deleteTimer": deleteTimer($m["name"]); break; // delete timer
                default: break;
            }
        }
    }
}

// set countdown for socket
function setCountdown($data,$io,$socket){
    // $data format: ["interval"=>$interval, "count"=>$count, "timerName"=>$timerName, "emitMode"=>$emitMode, "to"=>$to, "intervalAction"=>$intervalAction, "endAction"=>$endAction]

    if (empty($data["count"])) $i=0; else $i = $data["count"];
    if (empty($data["intervalAction"])) $data["intervalAction"] = function(){return [];};
    if (empty($data["endAction"])) $data["endAction"] = function(){return [];};
    if (empty($data["timerName"])) $data["timerName"] = "timer";
    if (empty($data["message"])) $data["message"] = "";
    if (empty($data["error"]))  $data["error"] = "";

    $result = false;
    if (isset($data["interval"])){
        $result = true;
        $timer = Timer::add($data["interval"],function()use(&$data,$socket,$io, &$timer, &$i){
            if ($i==null || $i<=0){
                // countdown end
                $message = ["action"=>"setCountdown", "data"=>["name"=>$data["timerName"], "status"=>"end", "count"=>0],"message"=>"", "error"=>""];
                switch($data["emitMode"]){
                    case "emit": $socket->emit("timer",$message); break;
                    case "emitTo": $io->to($data["to"])->emit("timer",$message); break;
                    case "emitGlobal": $io->emit("timer",$message); break;
                    default: break;
                }
                $action = $data["endAction"]();
                $del = true;
                foreach ($action as $a){
                    switch ($a["action"]){
                        case "del":  break; // will delete timer event without 'del' action
                        case "reset": $del = false; $i = $data["count"]; break; // reset timer to original count
                        case "setCount": $del = false; $i = $a["data"]["count"]; break; // set timer count to specified value
                        case "startNewCountdown": $del = false; if (isset($a["data"]["count"])) $i = $a["data"]["count"]; $data= $a["data"]; // start new countdown (note that interval must be same)
                        default: emitAction([$a],$io,$socket);
                    }
                }
                if ($del) Timer::del($timer);
            }else{
                // countdown in every interval
                $message = ["action"=>"setCountdown", "data"=>["name"=>$data["timerName"], "status"=>"counting", "count"=>$i],"message"=>"", "error"=>""];
                switch($data["emitMode"]){
                    case "emit": $socket->emit("timer",$message); break;
                    case "emitTo": $io->to($data["to"])->emit("timer",$message); break;
                    case "emitGlobal": $io->emit("timer",$message); break;
                    default: break;
                }
                $action = $data["intervalAction"]($i);
                $i--;
                foreach ($action as $a){
                    switch ($a["action"]){
                        case "del": Timer::del($timer); break; // delete timer
                        case "reset": $i = $data["count"]; break; // reset timer to original count
                        case "setCount": $i = $a["data"]["count"]; break; // set timer count to specified value
                        case "startNewCountdown": if (isset($a["data"]["count"])) $i = $a["data"]["count"]; $data= $a["data"]; // start new timer (note that interval must be same)
                        default: emitAction([$a],$io,$socket);
                    }
                }        
            }       
        });

        // handle timer id after timer is added
        global $serverLib;
        $handleActions = $serverLib->handleTimerId($data["timerName"],$timer,$socket->id);
        if ($handleActions != []) emitAction($handleActions,$io,$socket);
    }
    $socket->emit("timer",["action"=>"setCountdown", "data"=>["name"=>$data["timerName"], "status"=>"adding", "result"=>$result], "message"=>$data["message"], "error"=>$data["error"]]);
}

// delete timer
function deleteTimer($timerId){
    Timer::del($timerId);
}
?>