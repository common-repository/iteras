(function($) {
    "use strict";

    $(function () {
        $("#paywall_display_type").change(function() {
            $(".box-type").toggle($(this).val() == "samepage");
        }).change();

        $("#paywall_integration_method").change(function() {
            $(this).siblings(".description").toggle($(this).val() == "custom");
        }).change();
    });
}(jQuery));
