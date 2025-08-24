define(["jquery"], function ($) {
    return {
    gameUrl:"",
    alreadyStarthit:false,
    debugMode: false,

    init: function(socket){
      this.gameUrl = base_url + "/html/game/bull.html";
      this.socket = socket;
    },
  
    
    // socket actions
    startGame:function(msg,finishAction){
      // start game, get animation scripts and load game board

      $.when(
        $(".game").load(this.gameUrl),
        $.getScript(base_url+"/js/app/asset/bull/bullrush.js"),
        $.getScript(base_url+"/js/app/asset/bull/bullrush2.js"),
        $.getScript(base_url+"/js/app/asset/bull/bullinit.js"),
        $.getScript(base_url+"/js/app/asset/bull/bullmotion.js")
      ).done(function(){
          // display effects
          $(".fireworks").attr("src",base_url+"/images/bull/fireworks2.png");

          // init bull
          initBull();
          initBull2();
           
          //set info
          $(".winPoints").html(msg.data.winPoints);
          if (msg.data.opponents != undefined) $(".oppName").html(msg.data.opponents[0]);

          // calculate step size
          steps = msg.data.winPoints;
          calstepsize();
               
          // do finish action
          if (finishAction != undefined){
                if (typeof finishAction == "function"){
                  finishAction();
                }
          }
      });

       
    },

    updatePosition: function(msg){
      myScore = msg.data.me;
      $(".myScore").html(myScore);
      for (const key in msg.data.opponents) {
          oppScore = msg.data.opponents[key];
      }
      $(".oppScore").html(oppScore);

      if (this.debugMode) console.log("updatePosition: "+myScore+"/"+oppScore);

      if (!this.alreadyStarthit){
        starthit(myScore,oppScore);
        this.alreadyStarthit = true;
      }else{
        setstep(myScore,oppScore);
      }
    },

    showEndGameMessage:function(gameResult){
      if (this.debugMode) console.log(gameResult);
      opponents = gameResult.winLoseMessage.data.opponentName;
      myScore = gameResult.gameData.myPoints;
      for (const key in gameResult.gameData.oppPoints) {
        oppScore = gameResult.gameData.oppPoints[key];
    }
      $(".oppName").html(opponents[0]);
      $(".myScore").html(myScore);
      $(".oppScore").html(oppScore);


      var gameOver = true;
      $(".interact").hide();
      $(".waitInteract").hide();

      // check if player has win
      win = false;
      if (gameResult.winLoseMessage.data.winnerId instanceof  Array){
        playerWin = gameResult.winLoseMessage.data.winnerId.includes(gameResult.winLoseMessage.data.myId);
      }else{
        playerWin = gameResult.winLoseMessage.data.winnerId==gameResult.winLoseMessage.data.myId;
      }

      // show game over animation
      if (gameResult.winLoseMessage.data.win && playerWin) cond = 1; // win
      else if (gameResult.winLoseMessage.data.draw) cond = 0; //draw
      else cond = -1; // lose
      steps = 10;
      calstepsize();
      switch (cond){
        case 0: setresultdisplay(0,0); break;
        case 1: setresultdisplay(10,-10); break;
        case -1: setresultdisplay(-10,10); break;
      }
      displayresulteffects(cond);

      //show game over message
      $(".gameMessage").text(gameResult.winLoseMessage.message);
    },

    gameOver: function (msg){
      // put game over data to game result form and submit to result page
      gameResultStr = msg.data;
      $("#gameResultForm").attr("action",base_url+"/game/result");
      $("#gameResultField").val(msg.data);
      $("#gameResultForm").submit();
    },

    // show game result in result page
    showResult:function(gameResult){
      if (this.debugMode) console.log(gameResult);
      bullCon = this;
      this.startGame({data:gameResult.gameData},function(){
        bullCon.showEndGameMessage(gameResult);
      });
    },

    // when on 'game', do action
    socketAction:function(msg){
        switch (msg.action){
            case "startGame": this.startGame(msg); break;
            case "updatePosition": this.updatePosition(msg); break;
            case "gameOver": this.gameOver(msg); break;
            default: break;
        }
    }
  };
});
