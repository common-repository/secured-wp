(function ($) {
    $(document).ready(function () {

        /**
         * Handle the styling of the "Settings" tab on the plugin settings page
         */
        var tabs = $('#' + securedWp + '_settings_tabs_wrapper');
        if (tabs.length) {
            var current_tab_field = $('input[name="' + securedWp + '_active_tab"]'),
                prevent_tabs_change = false,
                active_tab = current_tab_field.val();
            if ('' == active_tab) {
                var active_tab_index = 0;
            } else {
                var active_tab_index = $('#' + securedWp + '_settings_tabs li[data-slug=' + active_tab + ']').index();
            }

            $('.' + securedWp + '_tab').css('min-height', $('#' + securedWp + '_settings_tabs').css('height'));

            /* jQuery tabs initialization */
            tabs.tabs({
                active: active_tab_index
            }).on("tabsactivate", function (event, ui) {
                if (!prevent_tabs_change) {
                    active_tab = ui.newTab.data('slug');
                    current_tab_field.val(active_tab);
                }
                prevent_tabs_change = false;
            });
            $('.' + securedWp + '_trigger_tab_click').on('click', function () {
                $('#' + securedWp + '_settings_tabs a[href="' + $(this).attr('href') + '"]').click();
            });
        }
    });
})(jQuery);
