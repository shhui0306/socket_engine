<?php 

namespace Library;

class roomLibrary{

    function checkAddRoomCondition($rooms=[],$data=[]){
        if (!isset($data["roomName"])){return  ["result"=>false,"error"=>"Room name missing.","devError"=>"[checkAddRoom] room name missing"];}
        else if (!preg_match("/^[a-zA-Z0-9]+$/",$data["roomName"])){return  ["result"=>false,"error"=>"Invalid room name. Room name must contain alphanumeric characters only.","devError"=>"[checkAddRoom] invalid room name"];}
        else if (isset($rooms[$data["roomName"]])){
                return ["result"=>false, "error"=>"Room name has already been used. Please use another name.", "devError"=>"[checkAddRoom] duplicate room"];
        }
        return ["result"=>true];
    }

    // get list of room names and mode
    function searchRoom($rooms=[], $data=""){
        // search certain mode
        $modeSearch = false;
        $roomList = [];
        if (isset($data["mode"])){
            $mode = $data["mode"];
            if ($data["mode"] != ""){
                $modeSearch = true;
            }
        } else $mode = null;

        if (!isset($data["joinableOnly"])){
            $joinableOnly = true;
        } else $joinableOnly = $data["joinableOnly"];

        if (!empty($data["groupId"])){
            $groupId = $data["groupId"];
        } else $groupId = null;

        // filter room and add room
        foreach ($rooms as $r){
            $addAble = true;
            // filter room by game mode
            if ($modeSearch){
                if ($r->mode != $mode) $addAble = false;
            }

            // filter room by status
            if ($joinableOnly){
                if ($r->status != 0) $addAble = false;
            }

            // filter by group id
            if ($groupId != null){
                if ($r->groupId != $groupId) $addAble = false;
            }

            if ($addAble){
                array_push($roomList, ["name"=>$r->name, "mode"=>$r->game->mode, "groupId"=>$r->groupId]);
            }
        }

        return $roomList;
    }

    // check join room condition
    function checkJoinRoomCondition($rooms, $data, $socketId){
        $roomName = $data["roomName"];
        $error = "";
       
        // check if room exist
        $check = true;
        if (!isset($rooms[$roomName])){
            $check = false;
            $error = "[checkJoinRoomCondition] room not exist";
        }
        
        // check if player joins any room
        foreach ($rooms as $r){
            if (in_array($socketId,$r->players)){
                $check = false;
                break;
            }
        }

        // check number of players in room is already exceeded or not
        if (isset($rooms[$roomName])){
            if (count($rooms[$roomName]->players) >= $rooms[$roomName]->game->maxPlayers){
                $check = false;
                $error = "[checkJoinRoomCondition] room full";
            }
        }

        //check if game is already started in room
        if (isset($rooms[$roomName])){
            if ($rooms[$roomName]->status != 0){
                $check = false;
                $error = "[checkJoinRoomCondition] game already started";
            }
        }
        return ["result"=>$check, "room"=>$roomName, "error"=>$error];
    }

    // find room name by socket id
    function findRoomBySocketId ($rooms, $socketId){
        foreach ($rooms as $name=>$r){
            if (in_array($socketId,$r->players)){
                return ["result"=>true, "room"=>$name];
            }
        }
        return ["result"=>false, "room"=>null, "error"=>"findRoomBySocketId: room not joined"];
    }
    
    function checkRoomStatus($room, $mode, $status){
        switch ($mode){
            case "==": if ($room->status == $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkRoomStatus(==): roomStatus:".$room->status." reqStatus:".$status];
            case ">=": if ($room->status >= $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkRoomStatus(>=): roomStatus:".$room->status." reqStatus:".$status];
            case "<=": if ($room->status <= $status) return ["result"=>true]; else return ["result"=>false,"message"=>"checkRoomStatus(<=): roomStatus:".$room->status." reqStatus:".$status];
        }
    }


    function changeRoomStatus($roomInstance){
        switch ($roomInstance->status){
            case 0:  // check if room has any players, promote room status to 1
                    if (count($roomInstance->players) >= 1){
                        $roomInstance->status = 1;
                    } break;
            case 1: // promote room status to 2; (end game)
                    $roomInstance->status = 2; break;
            default: break;
        }
        return $roomInstance;
}
}