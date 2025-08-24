var lpos = 0;
var rpos = 0;
var lsteps = 0;
var rsteps = 0;
var steps = 10; //moving steps from center of ring
var overlap = 0.17; // overlap for each bull to hit each other
var margin = 0.07; // margin of bull to the rim of the ring, same as left/right in bullmerge.css canvas and canvas2
var origin = 0; // origin position
var stepsize = 0;

function calstepsize(){
	stepsize = (0.5-overlap-margin)/(steps-1);
	origin = 0.5-overlap;
	lpos = margin;
	rpos = margin;
}

function starthit(leftstep,rightstep){
	// start game animation: two bull running towards each other and hit
	if (leftstep == undefined) leftstep = 0;
	if (rightstep == undefined) rightstep = 0;

	calstepsize();

	var rand = Math.random() > 0.5;
	if (rand){ startdir= 0; startdir2 = 1;}
	else { startdir= 1; startdir2 = 0; }

	//run animation
	keeprunbull(); keeprunbull2();

	lsteps = leftstep;
	rsteps = rightstep;
	ideallpos = origin+(leftstep*stepsize);
	idealrpos = origin+(rightstep*stepsize);

	//rush
	$("#canvas").animate({left: ideallpos*100+"%"},{duration: 1000, easing: "linear",});
	$("#canvas2").animate({right: idealrpos*100+"%"},{duration: 1000, easing: "linear",});

	//bounce and charge
	$("#canvas").animate({left: (ideallpos-stepsize/4)*100+"%"},{duration: 50, easing: "linear",});
	$("#canvas2").animate({right: (idealrpos-stepsize/4)*100+"%"},{duration: 50, easing: "linear",});
	$("#canvas").animate({left: ideallpos*100+"%"},{duration: 50, easing: "linear", complete: function(){chargebull(); }});
	$("#canvas2").animate({right: idealrpos*100+"%"},{duration: 50, easing: "linear", complete: function(){chargebull2();}});

	//set lpos and rpos to ideal position
	lpos = ideallpos;
	rpos = idealrpos;
}

function hitleft(move){
	if (lsteps == -rsteps)
	setstep(lsteps+move,rsteps-move);
	else
	setstep(lsteps+move,rsteps);
}

function hitright(move){
	if (lsteps == -rsteps)
	setstep(lsteps-move,rsteps+move);
	else
	setstep(lsteps,rsteps+move);
}

function bounceoff(move){
	if (lsteps == -rsteps)
	setstep(lsteps-move,rsteps-move);
}

function setstep(leftstep,rightstep){
	//set position according to given points
	lsteps = leftstep;
	rsteps = rightstep;
	ideallpos = origin+(leftstep*stepsize);
	idealrpos = origin+(rightstep*stepsize);
	$("#canvas").css("left", (lpos*100)+"%");
	$("#canvas2").css("right", (rpos*100)+"%");

	// determine direction
	leftdir = 0;
	if (ideallpos > lpos) leftdir = 1;
	else if (ideallpos < lpos) leftdir = -1;
	rightdir = 0;
	if (idealrpos > rpos) rightdir = 1;
	else if (idealrpos < rpos) rightdir = -1;

	// make bull step
	step();

	//move bulls and continue charge / stop charging if not touching
	$("#canvas").animate({left: ideallpos*100+"%"},{duration: 300, easing: "linear", complete: function(){charge(1); }});
	$("#canvas2").animate({right: idealrpos*100+"%"},{duration: 300, easing: "linear", complete: function(){charge(2); }});

	// set lpos and rpos
	lpos = ideallpos;
	rpos = idealrpos;

	// step animation
	function step(){
		if (leftdir == 1) {
			if (rightdir == -1){stepani(1,1); stepani(2,0); startdir= 0; startdir2= 1; }
			else if (rightdir == 1){stepani(1,1); stepani(2,1);  startdir= 0; startdir2= 1; }
			else {stepani(1,1);   startdir= 0; startdir2= 1; }
		}
		else if (leftdir == -1) {
			if (rightdir == -1){stepani(1,0); stepani(2,0); startdir= 1; startdir2= 0; }
			else if (rightdir == 1){stepani(1,0); stepani(2,1); startdir= 1; startdir2= 0; }
			else {stepani(1,0); startdir= 1;  startdir2= 0; }
		}
		else if (rightdir == 1) {stepani(2,1); startdir= 1; startdir2= 0;}
		else if (rightdir == -1)  {stepani(2,0);  startdir2= 0; startdir2= 1;}
	}

	//use step animation or run animation according to step size
	function stepani(bull,dir){
		//dir: 1 forward / 0 backward
		if (steps >= 5){
			switch (bull){
				case 1: if (dir == 1) {
						stepforwardnow(); 
						} else {
						stepbackwardnow();
						} break;

				case 2: if (dir == 1) {
						stepforwardnow2(); 
						} else {
						stepbackwardnow2();
						} break;
						
				default: break;
			}
		}else{
			switch (bull){
				case 1: runbull(); break;
				case 2: runbull2(); break;
				default: break;
			}
		}
	}

	// charge animation
	function charge(bull){
		if (bull == 1){
			if (lsteps != -rsteps){
				bullrunning = false;
				bullcharging = false;
				bullkeeprun = false;
			}else{
				chargebullnow();
			}
		}
		else if (bull == 2){
			if (lsteps != -rsteps){
				bullrunning2 = false;
				bullcharging2 = false;
				bullkeeprun2 = false;
			}else{
				chargebullnow2();
			}
		}
	}
}


function setstepnorush(leftstep,rightstep){
	//set position according to given points, set immedietely without rush animation
	calstepsize();
	lsteps = leftstep;
	rsteps = rightstep;
	ideallpos = origin+(leftstep*stepsize);
	idealrpos = origin+(rightstep*stepsize);
	lpos = ideallpos;
	rpos = idealrpos;
	$("#canvas").css("left", (lpos*100)+"%");
	$("#canvas2").css("right", (rpos*100)+"%");
}

function setresultdisplay(leftstep,rightstep){
	// move arena and ringline for display win and lose
	if (leftstep >= steps){
		$(".arena").css("background-position","100% 50%");
		$("#ringline").css({
			"border-radius": "100% /50%",
			"border-top-left-radius": " 0%",
			"border-bottom-left-radius": " 0%",
			"border-left-style": " none",
			"width": " 45%",
			"left": " 5%",
		});
		setstepnorush(0,0);
	}
	else if (rightstep >= steps){
		$(".arena").css("background-position","0% 50%");
		$("#ringline").css({
			"border-radius": "100% /50%",
			"border-top-right-radius": " 0%",
			"border-bottom-right-radius": " 0%",
			"border-right-style": " none",
			"width": " 45%",
			"left": " 49%",
		});
		setstepnorush(0,0);
	}
	else setstepnorush(leftstep,rightstep);
}

function resetani(){
	lpos = 0;
	rpos = 0;
	lsteps = 0;
	rsteps = 0;
	stopbullrun(); stopbullrun2();
	$("#canvas").css("left", margin*100+"%");
	$("#canvas2").css("right", margin*100+"%");
}

function displayresulteffects(cond){
	switch (cond){
		case 1:  fpos = lpos - 0.15;
				 $("#resulttext").addClass("resulttext_win");
				 $(".fireworks").show();
				 $(".fireworks").css("left",fpos*100+"%"); break;
		case -1: $("#resulttext").addClass("resulttext_lose"); $(".fireworks").hide(); break;
		case 0: $("#resulttext").addClass("resulttext_draw"); $(".fireworks").hide(); break;
	}
}