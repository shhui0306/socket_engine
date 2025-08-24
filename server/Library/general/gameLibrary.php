<?php 
namespace Library;

use Model\bullModel;
use Model\ticModel;

 class gameLibrary{    
    function startGame(&$roomInstance){return [];}
    function checkProgress($gameInstance,$socketId){}
    function checkWin($gameInstance,$socketId){}
    function interactProceedingAction(&$roomInstance,$socketId,&$interactResult){return [];}
    function switchGameAction($data,$socketId,&$roomInstance,$playerInfo){return [];}
    function doRobotAction($robotInstance,&$roomInstance){return [];}
    function endGameActions($checkWin, &$roomInstance){return [];}

    function createGameInstance($data){
        // create game instance according to game mode
        if (empty($data["mode"])) $data["mode"] = "";
        switch ($data["mode"]){
            case "tic": 
                return ["result"=>true, "game"=>new ticModel($data)];
            case "bull":
                return ["result"=>true, "game"=>new bullModel($data)];
            case "": return ["result"=>false, "error"=>"Game mode cannot be empty.", "devError"=>"Empty game mode"];
            default: return ["result"=>false, "error"=>"Invalid game mode.", "devError"=>"Invalid game mode ".$data["mode"]];
        }
    }

    // get game library according to game mode
    function getGameLib($game){
        $mode = isset($game->mode)?$game->mode:"";
        switch ($mode){
            // put game library here
            case "tic": return new ticLibrary();
            case "bull": return new bullLibrary();
            default: return ["action"=>"emit","name"=>"game","data"=>["action"=>"getGameLib", "data"=>["result"=>false],"message"=>"", "error"=>"Invalid game mode.", "devError"=>"Invalid game mode ".$mode]];
        }
    }
}