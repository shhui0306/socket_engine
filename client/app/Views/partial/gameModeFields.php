<?php foreach($gameModes as $mode){?>
    <div class="advancedSettings-<?=$mode["code"]?>">
        <?php foreach ($mode["settings"] as $s){?>
            <div class="row">
                <div class="col-5 text-right"><label for="<?=$s["prop"]?>"><?=$s["name"]?></label></div>
                <div class="col-7">
                    <?php switch ($s["type"]){
                        case "text": ?>
                        <input type="input" class="form-control submitField mb-1 mb-md-3" id='<?=$s["prop"]?>' name='<?=$s["prop"]?>' <?=isset($s["value"])?"value='".$s["value"]."'":""?>/>
                        <?php break;
                        case "number":?>
                        <input type="number" class="form-control submitField mb-1 mb-md-3" name="<?=$s["prop"]?>" id="<?=$s["prop"]?>" <?=isset($s["min"])?"min='".$s["min"]."'":""?> <?=isset($s["max"])?"max='".$s["max"]."'":""?> <?=isset($s["value"])?"value='".$s["value"]."'":""?> >
                        <?php break;
                        default: break;
                    } ?>
                </div> 
            </div>
        <?php }?>
    </div>
<?php }?>