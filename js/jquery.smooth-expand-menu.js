/*  
 -------------------------------------------------------------
 JAVASCRIPT - jQuery Smooth Expandable Menu
 Description: jQuery Plugin for building menus
 Author: pezflash - http: //www.codecanyon.net/user/pezflash
 Version: 1.3
 -------------------------------------------------------------
 */


(function($) {

    //CHECK LENGTH BEFORE EXECUTING
    jQuery.fn.exists = function() {
        return $(this).length > 0;
    }

    //CLASS CONSTRUCTOR / INIT FUNCTION
    $.smootherMenu = function(settings) {


        /////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //DEFAULT SETTINGS - NOTE: HTML SETTINGS WILL OVERWRITE THESE ONES
        settings = jQuery.extend({
            globalWidth: 200, /*	WIDTH VALUE (IN PIXELS) */
            lineHeight: 17, /*	ITEM VERTICAL SPACE VALUE (IN PIXELS) */
            animationSpeed: 500, /*	SLIDE ANIMATION SPEED (IN MILLISECONDS) */

            dividerSize: 1, /*	LINE DIVIDER VALUE (IN PIXELS) */
            dividerStyle: 'dashed', /*	LINE DIVIDER STYLE ('solid', 'dashed', 'dotted', 'none', ...) */
            dividerColor: '#999', /*	LINE DIVIDER COLOR (HEXADECIMAL) */
            dividerOnLastItem: 'true', /*	IF LAST ITEM HAS BOTTOM BORDER */

            menuFontSize: 12, /*	MENU FONT SIZE (IN PIXELS) */
            menuFontWeight: 700, /*	MENU FONT WEIGHT (NORMALLY 300, 400, 700...) */
            menuColor: '#777', /*	MENU COLOR (HEXADECIMAL) */
            menuHoverColor: '#000', /*	MENU HOVER COLOR (HEXADECIMAL) */

            submenuFontSize: 11, /*	SUBMENU FONT SIZE (IN PIXELS) */
            submenuFontWeight: 400, /*	SUBMENU FONT WEIGHT (NORMALLY 300, 400, 700...) */
            submenuColor: '#999', /*	SUBMENU COLOR (HEXADECIMAL) */
            submenuHoverColor: '#000', /*	SUBMENU HOVER COLOR (HEXADECIMAL) */
            submenuIndent: 8, /*	SUBMENU FONT SIZE (IN PIXELS) */
            activeItemColor: '#000'				/*	ACTIVE ITEMS COLOR (HEXADECIMAL) */

        }, settings);



        /////////////////////////////////////////////////////////////////////////////////////////////////////////////		
        //STORAGE VARS
        var main = $('#smooth-menu');
        var menu = $('#smooth-menu a');
        var submenu = $('#smooth-menu ul.second-level a');
        var secondLevel = $('#smooth-menu ul.second-level');
        var activeItem = $('#smooth-menu .active-item');
        var activeItemSecond = $('#smooth-menu li ul li .active-item');
        var lastItem = $('#smooth-menu li.last-item ul');



        /////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //APPLY GLOBAL SETTINGS

        //MAIN
        main.css({
            'width': settings.globalWidth,
            'line-height': settings.lineHeight + 'px'
        });


        //MENU (FIRST LEVEL)
        menu.css({
            'font-size': settings.menuFontSize,
            'font-weight': settings.menuFontWeight,
            'color': settings.menuColor
        })
                .hover(
                function() {
                    $(this).css('color', settings.menuHoverColor);
                },
                function() {
                    $(this).css('color', settings.menuColor);
                }
        );


        //SUBMENU (SECOND LEVEL)
        submenu.css({
            'font-size': settings.submenuFontSize,
            'font-weight': settings.submenuFontWeight,
            'color': settings.submenuColor,
            'margin-left': settings.submenuIndent + 'px'
        })
                .hover(
                function() {
                    $(this).css('color', settings.submenuHoverColor);
                },
                function() {
                    $(this).css('color', settings.submenuColor);
                }
        );


        //SECOND LEVEL UL / LINES
        secondLevel.css({
            'display': 'none',
            'border-top': settings.dividerSize + 'px' + ' ' + settings.dividerStyle + ' ' + settings.dividerColor,
            'border-bottom': settings.dividerSize + 'px' + ' ' + settings.dividerStyle + ' ' + settings.dividerColor,
            'margin': '5px 0px 5px 0px',
            'padding': '4px 0px 5px 0px',
            'line-height': (settings.lineHeight - 2) + 'px'
        });


        //ACTIVE ITEM
        activeItem.css('color', settings.activeItemColor)
                .hover(
                function() {
                    $(this).css({
                        'cursor': 'default',
                        'color': settings.activeItemColor
                    });
                }
        )
                .next("ul").css('display', 'block');


        //LAST ITEM
        if (settings.dividerOnLastItem !== 'true')
            lastItem.css('border-bottom', 0);




        /////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //MENU BUTTONS ACTIONS
        menu.click(function(event) {

            //STORAGE VARS
            var e = $(this);
            var ul = activeItem.next("ul");

            //BLOCK ITEMS WITHOUT SECOND LEVELS & ACTIVE ITEM
            if (!e.next("ul").exists() || e.css('cursor') == 'default') {

                //IF SECOND LEVEL BUTTON IS CLICKED, THEN ACTIVATE
                if (e.closest('ul').hasClass('second-level')) {
                    activeItemSecond.css('color', settings.submenuColor)
                            .hover(
                            function() {
                                $(this).css('cursor', 'pointer');
                            },
                            function() {
                                $(this).css('color', settings.submenuColor);
                            }
                    );

                    e.css({
                        'cursor': 'default',
                        'color': settings.activeItemColor
                    })
                            .hover(
                            function() {
                                $(this).css({'cursor': 'default', 'color': settings.activeItemColor});
                            }
                    );

                    activeItemSecond = e;
                    
                    // ****  This was originally coded incorrectly as "e.preventDefault() which caused an error and forced scroll to top after click
                    // ****  Performs redirect since original a tag redirect was disabled by jQuery
                    window.location.href=e.attr("href");
                } else {

                    //DIRECT ITEMS ALSO HIDE OPENED ULs
                    if (ul.exists())
                        ul.slideUp(settings.animationSpeed, smoothExpand);
                    else
                        smoothExpand();
                }

            } else {

                //IF UL IS OPEN, HIDE IT, THEN DISPLAY CLICKED UL
                if (ul.exists())
                    ul.slideUp(settings.animationSpeed, smoothExpand);
                else
                    smoothExpand();
            }

            //MAIN EXPANDABLE FUNCTION
            function smoothExpand() {

                //OPEN SECOND LEVEL OF CLICKED ITEM
                e.next("ul").slideDown(settings.animationSpeed, recordAnimatedMenuLocation(e.height() + e.next("ul").height(),$(e).offset().top));

                //REMOVE ALL STUFF ON ACTIVE ITEMS
                activeItem.css('color', settings.menuColor)
                        .hover(
                        function() {
                            $(this).css({'cursor': 'pointer', 'color': settings.menuHoverColor});
                        },
                        function() {
                            $(this).css('color', settings.menuColor);
                        }
                );

                activeItemSecond.css('color', settings.submenuColor)
                        .hover(
                        function() {
                            $(this).css({'cursor': 'pointer', 'color': settings.submenuHoverColor});
                        },
                        function() {
                            $(this).css('color', settings.submenuColor);
                        }
                );

                //MAKE CLICKED ITEM ACTIVE ONE
                e.css({
                    'cursor': 'default',
                    'color': settings.activeItemColor
                })
                        .hover(
                        function() {
                            $(this).css({'cursor': 'default', 'color': settings.activeItemColor});
                        }
                );

                //UPGRADE ACTIVE ITEM
                activeItem = e;
            }

            //PREVENT ANCHOR LINK
            // ****  This was originally coded incorrectly as "e.preventDefault() which caused an error and forced scroll to top after click
            // ****  This requires that "menu.click(function() {" noted above be changed to "menu.click(function(event) {"
            event.preventDefault();   
        });


    } //END OF CLASS CONSTRUCTOR



    /////////////////////////////////////////////////////////////////////////////////////////////////////////////	
    //ADD YOUR CUSTOM FUNCTIONS HERE (IF NEEDED)
    //function yourCustomFunction() {
    ///...
    //};
    function recordAnimatedMenuLocation(ht, toppos) {
//      This area used for logging events        
//        console.log(ht);
//        console.log(toppos);
//        console.log(this.innerHeight);   
//        if (ht + toppos > this.innerHeight) {
////            alert('off page');    
//            this.scrollTo(0,toppos);
//        }
    }

})(jQuery);


