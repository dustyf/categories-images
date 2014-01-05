jQuery(document).ready(function($) {
	var wordpress_ver = zciparams.wpversion, upload_button;
	$(".z_upload_image_button").click(function(event) {
		upload_button = $(this);
		var frame;
		if (wordpress_ver >= "3.5") {
			event.preventDefault();
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media();
			frame.on( "select", function() {
				// Grab the selected attachment.
				var attachment = frame.state().get("selection").first();
				frame.close();
				// Quick Edit
				if (upload_button.parent().prev().children().hasClass("tax_list")) {
					upload_button.parent().prev().children().val(attachment.attributes.url);
					upload_button.parent().prev().prev().children().attr("src", attachment.attributes.url);
				} else {
					$("#taxonomy_image").val(attachment.attributes.url);
					$('#taxonomy_image_id').val(attachment.id);
					$('img.taxonomy-image').attr('src',attachment.attributes.url);
				}
			});
			frame.open();
		}
		else {
			tb_show("", "media-upload.php?type=image&amp;TB_iframe=true");
			return false;
		}
	});

	$(".z_remove_image_button").click(function() {
		$("#taxonomy_image").val("");
		$('img.taxonomy-image').attr('src', zciparams.imageplaceholder);
		$(".inline-edit-col :input[name='taxonomy_image']").val("");
		return false;
	});
	
	if (wordpress_ver < "3.5") {
		window.send_to_editor = function(html) {
			imgurl = $("img",html).attr("src");
			if (upload_button.parent().prev().children().hasClass("tax_list")) {
				upload_button.parent().prev().children().val(imgurl);
				upload_button.parent().prev().prev().children().attr("src", imgurl);
			}
			else
				$("#taxonomy_image").val(imgurl);
			tb_remove();
		}
	}
	
	$(".editinline").live("click", function(){
	    var tax_id = $(this).parents("tr").attr("id").substr(4);
	    var thumb = $("#tag-"+tax_id+" .thumb img").attr("src");
		if (thumb != "zciparams.imageplaceholder") {
			$(".inline-edit-col :input[name='taxonomy_image']").val(thumb);
		} else {
			$(".inline-edit-col :input[name='taxonomy_image']").val("");
		}
		$(".inline-edit-col .title img").attr("src",thumb);
	    return false;  
	});
});