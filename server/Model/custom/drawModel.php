<?php 

namespace Model;

use CodeIgniter\Pager\Pager;
use Library\keyLibrary;

class drawModel extends interactModel{
    public $type = "draw";
    public $status = 0; //0: select item /1: draw /2: guess
    public $category = "";

    public $getItemUrl = ""; // get drawing items
    public $submitDrawUrl = ""; // submit drawing
    public $getDrawUrl = ""; // get drawing
    public $submitChoiceUrl = ""; // submit choices
    public $getCountUrl = ""; // get drawing item count

    public $drawings = []; // drawing storage
    public $questions = []; // question storage
    public $interactTime = 10;

    public $selectTime = 30; // time for players to select topic
    public $drawTime = 30; // time for players to draw
    public $guessTime = 15; // time for players to guess
    
    

    function __construct($data)
    {
        // category and url settings
        if (isset($data["category"])) $this->category = $data["category"];
        if (isset($data["getItemUrl"])) $this->getItemUrl = $data["getItemUrl"];
        if (isset($data["submitDrawUrl"])) $this->submitDrawUrl = $data["submitDrawUrl"];
        if (isset($data["getDrawUrl"])) $this->getDrawUrl = $data["getDrawUrl"];
        if (isset($data["submitChoiceUrl"])) $this->submitChoiceUrl = $data["submitChoiceUrl"];

        // time settings
        if (isset($data["selectTime"])) $this->selectTime = $data["selectTime"];
        if (isset($data["drawTime"])) $this->drawTime = $data["drawTime"];
        if (isset($data["guessTime"])) $this->guessTime = $data["guessTime"];
        $this->interactTime = $this->selectTime;

        // count settings
        if (isset($data["getCountUrl"])) $this->getCountUrl = $data["getCountUrl"];
        if (isset($data["requireTriggerCount"])) $this->requireTriggerCount = $data["requireTriggerCount"];
        if (isset($data["minTriggerCount"])) $this->minTriggerCount = $data["minTriggerCount"];

        
    }

    function getTrigger($playerInfo)
    {
        switch ($this->status){
            case 0: // get item choices from API
                if (empty($this->drawings[$playerInfo->socketId]["choices"])){
                $choices = $this->postCurlRequest($this->getItemUrl,["category"=>$this->category, "token"=>$playerInfo->token],true);
                $this->drawings[$playerInfo->socketId]["choices"] = $choices["items"];
                return ["type"=>"draw", "role"=>"choose","choices"=>$choices["items"]];
                }else return ["type"=>"draw", "popup"=>"hide"]; // hide popup if player already has selected item and gets new trigger
                
                
            case 2: // get drawing

                // check if player already has question
                if (isset($this->questions[$playerInfo->socketId])){
                    // hide popup if player already answered question and gets new trigger
                    return ["type"=>"draw", "role"=>"wait", "popup"=>"hide"];
                }

                //select any player with canvas drawing except self
                $userList = array_keys($this->drawings);
                foreach ($userList as $i=>$id){
                    if ($id == $playerInfo->socketId) unset($userList[$i]);
                    if (empty($this->drawings[$id]["canvas"])) unset($userList[$i]);
                }

                if ($userList != []){
                    $randomUser = $userList[array_rand($userList)];
                    // get drawings and choices
                    $drawing = $this->drawings[$randomUser];
                    $this->questions[$playerInfo->socketId] = $drawing;
                } else {
                    // get drawing from api
                    $apiResult = $this->postCurlRequest($this->getDrawUrl,["category"=>$this->category, "token"=>$playerInfo->token],true);
                    //var_dump($apiResult);
                    if ($apiResult["result"] && !empty($apiResult["canvas"])){
                        $drawing = $apiResult;
                        $this->questions[$playerInfo->socketId] = $drawing;
                    }else return ["type"=>"draw", "role"=>"error", "error"=>"Connection error. Please restart game."];
                }

                $this->questions[$playerInfo->socketId]["answered"] = false;
                return ["type"=>"draw", "role"=>"guess", "canvas"=>$drawing["canvas"], "choices"=>$drawing["choices"]];

            default: return ["type"=>"draw", "popup"=>"hide"];
        }
        
    }

    function manageInteractResult($playerInfo, $data)
    {
        switch ($data["type"]){
            case "selectItem": 
                if ($this->status == 0 && empty($this->drawings[$playerInfo->socketId]["correctAnswer"]) && isset($data["choice"])){
                    $choice = $data["choice"];
                    $this->drawings[$playerInfo->socketId]["correctAnswer"] = $choice;
                    return ["data"=>["role"=>"draw", "drawItem"=> $this->drawings[$playerInfo->socketId]["choices"][$choice]], "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];
                }
            return ["data"=> null , "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];
                

            case "submitDraw": 
                if (($this->status == 0 || $this->status == 1) && empty($this->drawings[$playerInfo->socketId]["canvas"])){
                    // submit drawing to API
                    if (!empty($this->submitDrawUrl)){
                        $response = $this->postCurlRequest($this->submitDrawUrl,["item"=>$this->drawings[$playerInfo->socketId]["correctAnswer"], "canvas"=>$data["canvas"], "token"=>$playerInfo->token],true);
                        if (isset($response["drawId"]))  $this->drawings[$playerInfo->socketId]["drawId"] = $response["drawId"];
                    }
                    // store drawing to model storage
                    $this->drawings[$playerInfo->socketId]["canvas"] = $data["canvas"];
                    return ["data"=>["role"=>"wait"], "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];
                }
                return ["data"=> null , "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];
                

            case "submitChoice": 
                if ($this->status == 2 && !empty($data["choice"])){
                    // submit answer to API
                    if (!empty($this->submitChoiceUrl) && !empty($this->questions[$playerInfo->socketId]["drawId"])){
                        // post curl request to api
                        $response = $this->postCurlRequest($this->submitChoiceUrl,["token"=>$playerInfo->token, "drawId"=>$this->questions[$playerInfo->socketId]["drawId"], "playerAnswer"=>$data["choice"]]);
                    }


                    if ($data["choice"] == $this->questions[$playerInfo->socketId]["correctAnswer"]){
                        // return correct result
                        // add interact token
                        $keyLib = new keyLibrary();
                        $tokenStr = json_encode(["data"=>$data,"time"=>time(), "rand"=>bin2hex(random_bytes(10))]);
                        $token = $keyLib->resultEnc($tokenStr);
                        array_push($this->interactToken,$token);

                        $returnData =  ["proceedGameAction"=>true,  "getTrigger"=>false, "data"=>["role"=>"wait", "result"=>true, "token"=>$token, "popup"=>"correct"]];
                    }else{
                        // return wrong result
                        $returnData =  ["proceedGameAction"=>false,  "getTrigger"=>false, "data"=>["role"=>"wait", "result"=>false, "popup"=>"wrong"]];
                    }

                    // add choice item name
                    if (isset($this->questions[$playerInfo->socketId]["choices"][$data["choice"]])){
                        $itemName = $this->questions[$playerInfo->socketId]["choices"][$data["choice"]];
                        $returnData["data"]["choice"] = $itemName;
                    }

                    // mark question as answered
                    $this->questions[$playerInfo->socketId]["answered"] = true;

                    return $returnData;
                }else return ["data"=> null , "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];

            default: return ["data"=> null , "ignoreGameAction"=>true, "proceedGameAction"=>false, "getTrigger"=>false];
        }
    }

    function interactTimerAction($i, &$roomInstance)
    {
        $action = [];
        $this->interactTime--;

        if ($this->interactTime <= 0){
             // end action
            switch ($this->status){
                case 0:
                    // random choose item if player has not choose topic
                    foreach($this->drawings as $id=>$itemData){
                        if (empty($itemData["correctAnswer"])){
                            $randomItem = $itemData["choices"][array_rand($itemData["choices"])];
                            $this->drawings[$id]["correctAnswer"] = $randomItem;
                            array_push($action,["action"=>"emitTo", "to"=>$id, "name"=>"interact", "data"=>["action"=>"getResult", "data"=>["role"=>"draw", "drawItem"=>$randomItem],  "message"=>"", "error"=>"", "devError"=>""]]);
                        }
                    }
                    // jump to status 1 (draw)
                    $this->status = 1;
                    $this->interactTime = $this->drawTime;
                    break;

                case 1:
                    // give false(fail) result if player has not submit drawing
                    global $serverLib;
                    foreach($this->drawings as $id=>$itemData){
                        if (empty($itemData["canvas"])){
                            if (in_array($id, $roomInstance->players)){
                                $gameLib = $serverLib->gameLib->getGameLib($roomInstance);
                                $interactResult = ["canvas"=>[], "proceedGameAction"=>"false", "getTrigger"=>false];
                                $failAction = $gameLib->interactProceedingAction($roomInstance,$id,$interactResult);
                                foreach($failAction as $a){
                                    array_push($action,$a);
                                }
                            }
                        }
                    }

                    // jump to status 2 (guess)
                    $this->status = 2;
                    $this->interactTime = $this->guessTime;

                    // remove drawings without canvas data
                    foreach ($this->drawings as $id=>$drawing){
                        if (empty($drawing["canvas"])){
                            unset($this->drawings[$id]);
                        }
                    }

                    // get trigger (guess choices)
                    $playerList = array_diff($roomInstance->players, $roomInstance->robots);
                    foreach($playerList as $playerId){
                        $triggerData =  $this->getTrigger($serverLib->players[$playerId]);
                        array_push($action,["action"=>"emitTo", "to"=>$playerId, "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$triggerData,  "message"=>"", "error"=>"", "devError"=>""]]);
                    }

                    break;


                case 2:
                    // jump to status 0 (choose item)
                    $this->status = 0;
                    $this->interactTime = $this->selectTime;

                    $this->questions = [];
                    $this->drawings = [];
                    $playerList = array_diff($roomInstance->players, $roomInstance->robots);
                    global $serverLib;
                    // get trigger (select item choices)
                    foreach($playerList as $playerId){
                        $triggerData =  $this->getTrigger($serverLib->players[$playerId]);
                        array_push($action,["action"=>"emitTo", "to"=>$playerId, "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$triggerData,  "message"=>"", "error"=>"", "devError"=>""]]);
                    }
                break;
            }
        }else {
            // interval action
            switch ($this->status){
                case 0:
                    // check if all players has chosen topic
                    $playerList = array_diff($roomInstance->players, $roomInstance->robots);
                    $chosenTopic = true;
                    foreach($playerList as $playerId){
                        if (empty($this->drawings[$playerId]["correctAnswer"])){
                            $chosenTopic = false;
                            break;
                        }
                    }

                    // if all players has chosen topic, jump to status 1;
                    if ($chosenTopic){
                        $this->status = 1;
                        $this->interactTime += $this->drawTime;
                    }
                    break;

                case 1: // check if all players has submitted drawing
                    $playerList = array_diff($roomInstance->players, $roomInstance->robots);
                    $submitted = true;
                    foreach($playerList as $playerId){
                        if (empty($this->drawings[$playerId]["canvas"])){
                            $submitted = false;
                        }
                    }

                        // if all players has submitted drawing, jump to status 2 and get trigger (get guess choices);
                    if ($submitted){
                        $this->status = 2;
                        $this->interactTime = $this->guessTime;

                        // get trigger
                        global $serverLib;
                        foreach($playerList as $playerId){
                                $triggerData =  $this->getTrigger($serverLib->players[$playerId]);
                                array_push($action,["action"=>"emitTo", "to"=>$playerId, "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$triggerData,  "message"=>"", "error"=>"", "devError"=>""]]);
                        }
                    }
                    break;

                case 2:
                    // check if all players has submitted choice
                    $playerList = array_diff($roomInstance->players, $roomInstance->robots);
                    $answered = true;
                    foreach($playerList as $playerId){
                        if (isset($this->questions[$playerId])){
                            if ($this->questions[$playerId]["answered"] == false){
                                $answered = false;
                                break;
                            }
                        }
                    }

                    // if all players has submitted choice, jump to status 0 and get trigger (get item choices);
                    if ($answered){
                        $this->status = 0;
                        $this->interactTime = $this->selectTime;
                        $this->questions = [];
                        $this->drawings = [];

                        // get trigger
                        global $serverLib;
                        foreach($playerList as $playerId){
                            $triggerData =  $this->getTrigger($serverLib->players[$playerId]);
                            array_push($action,["action"=>"emitTo", "to"=>$playerId, "name"=>"interact", "data"=>["action"=>"getTrigger", "data"=>$triggerData,  "message"=>"", "error"=>"", "devError"=>""]]);
                        }

                    }
                break;
                default: break;
            }

        }

        // emit timer 
        switch ($this->status){
            case 0:
                array_push($action,["action"=>"emitTo", "to"=>$roomInstance->name, "name"=>"interact", "data"=>["action"=>"interactTime", "data"=>["status"=>0, "time"=>$this->interactTime, "drawTime"=>$this->drawTime],  "message"=>"", "error"=>"", "devError"=>""]]);
                break;
            default: array_push($action,["action"=>"emitTo", "to"=>$roomInstance->name, "name"=>"interact", "data"=>["action"=>"interactTime", "data"=>["status"=>$this->status, "time"=>$this->interactTime],  "message"=>"", "error"=>"", "devError"=>""]]);
            break;
        }

        return $action;
    }

    function getTriggerCount($playerInfo){
        if ($this->requireTriggerCount){
            $count = $this->postCurlRequest($this->getCountUrl,[
                'category' => $this->category,
                'token'=>$playerInfo->token,
                'dataPack' =>$playerInfo->dataPack,
            ], true);

            if (empty($count["count"])){
                return ["result"=>false, "data"=>$count, "message"=>"", "error"=>"An error occured when getting item count. Please try again."];
            }

            if ($count["count"] >= $this->minTriggerCount){
                return ["result"=>true, "data"=>$count, "message"=>"", "error"=>""];
            }else{
                return ["result"=>false, "data"=>$count, "message"=>"", "error"=>"Not enough draw items to start game. Please change category."];
            }
        }else{
            return ["result"=>true, "data"=>null, "message"=>"No question count required", "error"=>""];
        }
    }

    private function postCurlRequest($url,$postData,$toArray=false){
        //echo "[postCurlRequest] url: ".$url."\n";
        //echo "postData: ".json_encode($postData)."\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        // execute!
        $response = curl_exec($ch);  
        // close the connection, release resources used
        curl_close($ch);
        //echo "response: ".$response."\n";
        if ($toArray) $response = json_decode($response,true); 
        return $response;
    }
}