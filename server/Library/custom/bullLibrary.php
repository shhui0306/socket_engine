<?php 
namespace Library;

 class bullLibrary extends gameLibrary{   
    // Bull game for two players only but this library has the ability to serve more players to play
    // still need to update the client to support >= 3 players (e.g. animation and client display) 
    
    function startGame(&$roomInstance){
        $instructions = [];
        // assign points to game instance
        $roomInstance->game->initPoints($roomInstance->players);
        $bullInstance = $roomInstance->game;

        global $serverLib;
        // return game data to each player in room except robots
        foreach ($roomInstance->players as $id){
            $opponents = array_diff(array_keys($roomInstance->game->points),[$id]);
            if (!in_array($id,$roomInstance->robots)){
                $game = [
                    "result"=>true,
                    "gameMode"=>$bullInstance->mode,
                    "winPoints"=>$bullInstance->winPoints,
                    "me"=>$serverLib->players[$id]->name,
                    "opponents"=>$serverLib->playerLib->findNamesFromIdList($serverLib->players,$opponents)
                ];
                array_push($instructions,["action"=>"emitTo", "to"=>$id, "name"=>"game", "data"=>["action"=>"startGame","data"=>$game, "message"=>"", "error"=>""]]);
            }         
        }
        return $instructions;              
    }
    
    function checkWin($gameInstance,$socketId){
        foreach ($gameInstance->points as $id=>$points){
            if ($points >= $gameInstance->winPoints){
                // if player has points >= winpoints, player wins
                global $serverLib;
                $name = isset($serverLib->players[$id])?$serverLib->players[$id]->name:"Disconnected player";
                $playerIds = array_keys($gameInstance->points);
                $playerNames = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$playerIds);
                return ["result"=>true, "win"=>true, "draw"=>false, "winnerId"=>$id, "winnerName"=>$name,"playerIds"=>$playerIds, "playerNames"=>$playerNames];
            }else if ($points <= -$gameInstance->winPoints){
                
                // opponent win if player has points <= -winPoints
                global $serverLib;
                $winner = [];
                foreach($gameInstance->points as $winnerId=>$points){
                    if ($points > -$gameInstance->winPoints){
                        array_push($winner,$winnerId);
                    }
                }
                $name = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$winner,true);
                if (count($winner) == 1) $winner = $winner[0];
                if (count($name) == 1) $name = $name[0];

                $playerIds = array_keys($gameInstance->points);
                $playerNames = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$playerIds);
                return ["result"=>true, "win"=>true, "draw"=>false, "winnerId"=>$winner, "winnerName"=>$name,"playerIds"=>$playerIds, "playerNames"=>$playerNames];
            }
        }
        return ["result"=>false, "win"=>false, "draw"=>false, "winnerId"=>null, "winnerName"=>null,"playerIds"=>null, "playerNames"=>null];
    }
     
    function interactProceedingAction(&$roomInstance,$socketId,&$interactResult){
        $instructions = [];

        if ($interactResult["proceedGameAction"]){
            //  proceed success (e.g. correct answer), move bull forward
            $opponents = array_diff(array_keys($roomInstance->game->points),[$socketId]);
        
            $roomInstance->game->wrongFlag = false;
            foreach ($opponents as $opp){
                if ($roomInstance->game->points[$socketId] >= -$roomInstance->game->points[$opp]){
                    // if opponent bull is touching player bull, move opponent bull backward as well
                    $roomInstance->game->points[$opp] = -$roomInstance->game->points[$socketId]-1;
                } 
            }
            $roomInstance->game->points[$socketId]++; //move player bull forward                  
            
            // send update position and check win message
            $updatePositionInstructions = $this->updatePosition($roomInstance,$socketId);
            foreach ($updatePositionInstructions as $ins){
                array_push($instructions,$ins);
            }
    
            // get new trigger
            global $serverLib;
            $playerInfo = $serverLib->players[$socketId];
            $trigger = $roomInstance->interact->getTrigger($playerInfo);
            array_push($instructions,["action"=>"emit", "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);
        }else{
            // proceed fail (e.g. wrong answer), set wrong flag or cancel wrong flag if already set
            $instructions = [];
            if ($roomInstance->game->wrongFlag){
                $roomInstance->game->wrongFlag = false;
            }else{
                $roomInstance->game->wrongFlag = true;
                // start wrong timer
                $wrongTimer = $this->wrongTimer($roomInstance,$socketId);
                array_push($instructions,["action"=>"setCountdown","data"=>$wrongTimer]);
            }
            $interactResult["getTrigger"] = false;
        }
       
        return $instructions;
    }
    
    function doRobotAction($robotInstance,&$roomInstance){
        $instructions = [];
        $robotId = $robotInstance->socketId;
        $opponents = array_diff(array_keys($roomInstance->game->points),[$robotId]);
        // randomize correct or wrong answer for robot
        $rand = rand(0,100);
        if ($rand <= $roomInstance->game->robotCorrectProbability) $correct = true; else $correct = false;
    
        if ($correct){
            // expected result (e.g. correct answer), move bull forward
            $roomInstance->game->wrongFlag = false;
            foreach ($opponents as $opp){
                if ($roomInstance->game->points[$robotId] >= -$roomInstance->game->points[$opp]){
                    // if opponent bull is touching player bull, move opponent bull backward as well
                    $roomInstance->game->points[$opp] = -$roomInstance->game->points[$robotId]-1;
                } 
            }
            $roomInstance->game->points[$robotId]++; //move player bull forward    
            
            // send update position and check win message
            $updatePositionInstructions = $this->updatePosition($roomInstance,$robotId);
            foreach ($updatePositionInstructions as $ins){
                array_push($instructions,$ins);
            }
            
        }else{
            if ($roomInstance->game->wrongFlag){
                $roomInstance->game->wrongFlag = false;
            }else{
                $roomInstance->game->wrongFlag = true;
                $wrongTimer = $this->wrongTimer($roomInstance,$robotId);
                array_push($instructions,["action"=>"setCountdown","data"=>$wrongTimer]);
            }
        }

        return $instructions;
    }

    function endGameActions($checkWin,&$roomInstance){
        $messages = [];
        // emit end game messages
        $bullInstance = $roomInstance->game;

        $ids = array_keys($bullInstance->points);

        // return game data to each player in room except robots
        foreach ($ids as $i=>$id){
            if (!in_array($id,$roomInstance->robots)){
                $oppPoints = $roomInstance->game->points;
                unset($oppPoints[$id]);
                $game = [
                    "result"=>true,
                    "gameMode"=>$roomInstance->game->mode,
                    "winPoints"=>$roomInstance->game->winPoints,
                    "myPoints"=>$roomInstance->game->points[$id],
                    "oppPoints"=>$oppPoints
                ];
               $messages[$id] = ["roomName"=>$roomInstance->name, "gameData"=>$game];
            }         
        }

        // win / lose  message
        global $serverLib;

        // if game timeout, set game to draw
        if (isset($checkWin["timeout"])){
            if ($checkWin["timeout"]){
                $checkWin["win"] = false;
                $checkWin["draw"] = true;
            }
        }

        // emit win / lose / draw message
        if ($checkWin["win"]){
            // emit "you have won the game" for winner and "(player name) has won the game" for other players
            $winners = "";
            if (is_array($checkWin["winnerId"])){
                foreach($checkWin["winnerName"] as $i=>$name){
                    $winners .= $name;
                    if ($i < count($checkWin["winnerName"]-2)){
                        $winners .= ", ";
                    }else if ($i == count($checkWin["winnerName"]-2)){
                        $winners .= " and ";
                    }
                } 
            }

            foreach($roomInstance->players as $id){
                $messageData = $checkWin;
                $messageData["myId"] = $id;
                $messageData["myName"] = $serverLib->players[$id]->name;
                $messageData["opponentId"] = array_values(array_diff($checkWin["playerIds"],[$id]));
                $messageData["opponentName"] =  array_values(array_diff($checkWin["playerNames"],[$messageData["myName"]]));
                $messageData["mode"] = $roomInstance->robots==[]?"human":"robot";
                if (!in_array($id,$roomInstance->robots)){
                    if (is_array($checkWin["winnerId"])){
                        // if game has multiple winners
                        if (in_array($id,$checkWin["winnerId"])){
                            // emit you win message to winner
                            $messages[$id]["winLoseMessage"] =  ["action"=>"gameOver", "data"=>$messageData, "winner"=>"true", "message"=>"Congratulations! You have won the game.", "error"=>"" ];
                        }else{
                            // emit player has won message to other players
                            $messages[$id]["winLoseMessage"] = ["action"=>"gameOver", "data"=>$messageData, "winner"=>"false", "message"=>"Game over! ".$winners." ".count($checkWin["winnerName"])>1?"have":"has"." won the game.", "error"=>""];
                        }
                    }else if ($checkWin["winnerId"] == $id){
                        // emit you win message to winner
                        $messages[$id]["winLoseMessage"] =  ["action"=>"gameOver", "data"=>$messageData, "winner"=>"true", "message"=>"Congratulations! You have won the game.", "error"=>"" ];
                    }else{
                        // emit player has won message to other players
                        $messages[$id]["winLoseMessage"] = ["action"=>"gameOver", "data"=>$messageData, "winner"=>"false", "message"=>"Game over! ".$checkWin["winnerName"]." has won the game.", "error"=>""];
                    }
                }
            }
        } else if ($checkWin["draw"]){
            foreach($roomInstance->players as $id){
                if (!in_array($id,$roomInstance->robots)){
                    $messageData = $checkWin;
                    $messageData["myId"] = $id;
                    $messageData["myName"] = $serverLib->players[$id]->name;
                    $messageData["opponentId"] = array_values(array_diff($checkWin["playerIds"],[$id]));
                    $messageData["opponentName"] =  array_values(array_diff($checkWin["playerNames"],[$messageData["myName"]]));
                    $messageData["mode"] = $roomInstance->robots==[]?"human":"robot";
                    $messages[$id]["winLoseMessage"] = ["action"=>"gameOver", "data"=>$messageData, "winner"=>"draw", "message"=>"Game over! It's a draw.", "error"=>""];
                }
            }
        } 

        // change room status to 2 (game ended)
        $roomLib = new roomLibrary();
        $roomInstance = $roomLib->changeRoomStatus($roomInstance);

        // return emit message instructions
        $instructions = [];
        foreach ($messages as $id=>$msg){
            $keyLib = new keyLibrary();
            $msgStr = $keyLib->resultEnc(json_encode($msg));
            array_push($instructions,["action"=>"emitTo", "to"=>$id, "name"=>"game","data"=>["action"=>"gameOver", "data"=>$msgStr, "message"=>"", "error"=>""]]);
        }
        return $instructions;
    }

    private function updatePosition(&$roomInstance,$socketId){
        $instructions = [];
        // send update position message
        foreach ($roomInstance->players as $player){
            if (!in_array($player,$roomInstance->robots)){
                $opponentPoints = $roomInstance->game->points;
                unset($opponentPoints[$player]);
                array_push($instructions,["action"=>"emitTo", "to"=>$player, "name"=>"game","data"=>["action"=>"updatePosition", "data"=>["me"=>$roomInstance->game->points[$player], "opponents"=>$opponentPoints], "message"=>"", "error"=>"", "devError"=>""]]);
            }
        }

        // check win
        $checkWin = $this->checkWin($roomInstance->game,$socketId);
        if ($checkWin["win"] || $checkWin["draw"]){
            $endGameActions = $this->endGameActions($checkWin,$roomInstance);
            foreach($endGameActions as $a){
                array_push($instructions,$a);
            }
        }
        return $instructions;
    }

    private function wrongTimer(&$roomInstance,$socketId){
        return [
            "interval"=>0.01,
            "count"=>$roomInstance->game->wrongTimerTime,
            "timerName"=>"wrongTimer",
            "emitMode"=>"",
            "to"=>"",
            "intervalAction"=>function($i)use(&$roomInstance,$socketId){
                if (!isset($roomInstance)) return [];
                
                $instructions = [];
                if (!$roomInstance->game->wrongFlag){
                    // make self and opponent bounce off
                    $bounce = false;
                    $opponents = array_diff(array_keys($roomInstance->game->points),[$socketId]);
                    foreach($opponents as $opp){
                        if ($roomInstance->game->points[$socketId] >= -$roomInstance->game->points[$opp]){
                            $bounce = true;
                            $roomInstance->game->points[$opp]--;
                        }
                    }
                    if ($bounce){ $roomInstance->game->points[$socketId]--; }

                    //update position
                    $updatePositionInstructions = $this->updatePosition($roomInstance,$socketId);
                    foreach ($updatePositionInstructions as $ins){
                        array_push($instructions,$ins);
                    }

                    // delete timer
                    array_push($instructions,["action"=>"del"]);

                    // get new trigger
                    global $serverLib;
                    $playerInfo = $serverLib->players[$socketId];
                    $trigger = $roomInstance->interact->getTrigger($playerInfo);
                    array_push($instructions,["action"=>"emit", "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);

                }
                return $instructions;
            }, 
            "endAction"=>function()use(&$roomInstance,$socketId){
                if (!isset($roomInstance)) return [];

                $instructions = [];
                $roomInstance->game->wrongFlag = false;

                $opponents = array_diff(array_keys($roomInstance->game->points),[$socketId]);

                $touching = false;
                foreach ($opponents as $opp){
                    if ($roomInstance->game->points[$opp] <= -$roomInstance->game->points[$socketId] ){
                        // if opponent bull is touching player bull, move opponent bull backward as well
                        $roomInstance->game->points[$opp] = (-$roomInstance->game->points[$socketId])+1;
                        $touching = true;
                    } 
                }
                if ($touching) $roomInstance->game->points[$socketId]--; //move player bull backword   


                //update position
                $updatePositionInstructions = $this->updatePosition($roomInstance,$socketId);
                foreach ($updatePositionInstructions as $ins){
                    array_push($instructions,$ins);
                }

                // get new trigger
                global $serverLib;
                $playerInfo = $serverLib->players[$socketId];
                $trigger = $roomInstance->interact->getTrigger($playerInfo);
                array_push($instructions,["action"=>"emit", "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);

                return $instructions;
            },
        ];
    }
}