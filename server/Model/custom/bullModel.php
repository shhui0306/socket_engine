<?php 

namespace Model;

class bullModel extends gameModel{
    public $mode = "bull";
    public $points = [];
    public $winPoints = 10;
    public $wrongFlag = false; // whether a player has wrong answer or not
    public $robotCorrectProbability = 70; //percentage of robot having a correct answer (step bull forward)
    public $wrongTimerTime = 10; //time for wrong answer timer, bounce off if both players has wrong answer within this time. 1 = 0.01s

    function __construct($data){
        // game settings
        $this->mode = "bull";
        $this->minPlayers = isset($data["minPlayers"])?$data["minPlayers"]:2;
        $this->maxPlayers = isset($data["maxPlayers"])?$data["maxPlayers"]:2;
        $this->forceStartTime = isset($data["forceStartTime"])?$data["forceStartTime"]:40;
        $this->gameTime = isset($data["gameTime"])?$data["gameTime"]:180;

        // bull settings
        $this->winPoints = isset($data["winPoints"])?$data["winPoints"]:10;
        $this->robotCorrectProbability = isset($data["robotCorrectProbability"])?$data["robotCorrectProbability"]:70;
        $this->wrongTimerTime = isset($data["wrongTimerTime"])?$data["wrongTimerTime"]:10;
    }

    function initPoints($playerList){
        // initiate position for bulls
        foreach ($playerList as $player)
        $this->points[$player] = 0;
    }
}
