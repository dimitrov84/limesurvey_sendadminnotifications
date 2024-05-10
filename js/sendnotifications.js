$(document).on('pjax:scriptcomplete ready', function () {
    if(typeof sendNotificationVar!='undefined' && typeof surveyIDVar!='undefined'){
        if ( surveyIDVar.indexOf("?")>0 ) {
            surveyIDVar = surveyIDVar.substring(0,surveyIDVar.indexOf("?"));
        }
        addSendSubmitNotification_list();
    }    
});

function addSendSubmitNotification_list() {
    if(typeof screenAction!='undefined' && typeof screenAction!='undefined') {
        if ( screenAction == 'responses_browse' ) {
            $('input[id*=id_]').each ( function () {
                id = $(this).val(); 
                row_id = $(this).attr('id'); 
                if ( row_id !== 'id_all' ) {
                    if ( id ) {
                        // Find the closest TD
                        div_id = $(this).closest('tr').find('div.ls-action_dropdown').prop('id');
                        div_id = div_id.replace("dropdown","dropdownmenu");
                        if ( $('#'+div_id).children().find('a.send-submit-notification').length < 1 ) {
                            $('#'+div_id).prepend(
                                '<li><a class="dropdown-item send-submit-notification" role="button" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+id+',\'R\');"><i class="fa fa-share-square"></i>Send Basic AND Detailed Admin Notification Emails</a></li>');
                        }
                        else {
                            //$.pjax.reload('#'+div_id);                            
                            $('ul[id="'+div_id+'"]').each(function() {
                                $(this).children().find('a.send-submit-notification').remove();
                                $(this).prepend(
                                    '<li><a class="dropdown-item send-submit-notification" role="button" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+id+',\'R\');"><i class="fa fa-share-square"></i>Send Basic AND Detailed Admin Notification Emails</a></li>');
                            });
                        }
                    }
                }
            });
        }
        if ( screenAction == 'tokens_browse' ) {
            $('input[id*=tid]').each ( function () {
                tid = $(this).val(); 
                row_id = $(this).attr('id'); 
                if ( row_id !== 'tid_all' ) {
                    if ( tid ) {
                        // Find the closest TD
                        div_id = $(this).closest('tr').find('div.ls-action_dropdown').prop('id');
                        div_id = div_id.replace("dropdown","dropdownmenu");
                        if ( $('#'+div_id).children().find('a.send-submit-notification').length < 1 ) {
                            $('#'+div_id).prepend(
                                '<div class="send-submit-notification" data-bs-toggle="tooltip" title="" data-bs-original-title=""><li><a class="dropdown-item send-submit-notification" role="button" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+tid+',\'T\');"><i class="fa fa-share-square"></i>Send Basic AND Detailed Admin Notification Emails</a></li></div>');
                        }
                        else {
                            //$.pjax.reload('#'+div_id);
                            $('ul[id="'+div_id+'"]').each(function() {
                                $(this).children().find('div.send-submit-notification').remove();
                                $(this).prepend(
                                    '<div class="send-submit-notification" data-bs-toggle="tooltip" title="" data-bs-original-title=""><li><a class="dropdown-item send-submit-notification" role="button" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+tid+',\'T\');"><i class="fa fa-share-square"></i>Send Basic AND Detailed Admin Notification Emails</a></li></div>');
                            });
                            
                        }
                    }
                }
            });
        }
        if ( screenAction == 'responses_view' ) {
            // looking for browsermenubarid
            if ( $('div.ls-topbar-buttons').children('#send-submit-notification-button').length < 1 ) {
                $('div.ls-topbar-buttons').first().prepend(
                    '<a id="send-submit-notification-button" class="btn btn-outline-secondary" title="" rel="tooltip" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+current_response_id+',\'R\');" data-original-title="Send Basic AND Detailed Admin Notification Emails"><span class="sr-only">Send Basic AND Detailed Admin Notification Emails</span><span class="fa fa-share-square" aria-hidden="true"></span> Send Basic/Admin Notification Emails</a>'
                );
            }
        }
    }
}

function sendResponseScreenNotifications (iSid, iId) {
    console.log(iSid+' --> '+iId);
}

function sendResponseScreenNotifications_old ( surveyid, responseid, type ) {
	var jsonUrl=sendNotificationVar.jsonurl;
	$("#updatedsrid").remove();
	// Remove the mail image - 'emailtemplates_30.png' height='16' width='16'
	// Show the ajax loading image
	$("#sendnotification_img_"+responseid).attr("src", "/limesurvey/images/ajax-loader.gif");
	$("#sendnotification_img_"+responseid).attr("width", "60");

	$.ajax({
                url: jsonUrl,
                dataType : 'json',
                data : {surveyid: surveyid, tid: responseid, type: type},
                success: function(data){
                    var $dialog = $('<div id="updatedsrid"></div>')
                        .html("<p>"+data.message+"</p>")
                        .dialog({
                            title: data.status,
                            dialogClass: 'updatedsrid',
                            buttons: { 
                                "Ok": function() { 
                                    $(this).dialog("close");
                                    //$("#sendnotification_img_"+responseid).attr("src", "/limesurvey/styles/blobblueish/images/emailtemplates_30.png");
                                    //	$("#sendnotification_img_"+responseid).attr("width", size);  
                                },
                                //"Reload": function() { window.location.reload(); } 
                            },
                            modal: true,
                            close: function () {
                                $(this).remove();
                            }
                        });
                },
                error: function(){
                    var $dialog = $('<div id="updatedsrid"></div>')
                        .html("<p>An error was occured</p>")
                        .dialog({
                            title: "Error",
                            dialogClass: 'updatedsrid',
                            buttons: { 
                                "Ok": function() { 
                                    $(this).dialog("close"); 
                                    //$("#sendnotification_img_"+responseid).attr("src", "/limesurvey/styles/blobblueish/images/emailtemplates_30.png");
                                    //                    $("#sendnotification_img_"+responseid).attr("width", size);
                                },
                            },
                            modal: true,
                            close: function () {
                                $(this).remove();
                            }
                        });
                },
	});
}

