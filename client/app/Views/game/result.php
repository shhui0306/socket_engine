<?= $this->extend('layout/layout') ?>

<?= $this->section('content') ?>
<div class='container-fluid'>
    <!-- message / error -->
    <div class="row">
        <div class='col-md-12'>
                <h5 class="gameMessage text-center"></h5>
                <h5 class="gameError text-center"></h5>  
        </div>
    </div>

    <!-- game / interact -->
    <div class='row'>
        <div class="col-md-6 offset-md-3 mt-3 game"></div>
    </div>

    <!-- leave room button -->
    <div class='row mt-2'>
        <div class="col-6 offset-3 col-md-4 offset-md-4 text-center">
            <!-- <form class="restartForm" target="<?=base_url()?>/game/restartGame" method="POST">
                <input type="hidden" name="joinRoomString">
                <input type="hidden" name="addRoomString">
                <button type="submit" class="btn btn-lg btn-info mt-4 w-100 w-mb-50">Restart game</button>
            </form> -->
            <a type="button" class="btn btn-lg btn-info mt-4 w-100 w-mb-50" href="<?=base_url().'/game/room'?> ">Leave room</a>
        </div>
    </div>

<?= $this->endSection() ?>
<?= $this->section('script') ?>
<style>
    .gameMessage, .gameError{
        font-size: 1.75rem;
        font-weight: 400;
    }
</style>

<script>
    var base_url ="<?=base_url()?>";
    var gameResult = <?=json_encode($result)?>;
    var additionalInfo = <?=$additionalInfo?>;
    var debugMode = <?=env('CI_ENVIRONMENT')=="development"?"true":"false"?>;

</script>
<script src="https://code.createjs.com/createjs-2015.11.26.min.js"></script>
<script data-main="<?=base_Url()?>/js/app" src="<?=base_Url()?>/js/lib/require.js"></script>
<?php $this->endSection() ?>