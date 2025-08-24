<?php 

namespace Model;


class roomModel{
    public $name;
    public $mode;
    public $groupId;
    public $players=[]; //players in game
    public $readyPlayers = [];  // players who are in ready start
    public $robots = []; //robots in game
    public $status=0; //0: created 1: game started 2: game ended
    public $game; // game instance e.g. tic tac toe
    public $interact; // interaction instance e.g. question / draw
    public $minRobotActionTime = 8;
    public $maxRobotActionTime = 15;
    public $timerId = "";

    function __construct($name, $mode, $game, $interact, $minRobotActionTime=8, $maxRobotActionTime=15, $groupId) {
        $this->name = $name;
        $this->mode = $mode;
        $this->groupId = $groupId;
        $this->game = $game;
        $this->interact = $interact;
        $this->minRobotActionTime = $minRobotActionTime;
        $this->maxRobotActionTime = $maxRobotActionTime;
    }
}