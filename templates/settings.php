<div class="wrap">
<?php 
$dplugin_name = 'WooCommerce Products Image Watermark';
$dplugin_link = 'http://berocket.com/product/woocommerce-products-image-watermark';
$dplugin_price = 22;
$dplugin_desc = '';
@ include 'settings_head.php';
@ include 'discount.php';
?>
<div class="wrap br_settings br_image_watermark_settings show_premium">
    <div id="icon-themes" class="icon32"></div>
    <h2>Products Image Watermark Settings</h2>
    <?php settings_errors(); ?>

    <h2 class="nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active general-tab" data-block="general"><?php _e('General', 'BeRocket_image_watermark_domain') ?></a>
        <a href="#css" class="nav-tab css-tab" data-block="css"><?php _e('CSS', 'BeRocket_image_watermark_domain') ?></a>
    </h2>

    <form class="image_watermark_submit_form" method="post" action="options.php">
        <?php 
        $options = BeRocket_image_watermark::get_option();
        $watermarks = array(
            'shop_single' => __('Shop image', 'BeRocket_image_watermark_domain'),
        ); ?>
        <div class="nav-block general-block nav-block-active">
            <p>
                <label><input type="checkbox" name="br-image_watermark-options[disable_image]" value="1"<?php if(@ $options['disable_image']) echo ' checked'; ?>><?php _e('Disable image replace', 'BeRocket_image_watermark_domain') ?></label>
                <br>
                <span><?php _e('Disable functionality, that adds watermarks to images. Images with watermarks will be not changed.', 'BeRocket_image_watermark_domain') ?></span>
            </p>
            <?php foreach( $watermarks as $water_name => $water_label ) { ?>
            <div class="berocket_watermark_image_block">
                <h3><?php echo $water_label; ?></h3>
                <?php 
                    if( ! isset( $options[$water_name]['width'] ) || ! is_array( $options[$water_name]['width'] ) ) {
                        $options[$water_name]['width'] = array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50');
                    }
                    if( ! isset( $options[$water_name]['height'] ) || ! is_array( $options[$water_name]['height'] ) ) {
                        $options[$water_name]['height'] = array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50');
                    }
                    if( ! isset( $options[$water_name]['top'] ) || ! is_array( $options[$water_name]['top'] ) ) {
                        $options[$water_name]['top'] = array(0 => '50', 1 => '50', 2 => '50', 3 => '50', 4 => '50');
                    }
                    if( ! isset( $options[$water_name]['left'] ) || ! is_array( $options[$water_name]['left'] ) ) {
                        $options[$water_name]['left'] = array(0 => '25', 1 => '25', 2 => '25', 3 => '25', 4 => '25');
                    }
                    $i = 0;
                        if( ! isset($options[$water_name]['width'][$i]) || ! (int)$options[$water_name]['width'][$i] ) {
                            $options[$water_name]['width'][$i] = 20;
                        }
                        if( ! isset($options[$water_name]['height'][$i]) || ! (int)$options[$water_name]['height'][$i] ) {
                            $options[$water_name]['height'][$i] = 20;
                        }
                ?>
                <div class="berocket_image_count_all berocket_image_count_<?php echo $i; ?>"<?php if(@ $options[$water_name]['image_count'] < $i) echo ' style="display:none;"'; ?>>
                <h4><?php _e('Image', 'BeRocket_image_watermark_domain'); echo " ".($i + 1); ?> </h4>
                <?php echo berocket_font_select_upload( '', $water_name, 'br-image_watermark-options['.$water_name.'][image]['.$i.']', @ $options[$water_name]['image'][$i], false, true, true ); ?>
                <table>
                    <tr>
                        <td>
                            <div class="br_watermark_parent">
                                <div class="br_watermark" data-id="<?php echo $water_name.'_'.$i; ?>" style="<?php echo 'width:100px;height:100px;top:', ((int)$options[$water_name]['top'][$i] * 2), 'px;left:', ((int)$options[$water_name]['left'][$i] * 2), 'px;'; ?>">
                                </div>
                            </div>
                        </td>
                        <td>
                            <p>
                                <label>Top: <span class="<?php echo $water_name.'_'.$i; ?>_top"><?php echo $options[$water_name]['top'][$i]; ?></span> %</label>
                                <input class="<?php echo $water_name.'_'.$i; ?>_top_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][top][<?php echo $i; ?>]" value="<?php echo $options[$water_name]['top'][$i]; ?>">
                            </p>
                            <p>
                                <label>Left: <span class="<?php echo $water_name.'_'.$i; ?>_left"><?php echo $options[$water_name]['left'][$i]; ?></span> %</label>
                                <input class="<?php echo $water_name.'_'.$i; ?>_left_input" type="hidden" name="br-image_watermark-options[<?php echo $water_name; ?>][left][<?php echo $i; ?>]" value="<?php echo $options[$water_name]['left'][$i]; ?>">
                            </p>
                        </td>
                    </tr>
                </table>
                </div>
            </div>
            <?php } ?>
            <p>
                <p><?php _e('Replace watermarked images to normal images. Without "Disable image replace" all images will be replaced again', 'BeRocket_image_watermark_domain') ?></p>
                <a class="button br_restore_image"><?php _e('Restore Images', 'BeRocket_image_watermark_domain') ?></a>
            </p>
        </div>
        <script>
            (function ($){
                function drop_for_10(value) {
                    if( value % 10 >= 5 ) {
                        value += 10 - value % 10;
                    } else {
                        value -= value % 10;
                    }
                    return value;
                }
                $(document).ready( function () {
                    jQuery( ".br_watermark" )
                        .draggable({
                            containment: "parent", 
                            scroll: false,
                            grid: [10, 10],
                            stop: function( event, ui ) {
                                var top = ui.position.top;
                                var left = ui.position.left;
                                var id = $(this).data('id');
                                var parent = $(this).parent();
                                var parent_top = parent.height();
                                var parent_left = parent.width();
                                top = drop_for_10(top);
                                left = drop_for_10(left);
                                parent_top = drop_for_10(parent_top);
                                parent_left = drop_for_10(parent_left);
                                var top_p = parseInt(top / parent_top * 100);
                                console.log(parent_top);
                                var left_p = parseInt(left / parent_left * 100);
                                $('.'+id+'_top').text(top_p);
                                $('.'+id+'_left').text(left_p);
                                $('.'+id+'_top_input').val(top_p);
                                $('.'+id+'_left_input').val(left_p);
                            }
                        });
                });
            })(jQuery);
        </script>
        <div class="nav-block css-block">
            <table class="form-table license">
                <tr>
                    <th scope="row"><?php _e('Custom CSS', 'BeRocket_image_watermark_domain') ?></th>
                    <td>
                        <textarea name="br-image_watermark-options[custom_css]"><?php echo $options['custom_css']?></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <input type="submit" class="button-primary" value="<?php _e('Save Changes', 'BeRocket_image_watermark_domain') ?>" />
        <div class="br_save_error"></div>
    </form>
</div>
<?php
$feature_list = array(
    'Custom watermark size',
    'Different watermarks for different image type',
    'Save aspect ratio for watermarks',
    'Up to 5 watermarks',
    'Place text to images',
    'Options to set text color, size and transparency',
);
@ include 'settings_footer.php';
?>
</div>
