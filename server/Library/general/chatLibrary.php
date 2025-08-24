<?php 
namespace Library;

class chatLibrary{
    public $bannedPatterns = ["f+u+c+k+", "屌"];
    public $filterPatterns = ["shit(ty|ted)?\b","戇.{0,6}鳩"];
    public $filterReplacement = "***"; // replacement filter words

    function sendChat($room,$sender,$message){
        if ($room == null){
            return [["action"=>"emit","name"=>"chat", "data"=>["action"=>"sendChat", "data"=>["result"=>false], "message"=>"", "error"=>"Please join room first.", "devError"=>"Player room not found"]]];
        }else{
            // stop emitting if message has banned word
            $cleanMessage = $message;
            foreach ($this->bannedPatterns as $bannedWord){
                if (preg_match("/".$bannedWord."/i",$cleanMessage))
                return [["action"=>"emit","name"=>"chat", "data"=>["action"=>"sendChat", "data"=>["result"=>false], "message"=>"", "error"=>"Please use appropriate language.", "devError"=>"Message contains banned word"]]];
            }

            // replace filter words
            foreach ($this->filterPatterns as $filterWord){
                $cleanMessage = preg_replace("/".$filterWord."/i",$this->filterReplacement,$cleanMessage);
            }
            
            return [["action"=>"emitTo", "to"=>$room, "name"=>"chat", "data"=>["action"=>"sendChat", "data"=>["result"=>true, "sender"=>$sender, "message"=>$cleanMessage], "message"=>"", "error"=>"Please join room first", "devError"=>"Player room not found"]]];
        }   
    }
}