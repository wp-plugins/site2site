jQuery(document).ready(function() {

	posts = new Array();
	posts_index = 0;

	jQuery('.s2s-item').each(function(element) {
		var post_id = jQuery(this).attr('data-id');
		posts.push(post_id);
	});

	if(posts.length > 0) {
		doAjaxCall();
		jQuery('#s2s-copy').attr('disabled','disabled');
		jQuery('#s2s-copy').attr('value','Copying...');
	}

	if(jQuery('#s2s-origin-site').length > 0 && jQuery('#s2s-origin-site').val() != '-1') {
		load_combo_cpts(jQuery('#s2s-origin-site').val(),jQuery('#s2s-selected-cpt').val());
	}

	if(jQuery('#s2s-target-site').length > 0 && jQuery('#s2s-target-site').val() != '-1') {
		load_combo_authors(jQuery('#s2s-target-site').val(),jQuery('#s2s-selected-author').val());
	}

	jQuery('#s2s-origin-site').change(function() {
		load_combo_cpts(jQuery(this).val(),-1);
	});

	jQuery('#s2s-target-site').change(function() {
		load_combo_authors(jQuery(this).val(),-1);
	});

	jQuery('#s2s-author-option-1').click(function() {
		jQuery('#s2s-row-select-author').fadeOut('slow');
	});

	jQuery('#s2s-author-option-2').click(function() {
		jQuery('#s2s-row-select-author').fadeIn('slow');
	});

});




function doAjaxCall() {
	var post_id = posts[posts_index];
	jQuery('#s2s-item-' + post_id).addClass('s2s-loading');
	var origin_site = jQuery('#s2s-origin-site').val();
	var target_site = jQuery('#s2s-target-site').val();
	if(jQuery('#s2s-author-option-1').is(':checked')) {
		var author_option = '1';
	} else {
		var author_option = '2';
	}
	var author_option = jQuery("[name='s2s-author-options']:checked").val();
	var author = jQuery('#s2s-author').val();
    post_data = 'action=s2s_copy_item&id=' + post_id + '&origin=' + origin_site + '&target=' + target_site + '&option=' + author_option + '&author=' + author;
    jQuery.ajax({
		type: 'post',
		url: ajaxurl,
		data: post_data,
		dataType: 'json',
		error: function(XMLHttpRequest, textStatus, errorThrown){
			jQuery('#s2s-item-' + post_id).removeClass('s2s-loading');
			jQuery('#s2s-item-' + post_id).addClass('s2s-ko');
			posts_index++;
			if(posts_index < posts.length) {
				doAjaxCall();
			} else {
				jQuery('#s2s-copy').removeAttr('disabled');
				jQuery('#s2s-copy').attr('value','Copy content now');
			}
		},
		success: function(data, textStatus){
			if(data.response && data.response == 'OK') {
				jQuery('#s2s-item-' + post_id).removeClass('s2s-loading');
				jQuery('#s2s-item-' + post_id).addClass('s2s-ok');
			} else {
				jQuery('#s2s-item-' + post_id).removeClass('s2s-loading');
				jQuery('#s2s-item-' + post_id).addClass('s2s-ko');				
			}
			posts_index++;
			if(posts_index < posts.length) {
				doAjaxCall();
			} else {
				jQuery('#s2s-copy').removeAttr('disabled');
				jQuery('#s2s-copy').attr('value','Copy content now');
			}			
		}
	});
}

function load_combo_cpts( selected_site, selected_cpt ) {
	if(selected_site != '-1') {
		jQuery('#s2s-cpt').empty();
	    post_data = 'action=s2s_load_cpts&site=' + selected_site;
	    jQuery.ajax({
			type: 'post',
			url: ajaxurl,
			data: post_data,
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown){
			},
			success: function(data, textStatus){
				if(data.response && data.response == 'OK') {
					if(data.items) {
						jQuery.each(data.items, function(i,item){
							var option = jQuery('<option>',{
		                  		value: item.id,
		                  		text: item.name
		            		});
		            		if(item.id == selected_cpt) {
		            			option.attr('selected','selected');
		            		}
		            		jQuery('#s2s-cpt').append(option);							
						});
					}
				}
			}
		});
	}	
}

function load_combo_authors( selected_site, selected_author ) {
	if(selected_site != '-1') {
		jQuery('#s2s-author').empty();
	    post_data = 'action=s2s_load_authors&site=' + selected_site;
	    jQuery.ajax({
			type: 'post',
			url: ajaxurl,
			data: post_data,
			dataType: 'json',
			error: function(XMLHttpRequest, textStatus, errorThrown){
			},
			success: function(data, textStatus){
				if(data.response && data.response == 'OK') {
					if(data.items) {
						jQuery.each(data.items, function(i,item){
							var option = jQuery('<option>',{
		                  		value: item.id,
		                  		text: item.name
		            		});							
		            		if(item.id == selected_author) {
		            			option.attr('selected','selected');
		            		}
		            		jQuery('#s2s-author').append(option);								
						});
					}
				}
			}
		});			
	}	
}

/*


function jsonFlickrApi(data) {
	if(data.stat == 'ok') {
		jQuery('#fms-image-' + flickr_images[flickr_index]).removeClass('fms-loading').addClass('fms-ok');
	} else {
		jQuery('#fms-image-' + flickr_images[flickr_index]).removeClass('fms-loading').addClass('fms-fail');
	}
	flickr_index++;
	if(flickr_index < flickr_images.length) {
		doAjaxCall();
	} else {
		jQuery('#fms-scan').removeAttr('disabled');
		jQuery('#fms-scan').attr('value','Scan now');
	}
}
*/
