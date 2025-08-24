define(["jquery"],function ($) {
    return{
        showRoomUser:function(msg){
            players = "<br/><table class='table table-sm'><thead><tr><th>Player</th><th>Ready</th>";
            // players += "<th></th>";
            players += "</tr></thead><tbody>";
            for (i=0; i<msg.data.playerList.length; i++){				
                players += "<tr><td>"+msg.data.playerList[i]+"</td>";
                ready = ""; if (msg.data.ready[i]) ready = "✓"; 
                players += "<td>"+ready+"</td>";
                // players += "<td><input type='hidden' class='form-control'name='playerName' value='"+ msg.data.playerList[i] +"'></td>";
                players += "</tr>";
            }
            players += "</tbody></table>";
            return players;
        },
        // display countdown
        displayCountdown:function(msg){
            countdowntext = "";
            if (msg!=null && msg.data.status == "counting"){
                switch (msg.data.name){
                    case "robotModeCountdown": countdowntext = "Start game after " + msg.data.count + " seconds."; break;
                    case "forceStartCountdown": countdowntext = "Start game after " + msg.data.count + " seconds."; break;
                    case "gameCountdown": 
                    if (!isNaN(msg.data.count)){
                        min = Math.floor(msg.data.count/60); if(min < 10) min = "0"+min;
                        sec = msg.data.count%60; if (sec<10) sec =  "0"+sec;
                        countdowntext = "Remaining Time: "+min+":"  +sec;
                    } break;
                }
            }
            return countdowntext;
        }
    };
});