var br_saved_timeout;
var br_savin_ajax = false;
(function ($){
    $(document).ready( function () {
        $('.image_watermark_submit_form').submit( function(event) {
            event.preventDefault();
            if( !br_savin_ajax ) {
                br_savin_ajax = true;
                var form_data = $(this).serialize();
                form_data = 'action=br_image_watermark_settings_save&'+form_data;
                var url = ajaxurl;
                clearTimeout(br_saved_timeout);
                destroy_br_saved();
                $('body').append('<span class="br_saved br_saving"><i class="fa fa-refresh fa-spin"></i></span>');
                $.post(url, form_data, function (data) {
                    if($('.br_saved').length > 0) {
                        $('.br_saved').removeClass('br_saving').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-check');
                    } else {
                        $('body').append('<span class="br_saved"><i class="fa fa-check"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    br_savin_ajax = false;
                }, 'json').fail(function() {
                    if($('.br_saved').length > 0) {
                        $('.br_saved').removeClass('br_saving').addClass('br_not_saved').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-times');
                    } else {
                        $('body').append('<span class="br_saved br_not_saved"><i class="fa fa-times"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    $('.br_save_error').html(data.responseText);
                    br_savin_ajax = false;
                });
            }
        });
        function destroy_br_saved() {
            $('.br_saved').addClass('br_saved_remove');
            var $get = $('.br_saved');
            setTimeout( function(){$get.remove();}, 200 );
        }
        $(window).on('keydown', function(event) {
            if (event.ctrlKey || event.metaKey) {
                switch (String.fromCharCode(event.which).toLowerCase()) {
                case 's':
                    event.preventDefault();
                    $('.image_watermark_submit_form').submit();
                    break;
                }
            }
        });
        $('.br_settings .nav-tab').click(function(event) {
            event.preventDefault();
            $('.nav-tab-active').removeClass('nav-tab-active');
            $('.nav-block-active').removeClass('nav-block-active');
            $(this).addClass('nav-tab-active');
            $('.'+$(this).data('block')+'-block').addClass('nav-block-active');
        });
        $(document).on('click', '.berocket_aapf_upload_icon', function(e) {
            e.preventDefault();
            $p = $(this);
            var custom_uploader = wp.media({
                title: 'Select custom Icon',
                button: {
                    text: 'Set Icon'
                },
                multiple: false 
            }).on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $p.prevAll(".berocket_aapf_selected_icon_show").html('<i class="fa"><image src="'+attachment.url+'" alt=""></i>');
                $p.prevAll(".berocket_aapf_icon_text_value").val(attachment.url);
            }).open();
        });
        $(document).on('click', '.berocket_aapf_remove_icon',function(event) {
            event.preventDefault();
            $(this).prevAll(".berocket_aapf_icon_text_value").val("");
            $(this).prevAll(".berocket_aapf_selected_icon_show").html("");
        });
        $('.br_restore_image').click( function(event) {
            event.preventDefault();
            if( !br_savin_ajax ) {
                br_savin_ajax = true;
                form_data = 'action=br_image_watermark_restore';
                var url = ajaxurl;
                clearTimeout(br_saved_timeout);
                destroy_br_saved();
                $('body').append('<span class="br_saved"><i class="fa fa-refresh fa-spin"></i></span>');
                $.post(url, form_data, function (data) {
                    if($('.br_saved').length > 0) {
                        $('.br_saved .fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-check');
                    } else {
                        $('body').append('<span class="br_saved"><i class="fa fa-check"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    br_savin_ajax = false;
                }, 'json').fail(function(data) {
                    if($('.br_saved').length > 0) {
                        $('.br_saved').addClass('br_not_saved').find('.fa').removeClass('fa-spin').removeClass('fa-refresh').addClass('fa-times');
                    } else {
                        $('body').append('<span class="br_saved br_not_saved"><i class="fa fa-times"></i></span>');
                    }
                    br_saved_timeout = setTimeout( function(){destroy_br_saved();}, 5000 );
                    $('.br_save_error').html(data.responseText);
                    br_savin_ajax = false;
                });
            }
        });
        
        $('.font_colorpick').each(function (i,o){
            $(o).css('backgroundColor', $(o).data('color'));
            $(o).colpick({
                layout: 'hex',
                submit: 0,
                color: $(o).css('backgroundColor'),
                onChange: function(hsb,hex,rgb,el,bySetColor) {
                    $(el).css('backgroundColor', '#'+hex).next().val(rgb.r+","+rgb.g+","+rgb.b);
                }
            })
        });
        $(document).on('change', '.br_wm_img_count', function() {
            for(var i = 0; i < 5; i++) {
                if( i > $(this).val() ) {
                    $(this).parents('.berocket_watermark_image_block').find('.berocket_image_count_'+i).hide();
                } else {
                    $(this).parents('.berocket_watermark_image_block').find('.berocket_image_count_'+i).show();
                }
            }
        });
    });
})(jQuery);