(self.webpackChunk=self.webpackChunk||[]).push([[737],{737:a=>{var o={cancelcolorbox:function(){$("#myModal").foundation("reveal","close")},initializeModal:function(a){var o=null!=a?a:"myModal";0==$("#"+o).length&&(0==$("#widget-layout").length?$("body"):$("#widget-layout")).prepend('<div id="'+o+'" class="modal bs-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true"></div>')},hideCallback:!1,revealModal:function(a){$("body").hasClass("modal-open")?!1===o.hideCallback?$("#myModal").modal("dispose"):$("#myModal").modal("hide"):$("#myModal").modal("dispose"),void 0!==a.hidecallback?o.hideCallback=!0:o.hideCallback=!1,o.initializeModal("myModal");var l={show:!0,focus:!($("#admin-body").length>0),backdrop:!($("#admin-body").length>0)||void 0!==a.backdrop&&1==a.backdrop};if(void 0===a.iframe)void 0!==a.loadmethod&&"post"==a.loadmethod?jQuery.post(a.url,a.datapost,(function(e){void 0!==a.showcallback&&$("#myModal").on("shown.bs.modal",a.showcallback),void 0!==a.hidecallback&&$("#myModal").on("hide.bs.modal",a.hidecallback),$("#myModal").html(e),new bootstrap.Modal("#myModal",l).show(),o.setCenteredDraggable()})):jQuery.get(a.url,(function(e){void 0!==a.showcallback&&$("#myModal").on("shown.bs.modal",a.showcallback),void 0!==a.hidecallback&&$("#myModal").on("hide.bs.modal",a.hidecallback),$("#myModal").html(e),new bootstrap.Modal("#myModal",l).show(),o.setCenteredDraggable()}));else{var e="",d="";void 0===a.hideheader?e='<div class="modal-header"><h4 class="modal-title" id="myModalLabel"><span class="material-icons">info</span>'+(void 0===a.title?"":a.title)+'</h4><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>':d=(void 0===a.title?"":"<b>"+a.title+"</b>")+'<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';var t=void 0===a.modalbodyclass?"":" "+a.modalbodyclass;void 0!==a.showcallback&&$("#myModal").on("shown.bs.modal",a.showcallback),void 0!==a.hidecallback&&$("#myModal").on("hide.bs.modal",a.hidecallback),$("#myModal").html('<div class="modal-dialog modal-xl"><div class="modal-content">'+e+'<div class="modal-body'+t+'">'+d+'<iframe src="'+a.url+'" frameborder="0" style="width:100%" height="'+a.height+'" /></div></div></div>'),new bootstrap.Modal("#myModal",l).show(),o.setCenteredDraggable()}},setCenteredDraggable:function(){if($("#admin-body").length>0){var a=$("#myModal .modal-dialog"),l=o.rememberPositions(),e=o.getPositions();(null===l||parseInt(l[1])>e.width||parseInt(l[0])>e.height||parseInt(l[0])<0||a.width()+parseInt(l[1])<0)&&(l=[(e.height-a.height())/2,(e.width-a.width())/2]),a.draggabilly({handle:".modal-header",containment:"#admin-body"}).css({top:parseInt(l[0]),left:parseInt(l[1])}).on("dragEnd",(function(l,e){o.rememberPositions(a.position().top,a.position().left)}))}},rememberPositions:function(a,o){if(sessionStorage)if(a&&o)try{var l=sessionStorage.setItem("mpos",a+","+o)}catch(a){}else try{if(null!==(l=sessionStorage.getItem("mpos")))return l.split(",")}catch(a){}return null},getPositions:function(){return{width:window.innerWidth||document.documentElement.clientWidth||document.body.clientWidth||0,height:window.innerHeight||document.documentElement.clientHeight||document.body.clientHeight||0}}};a.exports=o}}]);