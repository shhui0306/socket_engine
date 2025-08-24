define(["jquery"], function ($) {
    return {
    socket:null,
    gameUrl:"",
    debugMode: false,

    init: function(socket){
      this.socket = socket;
      this.gameUrl = base_url + "/html/game/tic.html";
    },
  
    winLineColor: "#ff2121", //color for coloring winning line

     gameBoardUpdate:function(msg){
        // update game board
        for (i=0; i<msg.data.gamePad.length; i++){
          $("#"+i).text(msg.data.gamePad[i]);
          if(msg.data.gamePad[i] == ""){
            $("#"+i).addClass("clickable");
          }else{
            $("#"+i).removeClass("clickable");
          }
        }
    },

     makeClickable:function(msg){
        // make square clickable / unclickable
        
        if (msg.data.clickable){
          // display choose message, add listener to clickable squares
          $(".answerCorrectText").text("You answer is correct! Please choose position on gamepad.");
          $(".waitInteractMessage").text("You answer is correct! Please choose position on gamepad.");
          socket = this.socket;
          $(".clickable").each(function(){
            $(this).on('click',function(){
              $(".answerText").text("");
              // click action
              sqno = $(this).attr("id");
              if (interactToken != null){
                // emit click to socket
                socket.emit("game",{action:"sendClick",data:{sqno:sqno, token:interactToken}});
              }else{
                // interact token is null, show error
                if (this.debugMode) console.log({action:"sendClick(ticController)", devError:"interactToken is null"})
              }
           });
          });
        }else{
          // remove listener to clickable squares
          $(".clickable").each(function(){
            $(this).off("click");
          });
        }
    },
    // socket actions
    startGame:function(msg,finishAction){
      // start game, init game board
      $(".game").load(this.gameUrl,function(){

        $("#ticMessage").html("Connect "+msg.data.connectedPoints+" squares to win")

        // init game board
        size = msg.data.size;
        squares = size*size
        squaresHtml = "";
        for (i=0; i<squares; i++){
            squaresHtml += '<li class="tic clickable" id="'+i+'"> </li>';
        }
        $("#gameBoard").html(squaresHtml);
        $("#gameBoard li").css("width",((100/size)-0.2)+"%");
        $("#gameBoard li").css("height",(100/size)+"%");

        if (size >=7) fontSize = 30;
        else if (size >=5) fontSize = 40;
        else fontSize = 50;
        $("#gameBoard li").css("font-size",fontSize+"px");

        // init symbol list
        mySymbol = msg.data.mySymbol;
        symbols = msg.data.symbols;
        if (Array.isArray(symbols)){
          symbolsHtml = "<tr><td>You</td><td>"+mySymbol+"</td></tr>";
          for (i=0; i<symbols.length; i++){
            if (symbols[i][1] != mySymbol){
              symbolsHtml += "<tr><td>"+symbols[i][0]+"</td><td>"+symbols[i][1]+"</td></tr>";
            }
          }
          $("#playerSymbols").html(symbolsHtml);
        }

        // do finish action
        if (finishAction != undefined){
          if (typeof finishAction == "function"){
            finishAction();
          }
        }
      });
    },

    winLoseMessage:function(msg){
      var gameOver = true;
      $(".interact").hide();
      $(".waitInteract").hide();

      //paint win line
      if (msg.data.line !== undefined){
        for (i=0; i<msg.data.line.length; i++){
          sqno = msg.data.line[i];
          $("#"+sqno).css("background",this.winLineColor);
        }
      }

      //show game over message
      $(".gameMessage").text(msg.message);
    },

    gameOver: function (msg){
      // put game over data to game result form and submit to result page
      gameResultStr = msg.data;
      $("#gameResultForm").attr("action",base_url+"/game/result");
      $("#gameResultField").val(msg.data);
      $("#additionalInfoField").val(JSON.stringify({symbolList: $("#playerSymbols").html()}));
      $("#gameResultForm").submit();
    },

    // show game result in result page
    showResult:function(gameResult){
      if (this.debugMode) console.log(gameResult);
      ticCon = this;
      this.startGame({data:gameResult.gameData},function(){
        ticCon.gameBoardUpdate({data:gameResult.gameData});
        ticCon.winLoseMessage(gameResult.winLoseMessage);
        if (additionalInfo.symbolList != undefined){
          $("#playerSymbols").html(additionalInfo.symbolList);
        }
      });
      
    },

    // when on 'game', do action
    socketAction:function(msg){
        switch (msg.action){
            case "startGame": this.startGame(msg); break;
            case "gameBoardUpdate": this.gameBoardUpdate(msg); break;
            case "makeClickable": this.makeClickable(msg); break;
            case "gameOver": this.gameOver(msg); break;
            default: break;
        }
    }
  };
});
