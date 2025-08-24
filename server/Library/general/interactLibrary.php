<?php 
namespace Library;
use Model\questionModel;
use Model\drawModel;

 class interactLibrary{
    function createInteractInstance($data){;
        switch ($data["interactType"]){
            case "question": return ["result"=>true, "interact"=>new questionModel($data)];
            case "draw": return ["result"=>true, "interact"=>new drawModel($data)];
            case "": return ["result"=>false, "error"=>"Interact type cannot be empty.", "devError"=>"Empty interact type"];
            default: return ["result"=>false, "error"=>"Invalid interact type.", "devError"=>"Invalid interact type ".$data["interactType"]];
        }
    }
}