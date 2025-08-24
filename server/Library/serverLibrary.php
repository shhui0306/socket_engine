<?php 
namespace Library;

use Model\playerModel;
use Model\roomModel;
use Model\ticModel;

class serverLibrary{
/* 
    Contents: 
    1. instance variables
    2. constructor
    3. initiation function
    4. room functions
    5. game functions
    6. interact functions
    7. chat functions
    8. disconnect function
    9. private functions
    . message functions
*/

    // --- instance variables ---
    public $serverProperty = 'tesing object properties';
    public $roomLib;
    public $playerLib;
    public $robotLib;
    public $keyLib;
    public $timerLib;
    public $gameLib;
    public $chatLib;
    public $rooms=[];
    public $players=[];

    // --- constructor ---
    function __construct() {
        $this->roomLib = new roomLibrary();
        $this->playerLib = new playerLibrary();
        $this->robotLib = new robotLibrary();
        $this->interactLib = new interactLibrary();
        $this->keyLib = new keyLibrary();
        $this->gameLib = new gameLibrary();
        $this->timerLib = new timerLibrary();
        $this->chatLib = new chatLibrary();
    }


    // --- initiation function ---
    function init($token,$socketId){
        $data = $this->validData($token);
        $checkData = $this->checkDataFields($data,["name"],[true]);
        $checkPlayer = $this->playerLib->checkDuplicatePlayer($this->players,$socketId);
        //error_log("test", 3, "./error.log"); //log test
        if ($checkData["result"] && $checkPlayer["result"]){
            $this->players[$socketId] = new playerModel($data, $socketId);
            $result = ["result"=>true, "name"=>$data["name"], "socketId"=>$socketId];
        }else $result = ["result"=>false];
        return [["action"=>"emit", "name"=>"init", "data"=>["action"=>"init", "data"=>$result, "message"=>$this->message([$checkData,$checkPlayer]) , "error"=>$this->error([$checkData,$checkPlayer]), "devError"=>$this->devError([$checkData,$checkPlayer])]]];
    }


    // --- room functions ---
    //add room, join room also if success
    function addRoom($token,$socketId){
        $roomAdded = false;
        $data = $this->validData($token);

        $checkData = $this->checkDataFields($data,["roomName","mode","groupId"],[true]);
        $checkPlayer = $this->playerLib->checkPlayerStatus($this->players,$socketId,"==",0);
        $checkRoom = $this->roomLib->checkAddRoomCondition($this->rooms,$data);
        $addGame = [];
        $addInteract = [];
        // add room if data is complete and player status is 0
        if ($checkData["result"] && $checkPlayer["result"] && $checkRoom["result"]){
            $addGame = $this->gameLib->createGameInstance($data);
            $addInteract = $this->interactLib->createInteractInstance($data);

            // check if game and interact has created
            if ($addGame["result"] && $addInteract["result"]){
                $minRobotActionTime = isset($data["minRobotActionTime"])?$data["minRobotActionTime"]:8;
                $maxRobotActionTime = isset($data["maxRobotActionTime"])?$data["maxRobotActionTime"]:15;

                // check if trigger count is able to start game
                $checkTrigger = $addInteract["interact"]->getTriggerCount($this->players[$socketId]);
                if ($checkTrigger["result"]){
                    // create room
                    $this->rooms[$data["roomName"]] = new roomModel($data["roomName"],$data['mode'],$addGame["game"],$addInteract["interact"],$minRobotActionTime,$maxRobotActionTime,$data["groupId"]);
                    $roomAdded = true; 
                }else{
                    // return error message
                    return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"addRoom", "data"=>["result"=>false, "name"=>$data["roomName"], "mode"=>$data["mode"], "count"=>$checkTrigger["data"]], "message"=>"", "error"=>$checkTrigger["error"]]]];
                }                               
            }
        }
        $instructions = [];
        if ($roomAdded){
            // emit success message
            $result = ["result"=>true, "roomName"=>$data["roomName"], "mode"=>$data["mode"]];
            array_push($instructions,["action"=>"emit", "name"=>"room", "data"=>["action"=>"addRoom", "data"=>$result, "message"=>$this->message([$checkData,$checkPlayer,$checkRoom]), "error"=>$this->error([$checkData,$checkPlayer,$checkRoom])]]);
            
            // join room immedietely
            array_push($this->rooms[$data["roomName"]]->players,$socketId); // add player to player list in room
            $this->players[$socketId] = $this->playerLib->changePlayerStatus($this->players[$socketId],$this->rooms); //promote player status
            $checkPromote = $this->playerLib->checkPlayerStatus($this->players,$socketId,"==",1);
            if ($checkPromote["result"]){
                array_push($instructions,["action"=>"join", "name"=>$data["roomName"]]);
                array_push($instructions, ["action"=>"emit", "name"=>"room", "data"=>["action"=>"newPlayer", "data"=>["name"=>$this->players[$socketId]->name],  "message"=>"", "error"=>""]]);
                
                // start timer
                array_push($instructions,["action"=>"setCountdown", "data"=>$this->timerLib->forceStartCountdown($data["roomName"],$socketId)]);

            }else{
                [["action"=>"emit", "name"=>"room", "data"=>["action"=>"addRoom", "data"=>["result"=>false, "name"=>$data["roomName"], "mode"=>$data["mode"]], "message"=>$this->message([$checkPromote]), "error"=>$this->error([$checkPromote])]]];
            }
        }else{
            // emit fail message
            array_push($instructions,["action"=>"emit", "name"=>"room", "data"=>["action"=>"addRoom", "data"=>["result"=>false, "name"=>$data["roomName"], "mode"=>$data["mode"]], "message"=>$this->message([$checkData,$checkPlayer,$checkRoom,$addGame,$addInteract]), "error"=>$this->error([$checkData,$checkPlayer,$checkRoom,$addGame,$addInteract]), "devError"=>$this->error([$checkData,$checkPlayer,$checkRoom,$addGame,$addInteract])]]);
        }
        return $instructions;;
    }


    //get room list
    function getRoom($token){
        // initialize global room list if not set
        $data = $this->validData($token);
        $roomList = $this->roomLib->searchRoom($this->rooms,$data);        
        return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoom", "data"=>["list"=>$roomList], "message"=>"", "error"=>""]]];
    }

    //join room
    function joinRoom($token, $socketId){
        $data = $this->validData($token);

        // check room name is in data and player status is 0
        $checkData = $this->checkDataFields($data,["roomName"],[true]);
        $checkPlayer = $this->playerLib->checkPlayerStatus($this->players,$socketId,"==",0);
        $checkJoin = $this->roomLib->checkJoinRoomCondition($this->rooms, $data, $socketId);
        if ($checkData["result"] && $checkPlayer["result"] && $checkJoin["result"]){

            // check if player group id matches room, if not, return error message
            if (!empty($data["groupId"])){
                 if ($data["groupId"] != $this->rooms[$data["roomName"]]->groupId){
                    return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>["result"=>false, "name"=>$data["roomName"]], "message"=>"Invalid player group.", "error"=>"Invalid player group.", "devError"=>"Invalid player group ".$data["groupId"]]]]; 
                 }
            }

            // check if trigger count is able to start game
            $checkTrigger = $this->rooms[$data["roomName"]]->interact->getTriggerCount($this->players[$socketId]);
            if (!$checkTrigger["result"]){
                // return error message
                return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>["result"=>false, "name"=>$data["roomName"], "mode"=>"", "count"=>$checkTrigger["data"]], "message"=>"", "error"=>$checkTrigger["error"]]]];
            }

            // add player to room
            array_push($this->rooms[$data["roomName"]]->players,$socketId); // add player to player list in room
            $this->players[$socketId] = $this->playerLib->changePlayerStatus($this->players[$socketId],$this->rooms); //promote player status
            $checkPromote = $this->playerLib->checkPlayerStatus($this->players,$socketId,"==",1);
            if ($checkPromote["result"]){
                $message =  [
                    ["action"=>"join", "name"=>$data["roomName"]],
                    ["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>$checkJoin, "message"=>"", "error"=>""]],
                    ["action"=>"emitTo", "to"=>$data["roomName"], "name"=>"room", "data"=>["action"=>"newPlayer", "data"=>["name"=>$this->players[$socketId]->name],  "message"=>"", "error"=>""]],
                ]; // join room, emit join success, emit new player message to room
                if (count($this->rooms[$data["roomName"]]->players) >= $this->rooms[$data["roomName"]]->game->minPlayers){
                    // show start button if room playersreached minPlayers
                    array_push($message,["action"=>"emitTo", "to"=>$data["roomName"], "name"=>"room", "data"=>["action"=>"showReadyStart", "data"=>["result"=>true], "message"=>"", "error"=>""]]);
                }
                return $message;
            } else {
                return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>["result"=>false, "name"=>$data["roomName"]], "message"=>$this->message([$checkPromote]), "error"=>$this->error([$checkPromote])]]];
            }
        }
        else return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>["result"=>false, "name"=>$data["roomName"], "mode"=>""], "message"=>$this->message([$checkData,$checkPlayer,$checkJoin]), "error"=>$this->error([$checkData,$checkPlayer,$checkJoin])]]];
    }

    //get list of players in room
    function getRoomPlayerList($socketId){
        $rname = $this->roomLib->findRoomBySocketId($this->rooms,$socketId);
        if ($rname["result"] !== false){
            $room = $rname["room"];
            $ids = $this->rooms[$room]->players;
            $names = $this->playerLib->findNamesFromIdList($this->players, $ids);
            $ready = [];
            foreach ($ids as $id){
                array_push($ready,in_array($id,$this->rooms[$room]->readyPlayers));
            }
            return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoomPlayerList", "data"=>["result"=>true, "roomName"=>$room, "playerList"=>$names, "ready"=>$ready, "count"=>count($ids)], "message"=>"", "error"=>""]]];
        } else {
            return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoomPlayerList", "data"=>["result"=>false, "roomName"=>null, "playerList"=>[], "ready"=>[], "count"=>0],"message"=>"", "error"=>$this->error([$rname])]]];
        }
    }

    function playerReadyStart($token, $socketId){
        // player is ready to start game
        $data = $this->validData($token);
        // check room name is in data and player status is 0
        $checkData = $this->checkDataFields($data,["roomName"],[true]);
        $checkPlayer = $this->playerLib->checkPlayerStatus($this->players,$socketId,"==",1);
        $checkRoom = $this->roomLib->findRoomBySocketId($this->rooms, $socketId);
        if ($checkData["result"] && $checkPlayer["result"] && $data["roomName"] == $checkRoom["room"]){
            $room = $data["roomName"];
            $message = [];
            if (!in_array($socketId, $this->rooms[$room]->readyPlayers)){
                array_push($this->rooms[$room]->readyPlayers,$socketId); // add player to ready player list in room
                 // emit player ready message to all players in room
                 array_push($message,["action"=>"emitTo", "to"=>$room, "name"=>"room", "data"=>["action"=>"playerReadyStart", "data"=>["result"=>true, "room"=>$room, "name"=>$this->players[$socketId]->name], "message"=>"", "error"=>""]]);
                 if (count($this->rooms[$room]->players) >= $this->rooms[$room]->game->minPlayers  && array_diff($this->rooms[$room]->players,$this->rooms[$room]->readyPlayers) == []){
                     // start game if all players ready
                     $start = $this->startGame($room,false, $socketId);
                     foreach ($start as $s){
                        array_push($message,$s);
                     }
                }
            }else{
                // player already in ready player list, emit error
                array_push($message,["action"=>"emit", "name"=>"room", "data"=>["action"=>"playerReadyStart", "data"=>["result"=>false, "room"=>$room, "name"=>$this->players[$socketId]->name], "message"=>"", "error"=>"Player already pressed ready.", "devError"=>"[playerReadyStart] Player already in readyPlayer array."]]);
            }
            return $message;
        }
        else return [["action"=>"emit", "name"=>"room", "data"=>["action"=>"playerReadyStart", "data"=>["result"=>false, "room"=>$data["roomName"]], "message"=>$this->message([$checkData,$checkPlayer, $checkRoom]), "error"=>$this->error([$checkData,$checkPlayer, $checkRoom]), "devError"=>$this->devError([$checkData,$checkPlayer, $checkRoom])]]];
    }

    // --- game functions ---
    function startGame($room,$startNewCountdown=false, $socketId){
        // start game, load game board and interact
        $instructions = [];

        // promote game status to 1
        $this->rooms[$room] = $this->roomLib->changeRoomStatus($this->rooms[$room]);
        $checkPromote = $this->roomLib->checkRoomStatus($this->rooms[$room],"==",1);
        if ($checkPromote["result"]){
            // add hide start button message if success
            array_push($instructions,["action"=>"emitTo", "to"=>$room, "name"=>"room", "data"=>["action"=>"showReadyStart", "data"=>["result"=>false], "message"=>"", "error"=>""]]);
        }else{
            // emit start game fail message if fail
            return [["action"=>"emitTo", "to"=>$room,"name"=>"game","data"=>["action"=>"startGame", "data"=>["result"=>false, "roomName"=>$room], "message"=>$this->message([$checkPromote]),"error"=>$this->error([$checkPromote])]]];
        }

        // add robot players if needed
        if (count($this->rooms[$room]->players) < $this->rooms[$room]->game->minPlayers){
            for ($i=0; $i<=($this->rooms[$room]->game->minPlayers - count($this->rooms[$room]->players)); $i++){
                // add robot player
                $this->rooms[$room]->mode = "robot"; //set room to robot mode
                $robotId = "robot_".$room."_".$i; //create robot id (robot_roomname_i)
                $this->players[$robotId] = $this->robotLib->createRobotPlayer($robotId,$this->rooms[$room]); // create robot player
                array_push($this->rooms[$room]->players,$robotId); //add robot player in player list
                array_push($this->rooms[$room]->robots, $robotId); //add robot player in robot list
            }
            // notice players to update player list
            array_push($instructions,["action"=>"emitTo", "to"=>$room, "name"=>"room", "data"=>["action"=>"newPlayer", "data"=>["name"=>"robot"],  "message"=>"", "error"=>""]]);
        }    

        // game 
        //get game board
        $gameLib = $this->gameLib->getGameLib($this->rooms[$room]->game);
        if (is_array($gameLib)){
            if (isset($gameLib["action"])){
                // add fail message
                array_push($instructions,$gameLib);
            }
        }else{
            // add start game
            $startGameMessage = $gameLib->startGame($this->rooms[$room]);
            foreach($startGameMessage as $msg){
                array_push($instructions,$msg);
            }
        } 
        
        //get trigger
        foreach($this->rooms[$room]->players as $player){
            if (!in_array($player,$this->rooms[$room]->robots)){
                $trigger = $this->rooms[$room]->interact->getTrigger($this->players[$player]);
                array_push($instructions,["action"=>"emitTo","to"=>$player, "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);
            }
        }        

        //start game countdown
        if ($startNewCountdown){
            array_push($instructions,["action"=>"startNewCountdown","data"=>$this->timerLib->gameCountdown($room)]);
        }else{
            array_push($instructions,["action"=>"setCountdown","data"=>$this->timerLib->gameCountdown($room)]);
        }
        return $instructions;
    }

    // switch game library actions
    function switchGameAction($token,$socketId){
        $room = $this->roomLib->findRoomBySocketId($this->rooms,$socketId)["room"];
        $instructions = [];
        $gameLib = $this->gameLib->getGameLib($this->rooms[$room]->game);
        if (is_array($gameLib)){
            if (isset($gameLib["action"])){
                // add fail message
                array_push($instructions,$gameLib);
            }
        }else{
            $instructions = $gameLib->switchGameAction($token,$socketId,$this->rooms[$room],$this->players[$socketId]);
        }
        return $instructions;
    }

    // --- interact functions ---
    function manageInteractResult($token,$socketId){
        $room = $this->roomLib->findRoomBySocketId($this->rooms,$socketId)["room"];
        // get result from interact
        $data = $this->validData($token);
        $result = $this->rooms[$room]->interact->manageInteractResult($this->players[$socketId],$data);
        $instructions = [["action"=>"emit","name"=>"interact","data"=>["action"=>"getResult", "data"=>$result["data"], "message"=>"", "error"=>"", "devError"=>""]]];
        
        // ignore game action if needed, else do game action
        $doGameAction = true;
        if (isset($result["ignoreGameAction"])){
            if ($result["ignoreGameAction"]){
                $doGameAction = false;
            }
        }

        // game actions: get proceeding game action
        if ($doGameAction){
            $gameLib = $this->gameLib->getGameLib($this->rooms[$room]->game);
            $actions = $gameLib->interactProceedingAction($this->rooms[$room],$socketId,$result);
                foreach($actions as $a){
                    array_push($instructions,$a);
                }
        }
        
        // get new trigger if needed
        if ($result["getTrigger"]){
            $trigger = $this->rooms[$room]->interact->getTrigger($this->players[$socketId]);
            array_push($instructions,["action"=>"emit", "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);
        }

        return $instructions;
    }

    // --- chat function ---
    function sendChat($token,$socketId){
        $room = $this->roomLib->findRoomBySocketId($this->rooms,$socketId)["room"];
        $data = $this->validData($token);
        $sender = $this->players[$socketId]->name;
        $message = $data["message"];

        $instructions = $this->chatLib->sendChat($room,$sender,$message);
        return $instructions;
    }

    // --- timer function ---
    function handleTimerId($timerName, $timerId,$socketId){
        $actions = $this->timerLib->handleTimerId($timerName,$timerId,$socketId);
        return $actions;
    }

    
    // --- disconnect function ---
    // preforms disconnect actions
    function disconnect($socketId){
        $actions = [];
        // leave room if player has joined
        $rname = $this->roomLib->findRoomBySocketId($this->rooms,$socketId);
        if ($rname["result"]){
            // remove player from room
            $room = $rname["room"];
            $this->rooms[$room]->players = array_diff($this->rooms[$room]->players,[$socketId]);
            $this->rooms[$room]->readyPlayers = array_diff($this->rooms[$room]->readyPlayers,[$socketId]);
            array_push($actions,["action"=>"emitTo", "to"=>$room, "name"=>"room", "data"=>["action"=>"playerLeave", "data"=>["name"=>$this->players[$socketId]->name], "message"=>"", "error"=>""]]);
            
            // remove robots from room if no more human players
            if (count($this->rooms[$room]->players) - count($this->rooms[$room]->robots) <= 0){
                foreach($this->rooms[$room]->robots as $r){
                    if (isset($this->players[$r])) unset($this->players[$r]);
                    $this->rooms[$room]->players = array_diff($this->rooms[$room]->players,[$r]);
                    $this->rooms[$room]->robots = array_diff($this->rooms[$room]->robots,[$r]);
                }
            }

            // remove room if no users
            if (count($this->rooms[$room]->players) == 0){
                $timerId = $this->rooms[$room]->timerId; 
                unset($this->rooms[$room]); //unset room
                if (!empty($timerId)){
                    array_push($actions,["action"=>"deleteTimer", "name"=>$timerId]); // delete timer
                }
            } 
            
        }
        // remove player from player list
        unset($this->players[$socketId]);
        return $actions;
    }


    // --- private functions ---
    // check if token data is valid, take data out from token
    private function validData($token=[]){
        if (isset($token["data"])){
            return $token["data"];
        }
        else {
            return [];
        }
    }

    // check if field exist in data
    // also could check if that field is empty
    private function checkDataFields($data=[],$fields=[], $checkEmpty=[]){
        $check = true;
        $error = "";
        foreach ($fields as $i=>$f){
            $checkField = true;
            if (!isset($data[$f])){
                $check = false;
                $checkField = false;
            } else if (isset($checkEmpty[$i])){
                if ($checkEmpty[$i]){
                    if ($data[$f] == ""){
                        $check = false;
                        $checkField = false;
                    }
                }
            }
            if (!$checkField){
                if ($error == ""){
                    $error = "checkDataFields - empty:";
                }
                $error.= $f." ";
            }
        }
        return ["result"=>$check,"error"=>$error];
    }

    
    
    // --- message functions ---

    // get message from each token
    private function message($tokens){
        $message = "";
        foreach ($tokens as $i=>$t){
            if (isset($t["message"])){
                if ($t["message"] != "")
                $message.=$t["message"]." ";
            }
        }
        return $message;
    }

    // get error from each token
    private function error($tokens){
        $error = "";
        foreach ($tokens as $i=>$t){
            if (isset($t["error"])){
                if ($t["error"] != "")
                $error.=$t["error"]." ";
            }
        }
        return $error;
    }

    // get dev error from each token
    private function devError($tokens){
        $error = "";
        foreach ($tokens as $i=>$t){
            if (isset($t["devError"])){
                if ($t["devError"] != "")
                $error.=$t["devError"]." ";
            }
        }
        return $error;
    }    
}