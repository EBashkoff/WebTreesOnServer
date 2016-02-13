<!-- CSS -->
<link rel="stylesheet" href="css/reset.css" type="text/css" media="all">
<link rel="stylesheet" href="css/styles.css" type="text/css" media="all">

<!-- SCRIPTS -->
<script type="text/javascript" src="js/jquery-1.11.0.js"></script>
<script type="text/javascript" src="js/jquery.smooth-expand-menu.js"></script>

<!-- CUSTOM SCRIPT CALL -->
<script type="text/javascript">
    // Using "jQuery" to protect against conflicts with other libraries like MooTools

    jQuery(document).ready(function() {
        var deviceSetting = {};
        if (getWindowClientArea()['type'] === 'phone') {
            deviceSetting['width'] = '100%';
            deviceSetting['menuFontSize'] = 38;
            deviceSetting['menuFontWeight'] = 900;
            deviceSetting['submenuFontSize'] = 36;
            deviceSetting['submenuFontWeight'] = 600;
            deviceSetting['lineHeight'] = 45;
            deviceSetting['dividerSize'] = 2;
        } else {
            deviceSetting['width'] = 250;
            deviceSetting['menuFontSize'] = 12;
            deviceSetting['menuFontWeight'] = 700;
            deviceSetting['submenuFontSize'] = 11;
            deviceSetting['submenuFontWeight'] = 400;
            deviceSetting['lineHeight'] = 17;
            deviceSetting['dividerSize'] = 1;

        }
        jQuery.smootherMenu({
            globalWidth: deviceSetting['width'], /*  WIDTH VALUE (IN PIXELS) */
            lineHeight: deviceSetting['lineHeight'], /*  ITEM VERTICAL SPACE VALUE (IN PIXELS) */
            animationSpeed: 350, /*  SLIDE ANIMATION SPEED (IN MILLISECONDS) */

            dividerSize: deviceSetting['dividerSize'], /*  LINE DIVIDER VALUE (IN PIXELS) */
            dividerStyle: 'solid', /*  LINE DIVIDER STYLE ('solid', 'dashed', 'dotted', 'none', ...) */
            dividerColor: '#894235', /*  LINE DIVIDER COLOR (HEXADECIMAL) */
            dividerOnLastItem: 'true', /*  IF LAST ITEM HAS BOTTOM BORDER */

            menuFontSize: deviceSetting['menuFontSize'], /*  MENU FONT SIZE (IN PIXELS) */
            menuFontWeight: deviceSetting['menuFontWeight'], /*  MENU FONT WEIGHT (NORMALLY 300, 400, 700...) */
            menuColor: '#A04D3E', /*  MENU COLOR (HEXADECIMAL) */
            menuHoverColor: '#894235', /*  MENU HOVER COLOR (HEXADECIMAL) */

            submenuFontSize: deviceSetting['submenuFontSize'], /*  SUBMENU FONT SIZE (IN PIXELS) */
            submenuFontWeight: deviceSetting['submenuFontWeight'], /*  SUBMENU FONT WEIGHT (NORMALLY 300, 400, 700...) */
            submenuColor: '#B28C85', /*  SUBMENU COLOR (HEXADECIMAL) */
            submenuHoverColor: '#894235', /*  SUBMENU HOVER COLOR (HEXADECIMAL) */
            submenuIndent: 8, /*  SUBMENU FONT SIZE (IN PIXELS) */
            activeItemColor: '#000000'			/*  ACTIVE ITEMS COLOR (HEXADECIMAL) */

        });

        /*  TIP: THE ABOVE EXAMPLE SETTINGS OVERWRITE ALL THE DEFAULT ONES INSIDE THE PLUGIN.
         IF YOU ARE OK WITH MOST OF THE DEFAULT SETTINGS YOU CAN EVEN START THE MENU WITH SIMPLE CALLS:
         jQuery.smoothMenu();						--> SIMPLE PLAIN PLUGIN CALL
         jQuery.smoothMenu({ globalWidth: 150 });			--> SAMPLE CHANGING JUST THE WIDTH
         jQuery.smoothMenu({ globalWidth: 150, dividerSize: 0 });	--> SAMPLE CHANGING WIDTH AND NO DIVIDERS
         */

    });

</script>