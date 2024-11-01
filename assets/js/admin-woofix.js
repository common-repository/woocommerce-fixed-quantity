/* global woofix_admin, ajaxurl, accounting, woocommerce_admin_meta_boxes, postboxes */

jQuery(document).ready(function($) {

    var woofixjs_admin = { decimal_point: '.', num_decimals: 2 };
    if (typeof woofix_admin !== 'undefined')
        woofixjs_admin = woofix_admin;

    var woofix = {
        woofix_product_data_selector: '#woofix_product_data',
        input_woofix_selector: 'input[name="_woofix"]',
        woocommerce_product_data_selector: '#woocommerce-product-data',
        woocommerce_regular_price_selector:'#_regular_price',
        woocommerce_product_type_selector: '#product-type',

        init: function () {
            woofix.on_woocommerce_product_type_changed();
            woofix.on_woocommerce_product_price_changed();
            woofix.show_hide_woofix_tab();

            woofix.on_woofix_tab_clicked();
            woofix.on_woofix_price_container_clicked();
            woofix.on_woofix_price_added();
            woofix.on_woofix_row_deleted();
            woofix.on_woofix_qty_changed();
            woofix.on_woofix_desc_changed();
            woofix.on_woofix_discont_changed();
            woofix.on_woofix_price_changed();

            woofix.load_woofix_page();
        },

        on_woofix_page_loaded: function () {

            var existing_data_elem = $(woofix.woofix_product_data_selector).find(woofix.input_woofix_selector);
            if (existing_data_elem.length > 0) {
                var existingVal = existing_data_elem.val();
                if (existingVal !== '') {
                    existingVal = JSON.parse(existingVal);
                    $.each(existingVal['woofix'], function(roleKey, data) {

                        if (!isNaN(roleKey)) {
                            roleKey = $(woofix.woofix_product_data_selector).find('.woofix_price_table_container:first-child').data('role-key');
                            var newdata = {};
                            newdata[roleKey] = data;
                            data = newdata;
                        }

                        var tableContainer = $(woofix.woofix_product_data_selector).find('.woofix_price_table_container[data-role-key="' + roleKey + '"]');
                        var table = tableContainer.find('.woofix_price_table tbody');
                        $.each(data, function (index, value) {
                            var discounted_price = woofix.get_discounted_price(value['woofix_disc']);
                            var row = $(woofix.woofix_product_data_selector).find('#woofix_template tr').clone();
                            row.find('input[data-name="woofix_desc"]').val(value['woofix_desc']);
                            row.find('input[data-name="woofix_qty"]').val(value['woofix_qty']);
                            row.find('input[data-name="woofix_disc"]').val(woofix.format_number_to_save(value['woofix_disc']));
                            row.find('input[data-name="woofix_price"]').val(woofix.format_number_to_save(discounted_price));
                            row.appendTo(table);
                        });
                        woofix.regenerate_index(roleKey);
                        woofix.regenerate_data();
                    });
                }
            }
        },

        on_woocommerce_product_price_changed: function () {
            $(woofix.woocommerce_regular_price_selector).on('change', function() {
                woofix.load_woofix_page();
            });
        },

        on_woocommerce_product_type_changed: function () {
            $(woofix.woocommerce_product_type_selector).on('change', function () {
                woofix.show_hide_woofix_tab();
            });
        },

        on_woofix_tab_clicked: function () {
            $('.woofix_tab').on('click', function () {
                var regularPrice = woofix.get_regular_price();
                if (!regularPrice || regularPrice <= 0) {
                    alert ('Please add regular price.');
                    return false;
                }

                woofix.block_ui();
                woofix.load_woofix_page();
            });
        },

        on_woofix_price_container_clicked: function () {
            $(woofix.woofix_product_data_selector).on('click', '.woofix_price_table_container h2, .woofix_price_table_container .button-link', postboxes.handle_click);
        },

        on_woofix_price_added: function () {
            $(woofix.woofix_product_data_selector).on('click', '.woofix_add_price', function() {
                var tableContainer = $(this).closest('.woofix_price_table_container');
                $('#woofix_template').find('tr').clone().appendTo(tableContainer.find('.woofix_price_table tbody'));

                woofix.regenerate_index(tableContainer.data('role-key'));
            });
        },

        on_woofix_row_deleted: function () {
            $(woofix.woofix_product_data_selector).on('click', '.woofix_delete', function() {
                var roleKey = $(this).closest('.woofix_price_table_container').data('role-key');
                $(this).closest('tr').remove();

                woofix.regenerate_index(roleKey);
                woofix.regenerate_data();
            });
        },

        on_woofix_qty_changed: function () {
            $(woofix.woofix_product_data_selector).on('change', 'input[name*="woofix_qty"]', function() {
                var newVal = $(this).val();
                // noinspection JSCheckFunctionSignatures
                if (!newVal || isNaN(newVal) || parseInt(newVal) <= 0) {
                    newVal = 1;
                }

                // noinspection JSCheckFunctionSignatures
                $(this).val(parseInt(newVal));

                woofix.regenerate_data();
            });
        },

        on_woofix_desc_changed: function () {
            $(woofix.woofix_product_data_selector).on('change', 'input[name*="woofix_desc"]', function() {
                woofix.regenerate_data();
            });
        },

        on_woofix_discont_changed: function () {
            $(woofix.woofix_product_data_selector).on('change', 'input[name*="woofix_disc"]', function() {
                woofix.validate_monetary(this);

                var discount = accounting.unformat($(this).val(), woofixjs_admin.decimal_point);
                if (discount > 100 || discount < 0) {
                    discount = 0;
                }

                $(this).val(woofix.format_number_to_save(discount));

                var $price = $(this).closest('tr').find('input[name*="woofix_price"]');
                $price.val(woofix.format_number_to_save(woofix.get_discounted_price(discount)));

                woofix.regenerate_data();
            });
        },

        /**
         * @returns {number}
         */
        get_regular_price: function () {
            var regularPriceValue = $(woofix.woocommerce_regular_price_selector).val();
            return accounting.unformat(regularPriceValue, woofixjs_admin.decimal_point);
        },

        /**
         *
         * @param {number} discount
         * @returns {number}
         */
        get_discounted_price: function (discount) {
            var regular_price = woofix.get_regular_price();
            return regular_price - (regular_price * discount / 100);
        },

        get_discount: function(regular_price, discounted_price) {
            if (regular_price < discounted_price || discounted_price < 0) {
                discounted_price = regular_price;
            }

            return 100 * (regular_price - discounted_price) / regular_price;
        },

        on_woofix_price_changed: function () {
            $(woofix.woofix_product_data_selector).on('change', 'input[name*="woofix_price"]', function() {

                woofix.validate_monetary(this);

                var regular_price = woofix.get_regular_price();
                var discounted_price = accounting.unformat($(this).val(), woofixjs_admin.decimal_point);
                if (regular_price < discounted_price || discounted_price < 0) {
                    discounted_price = regular_price;
                }

                $(this).val(woofix.format_number_to_save(discounted_price));
                var discount = woofix.get_discount(regular_price, discounted_price);

                var $disc = $(this).closest('tr').find('input[name*="woofix_disc"]');
                $disc.val(woofix.format_number_to_save(discount));

                woofix.regenerate_data();
            });
        },

        load_woofix_page: function () {
            if (typeof woocommerce_admin_meta_boxes !== "undefined") {
                $.ajax({
                    url: ajaxurl,
                    data: {
                        action: 'load_woofix_admin_page',
                        product_id: woocommerce_admin_meta_boxes.post_id
                    },
                    type: 'POST',
                    success: function( response ) {
                        $(woofix.woofix_product_data_selector).html(response);
                        $(document.body).trigger('post-load');
                        woofix.on_woofix_page_loaded();
                        woofix.unblock_ui();
                    }
                });
            }
        },

        regenerate_data: function() {
            var data = $(woofix.woofix_product_data_selector)
                .find('.woofix_price_table_container :input')
                .serializeWofix(woofixjs_admin);
            $(woofix.woofix_product_data_selector).find(woofix.input_woofix_selector).val(JSON.stringify(data));
        },

        regenerate_index: function(role) {
            $(woofix.woofix_product_data_selector).find('.woofix_price_table_container[data-role-key="' + role + '"]')
                .find('tbody tr')
                .each(function(index) {
                    $(this).find('[data-name]').each(function() {
                        var name = $(this).data('name');
                        var inputName = 'woofix[' + role + '][' + index + '][' + name + ']';
                        if (name === "woofix_disc" || name === "woofix_price")
                            inputName += ":woodecimal";

                        if (name === "woofix_qty")
                            inputName += ":number";

                        $(this).attr('name', inputName);
                        $(this).attr('id', inputName);
                    });
                });
        },

        show_hide_woofix_tab: function () {
            var productType = $(woofix.woocommerce_product_type_selector).val();
            // noinspection JSCheckFunctionSignatures
            if (['simple', 'variable'].indexOf(productType) >= 0) {
                $('.woofix_options').show();
            } else {
                $('.woofix_options').hide();
            }
        },

        validate_monetary: function (selector) {
            var value = $(selector).val();
            var regex = new RegExp('[^\-0-9\%\\' + woofixjs_admin.decimal_point + ']+', 'gi');
            var newvalue = value.replace(regex, '');

            if ( value !== newvalue ) {
                $(selector).val(newvalue);
                $(document.body).triggerHandler('wc_add_error_tip', [$(selector), 'i18n_mon_decimal_error']);
            } else {
                $(document.body).triggerHandler('wc_remove_error_tip', [$(selector), 'i18n_mon_decimal_error']);
            }
        },

        format_number_to_save: function (number) {
            return accounting.format(number, woofixjs_admin.num_decimals, '', woofixjs_admin.decimal_point);
        },

        block_ui: function() {
            $(woofix.woocommerce_product_data_selector).block({message: null});
        },

        unblock_ui: function() {
            // noinspection JSUnresolvedFunction
            $(woofix.woocommerce_product_data_selector).unblock();
        }
    };

    woofix.init();
});
