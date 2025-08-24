<?php namespace App\Controllers;
use App\Libraries\keyLibrary;
use CodeIgniter\Pager\Pager;

class GameController extends BaseController
{
	public $gameModes;
	public $interactTypes;

	public function __construct()
	{
		$this->gameModes = [
			[
				"code"=>"tic",
				"name"=>"Tic-tac-toe",
				"settings"=>[
					[
						"prop"=>"size",
						"name"=>"Board Size",
						"type"=>"number",
						"value"=>3,
						"min"=>3,
						"max"=>7
					],
					[
						"prop"=>"connectedPoints",
						"name"=>"Connected squares to win",
						"type"=>"number",
						"value"=>3,
						"min"=>3,
						"max"=>7,
						"mustSmallerThan"=>"size"
					]
				]
			],
			[
				"code"=>"bull",
				"name"=>"Bull fight",
				"settings"=>[
					[
						"prop"=>"winPoints",
						"name"=>"Points to win",
						"type"=>"number",
						"value"=>10,
						"min"=>5,
						"max"=>15
					],
					[
						"prop"=>"robotCorrectProbability",
						"name"=>"Robot mode difficulty",
						"type"=>"number",
						"value"=>70,
						"min"=>50,
						"max"=>100
					]
				]
			]
		];


		$this->interactTypes = [
			[
				"code"=>"question",
				"name"=>"Question and Answer"
			],
			[
				"code"=>"draw",
				"name"=>"Draw and Guess"
			]
		];
	}

	public function test()
	{
		return view('game/test');
	}

	public function index()
	{
		if ($this->request->getMethod() == 'post') {
			$data['playerName'] = $this->request->getPost('playerName');
			//$data["groupId"] = $this->request->getPost('groupId'); //use input field for test only, please get group id from database
			session()->set('playerName', $data['playerName']);
			session()->set('userToken', 'demo');
			session()->set('groupId', 'demoGroup');
			//session()->set('groupId', $data["groupId"]);

			return redirect()->to('/game/joinRoom');
		}else{
			return view('game/name');
		}
	}

	public function joinRoom(){
		// set user group
		session()->remove("roomName");
		if (session('playerName')){
			$keyLib = new keyLibrary();
			$initString = [
				"action"=>"init",
				"data"=>[
					"name"=>session('playerName'),
					"groupId"=>session('groupId'),
					"userToken"=>session('token'),
					'dataPack'=>"whatever"
				]
			];
			$data["playerName"] = session('playerName');
			$data['initString'] = $keyLib->emailEnc(json_encode($initString));
			$data["groupId"] = session('groupId');
			$data["gameModes"] = $this->gameModes;
			$data["interactTypes"] = $this->interactTypes;

			return view('game/joinRoom', $data);
		}else{
			return redirect()->to('/game');
		}
	}

	public function room(){
		if ($this->request->getMethod() == 'post') {
			$keyLib = new keyLibrary();

			// init string
			if (empty($this->request->getPost('createRoomName'))&& empty($this->request->getPost('joinRoomName'))){
				return redirect()->to('/game/joinRoom');
			}

			// set roomname session
			if (!empty($this->request->getPost('joinRoomName'))){
				session()->set('roomName', $this->request->getPost('joinRoomName'));
			}else if (!empty($this->request->getPost('createRoomName'))){
				session()->set('roomName', $this->request->getPost('createRoomName'));
			}

			if (!empty($this->request->getPost('createRoomMode'))){
				$gameMode =  $this->request->getPost('createRoomMode');
			}else $gameMode = "";

			if (!empty($this->request->getPost('createRoomInteract'))){
				$interactType =  $this->request->getPost('createRoomInteract');
			}else $interactType = "";

			// set game mode and interact type
			session()->set('gameMode', $gameMode);
			session()->set('interactType', $interactType);

			// get draw category if interact type is draw
			session()->set('drawCategory', "2");

			// init socket user
			if (session('playerName')){
				$initString = [
					"action"=>"init",
					"data"=>[
						"name"=>session('playerName'),
						"groupId"=>session('groupId'),
						"userToken"=>session('token'),
						'dataPack'=>"whatever"
					]
				];
				$data['initString'] = $keyLib->emailEnc(json_encode($initString));
			}

			// add room string
			if (!empty($this->request->getPost('createRoomName'))){
				$addRoomString = [
					"action"=>"addRoom",
					"data"=>[
						// room properties
						"roomName"=> $this->request->getPost('createRoomName'),
						"groupId"=>session('groupId'),

						// game properties
						"mode"=> session('gameMode'),
						"minPlayers"=>2,
						"maxPlayers"=>2,
						"forceStartTime"=>40,
						// "gameTime"=>180,

						// robot mode properties
						"minRobotActionTime"=>5,
						"maxRobotActionTime"=>12,
					]
				];

				// interact properties
				switch (session("interactType")){
					case "question":
						$interactProperties = [// interact properties
						"interactType"=> session("interactType"),
						"getTriggerUrl"=>base_url()."/game/dummy/question",
						"checkResultUrl"=>base_url()."/game/dummy/answer",
						"requireTriggerCount"=>true,
						"minTriggerCount"=>10,
						"triggerCountUrl"=>base_url()."/game/dummy/getQuestionCount",
						"gameTime"=>180,
						]; break;

					case "draw":
						$interactProperties = [// interact properties
							"interactType"=> session("interactType"),
							"getItemUrl"=>base_url()."/game/dummyDraw/getItem",
							"submitDrawUrl"=>base_url()."/game/dummyDraw/submitDraw",
							"getDrawUrl"=>base_url()."/game/dummyDraw/getDraw",
							"submitChoiceUrl"=>base_url()."/game/dummyDraw/submitChoice",
							"getCountUrl"=>base_url()."/game/dummyDraw/getItemCount",
							"category"=>session("drawCategory"),
							"requireTriggerCount"=>true,
							"minTriggerCount"=>3,
							"selectTime"=>30,
							"drawTime"=>30,
							"guessTime "=>15,
							"gameTime"=>600,
						]; break;

					default: $interactProperties = []; break;
				}
				$addRoomString["data"] =  array_merge($addRoomString["data"], $interactProperties);

				$addRoomString = $this->gameSettings($addRoomString);
				$data['addRoomString'] = $keyLib->resultEnc(json_encode($addRoomString));
			}

			// join room string
			if (!empty($this->request->getPost('joinRoomName'))){
				$joinRoomString = [
					"action"=>"joinRoom",
					"data"=>[
						"groupId"=>session('groupId'),
						"roomName"=> $this->request->getPost('joinRoomName'),
						"mode"=> session('gameMode')
					]
				];
				$data['joinRoomString'] = $keyLib->resultEnc(json_encode($joinRoomString));
			}

			// ready start string
			if (!empty($this->request->getPost('joinRoomName'))){
				$readyStartString = [
					"action"=>"playerReadyStart",
					"data"=>[
						"roomName"=> $this->request->getPost('joinRoomName')
					]
				];
				$data['readyStartString'] = $keyLib->resultEnc(json_encode($readyStartString));
			}else if (!empty($this->request->getPost('createRoomName'))){
				$readyStartString = [
					"action"=>"playerReadyStart",
					"data"=>[
						"roomName"=> $this->request->getPost('createRoomName')
					]
				];
				$data['readyStartString'] = $keyLib->resultEnc(json_encode($readyStartString));
			}

			return view('game/room', $data);
		}else
		{
			if (session('playerName')){
				return redirect()->to('/game/joinRoom');
			}else{
				return redirect()->to('/game');
			}
		}
	}

	function result(){
		// end game, show result and also update player information
		if (empty(session("roomName"))){
			log_message("error","Result:  room name missing / from player: ".session('playerName'));
			return redirect()->to('/game/joinRoom');
		} else{
			$room = session("roomName");
			session()->remove("roomName");
		}
		
		if (empty($this->request->getPost('gameResult'))){
			log_message("error","Result: result string missing / in room: ".$room." / from player: ".session('playerName'));
			return redirect()->to('/game/joinRoom');
		}else {
			$resultString = $this->request->getPost('gameResult'); 
		}

		if (empty($this->request->getPost('additionalInfo'))){
			$additionalInfo = "null";
		}else{
			$additionalInfo = $this->request->getPost('additionalInfo');
		}

		$keyLib = new keyLibrary();
		$result = json_decode($keyLib->resultDec($resultString),true);
		//log_message("info","Game result: ".\json_encode($result));

		// check if result data has room name
		if (!isset($result["roomName"])){
			log_message("error","Result: Invalid result data / in room: ".$room." / from player: ".session('playerName') ."/ result data: ".json_encode($result));
			return redirect()->to('/game/joinRoom');
		}else if ($result["roomName"] != $room){
			log_message("error","Result: Invalid room in result data / in room: ".$room." / from player: ".session('playerName')."/ result data: ".json_encode($result));
			return redirect()->to('/game/joinRoom');
		}

		// add result to database 
		// ...

		// display result
		return view('game/result', ["result"=>$result, "additionalInfo"=>$additionalInfo]);
	}

	private function gameSettings($addRoomArray){
		//add game settings to add room parameters
		$post = $this->request->getPost();
		$mode = $addRoomArray["data"]["mode"];

		if (in_array($mode,array_column($this->gameModes,"code"))){
			$index = array_search($mode,array_column($this->gameModes,"code"));
			foreach ($this->gameModes[$index]["settings"] as $setting){
				if(!empty($post[$setting["prop"]])){
					$postValue =  $post[$setting["prop"]];
					switch ($setting["type"]){
						case "number": // number, check if number is in range
							if (isset($setting["min"])){
								if ($postValue < $setting["min"]) $postValue = $setting["min"];
							}

							if (isset($setting["max"])){
								if ($postValue > $setting["max"]) $postValue = $setting["max"];
							}

							if (isset($setting["mustSmallerThan"])){
								if ($postValue > $addRoomArray["data"][$setting["mustSmallerThan"]]) $postValue = $addRoomArray["data"][$setting["mustSmallerThan"]];
							}

							if (isset($setting["mustLargerThan"])){
								if ($postValue < $addRoomArray["data"][$setting["mustLargerThan"]]) $postValue = $addRoomArray["data"][$setting["mustLargerThan"]];
							}

							$addRoomArray["data"][$setting["prop"]] = $postValue;
							break;


						// other types, just add value to array
						default:  $addRoomArray["data"][$setting["prop"]] = $postValue;
					}
				}else{
					// no value, use default value instead
					$addRoomArray["data"][$setting["prop"]] = $setting["value"];
				}
			}
		}
		return $addRoomArray;
	}
}