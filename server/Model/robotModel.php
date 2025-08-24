<?php 

namespace Model;


class robotModel extends playerModel{
    public $type = "robot";
    public $robotActionTime = 8;
    

    function __construct($data, $robotId,$roomInstance=null) {
        $this->name = $data["name"];
        $this->socketId = $robotId;
        $this->status = 0;
        if ($roomInstance instanceOf roomModel){
            $this->minRobotActionTime = rand($roomInstance->minRobotActionTime,$roomInstance->maxRobotActionTime);
        }else{
            $this->minRobotActionTime = rand(8,15);
        }       
    }

}