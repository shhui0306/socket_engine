<div class="container-fluid">
    <div class="row pt-0 pb-0 pt-md-3 pb-md-3 mt-1 mt-md-0 ml-3 mr-3 ml-md-0 mr-md-0">
        <div class="col-6">
            <h5>Question: </h5>
            Dummy question for testing
        </div>
        <div class="col-6">
            <form id="interactForm">
                <div class="row">
                    <div class="col-12">
                    <p>Select one or more of the followings:</p>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" name="answers[]">
                                <label class="form-check-label" for="defaultCheck1">
                                  Correct
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="2" name="answers[]">
                                <label class="form-check-label" for="defaultCheck1">
                                  Wrong
                                </label>
                            </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <input type="hidden" name="questionType" value="mc">
                        <input type="hidden" name="questionId" value="123">
                         <button type="submit" id='interactSubmit' class="btn btn-primary ">Submit Answer</button>
                    </div>
                </div>
            </form>
        </div>
        
    </div>
</div>