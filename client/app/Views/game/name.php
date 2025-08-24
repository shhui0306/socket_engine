<?= $this->extend('layout/layout') ?>

<?= $this->section('content') ?>
<div class='container-fluid'>
        <div class='col-lg-8 offset-lg-2'>
            <div class="card loginCard text-center">
                <div class="card-body">
                    <h3 class="card-title">Welcome to socket game</h3>
                    <h5>Create player</h5>
                    <p class="card-text">
                        <form action="/game" method="post">
                            <?= csrf_field() ?>
                            <label for="playerName" class="fieldLabel">Enter your name here:</label>
                            <input class="form-control form-control-lg nameInput text-center" type="input" name="playerName" />

                            <!-- <label for="username" class="fieldLabel">Username</label>
                            <input class="form-control form-control-lg nameInput text-center" type="input" name="username" />

                            <label for="password" class="fieldLabel">Password</label>
                            <input class="form-control form-control-lg nameInput text-center" type="password" name="password" /> -->

                            <!-- groupId input field for test only -->
                            <!-- <label for="groupId" class="fieldLabel">Group</label>
                            <input class="form-control form-control-lg nameInput text-center" type="input" name="groupId" /> -->

                            <input type="submit" class="btn btn-lg btn-info createButton" name="submit" value="Create User" />
                        </form>
                    </p>
                </div>
            </div>        
        </div>
</div>

<style>
    /* All screen sizes */
    .nameInput{
        margin-bottom: .75rem;
    }

    .loginCard{
        height: 80vh;
        border: 0px;
    }

    .loginCard .card-title{
        margin-bottom: 3rem;
        font-size: 2rem;
        font-weight: 300;
        padding-bottom: 1rem;
        border-bottom: 1px solid #ced4da;
    }

    .nameInput {
        margin-bottom: 1.5rem;
        font-size: 1.5rem;
        font-weight: 300;
    }

    .fieldLabel{
        font-size: 1.5rem;
        font-weight: 100;
    }

    .createButton{
        font-size: 1.5rem;
        font-weight: 300;
        margin-top: 1.5rem;
        line-height: 1.5;
    }

    

    /* large screen only */
    @media only screen and (min-width: 768px){
        .nameInput{
            width: 50%;
            margin-left: 25%;
        }

        .createButton{
            width: 30%;
        }
    }

    /* small screen only */
    @media only screen and (max-width: 768px){
        .card-body{
            padding-left: 0;
            padding-right: 0;
        }

        .nameInput{
            width: 75%;
            margin-left: 12.5%;
        }

        .createButton{
            width: 50%;
        }
    }

</style>

<?= $this->endSection() ?>

<?= $this->section('script') ?>

<?= $this->endSection() ?>