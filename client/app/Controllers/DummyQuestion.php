<?php namespace App\Controllers;

class DummyQuestion extends BaseController
{    
    public function question(){
        return view("dummy/question");
    }

    public function answer(){
        if (isset($_POST['answers'])){
            $answers = $_POST['answers'];
            log_message("error", $answers);
            if ($answers == "1") $result = "Correct";
            else if (strpos($answers,"1")) $result = "Partially Correct";
            else $result = "Wrong";
            $data = ["result"=>$result, "data"=>["question"=>"dummy question", "userAnswer"=>$answers]];
            return $this->response->setJSON($data);
        }
    }

    public function getQuestionCount(){
        $count = ["questionType"=>["mc","fb"], "typeCount"=>["mc"=>0,"fb"=>10],"totalCount"=>10];
        return $this->response->setJSON($count,true);
    }

	//--------------------------------------------------------------------

}
