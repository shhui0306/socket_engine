define(["jquery"],function ($) {

    return {
        socket:null,
        interactInput:[],
        debugMode: false,

        init:function(socket){
          this.socket = socket;
        },


        getTrigger:function(msg){
            submitCount = 0;
            $(".interact").append(msg.data.html);
            $(".waitInteract").hide();
            $(".interact").show();
        },

        

        submitForm:function(e){
            e.preventDefault();
            formData = new FormData(e.target);
            answers = formData.getAll('answers[]');
            questionType = formData.getAll('questionType');
            questionId = formData.getAll('questionId');
            if (this.debugMode) console.log(answers);
            this.socket.emit("interact",{
                action:"manageInteractResult", 
                data:{
                    answers: answers,
                    questionId:questionId,
                    questionType: questionType
                }
            });
            return false;
        },

        processResult:function(msg){
            // set interact token
            if (msg.data.token != null){
                interactToken = msg.data.token;
            }
        },

        // when on 'interact', do action
        socketAction:function(msg){
            switch (msg.action){
                case "getResult": this.processResult(msg); 
                $(".answerError").text("Your last question is: "+msg.data.result);break;
                default: break;
            }
        }
    };
});