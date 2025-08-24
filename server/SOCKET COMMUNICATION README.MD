# Socket communication structure and I/O
1. Functions

## Functions
### init
#### $socket->on("init",input) -> To init player
* input (encrypted by emailEnc): 
```
 ["action"=>"init","data"=>["name"=>"kelly", "groupId"=>"1", "userToken"=>"demo",'dataPack'=>"whatever"]];
```
* output 
```
[action: "init" data=>[name: "Kelly", result: true, socketId: "22df7514adecd74104738277"]]
```

### room
#### $socket->on("room",input) -> do room actions
##### "addRoom" -> create room
* input (encrypted by resultEnc): 
```
[
	"action"=>"addRoom",
	"data"=>[
		"roomName"=> "sam",
		"groupId"=>"1",
		"mode"=> "tic",
		"minPlayers"=>2,
		"maxPlayers"=>2,
		"forceStartTime"=>10,
		"gameTime"=>180,
		"minRobotActionTime"=>5,
		"maxRobotActionTime"=>12,
		"interactType"=> "question",
		"getTriggerUrl"=>"localhost:8080/game/dummy/question",
		"checkResultUrl"=>"localhost:8080/game/dummy/answer",
		"requireTriggerCount"=>true,
		"minTriggerCount"=>10,
		"triggerCountUrl"=>"localhost:8080/game/dummy/getQuestionCount",
	]
];
```
* output: 
```
["action"=>"addRoom", "data"=>["result"=>true, "roomName"=>"sam", "mode"=>"tic"], "message"=>"", "error"=>""]
```

##### "joinRoom" -> join room
* input (encrypted by resultEnc):
```
[
	"action"=>"joinRoom",
	"data"=>[
		"groupId"=>session('groupId'),
		"roomName"=> $this->request->getPost('joinRoomName'),
		"mode"=> session('gameMode')
	]
];
```
* output
```
["action"=>"joinRoom", "data"=>["result"=>true, "room"=>"hi", "error"=>""], "message"=>"", "error"=>""] // to player only
["action"=>"newPlayer", "data"=>["name"=>"Sam"], "message"=>"", "error"=>""]  // to all players
["action"=>"showReadyStart", "data"=>["result"=>true], "message"=>"", "error"=>""] // to all players when no. of room players >= minPlayers
```

##### "getRoom" -> get room list
* input (encrypted by resultEnc or unencrypted)
```
 ["action"=>"getRoom", "data"=>["mode"=>"tic", "groupId"=>"1"]]
```
* output
```
["action"=>"getRoom", "data"=>["list"=>[["name"=>"sam", "mode"=>"tic", "groupId"=>1]]], "message"=>"", "error"=>""]
```

##### "getRoomPlayerList" -> get player list in room
* input (encrypted by resultEnc or unencrypted) 
```
["action"=>"getRoomPlayerList"]
```
* output
```
["action"=>"getRoomPlayerList", "data"=>["count"=>2, "playerList"=>["Kelly","Sam"], "ready"=>[false,true]], "message"=>"", "error"=>""]
```

##### "playerReadyStart" -> player ready to start game
* input (encrypted by resultEnc)
```
["action"=>"playerReadyStart", "data"=>["roomName"=>"hi"]]
```
* output: 
```
// room: ready start result, to all players
[
	"action"=>"playerReadyStart",
	"data"=>["result"=>true, "room"=>"hi", "name"=>Sam], 
	"message"=>"",
	"error"=>""
]
```
// game: start game message, to all players when all players are ready (data for example only)
[
	"action"=>"startGame", 
	"data"=>[
				"result"=> true,
				"gameMode"=>"tic",
				"connectedPoints"=>3,
				"gamePad"=>["", "", "", "", "", "", "", "", ""],
				"lines"=>[[0, 1, 2],[3, 4, 5],[6, 7, 8],[0, 3, 6],[1, 4, 7],[2, 5, 8],[0, 4, 8],[2, 4, 6]],
				"mySymbol"=>"○",
				"size"=>3, 
				"symbols"=>[["Sam", "○"],["Kelly", "×"]]
			], 
	"message"=>"",			
	"error"=>""
]

//interact: get trigger message, to all players when all players are ready
```
[
	"action"=>getTrigger"
	"data"=>[
				"type"=>"question",
				"html"=>"<!-- DEBUG-VIEW START 1 APPPATH/Config/../Views/dummy/question.php --><div class="container-fluid">..."
			]
	"message"=>"",
	"error"=>""
]
```
### game
#### $socket->on("game",input) -> do game custom actions based on switchGameAction in game libraries

### interact
#### $socket->on("interact",input) -> do room actions
##### "manageInteractResult" -> send result to interact to manage
input (encrypted by resultEnc or unencrypted, data for sample only):
```
[
	"action"=>"manageInteractResult", 
	"data"=>[
				"answers"=>["1"],
				"questionId"=>"1",
				"questionType"=>"mc"
			]
]
```

output (data for example only):
```
[
	"action"=>"getResult"
	"data"=>[
				"userAnswer"=>["1"],
				"result"=>"Correct",
				"token"=>"DZjB/1U8aBDsAR0/p+N8KdYzyEdqU/7hv07J862n9BN425QGteaccuuEr8JYUxGIfWUVy+u/cXjR872NZ+is7c0WGbmZfAtBOX0oZxY73RiI6c581e/Akn2APTlBkTPViYkL7FC97jmO73kbGZbbAA=="
			]
	"message"=>"",
	"error"=>""
]
```
