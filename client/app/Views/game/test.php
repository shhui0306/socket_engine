
    1. Init user<br>
    Name: <input type="text" class="playerName"><br>
    <button onclick="init();">Create User</button>
    <br>
    <br>
    2. Create Room<br>
    Name: <input type="text" class="name"><br>
    Mode: <input type="text" class="mode"><br>
    <button onclick="addRoom();">Create Room</button>
    <br>
    <br>
    3. Search Room<br>
    Filter by mode: <input type="text" class="filterMode"><br>
    <button onclick="getRoom();">Get Rooms</button>
    <br>
    <br>
    4. Join Room<br>
    Name: <input type="text" class="joinRoomName"><br>
    <button onclick="joinRoom();">Join Room</button><br>
    <button onclick="getRoomPlayerList();">Show players in room</button>


<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src ='https://cdnjs.cloudflare.com/ajax/libs/socket.io/1.3.7/socket.io.min.js'></script>
<script> 
var socket = io ('http://127.0.0.1:2021');

// init
function init(){
    name = $(".playerName").val()
    socket.emit ('init', {data:{name:name}});
}
socket.on ( 'init' , function (msg){
     console.log(msg);
});


// get room
function getRoom(){
    mode = $(".filterMode").val();
    socket.emit ('room',{action:"getRoom", data:{mode:mode}});
}

// add room
function addRoom(){
    name = $(".name").val();
    mode = $(".mode").val();
    socket.emit ('room',{action:"addRoom", data:{roomName:name, mode:mode}});
}

// join room
function joinRoom(){
    name = $(".joinRoomName").val();
    socket.emit ('room',{action:"joinRoom", data:{roomName:name}});
}

// get room player list
function getRoomPlayerList(){
    socket.emit("room",{action:"getRoomPlayerList"});
}

// receive get/add room result
socket.on ( 'room' , function (msg){
    console.log(msg);
    switch (msg.action){
        case "newPlayer": getRoomPlayerList(); break;
        case "playerLeave": getRoomPlayerList(); break;
        default: break;
    }
});
</script>