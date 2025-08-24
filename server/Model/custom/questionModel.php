<?php 

namespace Model;

use Library\keyLibrary;

class questionModel extends interactModel{
    public $type = "question";
    public $getTriggerUrl;
    public $checkResultUrl;
    public $interactToken = [];
    
    function __construct($data)
    {
        $this->getTriggerUrl = isset($data["getTriggerUrl"])?$data["getTriggerUrl"]:null;
        $this->checkResultUrl = isset($data["checkResultUrl"])?$data["checkResultUrl"]:null;
        $this->requireTriggerCount = isset($data["requireTriggerCount"])?$data["requireTriggerCount"]:true;
        $this->minTriggerCount = isset($data["minTriggerCount"])?$data["minTriggerCount"]:PHP_INT_MAX;
        $this->triggerCountUrl = isset($data["triggerCountUrl"])?$data["triggerCountUrl"]:null;
    }

    function getTrigger($playerInfo){
        if (empty($this->getTriggerUrl)){
            // get dummy question
        $question =  <<<EOD
<div class="container-fluid">
    <div class="row">
        <div class="col-6">
            <h5>Question: </h5>
            Dummy question.
        </div>
        <div class="col-6">
            <form class="interactForm" onsubmit="event.preventDefault();">
                <div class="row">
                    <div class="col-12">
                    <p>Select one or more of the followings:</p>
                            <div class="form-check">
                                <input class="form-check-input answerInput" type="checkbox" value="1" name="answers[]">
                                <label class="form-check-label" for="defaultCheck1">
                                  Correct
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input answerInput" type="checkbox" value="2" name="answers[]">
                                <label class="form-check-label" for="defaultCheck1">
                                  Wrong
                                </label>
                            </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <input class="btn btn-primary interactSubmit" type="submit" value="Submit Answer">
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
EOD;
        }else{
            $post = [
                'name'=>$playerInfo->name,
                'token'=>$playerInfo->token,
                'dataPack'=>json_encode($playerInfo->dataPack)
            ];
            $ch = curl_init($this->getTriggerUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            // execute!
            $question = curl_exec($ch);  
            // close the connection, release resources used
            curl_close($ch);   
        }
        return ["type"=>"question", "html"=>trim($question)];
    }

    function manageInteractResult($playerInfo, $data){
        //echo "answers: ".json_encode($answers)."\n";

        if (empty($this->checkResultUrl)){
            // dummy result
            $question = "Dummy question.";
            if ($data['answers'] == ["1"]) $result = "Correct";
            else if (in_array("1",$data['answers'])) $result = "Partially Correct";
            else $result = "Wrong";
            
        }else{
            $post = [
                'name'=>$playerInfo->name,
                'token'=>$playerInfo->token,
                'dataPack'=> json_encode($playerInfo->dataPack),
                'answers'=>implode(",", $data['answers']),
                'questionId'=>implode($data['questionId']),
                'questionType'=>implode($data['questionType'])
            ];
            //error_log(print_r($post,true), 3, "./error.log"); //log test
            $ch = curl_init($this->checkResultUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            // execute!
            $response = curl_exec($ch);  
            // close the connection, release resources used
            curl_close($ch);  
            $response = json_decode($response);
            $result = $response->result;
        }
        $data =  ["result"=>$result, "data"=>["userAnswer"=>$data['answers']]];
        $proceed = ($data["result"]=="Correct"); // whether result should be proceeded to game
        $getTrigger = !$proceed; //whether get trigger is needed
        if ($proceed){
            // add interact token
            $keyLib = new keyLibrary();
            $tokenStr = json_encode(["data"=>$data,"time"=>time(), "rand"=>bin2hex(random_bytes(10))]);
            $token = $keyLib->resultEnc($tokenStr);
            array_push($this->interactToken,$token);
        } else $token = null;
        $data["token"] = $token;
        return ["proceedGameAction"=> $proceed, "getTrigger"=> $getTrigger, "data"=>$data];
    }

    function getTriggerCount($playerInfo){
        if ($this->requireTriggerCount){
            $post = [
                'name'=>$playerInfo->name,
                'token'=>$playerInfo->token,
                'dataPack'=>json_encode($playerInfo->dataPack)
            ];
            $ch = curl_init($this->triggerCountUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            // execute!
            $count = curl_exec($ch);  
            // close the connection, release resources used
            curl_close($ch);   
            // check if question count is more than minimun needed
            $count = json_decode($count,true);
            if ($count["totalCount"] >= $this->minTriggerCount){
                return ["result"=>true, "data"=>$count, "message"=>"", "error"=>""];
            }else{
                return ["result"=>false, "data"=>$count, "message"=>"", "error"=>"Not enough questions to start game. Please change topic."];
            }
        }else{
            return ["result"=>true, "data"=>null, "message"=>"No question count required", "error"=>""];
        }
    }
}