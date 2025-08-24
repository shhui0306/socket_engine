<?php 

namespace Library;

use Model\robotModel;

class robotLibrary{
    // robot name list
    public $robotNames = ["Nicholas", "Devon", "Frederick", "Elijah", "Hubert", "Clemente", "Tyson", "Jerrold", "Caleb", "Abe", "Angelo", "Jack", "Jamie", "Brant", "Rueben", "Cristobal", "Harold", "Juan", "Milton", "Karl", "Laure", "Pamelia", "Eula", "Phuong", "Roselyn", "Rebecca", "Simonne", "Assunta", "Mikaela", "Valrie", "Nicholle", "Vertie", "Shelia", "Kellee", "Machelle", "Clotilde", "Rozella", "Lyda", "Klara", "Rubie"];
    
    public function createRobotPlayer($robotId,$roomInstance){
        $name = $this->robotNames[array_rand($this->robotNames)];
        return new robotModel(["name"=>$name,"minRobotActionTime"=>"","maxRobotActionTime"=>""],$robotId,$roomInstance);
    }

    public function robotIntervalAction(&$robotInstance,$roomInstance){
        // change robot action time, return "doAction" true if robot action time is 0;
        $robotInstance->robotActionTime--;

        if ($robotInstance->robotActionTime <= 0){
            $robotInstance->robotActionTime = rand($roomInstance->minRobotActionTime, $roomInstance->maxRobotActionTime);
            //echo "robotActionTime: ".$robotInstance->robotActionTime."\n";
            return ["doAction"=>true];
        }else{
            return ["doAction"=>false];
        }

    }

}