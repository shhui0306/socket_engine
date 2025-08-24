<?php 
namespace Library;

class ticLibrary extends gameLibrary{

    function startGame(&$roomInstance){
        $instructions = [];
        // assign symbols to game instance
        $roomInstance->game->assignSymbol($roomInstance->players);
        $ticInstance = $roomInstance->game;

        global $serverLib;
        $ids = array_keys($ticInstance->symbols);
        $symbols = array_values($ticInstance->symbols);
        $names = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$ids);
        $nameSymbols = array_map(null,$names,$symbols);

        // return game data to each player in room except robots
        foreach ($ids as $i=>$id){
            if (!in_array($id,$roomInstance->robots)){
                $mySymbol = $symbols[$i];
                $game = [
                    "result"=>true,
                    "gameMode"=>$ticInstance->mode,
                    "mySymbol"=>$mySymbol,
                    "symbols"=>$nameSymbols,
                    "size"=> $ticInstance->size,
                    "gamePad"=>$ticInstance->gamePad,
                    "lines"=>$ticInstance->lines,
                    "connectedPoints"=>$ticInstance->connectedPoints
                ];
                array_push($instructions,["action"=>"emitTo", "to"=>$id, "name"=>"game", "data"=>["action"=>"startGame","data"=>$game, "message"=>"", "error"=>""]]);
            }         
        }

        return $instructions;        
         
    }

    function checkWin($ticInstance,$socketId){
        // check win
        $lines = $ticInstance->lines;
        $pad = $ticInstance->gamePad;
        foreach($lines as $line){
            // check if line has same symbol
            $symbol = $pad[$line[0]];
            if ($symbol == "") continue;
            $checkWin = true;
            foreach ($line as $sq){
                if ($pad[$sq] != $symbol){
                    $checkWin = false;
                    break;
                }
            }
            // return win message
            if ($checkWin){
                $socketId = array_search($symbol,$ticInstance->symbols);
                global $serverLib;
                $name = "Opponent";
                if (isset($serverLib->players[$socketId])){
                    $name = $serverLib->players[$socketId]->name;
                }else{
                    $name = "Disconnected player";
                }
                $playerIds = array_keys($ticInstance->symbols);
                $playerNames = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$playerIds);
                return ["win"=>true, "draw"=>false, "line"=>$line, "winnerId"=>$socketId, "winnerName"=>$name, "playerIds"=>$playerIds, "playerNames"=>$playerNames];
            }
        }

        // check if all squares are already filled
        if (!in_array("",$pad)){
            global $serverLib;
            $playerIds = array_keys($ticInstance->symbols);
            $playerNames = $serverLib->playerLib->findNamesFromIdList($serverLib->players,$playerIds);
            return ["win"=>false, "draw"=>true, "line"=>[], "winnerId"=>null, "winnerName"=>null, "playerIds"=>$playerIds, "playerNames"=>$playerNames];
        }

        // no winner yet, return message
        return ["win"=>false, "draw"=>false, "line"=>[], "winnerId"=>null, "winnerName"=>null, "playerIds"=>[], "playerNames"=>[]];
    }

    function interactProceedingAction(&$roomInstance,$socketId,&$interactResult){
        $action = [];

        if ($interactResult["proceedGameAction"]){
            // if proceed success (e.g. correct answer)
            // add select square message
            array_push($action,["action"=>"emit","name"=>"interact","data"=>["action"=>"waitInteractMessage", "data"=>"", "message"=>"Please select square in game board.", "error"=>"", "devError"=>""]]);

            // make squares clickable
            array_push($action,["action"=>"emit","name"=>"game","data"=>["action"=>"makeClickable", "data"=>["clickable"=>true], "message"=>"", "error"=>"", "devError"=>""]]);
        }
        return $action;
    }


    function sendClick($data,$socketId,&$roomInstance, $playerInfo){ 
        // place symbol to game pad, check if valid, then check win and return message
        $instructions = [];
        $haveToken = false;
        if (isset($roomInstance->interact->interactToken)){
            $haveToken = true;
            // return error if token not exist in data
            if (!isset($data["token"])){
                return [["aciton"=>"emit","name"=>"game","data"=>["action"=>"sendClick","data"=>["result"=>false], "message"=>"", "error"=>"Invalid action.", "devError"=>"No token in data"]]];
            }
            else if (!in_array($data["token"],$roomInstance->interact->interactToken)){
                return [["aciton"=>"emit","name"=>"game","data"=>["action"=>"sendClick","data"=>["result"=>false], "message"=>"", "error"=>"Invalid action.", "devError"=>"Invalid interact token ".$data["token"]]]];
            }
        }

        // check if position is valid, (sqno: square number / nth square )
        if (!isset($data["sqno"])){
            return [["aciton"=>"emit","name"=>"game","data"=>["action"=>"sendClick","data"=>["result"=>false], "message"=>"", "error"=>"Invalid action.", "devError"=>"No square number"]]];
        } else if (!isset($roomInstance->game->gamePad[$data["sqno"]])){
            return [["aciton"=>"emit","name"=>"game","data"=>["action"=>"sendClick","data"=>["result"=>false], "message"=>"", "error"=>"Invalid action.", "devError"=>"Invalid square number ".$data["sqno"]]]];
        } else{
            // valid position, update gamepad and emit update message
            $symbol =  $roomInstance->game->symbols[$socketId];
            $roomInstance->game->gamePad[$data["sqno"]] = $symbol;
            array_push($instructions,["action"=>"emitTo", "to"=>$roomInstance->name, "name"=>"game","data"=>["action"=>"gameBoardUpdate","data"=>["gamePad"=>$roomInstance->game->gamePad], "message"=>"", "error"=>"", "devError"=>""]]);
        }

        // turn off game board clickable
        array_push($instructions,["action"=>"emit","name"=>"game","data"=>["action"=>"makeClickable","data"=>["clickable"=>false], "message"=>"", "error"=>"", "devError"=>""]]);

        // check if any winners
        $newTrigger = false;
        $checkWin = $this->checkWin($roomInstance->game,$socketId);
        if ($checkWin["win"] || $checkWin["draw"]){
            $endGameActions = $this->endGameActions($checkWin,$roomInstance);
            foreach($endGameActions as $a){
                array_push($instructions,$a);
            }
        }else{
            $newTrigger = true;
        }
        
        // remove token if new trigger is needed
        if ($haveToken && $newTrigger){
            if (in_array($data["token"],$roomInstance->interact->interactToken)){
                // exist, remove token
                unset($roomInstance->interact->interactToken[array_search($data["token"],$roomInstance->interact->interactToken)]);
            }
        }

        // get new trigger
        if ($newTrigger){
            $trigger = $roomInstance->interact->getTrigger($playerInfo);
            array_push($instructions,["action"=>"emit", "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$trigger, "message"=>"", "error"=>""]]);
        }
        return $instructions;
    }

    function doRobotAction($robotInstance,&$roomInstance){
        // do robot action for robot
        $instructions = [];
        
        $robotId = $robotInstance->socketId;
        $lines = $roomInstance->game->lines;
        $pad = $roomInstance->game->gamePad;
        $connectedPoints = $roomInstance->game->connectedPoints;
        
        $putSymbol =  $roomInstance->game->symbols[$robotId];
        $symbolHasPut = false;
        
        // if there are connectedPoints-1 same symbols in same line, put symbol
        foreach ($lines as $line){
            $findSymbol = "";
            $pointCount = 0;
            $missingPoint = null;
            foreach ($line as $i=>$sq){
                if ($findSymbol == ""){
                    if ($pad[$sq] != ""){
                        $findSymbol = $pad[$sq];
                        $pointCount = 1;
                    }else if ($i >= 2){
                        continue;
                    }else{
                        $missingPoint = $sq;
                    }
                }else if ($pad[$sq] == $findSymbol){
                    $pointCount++;
                }else if ($pad[$sq] == ""){
                    $missingPoint = $sq;
                }
            }
            if ($pointCount == $connectedPoints-1 && $missingPoint != null){
                $roomInstance->game->gamePad[$missingPoint] = $putSymbol;
                $symbolHasPut = true;
                break;
            }
        }        
        // if not, put symbol in random position
        if (!$symbolHasPut){
            $availableSquares = [];
            foreach ($pad as $i=>$q){if ($q == "") array_push($availableSquares,$i);}
            if (count($availableSquares) >= 1){
                $putPos = $availableSquares[array_rand($availableSquares)];
                $roomInstance->game->gamePad[$putPos] = $putSymbol;
                //echo "square put in ".$putPos."\n";
                $symbolHasPut = true;
            }
        }

        // emit pad update message if symbol has put
        if ($symbolHasPut){
            array_push($instructions,["action"=>"emitTo", "to"=>$roomInstance->name, "name"=>"game","data"=>["action"=>"gameBoardUpdate","data"=>["gamePad"=>$roomInstance->game->gamePad], "message"=>"", "error"=>"", "devError"=>""]]);
        }

        // check if win or not
        $checkWin = $this->checkWin($roomInstance->game,$robotInstance->socketId);
        if ($checkWin["win"] || $checkWin["draw"]){
            $endGameActions = $this->endGameActions($checkWin,$roomInstance);
            foreach($endGameActions as $a){
                array_push($instructions,$a);
            }
        }
        return $instructions;
    }

    function endGameActions($checkWin,&$roomInstance){
        $messages = [];
        // emit end game messages
        $ticInstance = $roomInstance->game;

        $ids = array_keys($ticInstance->symbols);

        // return game data to each player in room except robots
        foreach ($ids as $i=>$id){
            if (!in_array($id,$roomInstance->robots)){
                $game = [
                    "result"=>true,
                    "gameMode"=>$ticInstance->mode,
                    "size"=> $ticInstance->size,
                    "gamePad"=>$ticInstance->gamePad,
                    "lines"=>$ticInstance->lines,
                    "connectedPoints"=>$ticInstance->connectedPoints
                ];
               $messages[$id] = ["roomName"=>$roomInstance->name, "gameData"=>$game];
            }         
        }

        // if game timeout, set game to draw
        if (isset($checkWin["timeout"])){
            if ($checkWin["timeout"]){
                $checkWin["win"] = false;
                $checkWin["draw"] = true;
            }
        }

        // win / lose  message
        global $serverLib;
        if ($checkWin["win"]){
            // emit "you have won the game" for winner and "(player name) has won the game" for other players
            foreach($roomInstance->players as $id){
                if (!in_array($id,$roomInstance->robots)){
                    $messageData = $checkWin;
                    $messageData["myId"] = $id;
                    $messageData["myName"] = $serverLib->players[$id]->name;
                    $messageData["opponentId"] = array_diff($checkWin["playerIds"],[$id]);
                    $messageData["opponentName"] =  array_diff($checkWin["playerNames"],[$messageData["myName"]]);
                    $messageData["mode"] = $roomInstance->robots==[]?"human":"robot";
                    if ($checkWin["winnerId"] == $id){
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
                    $messageData["opponentId"] = array_diff($checkWin["playerIds"],[$id]);
                    $messageData["opponentName"] =  array_diff($checkWin["playerNames"],[$messageData["myName"]]);
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

    // switch tic-tac-toe actions
    function switchGameAction($token,$socketId,&$roomInstance,$playerInfo){
        $instructions = [];
        switch($token["action"]){
            case "sendClick": $instructions = $this->sendClick($token["data"],$socketId,$roomInstance, $playerInfo); break;
            default: $instructions = [["action"=>"emit","name"=>"game","data"=>["action"=>"swithcGameAction", "data"=>"","message"=>"", "error"=>"Invalid action.", "devError"=>"[ticLibrary] invalid action: ".$token["action"]]]];
        }
        return $instructions;
    }
}