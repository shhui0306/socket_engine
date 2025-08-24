define([
    'jquery',
    'socket',
    'app/controller/ticController',
    'app/controller/questionController',
    'app/controller/drawController',
    'app/controller/bullController',
    'app/asset/roomComponents',
    ], function ($,io,ticController, questionController, drawController, bullController, roomComponents) {
    
    var socket = io('http://127.0.0.1:2021');
    var gameOver = false;
    
    //init()
    if (typeof(initString)!="undefined"&& initString !==null) {
        if (debugMode) console.log(initString);
        socket.emit ('init',initString);
    }else if (gameResult !== null){
        showResult(gameResult);
    }

    socket.on ('init' , function (msg){
        if (debugMode) console.log(msg);
        if (typeof (addRoomString)!="undefined"&& addRoomString!==null) {
            socket.emit ('room',addRoomString);
        }
        if (typeof (joinRoomString)!="undefined"&& joinRoomString!==null) {
            socket.emit ('room',joinRoomString);
        }
   });

    // get room player list
    function getRoomPlayerList(){
        if (debugMode) console.log("Get room player list");
        socket.emit("room",{action:"getRoomPlayerList"});
    }

   // ready start
   function sendReadyStart(){
       if (debugMode) console.log("'Ready' pressed");
        if (typeof (readyStartString)!="undefined"&& readyStartString!==null) {
           socket.emit('room',readyStartString);
        }
       $(".readyStart").hide();
   }

   $('.readyStart').on('click', function(){
    sendReadyStart();
   })

    socket.on ('room' , function (msg){
        if (debugMode) console.log(msg);
        if(msg.error){
            $('.gameError').html(msg.error);
        }
        switch (msg.action){
            case "addRoom": addRoomMessage(msg); break;
            case "joinRoom": joinRoomMessage(msg); break;
            case "newPlayer":  newPlayer(msg); break;
            case "playerReadyStart": readyStartMessage(msg); break;
            case "playerLeave":  playerLeave(msg); break;
            case "getRoomPlayerList": showRoomUser(msg); break;
            case "showReadyStart": showReadyStart(msg); break;
            default: break;
        }   
    });
    
    socket.on ( 'timer' , function (msg){
        if (debugMode) console.log(msg);
        switch(msg.action){
            case "setCountdown": displayCountdown(msg); break;
            default: break;
        }
    });
    
    // show add room message
    function addRoomMessage(msg){
        if (msg.data.result){
            $(".messageCard").show();
            today = new Date();
            time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
            message = "<p class='systemMessage text-info'>"+time+" - Room \""+msg.data.roomName+"\" has created.</p>";
            $(".roomMessages").prepend(message);
        }
    }

    // show join room message
    function joinRoomMessage(msg){
        if (msg.data.result){
            $(".messageCard").show();
            today = new Date();
            time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
            message = "<p class='systemMessage text-info'>"+time+" - You have joined room \""+msg.data.room+"\".</p>";
            $(".roomMessages").prepend(message);
        }
    }
    

    // show player leave message and update player list
    function playerLeave(msg){
        today = new Date();
        time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
        message = "<p class='systemMessage text-info'>"+time+" - "+msg.data.name+" has left room.</p>";
        $(".roomMessages").prepend(message);
        getRoomPlayerList();
    }

    // show player ready message and update player list
    function readyStartMessage(msg){
        today = new Date();
        time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
        message = "<p class='systemMessage text-info'>"+time+" - "+msg.data.name+" is ready.</p>";
        $(".roomMessages").prepend(message);
        getRoomPlayerList();
    }

    // show new player message and update player list
    function newPlayer(msg){
        today = new Date();
        time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
        message = "<p class='systemMessage text-info'>"+time+" - "+msg.data.name+" has joined room.</p>";
        $(".roomMessages").prepend(message);
        getRoomPlayerList();
    }

    // show room user
    function showRoomUser(msg){
        $('.inRoom').html(roomComponents.showRoomUser(msg));
    }
    
    // display countdown
    function displayCountdown(msg){
        if (!gameOver) $(".gameMessage").text(roomComponents.displayCountdown(msg));
    }
    
    function showReadyStart(msg){
        if (msg.data.result){
            // show ready start button
            $(".readyStart").show();
        }else{
            // hide ready start button
            $(".readyStart").hide();
        }
    }

    // game
    var gameController = null;
    function initgameController(msg){
        switch (msg.data.gameMode){
            case "tic":   gameController = ticController; break;
            case "bull": gameController = bullController; break;
            default: break;
        }
        if (gameController != null){
            gameController.init(socket);
            gameController.debugMode = debugMode;
        }
    }
    
    socket.on ( 'game' , function (msg){
        if (debugMode) console.log(msg);
        switch (msg.action){
            case "startGame": startGameMessage(); initgameController(msg); gameController.startGame(msg); break;
            default: if (gameController != null){
                gameController.socketAction(msg);
            }else{
                if (debugMode) console.log({devError:"gameController not initiated"});
            }break;
        }   
    });

    // show start game message
    function startGameMessage(){
        $(".gameCard").addClass("card");
        today = new Date();
        time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
        message = "<p class='systemMessage text-info'>"+time+" - Game has started.</p>";
        $(".roomMessages").prepend(message);
    }

    // show game result in result page
    function showResult(gameResult){
        initgameController({data:gameResult.gameData});
        gameController.showResult(gameResult);
    }
    
    // interact
    var interactToken = null;
    var interactController = null;
    var submitCount = 0;

    function initinteractController(msg){
        switch (msg.data.type){
            case "question": interactController = questionController;  break;
            case "draw": interactController = drawController; break;
            default: break;
        }
        if (interactController != null){
            interactController.init(socket);
            interactController.debugMode = debugMode;
        }
    }
    
    socket.on ( 'interact' , function (msg){
        if (debugMode) console.log(msg);
        if (interactController == null) initinteractController(msg);
        switch (msg.action){
            case "getTrigger": interactController.getTrigger(msg); break;
            default: if (interactController != null){
                interactController.socketAction(msg);
            }else{
                if (debugMode) console.log({devError:"interactController not initiated"});
            }break;
        }   
    });
    
    $(".interact").on("submit","#interactForm",function(e){
            if (debugMode) console.log("interact form submitted");
            $(".interact").html("");
            $(".interact").hide();
            $(".waitInteractMessage").text("Please wait...");
            $(".waitInteract").show();
            interactController.submitForm(e);
    }); 

    // chat
    $(".sendChat").on('click',function(){
        sendChat();
    });

    $(".chatText").keyup(function(e){
        if(e.keyCode == 13) sendChat();
    });

    function sendChat(){
        chatText = $(".chatText").val();
        if (chatText != ""){
            if (debugMode) console.log("send chat message: "+chatText);
            socket.emit("chat",{action:"sendChat", data:{message:chatText}});
            $(".chatText").val("");
        }
    }

    function displayChatMessage(msg){
        if (msg.data.result){
            sender = msg.data.sender;
            chatMessage = msg.data.message;
            today = new Date();
            time = (today.getHours()<10?"0":"")+today.getHours()+":"+(today.getMinutes()<10?"0":"")+today.getMinutes()+":"+(today.getSeconds()<10?"0":"")+today.getSeconds();
            message = "<p class='systemMessage'>"+time+" ["+sender+"] - "+chatMessage+"</p>";
            $(".roomMessages").prepend(message);
        }else{
            if (debugMode) console.log("displayChatMessage: emit error")
            if (msg.error != ""){
                message = "<p class='systemMessage text-danger'>"+msg.error+"</p>";
                $(".roomMessages").prepend(message);
            }
        } 
    }

    socket.on ( 'chat' , function (msg){
        if (debugMode) console.log(msg);
        switch (msg.action){
            case "sendChat": displayChatMessage(msg); break;
            default: break;
        }   
    });

    // disconnect
    function disconnect(){
        socket.disconnect();
        window.location.href = "/game/";
    }
}); 