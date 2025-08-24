<?php namespace App\Controllers;

use CodeIgniter\Pager\Pager;

class DummyDraw extends BaseController{
    // dummy draw API

    private $itemCategories = [
        // "categoryId"=>"categoryName"
        "1"=>"vehicles",
        "2"=>"fruit",
        "3"=>"animal" 
    ]; 
    private $drawItems = [  
        // "categoryId"=>["itemId"=>"itemName"]
        "1"=>[
            "1"=>"Bus",
            "2"=>"Taxi",
            "3"=>"Train",
            "4"=>"Car",
            "5"=>"Bicycle",
            "6"=>"Motorcycle"
        ],
        "2"=>[
            "7"=>"Apple",
            "8"=>"Orange",
            "9"=>"Pineapple",
            "10"=>"Pear",
            "11"=>"Peach",
            "12"=>"Lemon"
        ],
        "3"=>[
            "13"=>"Dog",
            "14"=>"Cat",
            "15"=>"Mouse",
            "16"=>"Cow",
            "17"=>"Horse"
        ]
    ];

    private $drawings = [
        // "(catId)"=>["id"=>"(drawId)", "itemId"=>"itemId", "token"=>"token", "canvas"=>"canvas json"]
        "1"=>[
            ["id"=>1, "itemId"=>"1", "token"=>"dummy", "canvas"=>"{\"size\":[\"Size\",285.73126,161.60001],\"layer\":[\"Layer\",{\"applyMatrix\":true,\"children\":[[\"Path\",{\"applyMatrix\":true,\"segments\":[[[70.40625,45.39999],[0,0],[0,10.63586]],[[71.40625,77.39999],[-1.90961,-10.50286],[0.26828,1.47554]],[[75.40625,86.39999],[-3.10008,3.10008],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[81.40625,15.39999],[0,0],[3.82565,-3.82565]],[[118.40625,26.39999],[-4.5151,-1.93504],[6.96798,2.98628]],[[156.40625,48.39999],[-3.2141,-6.4282],[0.92414,1.84829]],[[154.40625,61.39999],[0,-2.40386],[0,10.85514]],[[148.40625,93.39999],[1.52117,-10.64817],[-0.54566,3.81962]],[[148.40625,105.39999],[0.93325,-3.733],[-0.85538,3.42152]],[[134.40625,104.39999],[1.36711,0.34178],[-3.5588,-0.8897]],[[100.40625,100.39999],[2.59733,-2.59733],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[88.40625,13.39999],[0,0],[5.00153,-5.00153]],[[118.40625,11.39999],[-5.98659,0],[32,0]],[[214.40625,11.39999],[-32,0],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[216.40625,11.39999],[0,0],[14.27917,0]],[[252.40625,31.39999],[-8.17286,-10.89714],[3.30707,4.40942]],[[262.40625,51.39999],[-5.2259,-5.2259],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[156.40625,51.39999],[0,0],[27.66667,0]],[[239.40625,51.39999],[-27.66667,0],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[255.40625,49.39999],[0,0],[4.77542,0]],[[258.40625,63.39999],[0,-3.43926],[0,9.33333]],[[258.40625,91.39999],[0,-9.33333],[0,1.12396]],[[258.40625,99.39999],[0.89621,-0.89621],[-0.7357,0.7357]],[[253.40625,101.39999],[0.58579,-0.19526],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[153.40625,120.39999],[0,0],[31.66667,0]],[[248.40625,120.39999],[-31.66667,0],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[204.40625,87.39999],[0,0],[0,4.39314]],[[201.40625,99.39999],[0.71696,-4.30174],[-0.4316,2.5896]],[[202.40625,105.39999],[0,6.75011],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[203.40625,79.39999],[0,0],[1.53307,0]],[[216.40625,79.39999],[-0.4763,-0.9526],[1.07921,2.15843]],[[212.40625,103.39999],[2.70249,2.70249],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[141.40625,81.39999],[0,0],[1.99579,0]],[[146.40625,84.39999],[-1.96381,-0.78552],[8.61406,3.44562]],[[153.40625,103.39999],[0,-9.27573],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[156.40625,79.39999],[0,0],[2.3776,0]],[[171.40625,79.39999],[-1.7233,-1.7233],[3.57559,3.57559]],[[172.40625,101.39999],[0,-4.92407],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[74.40625,54.39999],[0,0],[20.71782,6.90594]],[[135.40625,78.39999],[-20.41745,-7.65654],[5.0913,1.90924]],[[152.40625,85.39999],[-5.59692,0],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[152.40625,85.39999],[0,0],[4.99167,0]],[[167.40625,81.39999],[-5.02778,0.55864],[14.91502,-1.65722]],[[212.40625,79.39999],[-15.09432,0],[9.33333,0]],[[240.40625,79.39999],[-9.33333,0],[1.65607,0]],[[248.40625,78.39999],[0,2.20325],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[86.40625,43.39999],[0,0],[2.41867,2.41867]],[[89.40625,52.39999],[0,6.69784],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[90.40625,42.39999],[0,0],[3.43666,0]],[[114.40625,50.39999],[-2.42901,-2.42901],[2.94314,2.94314]],[[111.40625,65.39999],[0.8658,-2.5974],[-0.2664,0.79919]],[[111.40625,69.39999],[0.56409,-0.56409],[-4.6959,4.6959]],[[93.40625,67.39999],[2.93161,-5.86321],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[96.40625,102.39999],[0,0],[-2.198,0]],[[94.40625,109.39999],[-1.3477,-1.3477],[4.25188,4.25188]],[[104.40625,102.39999],[0,3.3961],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[180.40625,133.39999],[0,0],[0,9.79521]],[[188.40625,129.39999],[0,8.55834],[0,0]]],\"strokeColor\":[0.53333,0,1],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}]]}]}"],
        ],
        "2"=>[
            ["id"=>2, "itemId"=>"8", "token"=>"dummy", "canvas"=>"{\"size\":[\"Size\",445.59027,251.51389],\"layer\":[\"Layer\",{\"applyMatrix\":true,\"children\":[[\"Path\",{\"applyMatrix\":true,\"segments\":[[[218.69443,50.66667],[0,0],[-20.38027,0]],[[160.69443,60.66667],[19.34793,-6.21898],[-37.66965,12.1081]],[[107.69443,150.66667],[-6.93437,-38.13904],[3.19867,17.59267]],[[137.69443,200.66667],[-11.27315,-12.8836],[35.66825,40.76371]],[[286.69443,221.66667],[-45.82694,24.99651],[10.72506,-5.85003]],[[321.69443,193.66667],[-6.12503,11.56949],[18.27726,-34.52372]],[[306.69443,71.66667],[29.61807,27.33975],[-26.96203,-24.88803]],[[215.69443,31.66667],[35.63926,5.74827],[-6.60132,-1.06473]],[[195.69443,27.66667],[6.77002,0],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[205.69443,44.66667],[0,0],[3.03693,6.07386]],[[229.69443,77.66667],[-5.58788,0],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[230.69443,53.66667],[0,0],[-9.27719,0]],[[208.69443,74.66667],[4.9459,-7.41885],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[149.69443,131.66667],[0,0],[5.93884,2.96942]],[[159.69443,146.66667],[-1.24883,-6.24417],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[163.69443,162.66667],[0,0],[2.69173,5.38346]],[[172.69443,176.66667],[-4.29847,-4.29847],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[200.69443,190.66667],[0,0],[6.81015,-2.27005]],[[227.69443,152.66667],[-4.17096,6.67354],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[234.69443,133.66667],[0,0],[6.67499,0]],[[254.69443,134.66667],[-6.66577,-0.35083],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[279.69443,142.66667],[0,0],[0,11.66732]],[[278.69443,177.66667],[1.36735,-11.62244],[-0.86477,7.35051]],[[275.69443,199.66667],[0,-7.4012],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[275.69443,204.66667],[0,0],[1.04562,2.09124]],[[279.69443,209.66667],[-1.63817,-1.63817],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}],[\"Path\",{\"applyMatrix\":true,\"segments\":[[[281.69443,209.66667],[0,0],[2.38545,0]],[[287.69443,208.66667],[-2.81429,0.70357],[0,0]]],\"strokeColor\":[1,0.53333,0],\"strokeWidth\":3,\"strokeCap\":\"round\",\"strokeJoin\":\"round\"}]]}]}"],
        ],
        "3"=>[
            ["id"=>3, "itemId"=>"13", "token"=>"dummy", "canvas"=>""],
        ]
    ];

    function getItem(){
        // find three random items in same category in database (get three items from dummy api)
        // input format: ["category"=>"(category id)", "token"=>"(player token)"]
        // output format: ["result"=>(boolean), "items"=>[(item ids)]]

        $data = $this->request->getPost();
        log_message("info","getItem: ".json_encode($data));
        if (isset($data["category"])){
            if (isset($this->itemCategories[$data["category"]])){
                $items = $this->drawItems[$data["category"]];
                $ids = array_rand($items,3);
                $choices = [];
                foreach($ids as $id){
                    $choices[$id] = $items[$id];
                }
                return $this->response->setJSON(["result"=>true, "items"=>$choices],true);

            }else return $this->response->setJSON(["result"=>false, "items"=>[], "error"=>"Invalid category"],true);

        }else{
            return $this->response->setJSON(["result"=>false, "items"=>[], "error"=>"Missing category"],true);
        }
    }

    function submitDraw(){
        // submit to database and get drawing id (log drawing submit and get dummy id from dummy api)
        // input format: ["item"=>"item id", "canvas"=>"canvas json", "token"=>"(player token)"]
        // output format: ["result"=>(boolean), "drawId"=>"draw ID"]

        $data = $this->request->getPost();
        if (isset($data["item"]) && isset($data["canvas"])){
            log_message("info","submitDraw: ".json_encode($data));
            return $this->response->setJSON(["result"=>true, "drawId"=>"1"],true); 
        }else{
            return $this->response->setJSON(["result"=>false, "drawId"=>null, "error"=>"Invalid post data"],true);
        }
    }

    function getDraw(){
        // get drawing from database (get dummy drawing from dummy api)
        // input format: ["category"=>"(category id)", "token"=>"(player token)"]
        // output format: ["result"=>(boolean), "drawId"=>"(drawing id)",  "canvas"=>"(json string)", "choices"=>[(array of item ids)], "correctAnswer"=>"item id"]

        $data = $this->request->getPost();
        log_message("info","getDraw: ".json_encode($data));
        if (isset($data["category"])){
            if (isset($this->itemCategories[$data["category"]])){
                // get drawing
                $randomId = array_rand($this->drawings[$data["category"]]);
                $drawing = $this->drawings[$data["category"]][$randomId];
                
                // get 2 random choices
                $items = $this->drawItems[$data["category"]];
                $choices = [$drawing["itemId"]=>$items[$drawing["itemId"]]];
                unset($items[$drawing["itemId"]]);
                $randomId = array_rand($items,2);
                foreach ($randomId as $id){
                    $choices[$id] = $items[$id];
                }
                
                // shuffle choices
                $ids = array_keys($choices);
                shuffle($ids);
                $shuffledChoices = [];
                foreach ($ids as $id){
                    $shuffledChoices[$id] = $choices[$id];
                }

                //return data
                return $this->response->setJSON(["result"=>true, "drawId"=>$drawing["id"], "canvas"=>$drawing["canvas"], "choices"=>$shuffledChoices, "correctAnswer"=>$drawing["itemId"]],true);

            }else return $this->response->setJSON(["result"=>false, "drawId"=>null, "canvas"=>null, "choices"=>null, "correctAnswer"=>null, "error"=>"Invalid category"],true);
        }else{
            return $this->response->setJSON(["result"=>false, "drawId"=>null,  "canvas"=>null, "choices"=>null, "correctAnswer"=>null, "error"=>"Invalid post data"],true);
        }
    }

    function getItemCount(){
        // get count of items from category
        // input format: ["category"=>"catId"]
        // output format: ["result"=>(boolean), "count"=>(number)]

        $data = $this->request->getPost();
        log_message("info","getDrawCount: ".json_encode($data));
        if (isset($data["category"])){
            if (isset($this->itemCategories[$data["category"]])){
                return $this->response->setJSON(["result"=>true, "count"=>count($this->drawItems[$data["category"]]) ],true);
            }else return $this->response->setJSON(["result"=>false, "count"=>null , "error"=>"Invalid category"],true);
        }else{
            return $this->response->setJSON(["result"=>false, "count"=>null, "error"=>"Invalid post data"],true);
        }
    }

    function submitChoice(){
        // save choice record to database (log choice submit and return success result from dummy api)
        // input format: ["drawId"=>"(draw id)", "playerAnswer"=>"(item ID)"]
        // output format: ["result"=>(boolean)]

        $data = $this->request->getPost();
        log_message("info","submitChoice: ".json_encode($data));
        if (isset($data["drawId"]) && isset($data["playerAnswer"])){
            return $this->response->setJSON(["result"=>true],true); 
        }else return $this->response->setJSON(["result"=>false, "error"=>"Invalid post data"],true);
    }
}