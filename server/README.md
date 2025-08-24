# Socket Server Library
Runs socket server using workerman PHPSocket.io. Simplify processes using serverLibrary to reduce number of events.

## Contents
1. Update log
2. Project structure
3. Socket flow
4. Socket Instructions
5. Countdown Timer Instructions
6. Server Library
7. Player Library
8. Room Library
9. Timer Library
10. Game Library
11. Custom game mode
12. Custom interact type

## Update log
2020-10-24: Add readme session for custom game mode and custom interact type
2021-1-13: Added interactTimerAction in interact type.


## Project structure:
├── index.php (socket.io communication)  
├──Library  
│└── general  
││   └── gameLibrary.php (game logic and functions, assign game models and abstract functions)  
││   └── interactLibrary.php (User interaction logic and functions, assign interact model and abstract functions)  
││   └── playerLibrary.php (players logic and functions)  
││   └── roomLibrary.php (Room logic and functions)  
│└── custom  
││   └── ticLibrary.php (Tic library extend game library)  
││   └── questionLibrary.php (Question-related logic and functions)  
│└── keyLibrary.php (Encrypt and decrypt functions)  
│└── robotLibrary.php (Create robot and functions)  
│└── serverLibrary.php  (Server logic and functions, call other libraries for individual purpose, and storing global lists)  
│  
├──Model  
│└── general  
││   └── gameModel.php (Basic game properties)  
││   └── interactModel.php (Abstract class for interactions)  
││   └── playerModel.php (Player properties)  
││   └── roomModel.php (Room properties)  
│└── custom  
││   └── ticModel.php extends gameModel.php (tic game properties)  
││   └── questionModel.php (Question properties)  
│└── RobotModel.php (Robot properties extends player model)  
  
## Socket flow:
 1. Gets token from client by socket events ($socket->on("event name"))
 2. Decodes token to array if nessessary, decoded example: ["action"=>"some action", "data"=>[(data array)]]
 3. Execute Library functions according to "action" parameter from decoded array
 4. Library processes decoded array data and returns emit/action instructions, instruction example [["action"=>"some action"],...]
 5. index.php executes socket instructions (e.g. emit, join/leave room, disconnect)
 6. Client receives emit message if server has emitted

 ## Socket instructions / emitAction($instructions,$io,$socket);
 - $instructions: array, array of instructions, format mentioned below
 - $io: io object from SocketIO initiation
 - $socket: socket object from socket connection
 Used for preforming socket actions (e.g. emit, join/leave room, disconnect) after library process.  
 Used after return output from library functions. The library output is then passed to emitAction() in index.php  

### Instruction format
Note input of emitAction() must be an array of instructions:
 ```
[
    ["action"=>"name of instruction",  
     "to"=>"emit to room name or socket id",
     "name"=>"emit/room name",
     "data"=>"data to be emitted",
    ],
    ...
]
 ```
### List of instructions:
- "emit": emit to client only, format ["action"=>"emit","name"=>"emit name","data"=>"data to be emitted"]
- "emitTo": emit to particular room or user, format ["action"=>"emitTo", "to"=>"room no or socket id", "name"=>"emit name","data"=>"data to be emitted"]
- "emitGlobal": emit to all users, format ["action"=>"emitGlobal", "name"=>"emit name","data"=>"data to be emitted"]
- "join": join room, format ["action"=>"join", "name"=>"room name"]
- "leave": leave room, format ["action"=>"leave", "name"=>"room name"]
- "disconnect": disconnect from server, format ["action"=>"disconnect"]
- "setCountdown": add countdown timer,format ["action"=>"setCountdown", "data"=>[(array, countdown timer settings, see Counter Timer below)]] 
- "deleteTimer": delete timer by timer id, ["action"=>"deleteTimer", "name"=>"timer id"]

## Countdown Timer instruction / setCountdown($data,$io,$socket)
Sets countdown timer for socket. Could be used to do actions in each interval and after countdown.
- $data: countdown format data, details in below section
 - $io: io object from SocketIO initiation
 - $socket: socket object from socket connection
 - When timer is created (or fails on check), emit "timer" message: ["action"=>"setCountdown", "data"=>["name"=>$data["timerName"]], "status"=>"adding", "result"=>$result, "message"=>$data["message"], "error"=>$data["error"]], it also sends timer ID to handleTimerId() in timer library.
 - In every interval, emit "timer" message ["action"=>"setCountdown", "data"=>["name"=>$data["timerName"], "status"=>"counting", "count"=>$i],"message"=>"", "error"=>""], executes intervalAction, reduce counter ($i) by 1.
 - When counter is null or <=0 , emits "timer" message: ["action"=>"setCountdown", "data"=>["name"=>$data["timerName"], "status"=>"end", "count"=>0],"message"=>"", "error"=>""], executes endAction, and deletes timer if needed.

### Countdown format data
```
[
    "interval"=>(number, decimal ok, interval of each count in seconds),
    "count"=>(integer, number of intervals to be counted down),
    "timerName"=>"string, name of timer",
    "emitMode"=>"string, must be one of below: emit/emitTo/emitGlobal, indicates method of emitting timer count",
    "to"=>"string, used only in 'emitTo', indicates room/player to be emitted to",
    "intervalAction"=>function($i){return array()},  //(optional) function, actions done in every interval, must return array of instructions, timer count could also be accessed by first parameter of function.
    "endAction"=>function(){return array()},  //(optional) function, actions done when countdown is finished, must return array of instructions
]
```

### Interval / End action instructions
Used for return of "intervalAction" and "endAction" functions. 
Return format must be an array of instructions similar to emitAction. 
 ```
[
    ["action"=>"name of instruction",  
     "to"=>"emit to room name or socket id",
     "name"=>"emit/room name",
     "data"=>"data to be emitted",
    ],
    ...
] 
```

Instructions in emitAction (e.g. emit, emitTo, emitGlobal, join, leave, disconnect) could also be used.  
Additional instructions regarding to timer are as belows:  
- "del":  deletes timer and stop countdown. Format: ["action"=>"del"]. In end action, timer will be deleted even without "del" action.
- "reset": resets timer to original count. Prevents deletion of timer in endAction. Format: ["action"=>"reset"]
- "setCount": sets timer to particular number. Prevents deletion of timer in endAction. Format: ["action"=>"setCount", "data"=>["count"=>(number)]]
- "startNewCountdown": Reset timer using new countdown data, which could be useful for changing to new countdown. Prevents deletion of timer in endAction.  
 Format:  ["action"=>"startNewCountdown", "data"=>[(array, new countdown format data, see 'Countdown data format')]]  
Note: Interval could not be changed by "startNewCountdown". Old interval will still be used even different value is used for new timer format data. To change timer interval, current countdown must be deleted and start new countdown.

## Server Library
Variables:  
- $rooms: global room list
- $players: global player list
- $roomLib: Room list library
- $playerLib: Player list library

### init functions / on "init"
#### init($token, $socketId)
- $token: array, decrypted token array, format: ["name"=>"(player name)"]
- $socketId: string, from $socket->id in index
Checks if $token has "data" field and has "name" field in data and check if $this->players has any duplicate socket id.
Then creates playerModel object to $this->players.  
result data ($result) format: ["result"=>true, "name"=>$data["name"], "socketId"=>$socketId] / ["result"=>false]  
return: [["action"=>"emit", "name"=>"init", "data"=>["action"=>"init", "data"=>$result, "message"=>"(message)", "error"=>"(error)"]]

### room functions / on "room"
Preform operations invoving global $this->rooms list.  
Triggered by socket "room" event and switched according to action.

#### addRoom($token, $socketId) / action "addRoom"
Add new roomModel object to $this->rooms list.  
Adds player to room player list and promotes player status to 1 when success.
Fail if player status != 0
- $token: array, format: ["action"=>"addRoom", "data"=>["name"=>"(room name)", "mode"=>"(room mode)"]]  
result data ($result) format: ["result"=>false] /  ["result"=>true, "roomName"=>"room name", "mode"=>"room mode"];  
return: [["action"=>"emit", "name"=>"room", "data"=>["action"=>"addRoom", "data"=>$result, "message"=>"(message)", "error"=>"(error)"]]
["action"=>"emit", "name"=>"room", "data"=>["action"=>"newPlayer", "data"=>["name"=>$this->players[$socketId]->name],  "message"=>"", "error"=>""] is also added to return array if success to update player list

#### getRoom($token) / action "getRoom"
Get list of room names and mode.
Filter list by mode if "mode" is specified in input
- $token: array, format: ["action"=>"getRoom", data=>["mode"=>"(room mode, optional)"]]  
list ($roomList) format: [["name"=>"room name", "mode"=>"room mode"],...]  
return: [["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoom", "data"=>["list"=>$roomList], "message"=>"(message)", "error"=>"(error)"]]]

#### joinRoom($token, $socketId) / action "joinRoom"
Joins particular room and emit new player message to all players in same room
"newPlayer" message should trigger other players to get room player list to update.
Fail if room name not given, player status != 0, room not exist, or player already joined room.
Also adds player to room player list and promotes player status to 1 when success.

- $token: array, format: ["action"=>"joinRoom", "data"=>["roomName"=>"(room name)"]]
- $socketId: string, from $socket->id in index
- return: array,
```
$result: ["result"=>$check, "room"=>$roomName", "error"=>"(error)"]

When success:
 [
    ["action"=>"join", "name"=>$data["roomName"]], //joins player to room
    ["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "data"=>$result , "message"=>"(message)", "error"=>"(error)"]], //informs client join success
    ["action"=>"emitTo", "to"=>$data["roomName"], "name"=>"room", "data"=>["action"=>"newPlayer", "data"=>["name"=>$this->players[$socketId]->name] , "message"=>"(message)", "error"=>"(error)"]] // emit new player message to all players in same room, should trigger emit room action "getRoomPlayerList" to update
 ] 

When fail:
[["action"=>"emit", "name"=>"room", "data"=>["action"=>"joinRoom", "result"=>false] , "message"=>"(message)", "error"=>"(error)"]]
```
#### getRoomPlayerList($socketId) / action "getRoomPlayerList"
Get list of player names from the player joined room.
- $socketId: string, from $socket->id in index
- return: [["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoomPlayerList", "result"=>true, "data"=>$names, "count"=>count($ids)]], "message"=>"(message)", "error"=>"(error)"] when success  
[["action"=>"emit", "name"=>"room", "data"=>["action"=>"getRoomPlayerList", "result"=>false], "message"=>"(message)", "error"=>"(error)"]] when fail (e.g. when player has not joined room)

#### playerReadyStart($token, $socketId) / action "playerReadyStart"
Indicate player is ready to start game.
- $token: array, decrypted token array. Format: ["data"=>["roomName"=>"(string, room name)"]]
- Adds socket id to readyPlayers list in room.
- When number of readyPlayers >= minPlayers and all players are in readyPlayers list, change room status to 1 (start game message is emitted from forceStartCountdown)
- return message:  
[..., "data"=>["result"=>true, "room"=>"(room name)", "name"=>"(player name)"], "message"=>"", "error"=>""] when success  
[..., "data"=>["result"=>false, ...], ..., "error"=>"...", "devError"=>"[playerReadyStart] Player already in readyPlayer array."]] when socket id is already in readyPlayers array  
Other errors from checkDataFields, checkPlayerStatus,findRoombySocketId will also be returned if fail.

### game functions / on "game"
#### switchGameAction($token,$socketId) / action "switchGameAction"
Intermediate function which calls switchGameAction in game library to switch actions and return instructions
It first gets game library according to game type in player room, then calls switchGameAction function in game library to get instructions.
- $token: array, decrypted token array. Format: ["data"=>["roomName"=>"(string, room name)"]]
- $socketId: string, from $socket->id in index
- return: array, array of instructions

#### startGame($room,$startNewCountdown = false)
Create add game board, add interact and start game countdown timer instructions.
It does not have action from index such that it has to be called from other functions as this function supposed to be called once only.
- $room: string, room name
- $startNewCountdown: boolean, when true, game countdown timer will be passed by room/startNewCountdown action. Else is passed by "setCountdown" action.
Return instructions:
1. Promotes room status to 1 (game started). Add room/showReadyStart message to instructions to hide "Start" button.  return game/startGame  error directly if promote fail.
2. Add robot players if room has players less than minPlayers. Add room/newPlayer message to notice player to update player list.
3. Get game library according to game mode and get game board message by (game library)->startGame as game/startGame message. add error message if invalid game type.
4. Get interact from room and get trigger HTML by room->interact->getTrigger as interact/getTrigger.
5. Start gameCountdown using timerLibrary by "startNewCountdown" / "setCountdown" action.

### interact function / on "interact"
#### manageInteractResult($token,$socketId) / action "manageInteractResult"
Sends result to interact to manage.
According to interact result, additional instructions such as get new trigger, calling interactProceedingAction in game may also be added.
- $token: array, decrypted token array. Format: ["data"=>["roomName"=>"(string, room name)"]]
- $socketId: string, from $socket->id in index
- return: array, array of instructions



### disconnect functions  / on "disconnect"
When socket disconnects from server, do following actions
#### disconnect($socketId)
1. Find if socket have joined any room
2. Remove player from room, add emit leave room message to all players in room instruction (which reminds them to update room player list)
3. If no more players left in room, delete room and delete timer by timerId stored in room instance.
4. Remove player from socket list
return: array, array of socket instructions.

### Private functions
Not used directly on socket but to used to reduce repetitive code in the library.

#### validData($token=[])
Check if token has "data" field. 
- $token: array, decrypted token array
- return: array of token data if have field. else return empty array

#### checkDataFields($data=[],$fields=[], $checkEmpty=[])
Check if token data has the following fields, also could check if fields are empty
- $data: array, data array from decrypted token
- $fields: array, array of field names to be checked if the particular field present in $data.
- $checkEmpty: array, array of booleans. If particular field has true on this position, also checks if that field in $data is empty, return ["result"=>false, "error"=>"(error)"] if empty values present.
- return: boolean, true if all fields present in $data and no empty fields for $checkEmpty
Example:  
If a $data is ["name"=>"Sam", "age"=>18] and need to check if name and age is present and name must not be empty,  
Could use checkDataFields($data,["name","age"], [true,false]) to check and return false.  
If missing one of the fields or name is empty e.g. ["age"=>18] / ["name"=>"sam"] / ["name"=>"","age"=>18], it returns false.

#### getGameLib($game)
Get game library according to $game->mode in game instance.
- $game: game instance, $this->rooms[$room]->game for most cases.
- return: game library instance according to game mode (e.g. ticLibrary)
If invalid game mode type, return error message ["action"=>"emit","name"=>"game","data"=>["action"=>"getGameLib", "data"=>["result"=>false],"message"=>"", "error"=>"Invalid game mode.", "devError"=>"Invalid game mode ".$mode]]; 

## Player library functions
### checkDuplicatePlayer(\$playerList,\$socketId)
Check if any duplicate socket id in player list
- $playerList: array, $this->players from serverLibrary
- $socketId: string, socket id
- return: ["result"=>true] if no duplicates, ["result"=>true, "error"=>"(error)"] if has duplicates

### checkPlayerStatus($playerList,$socketId,$mode,$status)
Check player has certain status.  
Player status:  
0: Player created   
1: Player joined room  
- $playerList: array, $this->players from serverLibrary
- $socketId: string, socket id
- $mode: string, must be one of followings: "==" / ">=" / "<="
- $status: number, compare player status to this number
- return: ["result"=>true] if meets condition  
["result"=>false, "message"=>"(message)"] if condition not met   
["result"=>false, "error"=>"(error)"]if socket id not exist in player list

### changePlayerStatus($playerInstance,$roomList)
Checks condition and promote player status by one level.  
Status 0: Checks if player has joins any room, if joined, promote status to 1.
- $playerInstance: playerModel object, instance of player ($this->players[$socketId])
- $roomList: array, room list ($this->roms)
- return: playerModel object

### findNamesFromIdList($playerList, $idList)
Find player names from a list of socket ids
- $playerList: array, $this->players from serverLibrary
- $idList: array, array of socket ids
return: array, array of player names

## Room library functions
### checkAddRoomCondition($rooms=[],$data=[])
Check if room name is valid to add to room list.
- $rooms: array, room list ($this->roms) 
- $data: array, data from decrypted token string, format: ["roomName"=>(string, room name)]
- return ["result"=>false,"devError"=>"[checkAddRoom] room name missing"] if "roomName" key is missing from data array
- return ["result"=>false,"devError"=>"[checkAddRoom] invalid room name"] if "roomName" contains non alphanumeric characters
- return ["result"=>false, "devError"=>"[checkAddRoom] duplicate room"] if room name already exists in room list.
- else return ["result"=>true]

### searchRoom($rooms=[], $data="")
Search for rooms. Could be set to filter certain mode or joinable rooms only.
- $rooms: array, room list ($this->roms) 
- $data: array, data from decrypted token string, format: ["mode"=>"(optional) mode filter", "joinableOnly"=>"(optional) show joinable rooms only, default true if not defined"]
- When "mode" is set, return rooms with selected game mode only.
- When joinable is true (by default), return rooms with room status 0 only. Else return rooms regardless of condition.
- return: array, format:[["name"=>"(string, room name)", "mode"=>"(string, game mode)"],...]

### checkJoinRoomCondition($rooms, $data, $socketId)
Check if room is joinable for player.
- $rooms: array, room list ($this->roms) 
- $data: array, data from decrypted token string, format: []
- $socketId: string, socket id
- return ["result"=>false, "room"=>$roomName, "devError"=> "[checkJoinRoomCondition] room not exist"] if room name not exist in room list.
- return ["result"=>false, "room"=>$roomName, "devError"=> "[checkJoinRoomCondition] room full"] if room has number of players more than maxPlayers.
- return ["result"=>false, "room"=>$roomName, "devError"=> "[checkJoinRoomCondition] game already started"] if room status is not 0.
- else return ["result"=>true, "room"=>$roomName, "error"=> ""]

### findRoomBySocketId ($rooms, $socketId)
Find joined room name from player socket id.
- $rooms: array, room list ($this->roms) 
- $socketId: string, socket id
- return ["result"=>true, "room"=>$name] if player joined room.
- return ["result"=>false, "room"=>null, "error"=>"findRoomBySocketId: room not joined"] if player has not joined any room

### checkRoomStatus($room, $mode, $status)
Check room has certain status.  
Room status:  
0: Created  
1: Game started   
- $room: roomModel object, e.g. $this->rooms["(room name)"]
- $mode: string, must be one of followings: "==" / ">=" / "<="
- $status: number, compare player status to this number
- return: ["result"=>true] if meets condition  
["result"=>false, "message"=>"(message)"] if condition not met   


### changeRoomStatus($roomInstance)
Checks and promotes room status by one level.
Status 0: check if room has at least one player, promote status to 1. 
- $roomInstance: roomModel object, e.g. $this->rooms["(room name)"]
- return: playerModel object

## Timer library functions
Creates countdown instruction.
Action name should be "setCountdown" for new emitAction or "startNewCountdown" when inside intervalAction/endAction of a countdown.

### forceStartCountdown($room)
Countdown time according to game "forceStartTime" to start game. (30s if forceStartTime is undefined)
- $room: string, room name
- return timer instructions array when success. 
  
interval action: 
- if room no longer exists, emit error and delete timer
- if game already started, delete timer
- otherwise no additional action but emit count only.
  
end action: 
- if room no longer exists, emit error and delete timer
- if game already started, delete timer
- otherwise call startGame in serverLibrary to start game and start gameCountdown by startNewCountdown


### gameCountdown($room)
Countdown time when game is running. (180s if gameTime is undefined)
- $room: string, room name
- return timer instructions array when success. 
  
interval action: 
- calls robotAction in game library.
- calls interactTimerAction in interact model.
- if room no longer exists, emit error "[gameCountdown] room not exist, delete countdown" and delete timer.
- otherwise no additional action but emit count only.
  
end action: 
- if room no longer exists, emit error "[gameCountdown] room not exist, delete countdown".
- otherwise, emit end game message by calling $gameLib->endGameActions. 
- end game result will be draw by default but could be changed in game mode by checking $checkWin["timeout"] in game library endGameActions function.

### handleTimerId($timerName, $timerId,$socketId)
Handles timer id after timer has been created.
- $timerName: string, timer name
- $timerId: number/string, timer id
- $socketId: string, socket id of player
- return: array, socket instruction
  
For timer name "forceStartCountdown" and "gameCountdown", timerId will be stored in $timerId in room instance of player and return empty instruction array.  
For other timer name, empty instruction array will be returned directly


## Game Library functions
In this section, getGameLib and createGameInstance will be discussed as these function is used for game library solely.
For other functions, please see "Custom game mode" section.
### getGameLib($game)
Get game library according to $game->mode in game instance.
- $game: game instance, $this->rooms[$room]->game for most cases.
- return: game library instance according to game mode (e.g. ticLibrary)
If invalid game mode type, return error message ["action"=>"emit","name"=>"game","data"=>["action"=>"getGameLib", "data"=>["result"=>false],"message"=>"", "error"=>"Invalid game mode.", "devError"=>"Invalid game mode ".$mode]]; 

### createGameInstance($data)
Create game model instance according to $data["mode"].
- $data: array, data from decrypted token string
- return: ["result"=>true, "game"=>(game model instance)] if game mode is valid.  
 returns error message ["result"=>false, "error"=>"...", "devError"=>"..."] if game mode is invalid.

## Custom game mode
### Create new game mode
To create game mode to system, new library and model could be created for particular game mode.  
The library must be extend "gameLibrary" and model must extend "gameModel".  
For example if created a new game mode called "dice" is needed, first create libary "diceLibrary"
```
In Libary/custom/diceLibrary.php:

<?php 
namespace Library;
use Model\diceModel;

class diceLibrary extends gameLibrary{ 
    function startGame(&$roomInstance){return [];}
    function checkWin($gameInstance,$socketId){}
    function interactProceedingAction(&$roomInstance,$socketId,&$interactResult){return [];}
    function doRobotAction($robotInstance,&$roomInstance){return [];}
    function endGameActions($checkWin,&$roomInstance){return [];}
    function switchGameAction($token,$socketId,&$roomInstance){
        $instructions = [];
        switch($token["action"]){
            default: $instructions = [["action"=>"emit","name"=>"game","data"=>["action"=>"switchGameAction", "data"=>"","message"=>"", "error"=>"(error message)", "devError"=>"(error message for developer)"]]];
        }
        return $instructions;
    }
}
```

Then create new model "diceModel" for holding data for new game mode.
Instance variables could be added to model for holding game data.
Note that constructor is called before room instance has created. If particular variable depends on players, seperate function should be used instead.
Functions could be added if neccessary but should be concise. (e.g. intiating player roles, intiating score when game has started)
Format: 
```
In Model/custom/diceModel.php:

<?php 
namespace Model;

class diceModel extends gameModel{
    public $mode = "dice";
    function __construct($data){
    }
}
```

Then in Library/general/gameLibrary.php, add following code to createGameInstance() for switching to "diceModel"
```
case "dice": return ["result"=>true, "game"=>new diceModel($data)];
```
And add following code to getGameLib() in the same file for switching to "diceLibrary"
```
case "dice": return new diceLibrary();
```

### Custom Library functions
Add actions to the following functions. Custom functions could also be added to library.

#### startGame(&$roomInstance)
Actions to be done when starting the game (e.g. when force start countdown counts to 0 or all players presses start)  
- to-do recommendations: initiate player roles, initiate score, updating game model etc.
- return: array, socket instructions

#### checkWin($gameInstance,$socketId)
When player has action in game, check whether any players has won the game or whether the game has entered draw status
- to-do recommendations: check game model to check if any winning or draw conditions has met
- return: array, check win data, recommened format: ["win"=>(boolean), "draw"=>(boolean), "winnerId"=>(string or null), "winnerName"=>(string or null), "playerIds"=>(array or null), "playerNames"=>(array or null)]

#### interactProceedingAction(&$roomInstance,$socketId,&$interactResult)
When interact result has been received, do actions
- to do recommendations: update game model according to interaction result, check win if needed
- return: array, socket instructions

#### doRobotAction($robotInstance,&$roomInstance)
When action time in a particular robot has counted to 0, do following action for particular robot:
- to do recommendations: update game model, check win
- return: array, socket instructions

#### endGameActions($checkWin,&$roomInstance)
When game has ended (e.g. game time over, player has won, game enters draw state), do actions
- $checkWin: array, result from checkWin()
- to do recommendations: take data from game model and prepare end game message, change room status to 2
- return: array, socket instructions

#### switchGameAction($token,$socketId,&$roomInstance,$playerInfo)
When server receives a "game" message, switch to custom game library function and process token data
- to do recommendations: switch to functions for handling player input
- $token: array, decrypted token array
- return: array, socket instructions

#### Recommendations for custom functions
1. Check if room interact has interact token
2. Check if data has interact token, then check if valid or not
3. Update game model
4. Check win, end game if have winners
5. If no winners, remove interact token from game interact and get new trigger from interact

## Custom interact type
### Create new interact type
To create new interact type, a new model has to be created.
The model must extend "interactModel".
For example, if the new interact type is called "question", a new model "questionModel" has to be created in Model/custom
```
In Model/custom/questionModel.php:

<?php 

namespace Model;

use Library\keyLibrary;

class questionModel extends interactModel{
    public $type = "question";
    public $getTriggerUrl;
    public $checkResultUrl;
    public $interactToken = [];
    
    function __construct($data){
    }

    function getTrigger($playerInfo){
    }

    function manageInteractResult($playerInfo,$msg){
    }

    function getTriggerCount($playerInfo){
    }

    fuction interactTimerAction($i, &$roomInstance){

    }
}
```

- $type: string, interact type, e.g. "question"
- $getTriggerUrl: string, url to get trigger
- $checkResultUrl: string, url to check result
- $interactToken: array, interact tokens to indicate player has got expected result
- $expectedResult: string, expected result for reference

#### getTrigger($playerInfo)
Gets trigger for user interaction. e.g. get question for user to answer
- to do recommendations: get HTML from $getTriggerUrl using CURL, return HTML data
- return: array, format ["type"=>"(interact type)", "html"=>"trigger html"]

#### manageInteractResult($playerInfo,$msg)
$msg: "data" field of decoded token data. e.g. answers from user
- to do recommendations: set post data using $data, post to $getTriggerUrl using CURL, check if proceeding game action and new trigger is needed or not, add interact token if result is as expected
- return: array, ["proceedGameAction"=> (boolean), "ignoreGameAction"=>(boolean), "getTrigger"=> (boolean), "data"=>(result data)]
proceedGameAction: boolean,  pass value to interactProceedingAction in game library for action
ignoreGameAction: if true, ignores proceedGameAction, interactProceedingAction will not be called if true.
getTrigger: whether trigger should be get after getting result
data: result data from CURL post

#### getTriggerCount($playerInfo) *optional
Gets trigger count from database and return result if count has reached a point which game could be start.  
e.g. count the number of questions
- to do recommendations: find count of triggers from database using CURL, compare to number required in room, return result
- return: ["result"=>(boolean), "data"=>(any type), "message"=>"(message)", "error"=>"(error)"]

### interactTimerAction($i, &$roomInstance) *optional
Do actions when game timer ticks.
e.g. check if player has submitted result or not
- to do recommendations: check if player has submitted result, give timeout message to player if not and get new trigger
- return: array, array of socket instructions


