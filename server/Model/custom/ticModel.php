<?php 

namespace Model;

class ticModel extends gameModel{
    public $size;
    public $connectedPoints;
    public $symbols = [];
    public $gamePad = [];



    function __construct($data){
        // game settings
        $this->mode = "tic";
        $this->minPlayers = isset($data["minPlayers"])?$data["minPlayers"]:2;
        $this->maxPlayers = isset($data["maxPlayers"])?$data["maxPlayers"]:2;
        $this->forceStartTime = isset($data["forceStartTime"])?$data["forceStartTime"]:40;
        $this->gameTime = isset($data["gameTime"])?$data["gameTime"]:180;

        // tic settings
        $this->size = isset($data["size"])?$data["size"]:3;
        $this->connectedPoints = isset($data["connectedPoints"])?$data["connectedPoints"]:3;  
        $this->gamePad = array_pad([],$this->size*$this->size,"");
        $this->findLines(); // find possible lines to win
        //symbol will be set when start game
    }

    function assignSymbol($playerList){
        $symbols = ["○","×","△","□"];
        $playerSymbol = [];
        shuffle($playerList);
        foreach($playerList as $i=>$id){
            $playerSymbol[$id] = $symbols[$i];
        }
        $this->symbols = $playerSymbol;
    }

    function findLines(){
        $cp = $this->connectedPoints; // connected points to win
        $sz = $this->size; // size
        $lines = [];

        // find possible horizontal lines
        for($y=0; $y<$sz; $y++){
            for($x=0; $x<$sz; $x++){
                if ($x+$cp <= $sz){
                    $line = [];
                    for ($i=0; $i<$cp; $i++){
                        array_push($line,($y*$sz +$x + $i));
                    }
                    array_push($lines,$line);
                }
            }
        }

        // find possible vertical lines
        for($x=0; $x<$sz; $x++){
            for($y=0; $y<$sz; $y++){
                if ($y+$cp <= $sz){
                    $line = [];
                    for ($i=0; $i<$cp; $i++){
                        array_push($line,($y*$sz +$x + $i*$sz ));
                    }
                    array_push($lines,$line);
                }
            }
        }

        // find possible diagonal lines (top-left to bottom-right)
        for($x=0; $x<$sz; $x++){
            for($y=0; $y<$sz; $y++){
                if ($y+$cp <= $sz && $x+$cp <= $sz){
                    $line = [];
                    for ($i=0; $i<$cp; $i++){
                        array_push($line,($y*$sz +$x + $i*$sz + $i ));
                    }
                    array_push($lines,$line);
                }
            }
        }

        // find possible diagonal lines (top-right to bottom-left)
        for($x=0; $x<$sz; $x++){
            for($y=0; $y<$sz; $y++){
                if ($y+$cp <= $sz && $x-$cp+1 >=0){
                    $line = [];
                    for ($i=0; $i<$cp; $i++){
                        array_push($line,($y*$sz +$x + $i*$sz - $i ));
                    }
                    array_push($lines,$line);
                }
            }
        }
        $this->lines = $lines;
    }
}