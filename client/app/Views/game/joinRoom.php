<?= $this->extend('layout/layout') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    
    <div class="row text-center">
        <div class="col-12">
            <h5 class="page-title mb-3">Join Room</h5>
        </div>
    </div>
    
    
    <div class="row joinCreateRow">
        <div class="card col-md-6 pt-1 pb-1 pt-md-3 pb-md-3">
            <!-- Room Table -->
            <div class="row text-center">
                <div class="col-12">
                    <h5 class="col-title mb-3">Available Rooms</h5>
                </div>
            </div>

            <div class="row text-center">
                <div class="col-12 roomTableColumn">
                    <table class="table">
                        <thead class="roomTableHead">
                            <tr>
                                <th class="pt-2 pb-2">
                                    Name
                                </th>
                                <th class="pt-2 pb-2">
                                    Mode
                                </th>
                                <th class="pt-2 pb-2">
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="table-active search">
                            <tr>
                                <td></td>
                                <td>
                                    <select id="filterMode" class="form-control filterMode">
                                        <option value="">-- All --</option>
                                        <?php if (isset($gameModes)){
                                            foreach ($gameModes as $gm){
                                                ?>
                                            <option value="<?=$gm["code"]?>"><?=$gm["name"]?></option>
                                            <?php
                                            }
                                        }?>
                                    </select>
                                </td>
                                <td>
                                    <a class="btn btn-info" href="#" onclick="getRoom()" role="button">Reload</a>
                                </td>
                            </tr>
                        </tbody>
                        <form class="joinRoomForm" action='/game/room' method='post'>
                            <input type="hidden" class="joinRoomName" name="joinRoomName" value="">
                            <tbody class="roomList">
                            </tbody>
                        </form>
                    </table>
                </div>
            </div>
        </div>

        <div class="card col-md-6 text-center mt-0 pt-1 pb-1 pt-md-3 pb-md-3">
            <!-- Create Room -->
            <div class="row text-center">
                <div class="col-12">
                    <div class="row text-center">
                        <div class="col-12">
                            <h5 class="col-title mb-3">Create Room</h5>
                        </div>
                    </div>
                </div>
            </div>

            <form action='/game/room' method='post'>
                <div class="row">
                    <div class="col-5 text-right"> 
                        <label for="createRoomNam">Room Name</label>
                    </div>
                    <div class="col-7 text-left">
                        <input type="input" class="form-control submitField mb-1 mb-md-3" id='createRoomName' name='createRoomName'/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5 text-right">
                        <label for="createGameMode">Game Mode</label>
                    </div>
                    <div class="col-7 text-left">
                        <select id="createGameMode" class="form-control submitField mb-1 mb-md-3" name='createRoomMode' onchange="changeAdvancedSettings()">
                            <option value="">-- Select --</option>
                            <?php if (isset($gameModes)){
                                foreach ($gameModes as $gm){
                                    ?>
                                <option value="<?=$gm["code"]?>"><?=$gm["name"]?></option>
                                <?php
                                }
                            }?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-center">
                        <div id="advancedSettings" class="pb-2 pb-md-3 collapse">
                            <div class="noMode-advancedSettings">
                                <div class="row">
                                    <div class="col-12 text-center">
                                        (Please select game mode first)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-5 text-right">
                        <label for="createGameMode">Interact Type</label>
                    </div>
                    <div class="col-7 text-left">
                        <select id="createInteractType" class="form-control submitField mb-1 mb-md-3" name='createRoomInteract'>
                            <option value="">-- Select --</option>
                            <?php if (isset($interactTypes)){
                                foreach ($interactTypes as $it){
                                    ?>
                                <option value="<?=$it["code"]?>"><?=$it["name"]?></option>
                                <?php
                                }
                            }?>
                        </select>
                    </div>
                </div>


                <div class="row">
                    <div class="col-12 text-center">
                        <a href="#" class="btn btn-lg btn-info submitButton" data-target="#advancedSettings" data-toggle="collapse" aria-expanded="false" aria-controls="my-collapse">Game Settings</a>
                    </div>
                </div>
                

                <div class="row mt-3">
                    <div class="col-12 text-center">
                        <input type="submit" class="btn btn-lg btn-info submitButton" value="Submit">
                    </div>
                </div>
            </form>
        </div>      
    </div>
    
    <div class="row text-center">
        <div class="col-12">
            <button class="btn btn-lg btn-danger disconnectButton" onclick="disconnect()">Disconnect</button>
        </div>
    </div>
</div>


<!-- advanced settings storage -->
<div class="settingsSelect d-none">
    <div class="noMode-advancedSettings">
        <div class="row">
            <div class="col-12 text-center">
                (Please select game mode first)
            </div>
        </div>
    </div>
    <div class="noSettings-advancedSettings">
        <div class="row">
            <div class="col-12 text-center">
                (No settings available)
            </div>
        </div>
    </div>
    <?= $this->include('partial/gameModeFields') ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('script') ?>
<script src ='https://cdnjs.cloudflare.com/ajax/libs/socket.io/1.3.7/socket.io.min.js'></script>
<script>
    var socket = io ('http://127.0.0.1:2021');
    var gameModes = <?=json_encode($gameModes)?>;
    var debugMode = <?=env('CI_ENVIRONMENT')=="development"?"true":"false"?>;

    socket.on ( 'init' , function (msg){
        if (debugMode) console.log(msg);
        //getRoom();
    });

    (function() {
        //init()
        <?php if (isset($initString)) {
             echo "socket.emit ('init','" . $initString. "');"; } ?>
    })();

    function getRoom(){
	    // send get room request to socket
	    mode = $(".filterMode").val();
        socket.emit ('room',{action:"getRoom", data:{mode:mode <?=isset($groupId)?", groupId:\"".$groupId."\"":""?>}});
		$(".roomList").html(`<tr><td colspan="3" class="emptyMessage">Loading...</td></tr>`);
	}


    socket.on ( 'room' , function (msg){
        if (debugMode) console.log(msg);
        $(".roomlist").html('');
        switch (msg.action){
            case "getRoom": showRoomList(msg); break;
            default: break;
        }   
    });


    // show room list
    function showRoomList(msg){
        table = "";
        if (msg.data.list.length == 0){
            table += '<tr><td colspan="3" class="emptyMessage">No rooms available now. Please create room.</td></tr>';
        }else{
            for (i=0; i<msg.data.list.length; i++){
                mode = msg.data.list[i].mode;
                modeName = "(Unknown)"
                for (j=0; j<gameModes.length; j++){
                    if (mode == gameModes[j].code){
                        modeName = gameModes[j].name;
                        break;
                    }
                }
                table += '<tr class="roomListRow"><td>'+msg.data.list[i].name+'</td><td>'+modeName+'</td>';
                table += '<td><a class="btn btn-info" href="#" role="button" onclick="joinRoom(\''+msg.data.list[i].name+'\')">Join</a></td></tr>';
	        }
        }
	    $(".roomList").html(table);
    }

    // join room
    function joinRoom(roomName){
        $(".joinRoomName").val(roomName);
        $(".joinRoomForm").submit();
    }


    // change advanced settings
    function changeAdvancedSettings(){
        $("#advancedSettings").html("");
        gameMode = $("#createGameMode").val();

        if ( gameMode == ""){
            $(".noMode-advancedSettings").clone().appendTo("#advancedSettings");
        } else if ( $(".advancedSettings-"+gameMode).length == 0 ){
            $(".noSettings-advancedSettings").clone().appendTo("#advancedSettings");
        } else {
            $(".advancedSettings-"+gameMode).clone().appendTo("#advancedSettings");
        }
    }

    // disconnect
    function disconnect(){
        socket.disconnect();
        window.location.href = "/game/";
    }

</script>

<style>
    /* All screen sizes */
    .page-title{
        font-size: 2rem;
        font-weight: 300;
    }

    .col-title{
        font-size: 1.5rem;
        font-weight: 300;
    }

    .roomTableHead th{
        font-size: 1.25rem;
        font-weight:300;
        background-color: #17a2b8;
        color: white;
    }

    .disconnectButton{
        font-size: 1.25rem;
        font-weight: 300;
        margin-top: 1.5rem;
        line-height: 1.5;
    }

    .joinCreateRow{
        min-height: 50vh;
    }

    .submitField{
        width: 75%;
        margin-bottom: 1.5rem;
    }

    .submitButton{
        font-size: 1.25rem;
        font-weight: 300;
        line-height: 1.5;
    }

    .emptyMessage{
        font-size: 1.25rem;
        font-weight: 300;
    }

    .roomListRow{
        font-size: 1.25rem;
        font-weight: 100;
    }

    .createRoomCard{
        width: 100vw;
        font-size: 1.25rem;
        font-weight: 300;
        margin-top: 0.5rem;
    }

    .submitButton{
        width: 25vw;
    }
    
    .disconnectButton{
        width: 40vw;
    }

    .noMode-advancedSettings, .noSettings-advancedSettings{
        font-size: 1.25rem;
        font-weight: 300;
    }

    /* small screen only */
    @media only screen and (max-width: 768px){
        .submitButton, .disconnectButton{
            width: 75vw;
        }

        .roomTableColumn{
            padding-left: 0;
            padding-right: 0;
        }
    }
</style>

<?= $this->endSection() ?>