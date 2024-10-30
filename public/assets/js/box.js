(function($) {
    var truncate = require("truncate");
    window.iterasPaywallContent = function(wall) {
        var box = $(".iteras-paywall-box").show();
        var content = $(".iteras-content-wrapper");
        if (!content.hasClass("iteras-content-truncated"))
            content.html(truncate(content.html(), box.data("snippet-size") || 300));
    };
})(jQuery);
