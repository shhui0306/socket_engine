define(["jquery"],function ($) {

    return {
        socket:null,
        interactInput:[],
        debugMode: false,
        drawUrl:"",
        drawInited: false,
        popupTime: 15,
        popupTimer:{},

        init:function(socket){
        this.drawUrl = base_url + "/html/interact/draw.html";
        this.socket = socket;
        drawCon = this;
        // load draw interface
        $(".interact").show();
        $(".interact").load(this.drawUrl,function(){
            //var canvas = document.getElementById('myCanvas');
            $( document ).ready(function(){
                paper.install(window);
                paper.setup(document.getElementById('myCanvas'));
                drawCon.switchDrawRole("draw");
                drawCon.initCanvas();
                drawCon.switchDrawRole("choose");
            });
        }) 
                
        },


        getTrigger:function(msg){
            $(".waitInteract").hide();
            $(".interact").show();


            drawCon = this;
            ms = msg;
            $( document ).ajaxStop(function(){
                // first trigger, run after ajax finish
                switch (ms.data.role){
                    case "choose":
                        drawCon.switchDrawRole("choose");
                        drawCon.updateChooseItems(ms.data.choices);
                        break;
    
    
                    case "guess": 
                        drawCon.switchDrawRole("guess");
                        drawCon.updateCanvas(ms.data.canvas);
                        drawCon.updateGuessChoices(ms.data.choices);
                        break;
                }
            });

            if (msg.data.role !== undefined && msg.data.role !== null){
               switch (msg.data.role){
                case "choose":
                    this.switchDrawRole("choose");
                    this.updateChooseItems(msg.data.choices);
                    break;

                case "guess": 
                    this.switchDrawRole("guess");
                    this.updateCanvas(msg.data.canvas);
                    this.updateGuessChoices(msg.data.choices);
                    break;

                case "error":
                    $(".drawErrorText").text(msg.data.error);
                    this.switchDrawRole("error");
                    break;
                } 
            }

            if (msg.data.popup !== undefined && msg.data.popup !== null){
                this.noticeGamePopup(msg.data.popup);
            }          
        },


        socketAction:function(msg){
            if (this.debugMode) console.log(msg);
            switch (msg.action){
                case "getResult": this.getResult(msg); break;
                case "interactTime": this.displayInteractTime(msg); break;
                case "forceSubmitDrawing": this.submitDraw(); break;
            }
        },


        displayInteractTime: function(msg){
            if (msg.data.status == 0 && this.playerRole == "draw"){
                this.displayDrawTime(msg.data.time + msg.data.drawTime);
            }else{
                this.displayDrawTime(msg.data.time);
            }
        },

        submitDraw: function(){
            canvasData = JSON.stringify({size:view.size, layer:project.activeLayer});
            this.socket.emit('interact',{action:"manageInteractResult", data:{type:"submitDraw", canvas:canvasData}});
            this.switchDrawRole("wait");
        },

        getResult: function(msg){
            if (msg.data.role != undefined && msg.data.role != null) this.switchDrawRole(msg.data.role);
            if (msg.data.token != undefined && msg.data.token != null) interactToken = msg.data.token;
            if (msg.data.drawItem != undefined && msg.data.drawItem != null) this.displayDrawQuestion(msg.data.drawItem);
            if (msg.data.choice != undefined && msg.data.choice != null) $(".answerError").text("Your last answer is: "+msg.data.choice);
            if (msg.data.popup != undefined && msg.data.popup != null) this.noticeGamePopup(msg.data.popup);
        },

        noticeGamePopup: function(popup){
            switch (popup){
                case "correct": 
                this.popupTime = 15;
                drawCon = this;
                $(".drawPopup").show();
                $(".correctAnswerPopup").show();
                $(".wrongAnswerPopup").hide();
                $(".drawPopupTime").text(drawCon.popupTime);
                this.popupTimer =  setInterval(function(){
                    drawCon.popupTime--;
                    if (drawCon.popupTime > 0){
                        $(".drawPopupTime").text(drawCon.popupTime);
                    }else{
                        clearInterval(drawCon.popupTimer);
                        $(".correctAnswerPopup").hide();
                        $(".drawPopup").hide();
                    }                  
                }, 1000);
                
                break;

                case "wrong":
                this.popupTime = 5;
                drawCon = this;
                $(".drawPopup").show();
                $(".wrongAnswerPopup").show();
                $(".correctAnswerPopup").hide();
                $(".drawPopupTime").text(this.popupTime);
                this.popupTimer =  setInterval(function(){
                    drawCon.popupTime--;
                    if (drawCon.popupTime > 0){
                        $(".drawPopupTime").text(drawCon.popupTime);
                    }else{
                        clearInterval(drawCon.popupTimer);
                        $(".wrongAnswerPopup").hide();
                        $(".drawPopup").hide();
                    }                  
                }, 1000);
                break;
                
                case "hide": 
                $(".drawPopup").hide();
                break;
                
                default: //hide 
                $(".drawPopup").hide();
                break;
            }
        },

        // canvas settings
        playerRole:null,
        myViewSize: [null,null],
        drawerViewSize: [null,null],

        strokeColor: "#000000",
        strokeWidth: 3,


        initCanvas: function(){
            tool = new Tool();
            var myPath;
            this.myViewSize = [view.size._width, view.size._height];

            drawCon = this;
            tool.onMouseDown = function(event) {
                console.log(drawCon.drawRole)
                if (drawCon.playerRole=='draw'){
                    myPath = new Path();
                    myPath.strokeColor = drawCon.strokeColor;
                    myPath.strokeWidth = drawCon.strokeWidth;
                    myPath.strokeCap = 'round';
                    myPath.strokeJoin = 'round';
                    myPath.add(event.point);
                }
            }

            tool.onMouseDrag = function(event) {
                if (drawCon.playerRole=='draw'){
                    myPath.add(event.point);
                }        
            }

            tool.onMouseUp = function(event) {
                if (drawCon.playerRole=='draw'){
                    if (myPath.segments.length == 1){
                        // single dot
                        myPath = new Path.Circle({
                            center: event.point,
                            radius: drawCon.strokeWidth/2
                        });
                        myPath.strokeColor = drawCon.strokeColor;
                        myPath.fillColor = drawCon.strokeColor;
                    }else{
                        myPath.simplify();
                    }
                }
            } 

            view.onResize = function(){
                // Whenever the view is resized, move the path to its center:
                myPath.position = view.center;
            }

            // color palette
            var hueb = new Huebee('.color-input',{
                saturations: 1,
                staticOpen: true,
            })
            
            hueb.on( 'change', function( color ) {
            drawCon.strokeColor = color;
              $("#sampleLine").css('border-top-color',color);
            });
            
            
            $(".widthSelect").on('input change',function(){
                swidth = $(".widthSelect").val();
                drawCon.strokeWidth = swidth;
                $("#sampleLine").css('border-top-width',swidth+"px");
            });


            // clear canvas / finish drawing

            $(".clearCanvasButton").click(function(){
                if (drawCon.playerRole=='draw'){
                    project.activeLayer.removeChildren();
                }
            });

            socket = this.socket;
            $(".confirmFinishButton").click(function(){
                canvasData = JSON.stringify({size:view.size, layer:project.activeLayer});
                socket.emit('interact',{action:"manageInteractResult", data:{type:"submitDraw", canvas:canvasData}});
            })


            this.switchDrawRole(this.playerRole);
        }, 

        // switch role
        switchDrawRole: function(role){
            this.playerRole = role;
            switch (role){
                case "draw":  // draw
                    try {project.activeLayer.removeChildren();} catch (error) { }
                    $(".dsth_messages").hide();
                    $(".drawerMessage").show();
                    $(".canvasRow").show();
                    $(".chooseCategoryRow").hide();
                    $(".guessChoices").hide();
                    $(".drawerPalette").show();
                    break;
            
                case "guess": // guess
                    try {project.activeLayer.removeChildren();} catch (error) { }
                    $(".dsth_messages").hide();
                    $(".guessMessage").show();
                    $(".canvasRow").show();
                    $(".chooseCategoryRow").hide();
                    $(".drawerPalette").hide();
                    $(".guessChoices").show();
                    break;
            
                case "choose": // choose
                    try {project.activeLayer.removeChildren();} catch (error) { }
                    $(".dsth_messages").hide();
                    $(".chooseMessage").show();
                    $(".canvasRow").show();
                    $(".chooseCategoryRow").hide();
                    $(".drawerPalette").hide();
                    $(".guessChoices").show();
                    break;
            
            
                case "error": // player number error
                    try {project.activeLayer.removeChildren();} catch (error) { }
                    $(".dsth_messages").hide();
                    $(".canvasRow").hide();
                    $(".drawErrorMessage").show();
                    break;
            
                default:  // wait
                    try {project.activeLayer.removeChildren();} catch (error) { }
                    $(".dsth_messages").hide();
                    $(".waitMessage").show();
                    $(".canvasRow").hide();
                    $(".chooseCategoryRow").hide();
                    $(".correctAnswer").parent().hide();
                    break;
            }
        },

        // update canvas
        updateCanvas: function(data){
            if (data.layer == undefined){
                data = JSON.parse(data);
            }
            if (data.size != undefined){
                this.drawerViewSize = [data.size[1],data.size[2]];
            }
            if (data.layer instanceof Array){
                project.activeLayer.removeChildren();
                layer = data.layer[1].children;
                layer.forEach(item=>{
                    switch(item[0]){
                        case "Path": 
                        path = item[1];
                        //transform path
                        path = this.transformPath(path);
                        myPath = new Path(path);
                        break;
                        default: break;
                    }
                });
            }
        },

        // transform path to viewer size
        transformPath: function(path){
            // transform path into viewer size
            seg = path.segments;
            if(!isNaN(seg[0][0])){
                // single point
                seg[0] = this.transformPoint(seg[0]);
            }else{
                // path segments
                seg.forEach((s,i)=>{
                    s.forEach((p,j)=>{
                        s[j] = this.transformPoint(p);
                    });
                    seg[i] = s;
                })
            }
            path.segments = seg;
            if(path.strokeWidth != undefined){
            path.strokeWidth = path.strokeWidth/this.drawerViewSize[0]*this.myViewSize[0];
            }
            return path;
        },

        // transform point to viewer size
        transformPoint: function(dpoint){
            // transform point into viewer size
            this.myViewSize = [view.size._width, view.size._height];
            if (dpoint[0] == "Point") point =  [dpoint[1]/this.drawerViewSize[0]*this.myViewSize[0], dpoint[2]/this.drawerViewSize[1]*this.myViewSize[1]]
            else point =  [dpoint[0]/this.drawerViewSize[0]*this.myViewSize[0], dpoint[1]/this.drawerViewSize[1]*this.myViewSize[1]];
            return point;
        },


        // display
        updateGuessChoices: function(choices){
            $(".choiceButton").off("click");
            chcHtml="";
            for (const key in choices) {
                if (choices.hasOwnProperty(key)) {
                     chcHtml += `<button type="button" class="choiceButton btn btn-info btn-lg btn-block" value="`+key+`">`+choices[key]+`</button>`
                    
                }
            }
            $(".choiceSelect").html(chcHtml);
            socket = this.socket;
            drawCon = this;
            $(".choiceButton").click(function(){
                socket.emit('interact',{action:"manageInteractResult", data:{type:"submitChoice", choice:$(this).val()}});
                $(".choiceButton").off("click");
                drawCon.switchDrawRole("wait");
            })
        },

        updateChooseItems: function(items){
            $(".itemButton").off("click");
            itmHtml="";
            for (const key in items) {
                if (items.hasOwnProperty(key)) {
                    itmHtml += `<button type="button" class="itemButton btn btn-info btn-lg btn-block" value="`+key+`">`+items[key]+`</button>`
                }
            }
            $(".choiceSelect").html(itmHtml);
            socket = this.socket;
            drawCon = this;
            $(".itemButton").click(function(){
                socket.emit('interact',{action:"manageInteractResult", data:{type:"selectItem", choice:$(this).val()}});
                $(".itemButton").off("click");
                drawCon.switchDrawRole("wait");
            })
        },

        displayDrawTime: function(time){
            $(".drawTime").text(time);
        },

        displayDrawQuestion: function(question){
            $(".drawQuestion").text(question);
        },

        displayCorrectAnswer: function(answer){
            $(".correctAnswer").text(answer);
            $(".correctAnswer").parent().show();
        },
    }  
});