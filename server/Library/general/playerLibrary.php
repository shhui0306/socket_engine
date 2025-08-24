<?php 

namespace Library;

class playerLibrary{

    // check if duplicate player in player list, return true if no duplicates
    function checkDuplicatePlayer($playerList,$socketId){
            if (isset($playerList[$socketId])){
                if ($playerList[$socketId]->socketId == $socketId){
                    return ["result"=>false,"error"=>"You are already logged in on from another device. Logging in on more than one device is not allowed.", "devError"=>"[checkDuplicatePlayer] duplicate player"]; 
                }
            }
            return ["result"=>true];
    }

    // check player status
    function checkPlayerStatus($playerList,$socketId,$mode,$status){
        if (isset($playerList[$socketId])){
            if ($playerList[$socketId]->socketId == $socketId){
                    $p = $playerList[$socketId];
                    switch ($mode){
                        case "==": if ($p->status == $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkPlayerStatus(==): userStatus:".$p->status." reqStatus:".$status];
                        case ">=": if ($p->status >= $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkPlayerStatus(>=): userStatus:".$p->status." reqStatus:".$status];
                        case "<=": if ($p->status <= $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkPlayerStatus(<=): userStatus:".$p->status." reqStatus:".$status];
                }
            }
        }
        return ["result"=>false,"error"=>"Please log in first.", "devError"=>"checkPlayerStatus(".$mode."): user not found"];
    }

    function changePlayerStatus($playerInstance,$roomList){
            $roomLib = new roomLibrary();
            switch ($playerInstance->status){
                case 0:  // check if user joined room, change user status to 1.
                $checkRoom = $roomLib->findRoomBySocketId($roomList,$playerInstance->socketId);
                if ($checkRoom["result"] === true) $playerInstance->status = 1; break;
                default: break;
            }
            return $playerInstance;
    }

    // find names from array of socket ids
    function findNamesFromIdList($playerList, $idList, $displayDisconnected=false){
        $nameList = [];
        foreach ($idList as $id){
            if (isset($playerList[$id])){
                array_push($nameList, $playerList[$id]->name);
            } 
            else if ($displayDisconnected) array_push($nameList,"Disconnected player");
            else array_push($nameList,"");
        }
        return $nameList;
    }
}