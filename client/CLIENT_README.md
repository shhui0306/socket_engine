# Socket client side readme

## JS structure and functions
### Structure
public
|--html
    |--game
|--images
|--js
    |--app
        |--asset
        |--controller
        |--main.js
    |--lib
        |--require.js
    |--app.js


## JS socket connection functions

### init
#### socket.emit ('init',initString); -> To init a player
* input:  ["action"=>"init","data"=>["name"=>session('nickname') ] ]

#### socket.on 'init' -> When receive init message
Check if addRoomString or joinRoomString is available in POST data.
Emit addRoomString or joinRoomString after receiving init message.

* socket.emit ('room',addRoomString); ->Add room
    * input:
``` 
	[
		"action"=>"addRoom",
		"data"=>[
			// room properties
			"roomName"=> $this->request->getPost('createRoomName'),
			"groupId"=>session('groupId')
			// game properties
			"mode"=> session('gameMode'),
			"minPlayers"=>2,
			"maxPlayers"=>2,
			"forceStartTime"=>10,
			"gameTime"=>180
			// robot mode properties
			"minRobotActionTime"=>5,
			"maxRobotActionTime"=>12
			// interact properties
			"interactType"=> session("interactType"),
			"getTriggerUrl"=>base_url()."/game/dummy/question",
			"checkResultUrl"=>base_url()."/game/dummy/answer",
			"requireTriggerCount"=>true,
			"minTriggerCount"=>10,
			"triggerCountUrl"=>base_url()."/game/dummy/getQuestionCount"
			// game mode settings 
			"size"=>3,
			"connectedPoints"=>3
		]
	]
```

* socket.emit ('room',joinRoomString);-> Join room
    * input:
```
	[
		"action"=>"joinRoom",
		"data"=>[
			"groupId"=>session('groupId'),
			"roomName"=> $this->request->getPost('joinRoomName'),
			"mode"=> session('gameMode')
		]
	]
```

### rooms
#### getRoomPlayerList() / socket.emit("room",{action:"getRoomPlayerList"})
Gets player list in room and updates to player list table.

#### playerReadyStart() / socket.emit('room',readyStartString);
When room is waiting for players, emit message to indicate player is ready to start game.

#### socket.on 'room' -> when receive room message
Check actions in room message and pass to functions accordingly.  
example message: 
```
	{
		action: "addRoom", 
		data: 	{
					result: true, 
					roomName: "sam", 
					mode: "tic"
				}, 
		message: "", 
		error: ""
	}
```

- action 'addRoom': addRoomMessage(), show add room message
- 'joinRoom': joinRoomMessage(), show join room message
- 'newPlayer': newPlayer(), show new player message, update player list
- 'playerReadyStart': readyStartMessage(), show player ready message, update player list
- 'playerLeave': playerLeave(), show player leave message, update player list
- 'getRoomPlayerList': getRoomPlayerList(), Update player list in room
- 'showReadyStart': showReadyStart(), Show/hide ready start button

### timer
#### socket.on 'timer' -> when receive timer message
Check actions in timer message and pass to functions accordingly.
example message: 
```
	{
		action: "setCountdown",
		 data:  {
					name: "forceStartCountdown",
					status: "counting", 
					count: 40
				}, 
		message: "",
		error: ""
	}
```

- 'setCountdown': display countdown in room

### game
#### socket.on  'game' -> when receive game message
Check actions in game message and pass to functions accordingly.
* example: 
```
	{
		action: "startGame", 
		data:	{
					result: true,
					connectedPoints: "3"
					gameMode: "tic",
					gamePad: ["", "", "", "", "", "", "", "", ""],
					lines: [[0, 1, 2],[3, 4, 5],[6, 7, 8],[0, 3, 6],[1, 4, 7],[2, 5, 8],[0, 4, 8],[2, 4, 6]],
					mySymbol: "○",
					size: "3",
					symbols: [["Sam", "○"], ["Tyson", "×"]]
				},
		message: "", 
		error: ""
	}
```

* actions
- 'startGame': initiates game controller according to game mode using initgameController(), then runs init() function in game controller.
- others: passes to socketAction() in game controller to switch to other functions in game controller.

### interact
#### socket.emit('interact',{action: 'manageInteractResult', data:{}}) ->  send interact result
Send interact result to server. result data varies from interact type.
* input:
```
 {
    action:"manageInteractResult", 
    data:{
		// result data
        answers: answers,
        questionId:questionId,
        questionType: questionType
    }
}
```

#### socket.on 'interact' -> when receive interact message
Check actions in interact message and pass to functions accordingly.  
If interact controller is null, init interact controller according to type first by initinteractController()  
example: 
```
{
	action: "getTrigger",
	data: 	{
				html: "<div class="container-fluid">..."
			}, 
	message: "", 
	error: ""
}
```

* action
- 'getTrigger': displayer interact trigger using getTrigger() function in interact controller.
- others: passes to socketAction() in interact controller to switch to other functions in interact controller.

### disconnect
#### disconnect() -> disconnect from socket server
disconnect socket from server, redirect to join room page.





