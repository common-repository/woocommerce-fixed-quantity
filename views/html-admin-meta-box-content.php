<?php
/**
 * @var array $custom_fields
 * @var string $woofix_qty_desc
 * @var string $woofix_default_role
 * @var array $woofix_roles
 *
 */
?>
<div id="woofix_product_data_content">
    <div class="options_group">
        <input type="hidden" name="_woofix" value="<?php echo !empty($custom_fields["_woofix"][0])? $custom_fields["_woofix"][0] : ''; ?>" />

        <p><em>
                <strong><?php _e('Note:', 'woofix'); ?></strong>
                <?php _e('To use custom description, please use this template:', 'woofix'); ?> <quote>{qty}, {price}, {total}, {discount}</quote>
            </em></p>

        <div id="woofix_product_data_table">
            <?php foreach ($woofix_roles as $role): ?>
                <div class="postbox woofix_price_table_container <?php echo ($role['key'] == $woofix_default_role)? "" : "closed"; ?>"
                     data-role-key="<?php echo $role['key']; ?>">

                    <button type="button" class="handlediv button-link"
                            aria-expanded="<?php echo ($role['key'] == $woofix_default_role)? "true" : "false"; ?>">
                        <span class="screen-reader-text"><?php echo __("Toggle panel:", 'woofix') . ' ' . $role['name']; ?></span>
                        <span class="toggle-indicator" aria-hidden="true"></span>
                    </button>
                    <h2><span><?php echo $role['name'] . ' ' . __("Role", 'woofix'); ?></span></h2>
                    <div class="inside">
                        <p><a class="button button-primary woofix_add_price">
                                <?php _e('Add Price', 'woofix'); ?>
                            </a></p>

                        <table class="table woofix_price_table">
                            <thead>
                            <tr>
                                <th class="woofix_desc"><?php _e('Description', 'woofix'); ?></th>
                                <th class="woofix_qty"><?php _e('Qty', 'woofix'); ?></th>
                                <th class="woofix_disc"><?php _e('Disc (%)', 'woofix'); ?></th>
                                <th class="woofix_price"><?php _e('Price Per Qty', 'woofix'); ?></th>
                                <th><?php _e('Action', 'woofix'); ?></th>
                            </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <br>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <table id="woofix_template" class="woofix hidden">
        <tr>
            <td><input placeholder="" type="text" class="woofix_input_desc" data-name="woofix_desc" value="<?php echo $woofix_qty_desc; ?>" /></td>
            <td><input placeholder="" type="text" class="woofix_input_qty" data-name="woofix_qty" /></td>
            <td><input placeholder="" type="text" class="woofix_input_disc" data-name="woofix_disc" /></td>
            <td><input placeholder="" type="text" class="woofix_input_price" data-name="woofix_price" /></td>
            <td><a class="woofix_delete button"><?php _e('Remove', 'woofix'); ?></a></td>
        </tr>
    </table>
</div>
