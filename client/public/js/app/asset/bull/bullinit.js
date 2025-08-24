
// init scripts
// Global Scripts
var canvas, stage, exportRoot, anim_container, dom_overlay_container, fnStartAnimation;
function initBull() {
	canvas = document.getElementById("canvas");
	anim_container = document.getElementById("animation_container");
	dom_overlay_container = document.getElementById("dom_overlay_container");
	var comp=AdobeAn.getComposition("01842B809FB5514483C3D771BC9A3A77");
	var lib=comp.getLibrary();
	var loader = new createjs.LoadQueue(false);
	loader.addEventListener("fileload", function(evt){handleFileLoad(evt,comp)});
	loader.addEventListener("complete", function(evt){handleComplete(evt,comp)});
	var lib=comp.getLibrary();
	loader.loadManifest(lib.properties.manifest);
}
function handleFileLoad(evt, comp) {
	var images=comp.getImages();	
	if (evt && (evt.item.type == "image")) { images[evt.item.id] = evt.result; }	
}
function handleComplete(evt,comp) {
	//This function is always called, irrespective of the content. You can use the variable "stage" after it is created in token create_stage.
	var lib=comp.getLibrary();
	var ss=comp.getSpriteSheet();
	var queue = evt.target;
	var ssMetadata = lib.ssMetadata;
	for(i=0; i<ssMetadata.length; i++) {
		ss[ssMetadata[i].name] = new createjs.SpriteSheet( {"images": [queue.getResult(ssMetadata[i].name)], "frames": ssMetadata[i].frames} )
	}
	exportRoot = new lib.bullrush();
	stage = new lib.Stage(canvas);	
	//Registers the "tick" event listener.
	fnStartAnimation = function() {
		stage.addChild(exportRoot);
		createjs.Ticker.setFPS(lib.properties.fps);
		createjs.Ticker.addEventListener("tick", stage)
		stage.addEventListener("tick", handleTick)
		function getProjectionMatrix(container, totalDepth) {
			var focalLength = 528.25;
			var projectionCenter = { x : lib.properties.width/2, y : lib.properties.height/2 };
			var scale = (totalDepth + focalLength)/focalLength;
			var scaleMat = new createjs.Matrix2D;
			scaleMat.a = 1/scale;
			scaleMat.d = 1/scale;
			var projMat = new createjs.Matrix2D;
			projMat.tx = -projectionCenter.x;
			projMat.ty = -projectionCenter.y;
			projMat = projMat.prependMatrix(scaleMat);
			projMat.tx += projectionCenter.x;
			projMat.ty += projectionCenter.y;
			return projMat;
		}
		function handleTick(event) {
			var cameraInstance = exportRoot.___camera___instance;
			if(cameraInstance !== undefined && cameraInstance.pinToObject !== undefined)
			{
				cameraInstance.x = cameraInstance.pinToObject.x + cameraInstance.pinToObject.pinOffsetX;
				cameraInstance.y = cameraInstance.pinToObject.y + cameraInstance.pinToObject.pinOffsetY;
				if(cameraInstance.pinToObject.parent !== undefined && cameraInstance.pinToObject.parent.depth !== undefined)
				cameraInstance.depth = cameraInstance.pinToObject.parent.depth + cameraInstance.pinToObject.pinOffsetZ;
			}
			applyLayerZDepth(exportRoot);
		}
		function applyLayerZDepth(parent)
		{
			var cameraInstance = parent.___camera___instance;
			var focalLength = 528.25;
			var projectionCenter = { 'x' : 0, 'y' : 0};
			if(parent === exportRoot)
			{
				var stageCenter = { 'x' : lib.properties.width/2, 'y' : lib.properties.height/2 };
				projectionCenter.x = stageCenter.x;
				projectionCenter.y = stageCenter.y;
			}
			for(child in parent.children)
			{
				var layerObj = parent.children[child];
				if(layerObj == cameraInstance)
					continue;
				applyLayerZDepth(layerObj, cameraInstance);
				if(layerObj.layerDepth === undefined)
					continue;
				if(layerObj.currentFrame != layerObj.parent.currentFrame)
				{
					layerObj.gotoAndPlay(layerObj.parent.currentFrame);
				}
				var matToApply = new createjs.Matrix2D;
				var cameraMat = new createjs.Matrix2D;
				var totalDepth = layerObj.layerDepth ? layerObj.layerDepth : 0;
				var cameraDepth = 0;
				if(cameraInstance && !layerObj.isAttachedToCamera)
				{
					var mat = cameraInstance.getMatrix();
					mat.tx -= projectionCenter.x;
					mat.ty -= projectionCenter.y;
					cameraMat = mat.invert();
					cameraMat.prependTransform(projectionCenter.x, projectionCenter.y, 1, 1, 0, 0, 0, 0, 0);
					cameraMat.appendTransform(-projectionCenter.x, -projectionCenter.y, 1, 1, 0, 0, 0, 0, 0);
					if(cameraInstance.depth)
						cameraDepth = cameraInstance.depth;
				}
				if(layerObj.depth)
				{
					totalDepth = layerObj.depth;
				}
				//Offset by camera depth
				totalDepth -= cameraDepth;
				if(totalDepth < -focalLength)
				{
					matToApply.a = 0;
					matToApply.d = 0;
				}
				else
				{
					if(layerObj.layerDepth)
					{
						var sizeLockedMat = getProjectionMatrix(parent, layerObj.layerDepth);
						if(sizeLockedMat)
						{
							sizeLockedMat.invert();
							matToApply.prependMatrix(sizeLockedMat);
						}
					}
					matToApply.prependMatrix(cameraMat);
					var projMat = getProjectionMatrix(parent, totalDepth);
					if(projMat)
					{
						matToApply.prependMatrix(projMat);
					}
				}
				layerObj.transformMatrix = matToApply;
			}
		}
	}	    
	//Code to support hidpi screens and responsive scaling.
	function makeResponsive(isResp, respDim, isScale, scaleType) {		
		var lastW, lastH, lastS=1;		
		window.addEventListener('resize', resizeCanvas);		
		resizeCanvas();		
		function resizeCanvas() {			
			var w = lib.properties.width, h = lib.properties.height;			
			var iw = window.innerWidth, ih=window.innerHeight;			
			var pRatio = window.devicePixelRatio || 1, xRatio=iw/w, yRatio=ih/h, sRatio=1;			
			if(isResp) {                
				if((respDim=='width'&&lastW==iw) || (respDim=='height'&&lastH==ih)) {                    
					sRatio = lastS;                
				}				
				else if(!isScale) {					
					if(iw<w || ih<h)						
						sRatio = Math.min(xRatio, yRatio);				
				}				
				else if(scaleType==1) {					
					sRatio = Math.min(xRatio, yRatio);				
				}				
				else if(scaleType==2) {					
					sRatio = Math.max(xRatio, yRatio);				
				}			
			}			
			canvas.width = w*pRatio*sRatio;			
			canvas.height = h*pRatio*sRatio;
			//canvas.style.width = dom_overlay_container.style.width = anim_container.style.width =  w*sRatio+'px';				
			//canvas.style.height = anim_container.style.height = dom_overlay_container.style.height = h*sRatio+'px';
			canvas.style.width = "20%";
			canvas.style.height = "auto";
			stage.scaleX = pRatio*sRatio;			
			stage.scaleY = pRatio*sRatio;			
			lastW = iw; lastH = ih; lastS = sRatio;            
			stage.tickOnUpdate = false;            
			stage.update();            
			stage.tickOnUpdate = true;		
		}
	}
	makeResponsive(false,'both',false,1);	
	AdobeAn.compositionLoaded(lib.properties.id);
	fnStartAnimation();
}


// Global Scripts
var canvas2, stage2, exportRoot2, anim_container2, dom_overlay_container2, fnStartAnimation2;
function initBull2() {
	canvas2 = document.getElementById("canvas2");
	anim_container2 = document.getElementById("animation_container2");
	dom_overlay_container2 = document.getElementById("dom_overlay_container2");
	var comp=AdobeAn.getComposition("01842B809FB5514483C3D771BC9A3A78");
	var lib2=comp.getLibrary();
	var loader = new createjs.LoadQueue(false);
	loader.addEventListener("fileload", function(evt){handleFileLoad2(evt,comp)});
	loader.addEventListener("complete", function(evt){handleComplete2(evt,comp)});
	var lib2=comp.getLibrary();
	loader.loadManifest(lib2.properties.manifest);
}
function handleFileLoad2(evt, comp) {
	var images=comp.getImages();	
	if (evt && (evt.item.type == "image")) { images[evt.item.id] = evt.result; }	
}
function handleComplete2(evt,comp) {
	//This function is always called, irrespective of the content. You can use the variable "stage2" after it is created in token create_stage2.
	var lib2=comp.getLibrary();
	var ss=comp.getSpriteSheet();
	var queue = evt.target;
	var ssMetadata = lib2.ssMetadata;
	for(i=0; i<ssMetadata.length; i++) {
		ss[ssMetadata[i].name] = new createjs.SpriteSheet( {"images": [queue.getResult(ssMetadata[i].name)], "frames": ssMetadata[i].frames} )
	}
	exportRoot2 = new lib2.bullrush2();
	stage2 = new lib2.Stage(canvas2);	
	//Registers the "tick" event listener.
	fnStartAnimation2 = function() {
		stage2.addChild(exportRoot2);
		createjs.Ticker.setFPS(lib2.properties.fps);
		createjs.Ticker.addEventListener("tick", stage2)
		stage2.addEventListener("tick", handleTick)
		function getProjectionMatrix(container, totalDepth) {
			var focalLength = 528.25;
			var projectionCenter = { x : lib2.properties.width/2, y : lib2.properties.height/2 };
			var scale = (totalDepth + focalLength)/focalLength;
			var scaleMat = new createjs.Matrix2D;
			scaleMat.a = 1/scale;
			scaleMat.d = 1/scale;
			var projMat = new createjs.Matrix2D;
			projMat.tx = -projectionCenter.x;
			projMat.ty = -projectionCenter.y;
			projMat = projMat.prependMatrix(scaleMat);
			projMat.tx += projectionCenter.x;
			projMat.ty += projectionCenter.y;
			return projMat;
		}
		function handleTick(event) {
			var cameraInstance = exportRoot2.___camera___instance;
			if(cameraInstance !== undefined && cameraInstance.pinToObject !== undefined)
			{
				cameraInstance.x = cameraInstance.pinToObject.x + cameraInstance.pinToObject.pinOffsetX;
				cameraInstance.y = cameraInstance.pinToObject.y + cameraInstance.pinToObject.pinOffsetY;
				if(cameraInstance.pinToObject.parent !== undefined && cameraInstance.pinToObject.parent.depth !== undefined)
				cameraInstance.depth = cameraInstance.pinToObject.parent.depth + cameraInstance.pinToObject.pinOffsetZ;
			}
			applyLayerZDepth2(exportRoot2);
		}
		function applyLayerZDepth2(parent)
		{
			var cameraInstance = parent.___camera___instance;
			var focalLength = 528.25;
			var projectionCenter = { 'x' : 0, 'y' : 0};
			if(parent === exportRoot2)
			{
				var stage2Center = { 'x' : lib2.properties.width/2, 'y' : lib2.properties.height/2 };
				projectionCenter.x = stage2Center.x;
				projectionCenter.y = stage2Center.y;
			}
			for(child in parent.children)
			{
				var layerObj = parent.children[child];
				if(layerObj == cameraInstance)
					continue;
				applyLayerZDepth2(layerObj, cameraInstance);
				if(layerObj.layerDepth === undefined)
					continue;
				if(layerObj.currentFrame != layerObj.parent.currentFrame)
				{
					layerObj.gotoAndPlay(layerObj.parent.currentFrame);
				}
				var matToApply = new createjs.Matrix2D;
				var cameraMat = new createjs.Matrix2D;
				var totalDepth = layerObj.layerDepth ? layerObj.layerDepth : 0;
				var cameraDepth = 0;
				if(cameraInstance && !layerObj.isAttachedToCamera)
				{
					var mat = cameraInstance.getMatrix();
					mat.tx -= projectionCenter.x;
					mat.ty -= projectionCenter.y;
					cameraMat = mat.invert();
					cameraMat.prependTransform(projectionCenter.x, projectionCenter.y, 1, 1, 0, 0, 0, 0, 0);
					cameraMat.appendTransform(-projectionCenter.x, -projectionCenter.y, 1, 1, 0, 0, 0, 0, 0);
					if(cameraInstance.depth)
						cameraDepth = cameraInstance.depth;
				}
				if(layerObj.depth)
				{
					totalDepth = layerObj.depth;
				}
				//Offset by camera depth
				totalDepth -= cameraDepth;
				if(totalDepth < -focalLength)
				{
					matToApply.a = 0;
					matToApply.d = 0;
				}
				else
				{
					if(layerObj.layerDepth)
					{
						var sizeLockedMat = getProjectionMatrix(parent, layerObj.layerDepth);
						if(sizeLockedMat)
						{
							sizeLockedMat.invert();
							matToApply.prependMatrix(sizeLockedMat);
						}
					}
					matToApply.prependMatrix(cameraMat);
					var projMat = getProjectionMatrix(parent, totalDepth);
					if(projMat)
					{
						matToApply.prependMatrix(projMat);
					}
				}
				layerObj.transformMatrix = matToApply;
			}
		}
	}	    
	//Code to support hidpi screens and responsive scaling.
	function makeResponsive2(isResp, respDim, isScale, scaleType) {		
		var lastW, lastH, lastS=1;		
		window.addEventListener('resize', resizecanvas2);		
		resizecanvas2();		
		function resizecanvas2() {			
			var w = lib2.properties.width, h = lib2.properties.height;			
			var iw = window.innerWidth, ih=window.innerHeight;			
			var pRatio = window.devicePixelRatio || 1, xRatio=iw/w, yRatio=ih/h, sRatio=1;			
			if(isResp) {                
				if((respDim=='width'&&lastW==iw) || (respDim=='height'&&lastH==ih)) {                    
					sRatio = lastS;                
				}				
				else if(!isScale) {					
					if(iw<w || ih<h)						
						sRatio = Math.min(xRatio, yRatio);				
				}				
				else if(scaleType==1) {					
					sRatio = Math.min(xRatio, yRatio);				
				}				
				else if(scaleType==2) {					
					sRatio = Math.max(xRatio, yRatio);				
				}			
			}			
			canvas2.width = w*pRatio*sRatio;			
			canvas2.height = h*pRatio*sRatio;
			//canvas2.style.width = dom_overlay_container2.style.width = anim_container2.style.width =  w*sRatio+'px';				
			//canvas2.style.height = anim_container2.style.height = dom_overlay_container2.style.height = h*sRatio+'px';
			canvas2.style.width = "20%";
			canvas2.style.height = "auto";
			stage2.scaleX = pRatio*sRatio;			
			stage2.scaleY = pRatio*sRatio;			
			lastW = iw; lastH = ih; lastS = sRatio;            
			stage2.tickOnUpdate = false;            
			stage2.update();            
			stage2.tickOnUpdate = true;		
		}
	}
	makeResponsive2(false,'both',false,1);	
	AdobeAn.compositionLoaded(lib2.properties.id);
	fnStartAnimation2();
}

//animation scripts
//b1
var bullrunning = false
var bullkeeprun = false;
var bullcharging = false;
var bullstepforward = false;
var bullstepbackward = false;
var startdir = 0; // hit direction 0: front first 1: back first
var initposframe = true; // bull in initial pose
function runbull(){
	bullrunning = true;
	bullcharging = false;
	bullkeeprun = false;
	if (initposframe)
	exportRoot.gotoAndPlay(1);
}
function keeprunbull(){
	bullrunning = true;
	bullcharging = false;
	bullkeeprun = true;
	if (initposframe)
	exportRoot.gotoAndPlay(1);
}
function stopbullrun(){
	bullrunning = false;
	bullcharging = false;
	bullkeeprun = false;
	if (initposframe)
	exportRoot.gotoAndPlay(1);
}
function chargebull(){
	bullrunning = false;
	bullcharging = true;
	bullkeeprun = false;
	if (initposframe)
	{if (startdir == 0) exportRoot.gotoAndPlay(37);
	else exportRoot.gotoAndPlay(65);}
}
function chargebullnow(){
	bullrunning = false;
	bullcharging = true;
	bullkeeprun = false;
	if (startdir == 0) exportRoot.gotoAndPlay(37);
	else exportRoot.gotoAndPlay(65);
}
function stepforward(){
	 bullstepforward = true;
}
function stepbackward(){
	bullstepbackward = true;
}
function stepforward(){
	 bullstepforward = true;
}
function stepbackward(){
	bullstepbackward = true;
}
function stepforwardnow(){
	 bullstepforward = true;
	exportRoot.gotoAndPlay(94);
}
function stepbackwardnow(){
	bullstepbackward = true;
	exportRoot.gotoAndPlay(123);
}
//b2
var bullrunning2 = false
var bullkeeprun2 = false;
var bullcharging2 = false;
var bullstepforward2 = false;
var bullstepbackward2 = false;
var startdir2 = 0; // hit direction 0: front first 1: back first
var initposframe2 = true; // bull in initial pose
function runbull2(){
	bullrunning2 = true;
	bullcharging2 = false;
	bullkeeprun2 = false;
	if (initposframe2)
	exportRoot2.gotoAndPlay(1);
}
function keeprunbull2(){
	bullrunning2 = true;
	bullcharging2 = false;
	bullkeeprun2 = true;
	if (initposframe2)
	exportRoot2.gotoAndPlay(1);
}
function stopbullrun2(){
	bullrunning2 = false;
	bullcharging2 = false;
	bullkeeprun2 = false;
	if (initposframe2)
	exportRoot2.gotoAndPlay(1);
}
function chargebull2(){
	bullrunning2 = false;
	bullcharging2 = true;
	bullkeeprun2 = false;
	if (initposframe2)
	{if (startdir2 == 0) exportRoot2.gotoAndPlay(37);
	else exportRoot2.gotoAndPlay(65);}
}
function chargebullnow2(){
	bullrunning2 = false;
	bullcharging2 = true;
	bullkeeprun2 = false;
	if (startdir2 == 0) exportRoot2.gotoAndPlay(37);
	else exportRoot2.gotoAndPlay(65);
}
function stepforward2(){
	 bullstepforward2 = true;
}
function stepbackward2(){
	bullstepbackward2 = true;
}
function stepforward2(){
	 bullstepforward2 = true;
}
function stepbackward2(){
	bullstepbackward2 = true;
}
function stepforwardnow2(){
	 bullstepforward2 = true;
	exportRoot2.gotoAndPlay(94);
}
function stepbackwardnow2(){
	bullstepbackward2 = true;
	exportRoot2.gotoAndPlay(123);
}
