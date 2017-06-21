jQuery(document).ready(function($) {
    var oauthpopup = function(options) {
        var dualScreenLeft = window.screenLeft != undefined ? window.screenLeft : screen.left;
        var dualScreenTop = window.screenTop != undefined ? window.screenTop : screen.top;

        var width = window.innerWidth ? window.innerWidth : document.documentElement.clientWidth ? document.documentElement.clientWidth : screen.width;
        var height = window.innerHeight ? window.innerHeight : document.documentElement.clientHeight ? document.documentElement.clientHeight : screen.height;

        var w = 700;
        var h = 400;
        var left = ((width / 2) - (w / 2)) + dualScreenLeft;
        var top = ((height / 2) - (h / 2)) + dualScreenTop;

        options.windowName = options.windowName ||  'ConnectWithOAuth'; // should not include space for IE
        options.windowOptions = options.windowOptions || 'menuBar=0,toolbar=0,status=0,scrollbars=yes, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left;
        options.callback = options.callback || function(){ window.location.reload(); };
        var that = this;
        //log(options.path);
        that._oauthWindow = window.open(options.path, options.windowName, options.windowOptions);
        that._oauthInterval = window.setInterval(function(){
            if (that._oauthWindow.closed) {
                window.clearInterval(that._oauthInterval);
                options.callback();
            }
        }, 1000);
    };

    function generateNonce() {
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for(var i = 0; i < 16; i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }

        return text;
    }

    $('#pdms_policy_settings_section_api_field_login').click(function () {
        oauthpopup({
            path: pdms_apiBaseUrl + "auth/connect/authorize?" +
                  "response_type=code" +
                  "&client_id=" +
                  "&scope=openid profile offline_access" +
                  "&nonce=" + generateNonce() +
                  "&redirect_uri=" + encodeURIComponent(pdms_apiBaseUrl + 'auth/redirect?redirect_uri=' + window.location.href),
            windowName: "Login to PowerDMS"
        });
    });
});