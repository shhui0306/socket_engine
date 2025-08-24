<?php 
namespace Library;

class timerLibrary{
    // countdown to force start game
    function forceStartCountdown($room,$socketId){
        $interval = 1; // in seconds
        $timerName = "forceStartCountdown";
        $emitMode = "emitTo";
        $to = $room;
        global $serverLib;
        if (isset($serverLib->rooms[$room]->game->forceStartTime)) $count = $serverLib->rooms[$room]->game->forceStartTime; //get force start time count
        else $count = 40; //default 30s if not found

        if (!empty($serverLib->rooms[$room]->timerId)){
            return [["action"=>"emitTo", "to"=>$room, "name"=>"timer", "data"=>["action"=>"setCountdown", "data"=>"", "message"=>"", "error"=>"[gameCountdown] timer already started"]]]; // return error message
        } 

        $intervalAction = function($i)use($room){
            global $serverLib;
            if (!isset($serverLib->rooms[$room])){
                //delete timer if room no longer exists
                return [["action"=>"del"],["action"=>"emit", "name"=>"timer", "data"=>["action"=>"setCountdown", "data"=>"", "message"=>"", "error"=>"[forceStartCountdown] room not exist, delete countdown"]]]; 
            } else if ($serverLib->roomLib->checkRoomStatus($serverLib->rooms[$room],"==",1)["result"]){
                // delete timer if game is already started.
                return [["action"=>"del"]];
            }
            else return [];
        };

        $endAction = function()use($room,$socketId){
            global $serverLib;
            if (!isset($serverLib->rooms[$room])){
                //delete timer if room no longer exists
                return [["action"=>"emit", "name"=>"timer", "data"=>["action"=>"setCountdown", "data"=>"", "message"=>"", "error"=>"[forceStartCountdown] room not exist, delete countdown"]],["action"=>"del"]]; 
            }else if ($serverLib->roomLib->checkRoomStatus($serverLib->rooms[$room],"==",1)["result"]){
                // delete timer if game is already started.
                return [["action"=>"del"]];
            }
            else return $serverLib->startGame($room,true,$socketId); // start game
            };
        return ["interval"=>$interval, "count"=>$count, "timerName"=>$timerName, "emitMode"=>$emitMode, "to"=>$to, "intervalAction"=>$intervalAction, "endAction"=>$endAction];
    }

    // gameplay time countdown
    function gameCountdown($room){
        $interval = 1; // in seconds
        $timerName = "gameCountdown";
        $emitMode = "emitTo";
        $to = $room;
        global $serverLib;
        if (isset($serverLib->rooms[$room]->game->gameTime)) $count = $serverLib->rooms[$room]->game->gameTime; //get game time count
        else $count = 180; //default 180s if not found
        $gameLib = $serverLib->gameLib->getGameLib($serverLib->rooms[$room]->game);
        $intervalAction = function($i)use($room,$gameLib){
            global $serverLib;
            if (!isset($serverLib->rooms[$room])) return [["action"=>"emitTo", "to"=>$room, "name"=>"timer", "data"=>["action"=>"setCountdown", "data"=>"", "message"=>"", "error"=>"[gameCountdown] room not exist, delete countdown"]],["action"=>"del"]]; //delete timer if room no longer exists
            else if ($serverLib->roomLib->checkRoomStatus($serverLib->rooms[$room],"==",2)["result"]){
                // delete timer if game already ended
                return [["action"=>"del"]];
            } 
            else {
                $actions = [];
                // do interact action if available
                $interactAction = $serverLib->rooms[$room]->interact->interactTimerAction($i, $serverLib->rooms[$room]);
                foreach ($interactAction as $r){
                    array_push($actions, $r);
                }

                // change robot time and do robot actions if available
                foreach($serverLib->rooms[$room]->robots as $r){
                    $robotAction = $serverLib->robotLib->robotIntervalAction($serverLib->players[$r],$serverLib->rooms[$room]);
                    if ($robotAction["doAction"]){
                        $robotAction = $gameLib->doRobotAction($serverLib->players[$r],$serverLib->rooms[$room]);
                        foreach($robotAction as $r){
                            array_push($actions,$r);
                        }
                    }
                }

                return $actions; // no action, just emit countdown
            }
        };
        $endAction = function()use($room,$gameLib){
            global $serverLib;
            if (!isset($serverLib->rooms[$room])) return [["action"=>"emitTo", "to"=>$room, "name"=>"timer", "data"=>["action"=>"setCountdown", "data"=>"", "message"=>"", "error"=>"[gameCountdown] room not exist, delete countdown"]]]; //delete timer if room no longer exists
            else if ($serverLib->roomLib->checkRoomStatus($serverLib->rooms[$room],"==",2)["result"]){
                // delete timer if game already ended
                return [["action"=>"del"]];
            } else {
                // end game, result draw by default (could be changed in game library endGameActions function by checking $checkWin["timeout"])
                $playerIds = $serverLib->rooms[$room]->players;
                $playerNames = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$playerIds);
                $endGameMessages = $gameLib->endGameActions(["result"=>true, "timeout"=>true, "win"=>false, "draw"=>true, "winnerId"=>null, "winnerName"=>null, "playerIds"=>$playerIds, "playerNames"=>$playerNames],$serverLib->rooms[$room]);
                return $endGameMessages;
            }
        };
        return ["interval"=>$interval, "count"=>$count, "timerName"=>$timerName, "emitMode"=>$emitMode, "to"=>$to, "intervalAction"=>$intervalAction, "endAction"=>$endAction];
    }

    function handleTimerId($timerName, $timerId,$socketId){
        switch ($timerName){
            case 'forceStartCountdown':
            case 'gameCountdown':
                // store timer id in room
                global $serverLib;
                $room = $serverLib->roomLib->findRoomBySocketId($serverLib->rooms,$socketId)["room"];
                if ($room != null){
                    $serverLib->rooms[$room]->timerId = $timerId;
                }
                return [];
            default: return [];
        }
    }
}