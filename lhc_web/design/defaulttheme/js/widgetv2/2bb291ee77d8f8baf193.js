(window.webpackJsonpLiveHelperChat=window.webpackJsonpLiveHelperChat||[]).push([[4],{30:function(t,i,e){"use strict";Object.defineProperty(i,"__esModule",{value:!0}),i.proactiveChat=void 0;var n=function(){function t(t,i){for(var e=0;e<i.length;e++){var n=i[e];n.enumerable=n.enumerable||!1,n.configurable=!0,"value"in n&&(n.writable=!0),Object.defineProperty(t,n.key,n)}}return function(i,e,n){return e&&t(i.prototype,e),n&&t(i,n),i}}(),o=e(1),s=e(2);var a=new(function(){function t(){!function(t,i){if(!(t instanceof i))throw new TypeError("Cannot call a class as a function")}(this,t),this.params={},this.timeoutStatuscheck=null,this.timeoutActivity=null,this.attributes=null,this.chatEvents=null,this.dynamicInvitations=[],this.iddleTimeoutActivity=null,this.checkMessageTimeout=null,this.nextRescheduleTimeout=null}return n(t,[{key:"setParams",value:function(t,i,e){var n=this;this.params=t,this.attributes=i,this.chatEvents=e,this.initInvitation(),this.attributes.eventEmitter.addListener("tagAdded",(function(){n.initInvitation({init:0})})),this.attributes.eventEmitter.addListener("checkMessageOperator",(function(){n.initInvitation({init:0})}))}},{key:"showInvitation",value:function(t,i){var e=this.attributes.userSession.getSessionAttributes();if(!(0===i&&!0===this.attributes.widgetStatus.value||e.id)){if(t.inject_html&&t.invitation){var n=document.getElementsByTagName("head")[0],o=document.createElement("script");o.setAttribute("type","text/javascript"),o.setAttribute("src",LHC_API.args.lhc_base_url+this.attributes.lang+"chat/htmlsnippet/"+t.invitation+"/inv/0/?ts="+Date.now()),n.appendChild(o)}t.only_inject||(this.attributes.proactive=t,this.chatEvents.sendChildEvent("proactive",[t]),clearTimeout(this.checkMessageTimeout),clearTimeout(this.nextRescheduleTimeout))}}},{key:"initInvitation",value:function(t){var i=this;clearTimeout(this.checkMessageTimeout);var e=this.attributes.userSession.getSessionAttributes(),n=t&&0===t.init?0:1;if(!e.id&&1==this.attributes.onlineStatus.value){var a={vid:this.attributes.userSession.getVID(),dep:this.attributes.department.join(",")};LHC_API.args.priority&&(a.priority=LHC_API.args.priority),LHC_API.args.operator&&(a.operator=LHC_API.args.operator),this.attributes.identifier&&(a.idnt=this.attributes.identifier),this.attributes.tag&&(a.tag=this.attributes.tag),a.l=encodeURIComponent(window.location.href.substring(window.location.protocol.length)),a.dt=encodeURIComponent(document.title),a.init=n,o.helperFunctions.makeRequest(LHC_API.args.lhc_base_url+this.attributes.lang+"widgetrestapi/checkinvitation",{params:a},(function(t){if(t.invitation){var e={vid_id:t.vid_id,invitation:t.invitation,inject_html:t.inject_html,qinv:t.qinv};setTimeout((function(){i.showInvitation(e,n)}),!0===i.attributes.widgetStatus.value?0:t.delay||0)}else LHC_API.args.check_messages&&(i.checkMessageTimeout=setTimeout((function(){i.initInvitation({init:0})}),1e3*i.params.interval));t.next_reschedule&&(i.nextRescheduleTimeout=setTimeout((function(){i.initInvitation({init:0})}),t.next_reschedule)),t.dynamic&&t.dynamic.forEach((function(e){if(i.dynamicInvitations.push(e.id),1===e.type)s.domEventsHandler.listen(document,"mouseout",(function(n){var o=(n=n||window.event).relatedTarget||n.toElement;o&&"HTML"!=o.nodeName||(i.showInvitation({vid_id:t.vid_id,invitation:e.id,inject_html:e.inject_html,qinv:t.qinv,only_inject:e.only_inject}),e.every_time||s.domEventsHandler.unlisten("lhc_inv_mouse_out_"+e.id))}),"lhc_inv_mouse_out_"+e.id);else if(2===e.type){i.iddleTimeoutActivityReset=function(){clearTimeout(i.iddleTimeoutActivity),i.iddleTimeoutActivity=setTimeout((function(){i.showInvitation({vid_id:t.vid_id,invitation:e.id,inject_html:e.inject_html,qinv:t.qinv,only_inject:e.only_inject}),clearTimeout(i.iddleTimeoutActivity),e.every_time||(["mousemove","mousedown","click","scroll","keypress","load"].forEach((function(t){s.domEventsHandler.unlisten("lhc_inv_iddl_win_"+t)})),["mousemove","scroll","touchstart","touchend"].forEach((function(t){s.domEventsHandler.unlisten("lhc_inv_iddl_doc_"+t)})))}),1e3*e.iddle_for)},i.iddleTimeoutActivityReset(),["mousemove","mousedown","click","scroll","keypress","load"].forEach((function(t){s.domEventsHandler.listen(window,t,i.iddleTimeoutActivityReset,"lhc_inv_iddl_win_"+t)})),["mousemove","scroll","touchstart","touchend"].forEach((function(t){s.domEventsHandler.listen(document,t,i.iddleTimeoutActivityReset,"lhc_inv_iddl_doc_"+t)}))}}))}))}}}]),t}());i.proactiveChat=a}}]);
//# sourceMappingURL=2bb291ee77d8f8baf193.js.map