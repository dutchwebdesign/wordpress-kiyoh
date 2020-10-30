<div class="wrap">
    <h1><?php echo __("Kiyoh reviews settings") ?></h1>
    <form method="post" action="options.php">
        <?php settings_fields( 'kiyoh_settings' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="kiyoh_reviews_enabled"><?php echo __("Kiyoh reviews") ?></label></th>
                <td>
                    <select id="kiyoh_reviews_enabled" name="kiyoh_reviews_enabled">
                        <option <?php echo (get_option('kiyoh_reviews_enabled') == true) ? 'selected="selected"' : "" ?> value="1"><?php echo __("Enabled") ?></option>
                        <option <?php echo (get_option('kiyoh_reviews_enabled') == false) ? 'selected="selected"' : "" ?> value="0"><?php echo __("Disabled") ?></option>
                    </select>
                    <p class="description"><?php echo __('Send invite mail for company review after completed order (also enables WordPress widget with company score)') ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_review_products"><?php echo __("Kiyoh products review") ?></label></th>
                <td>
                    <select id="kiyoh_review_products" name="kiyoh_review_products">
                        <option <?php echo (get_option('kiyoh_review_products') == true) ? 'selected="selected"' : "" ?> value="1"><?php echo __("Enabled") ?></option>
                        <option <?php echo (get_option('kiyoh_review_products') == false) ? 'selected="selected"' : "" ?> value="0"><?php echo __("Disabled") ?></option>
                    </select>
                    <p class="description"><?php echo __('Includes products in invite for review (and shows reviews beneath product) <strong>Note:</strong> When "Kiyoh reviews" is disabled there aren\'t sent mail') ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_api_key"><?php echo __("API Key") ?></label></th>
                <td><input type="text" id="kiyoh_api_key" name="kiyoh_api_key" value="<?php echo get_option('kiyoh_api_key'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_location"><?php echo __("Location ID") ?></label></th>
                <td><input type="text" id="kiyoh_location" name="kiyoh_location" value="<?php echo get_option('kiyoh_location'); ?>" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_order_state_trigger"><?php echo __("Order state trigger") ?></label></th>
                <td>
                    <select id="kiyoh_order_state_trigger" name="kiyoh_order_state_trigger">
                        <?php foreach (Kiyoh::get_kiyoh_order_state_triggers() as $status) { ?>
                            <option <?php echo (get_option('kiyoh_order_state_trigger') === $status) ? 'selected="selected"' : "" ?> value="<?php echo $status ?>"><?php echo $status ?></option>
                        <?php } ?>
                    </select>
                    <p class="description"><?php echo __('Select state of order when review invite mail should send to customer.') ?></p>

                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_reviews_enabled"><?php echo __("Kiyoh server") ?></label></th>
                <td>
                    <select id="kiyoh_server" name="kiyoh_server">
                        <option <?php echo (get_option('kiyoh_server') === "https://www.kiyoh.com") ? 'selected="selected"' : "" ?> value="https://www.kiyoh.com">kiyoh.com</option>
                        <option <?php echo (get_option('kiyoh_server') === "https://www.klantenvertellen.nl") ? 'selected="selected"' : "" ?> value="https://www.klantenvertellen.nl">klantenvertellen.nl</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_delay"><?php echo __("Delay") ?></label></th>
                <td>
                    <input type="text" id="kiyoh_delay" name="kiyoh_delay" value="<?php echo get_option('kiyoh_delay'); ?>" />
                    <p class="description"><?php echo __('Enter here the delay(number of days) after which you would like to send review invite email to your customer.<br/>You may enter 0 to send review invite email immediately after customer event.') ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_email_lang"><?php echo __("Language e-mail") ?></label></th>
                <td>
                    <select id="kiyoh_email_lang" name="kiyoh_email_lang">
                        <?php $lang_codes = Kiyoh::get_email_lang_codes();
                        foreach($lang_codes as $lang_code => $label) { ?>
                            <option <?php echo (get_option('kiyoh_email_lang') == $lang_code) ? 'selected="selected"' : "" ?> value="<?php echo $lang_code ?>"><?php echo $label ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_debug"><?php echo __("Debug log") ?></label></th>
                <td>
                    <select id="kiyoh_debug" name="kiyoh_debug">
                        <option <?php echo (get_option('kiyoh_debug') == true) ? 'selected="selected"' : "" ?> value="1"><?php echo __("Enabled") ?></option>
                        <option <?php echo (get_option('kiyoh_debug') == false) ? 'selected="selected"' : "" ?> value="0"><?php echo __("Disabled") ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="kiyoh_debug"><?php echo __("Sync products Kiyoh") ?></label></th>
                <td>
                    <input type="button" id="sync_products" value="Sync products Kiyoh" />
                    <p class="description"><?php echo __('Send products manually to Kiyoh. This is done automatically daily.') ?></p>
                </td>
            </tr>
        </table>
        <script>
            jQuery("#sync_products").click(function() {
                jQuery.ajax({
                    url : '<?php echo sprintf("%s?action=sync_products", admin_url( 'admin-ajax.php' )) ?>',
                    type : 'POST',
                    dataType : 'json',
                    success: function (data) {
                        if(data.status == 'success') {
                            jQuery("#sync_products").val("Products successfully synced!");
                        }
                    },
                });
            });
        </script>
        <?php  submit_button(); ?>
    </form>
</div>