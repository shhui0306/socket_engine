<?php 

namespace Model;

class playerModel{
    public $type = "human";
    public $name;
    public $dataPack=[];
    public $token;
    public $socketId="";
    public $status;

    function __construct($data, $socketId) {
        $this->name = $data["name"];
        $this->token = $data['userToken'];
        $this->dataPack = $data["dataPack"];
        $this->socketId = $socketId;
        $this->status = 0;
    }

}