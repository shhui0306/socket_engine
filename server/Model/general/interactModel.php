<?php 

namespace Model;

abstract class interactModel{
    public $type;
    public $getTriggerUrl;
    public $checkResultUrl;
    public $requireTriggerCount;
    public $triggerCountUrl;
    public $minTriggerCount;
    public $interactToken = [];
    
    abstract function getTrigger($playerInfo); // return ["type"=>"interact type", "html"=>"trigger html"]
    abstract function manageInteractResult($playerInfo, $data); // return ["proceedGameAction"=> (bool), "getTrigger"=> (bool), "data"=>[result data]]
    function getTriggerCount($playerInfo){return ["result"=>true, "data"=>null, "message"=>"No question count required", "error"=>""];} //  return ["result"=>(bool), "data"=>(array/null), "message"=>"", "error"=>""]
    function interactTimerAction($i, &$roomInstance){return [];} //return []
}