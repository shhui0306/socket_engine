<?= $this->extend('layout/layout') ?>

<?= $this->section('content') ?>
<div class='container-fluid'>
    <div class='row'>
        <div class='col-md-12 w-100 text-center mt-2 mb-5'>
            <h1><?=session('roomName')?></h1>
        </div>
    </div>
    <!-- message / error -->
    <div class="row">
        <div class='col-md-12'>
                <h5 class="gameMessage text-center"></h5>
                <h5 class="gameError text-center"></h5>  
        </div>
    </div>
    <div class="row">
        <div class="col-12 text-center">
            <button type="button" class="btn btn-lg btn-primary readyStart wideButton mt-3" style="display: none;">Start</button>
            <h5 class="answerError text-center"></h5>  
        </div>
    </div>

    <!-- game / interact -->
    <div class="gameCard pt-1 pb-1">
        <div class='row'>
            <div class="col-md-4 game"></div>
            <div class="col-md-8  interact" > </div>
            <div class="col-md-8 pt-0 pb-0 pt-md-3 pb-md-3 mt-1 mt-md-0 ml-3 mr-3 ml-md-0 mr-md-0 waitInteract" style="display: none;"><h3 class="waitInteractMessage">Please wait...</h3></div>
        </div>
    </div>
    

    <!-- player list and messages -->
    <div class="messageCard card pt-3 pb-3" style="display: none;">
        <div class='row'>
            <!-- player list -->
            <div class="col-sm-4 col-md-5 pr-3 pl-4 text-center">
                <div class='inRoom'></div>
            </div>
            <div class="col-sm-8 col-md-7 w-100 text-center">
                <div class='row my-3'>
                    <div class='col-md-12'>
                        <h5>You can type your chat here:</h5>
                    </div>
                </div>
                <!-- send chat message -->
                <div class="row mb-3 justify-content-center">
                    <div class='col-md-6'>
                        <input type="text" class="chatText form-control form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class='col-md-12'>
                        <button type="button" class="sendChat btn btn-info">Send</button>
                    </div>
                </div>
                <!-- messages -->
                <div class="row">
                    <div class='col-md-12 roomMessages'></div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
            <div class="col-12 text-center mt-3">
                <a type="button" class="btn btn-lg btn-danger wideButton" data-toggle="modal" data-target="#leaveModal" >Leave room</a>
            </div>
    </div>
</div>

<!-- Confirm submit modal-->
<div class="modal fade" id="leaveModal" tabindex="-1" role="dialog" aria-labelledby="ModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ModalTitle">Leave confirm</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          Are you sure to leave?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <a type="button" class="btn btn-danger" href="<?=base_url().'/game/joinRoom'?>">Leave room</a>
        </div>
      </div>
    </div>
</div>


<!-- game result form -->
<form id="gameResultForm" method="POST">
    <input type="hidden" id="gameResultField" name="gameResult">
    <input type="hidden" id="additionalInfoField" name="additionalInfo">
    <?=csrf_field()?>
</form>

<?= $this->endSection() ?>
<?= $this->section('script') ?>
<style>
    .gameMessage, .gameError{
        font-size: 1.75rem;
        font-weight: 400;
    }

    .waitInteractMessage{
        font-size: 1.5rem;
        font-weight: 400;
    }

    .interact{
        font-size: 1.1rem;
    }

    .interact h5,h4,h3{
        font-size: 1.25rem;
    }

    .roomMessages{
        max-height: 10rem;
        line-height: 1rem;
        overflow: auto;
        min-width: 75%;
    }

    .roomMessages::-webkit-scrollbar {
      width: 10px;
    }

    /* Track */
    .roomMessages:-webkit-scrollbar-track {
      background: #f1f1f1; 
    }

    /* Handle */
    .roomMessages::-webkit-scrollbar-thumb {
      background: #888; 
    }

    /* Handle on hover */
    .roomMessages::-webkit-scrollbar-thumb:hover {
      background: #555; 
    }

    .roomMessages p{
        margin-bottom: 0.5rem;
    }

    .interact button{
        margin-top: 0.5rem;
    }

    .inRoom{
        display: flex;
    }

    .inRoom thead th {
        font-size: 1em;
        font-weight: 400;
        background-color: #17a2b8;
        color: white;
    }

    .wideButton{
        width: 35vw;
    }

    @media only screen and (max-width: 768px){
        .wideButton{
            width: 75vw;
        }
    }
</style>
<script>
    var base_url ="<?=base_url()?>";
    var addRoomString = "<?= isset($addRoomString)?$addRoomString:null;?>";
    var joinRoomString = "<?=isset($joinRoomString)?$joinRoomString:null;?>";
    var initString = "<?=isset($initString)?$initString:null;?>";
    var readyStartString = "<?=isset($readyStartString)?$readyStartString:null;?>";
    var debugMode = <?=env('CI_ENVIRONMENT')=="development"?"true":"false"?>;
</script>
<script src="https://code.createjs.com/createjs-2015.11.26.min.js"></script>
<script src="<?=base_url()?>/js/lib/paper-full.js"></script>
<script src="<?=base_url()?>/js/lib/huebee.pkgd.min.js"></script>
<script data-main="<?=base_Url()?>/js/app" src="<?=base_Url()?>/js/lib/require.js"></script>
<?php $this->endSection() ?>