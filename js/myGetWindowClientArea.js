function getWindowClientArea() {
//    var is_touch_device = 'ontouchstart' in document.documentElement;
    var touchdevicetypes = new Array('Apple-iPhone', 'Apple-iPad', 'Apple-iPod', 'Android');
    var is_touch_device = false;
    for (var i = 0; i < touchdevicetypes.length; i++) {
        if (navigator.userAgent.indexOf(touchdevicetypes[i])>=0) {
            is_touch_device = true;
            break;
        }
    }

    if (!is_touch_device) {  // Is not a touch device
        if (!isNaN(parseInt(window.innerWidth, 10))) { //Non-IE
            return {width: window.innerWidth, height: window.innerHeight, type: "computer"};
        } else if (document.documentElement && (document.documentElement.clientWidth || document.documentElement.clientHeight)) { //IE 6+ in 'standards compliant mode'
            return {width: document.documentElement.clientWidth, height: document.documentElement.clientHeight, type: "computer"};
        } else if (document.body && (document.body.clientWidth || document.body.clientHeight)) { //IE 4 compatible
            return {width: document.body.clientWidth, height: document.body.clientHeight, type: "computer"};
        }
    } else { //  Is a touch device
        var _w = Math.max(screen.availWidth, screen.availHeight);
        var _h = Math.min(screen.availWidth, screen.availHeight);
        var tabletView = (_w > 960 && _h >= 600);
        if (tabletView) {  //  Is a tablet
            var _adjustheight = ((navigator.userAgent.indexOf("Firefox") !== -1) ? .71 : .67);
            return {width: screen.availWidth, height: screen.availHeight * _adjustheight, type: "tablet"};
        } else { // Is a phone
            var _adjustheight = ((navigator.userAgent.indexOf("Firefox") !== - 1) ? 1 : 1.2);
                    return {width: screen.availWidth, height: screen.availHeight * _adjustheight, type: "phone"};
        }
    }
}