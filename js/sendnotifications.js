$(document).ready( function () {
    setTimeout(function setupSendTimeout() { addSendTimeout(); setTimeout(setupSendTimeout,1000); }, 1000);
});

function addSendTimeout() {
    if(typeof sendNotificationVar!='undefined' && typeof surveyIDVar!='undefined'){
        if ( $('a[data-notification="yes"]') == 'undefined' || $('a[data-notification="yes"]').size()<=0 ) {
            addSendSubmitNotification_list();
        }
    }
}

function addSendSubmitNotification_list() {
    if(typeof screenAction!='undefined' && typeof screenAction!='undefined') {
        if ( screenAction == 'responses_browse' ) {
            $('input[id*=id_]').each ( function () {
                id = $(this).val(); 
                row_id = $(this).attr('id'); 
                if ( row_id !== 'id_all' ) {
                    if ( id ) {
                        // Find the closest TD
                        $(this).closest('tr').find('.button-column').append(
                            '<a data-notification="yes" class="btn btn-default btn-xs" data-toggle="tooltip" title="" rel="tooltip" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+id+',\'R\');" data-original-title="Send Basic AND Detailed Admin Notification Emails"><span class="sr-only">Send Basic AND Detailed Admin Notification Emails</span><span class="fa fa-share-square" aria-hidden="true"></span></a>');
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
                        $(this).closest('tr').find('.button-column').append(
                            '<a data-notification="yes" class="btn btn-default btn-xs" data-toggle="tooltip" title="" rel="tooltip" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+tid+',\'T\');" data-original-title="Send Basic AND Detailed Admin Notification Emails"><span class="sr-only">Send Basic AND Detailed Admin Notification Emails</span><span class="fa fa-share-square" aria-hidden="true"></span></a>');
                    }
                }
            });
        }
        if ( screenAction == 'responses_view' ) {
            // looking for browsermenubarid
            $('#browsermenubarid').children().first().children().first().prepend(
                '<a data-notification="yes" class="btn btn-default" data-toggle="tooltip" title="" rel="tooltip" onClick="sendResponseScreenNotifications_old ('+surveyIDVar+','+current_response_id+',\'R\');" data-original-title="Send Basic AND Detailed Admin Notification Emails"><span class="sr-only">Send Basic AND Detailed Admin Notification Emails</span><span class="fa fa-share-square" aria-hidden="true"></span> Send Basic/Admin Notification Emails</a>'
            );
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
                        .html("<p>We encountered an error</p>")
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

