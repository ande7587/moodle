/** re-arrange the page layout from 3-column into 2-column */
YUI().use('node', 'cookie', function (Y) {
    /** copy gallery-media module inline here */

    /*
     * Copyright (c) 2010 Nicholas C. Zakas. All rights reserved.
     * http://www.nczonline.net/
     */

    /**
     * Media module
     * @module gallery-media
     */

    /**
     * The Media namespace.
     * @class Media
     * @static
     */
    Y.namespace("Media");

    //-------------------------------------------------------------------------
    // Private variables
    //-------------------------------------------------------------------------

    var mediaList   = {},                   //list of media queries to track
        wasMedia    = {},                   //state of media queries when last checked
        win         = Y.config.win,         //window reference
        controller  = new Y.Event.Target(), //custom events for media queries
        UA          = YUI.Env.UA,           //user agent info
        div,                                //HTML element used to track media queries
        style,                              //HTML element used to inject style rules
        nativeListenerSupport = !!win.matchMedia; //determines native listener support

    //-------------------------------------------------------------------------
    // Private functions
    //-------------------------------------------------------------------------

    if (!nativeListenerSupport) {

        //resize is really the only thing to monitor for desktops
        Y.on("windowresize", function() {
            var query,
                medium,
                isMatch,
                wasMatch;

            for (query in mediaList) {
                if (mediaList.hasOwnProperty(query)) {
                    medium = mediaList[query];
                    wasMatch = wasMedia[query];
                    isMatch = Y.Media.matches(query);

                    if (isMatch !== wasMatch) {
                        controller.fire(query, {
                            media: query,
                            matches: isMatch
                        });
                    }
                }
            }
        });
    }

    //-------------------------------------------------------------------------
    // Public interface
    //-------------------------------------------------------------------------

    /**
     * Determines if a given media query is currently valid.
     * @param {String} query The media query to test.
     * @return {Boolean} True if the query is valid, false if not.
     */
    Y.Media.matches = function(query){

        var result = false;

        if (win.matchMedia) {
            result = win.matchMedia(query).matches;
        } else {

            //if the <div> doesn't exist, create it and make sure it's hidden
            if (!div){
                div = document.createElement("div");
                div.id = "yui-m-1";
                div.style.cssText = "position:absolute;top:-1000px";
                document.body.insertBefore(div, document.body.firstChild);
            }

            div.innerHTML = "_<style media=\"" + query + "\"> #yui-m-1 { width: 1px; }</style>";
            div.removeChild(div.firstChild);
            result = div.offsetWidth == 1;
        }

        wasMedia[query] = result;
        return result;
    };

    /**
     * Allows you to specify a listener to call when a media query becomes
     * valid for the given page.
     * @param {String} query The media query to listen for.
     * @param {Function} listener The function to call when the query becomes valid.
     * @param {Object} context (Optional) The this-value for the listener.
     * @return {EventHandle} An event handle to allow detaching.
     * @method on

     */
    Y.Media.on = function(query, listener, context) {

        if (nativeListenerSupport && !mediaList[query]) {

            /**
             * Chrome/Safari are strange. They only track media query changes if
             * there are media queries in CSS with at least one rule. So, this
             * injects a style into the page so changes are tracked.
             */
            if (YUI.Env.UA.webkit) {
                if (!style) {
                    style = document.createElement("style");
                    document.getElementsByTagName("head")[0].appendChild(style);
                }

                style.appendChild(document.createTextNode("@media " + query + " { .-yui-m {} }"));
            }

            //need to cache MediaQueryList or else Firefox loses the event handler
            mediaList[query] = win.matchMedia(query);
            mediaList[query].addListener(function(mql) {
                controller.fire(query, { media: query, matches: mql.matches });
            });
        }

        //track that the query has a listener
        if (!mediaList[query]) {
            mediaList[query] = 1;
        }

        //in all cases, use a custom event for managing
        return controller.on(query, listener, context);
    };

    // MOOD-419 20140923 dhanzely - Fixes issue in IE/Chrome where Maximize Content
    // button was rendered intermittently due to the DOM not being completely loaded
    // in time.
    //
    // The diff is a bit ugly, but only the following line is added; the rest of
    // the change is from nudging the code in one more indentation.
    Y.on('domready', function () {

        /*============ CUSTOM RESIZE ===========*/

        var break_point     = 1024;
        var page_content    = Y.one('#page-content');
        var region_main     = Y.one('#region-main');
        var side_pre        = Y.one('#block-region-side-pre');
        var side_post       = Y.one('#block-region-side-post');
        var left_content    = Y.one('#region-bs-main-and-pre');
        var right_blocks    = Y.all('#block-region-side-post div.block');

        if (side_post != null) {
            var side_post_display = side_post.getStyle('display');

            var switch_to_2_column = function() {
                // move the main content and all blocks to under the page content
                region_main.appendTo(page_content);
                side_pre.appendTo(page_content);

                // move the right-blocks to under the left blocks
                right_blocks.each(function(node) {
                   node.appendTo(side_pre);
                });

                side_post.setStyle('display', 'none');
            };

            var switch_to_3_column = function() {
                // move the main content and all blocks to under the page content
                region_main.appendTo(left_content);
                side_pre.appendTo(left_content);

                // return the right-blocks to the right-side
                right_blocks.each(function(node) {
                   node.appendTo(side_post);
                });

                side_post.setStyle('display', side_post_display);
            };


            // check for initial resize
            if (Y.Media.matches('screen and (max-width:'+break_point+'px)')) {
                switch_to_2_column();
            }

            // register events when screen size change
            Y.Media.on('screen and (max-width:'+break_point+'px)', function(result) {
                if (result.matches) {
                    switch_to_2_column();
                }
            });


            Y.Media.on('screen and (min-width:'+(break_point+1)+'px)', function(result) {
                if (result.matches) {
                    switch_to_3_column();
                }
            });
        }

        //Banner respond to scroll
        var lower_banner = Y.one('#header-heading');
        var img = Y.one('#header-img-wrap');
        var lowerBannerHeight = parseInt(Y.one(lower_banner).getComputedStyle('height'));
        Y.on('scroll', function(e) {
            if(window.scrollY > lowerBannerHeight) {
                Y.one(img).setStyle('background-position','0 -' + lowerBannerHeight + 'px');
            }
            if(window.scrollY < lowerBannerHeight) {
                Y.one(img).setStyle('background-position','0 -' + window.scrollY + 'px');
            }
        });

        // respond to top panel buttons
        var user_image_button = Y.one('.usermenu ul.menubar');
        var user_image_button_2 = Y.one('#lower-user-menu .usermenu ul.menubar');
        var help_panel_logo = Y.one('#help-panel-logo');
        var course_panel_logo = Y.one('#my-courses');
        var m_links_logo = Y.one('#m-links');
        var winWidth = function() {
            return parseInt(Y.one('body').getComputedStyle('width'));
        };

        if(user_image_button_2) {
            if(help_panel_logo) {
                help_panel_logo.setStyle('right',parseInt(user_image_button_2.getComputedStyle('width')));
            }
            user_image_button_2.on('click', function(e) {
                if (winWidth() < 769) {
                    if (Y.one('#lower-user-menu .moodle-actionmenu').hasClass('show') && Y.one('#header-heading').hasClass('smallScreen')) {
                        Y.one('#header-heading').removeClass('smallScreen');
                    }
                    else {
                        Y.one('#header-heading').addClass('smallScreen');
                    }
                };
                if(Y.one('#course-panel')){Y.one('#course-panel').removeClass('active');}
                if(Y.one('#m-links-panel')){Y.one('#m-links-panel').removeClass('active');}
                if(Y.one('#help-panel')){Y.one('#help-panel').removeClass('active');}
            });
        }


        if(user_image_button) {
            if (help_panel_logo) {
                help_panel_logo.setStyle('right',parseInt(user_image_button.getComputedStyle('width')));
            }
            user_image_button.on('click', function(e) {
                if(Y.one('#course-panel')){Y.one('#course-panel').removeClass('active');}
                if(Y.one('#m-links-panel')){Y.one('#m-links-panel').removeClass('active');}
                if(Y.one('#help-panel')){Y.one('#help-panel').removeClass('active');}
            });
        }

        if (help_panel_logo) {
                help_panel_logo.on('click', function(e) {
                    if (winWidth() < 769) {
                        if (Y.one('#header-heading').hasClass('smallScreen') && Y.one('#help-panel').hasClass('active')) {
                            Y.one('#header-heading').removeClass('smallScreen');
                        }
                        else {
                            Y.one('#header-heading').addClass('smallScreen');
                        }
                    };
                        if(Y.one('#help-panel')){Y.one('#help-panel').setStyle('right',parseInt(Y.one('.usermenu').getComputedStyle('width'))-14).toggleClass('active');}
                        if(Y.one('#course-panel')){Y.one('#course-panel').removeClass('active');}
                        if(Y.one('#m-links-panel')){Y.one('#m-links-panel').removeClass('active');}

                        e.stopPropagation();
                });
                Y.one('body').on('click', function(e) {
                        Y.one('#help-panel').removeClass('active');
                        Y.one('#header-heading').removeClass('smallScreen');
                });
        }

        if (course_panel_logo) {
                course_panel_logo.on('click', function(e) {
                    if (winWidth() < 769){
                        if (Y.one('#header-heading').hasClass('smallScreen') && Y.one('#course-panel').hasClass('active')) {
                            Y.one('#header-heading').removeClass('smallScreen');
                        }
                        else {
                            Y.one('#header-heading').addClass('smallScreen');
                        }
                    }
                        Y.one('#course-panel').toggleClass('active');
                        if(Y.one('#help-panel')){Y.one('#help-panel').removeClass('active');}
                        if(Y.one('#m-links-panel')){Y.one('#m-links-panel').removeClass('active');}

                        e.stopPropagation();
                });
                Y.one('body').on('click', function(e) {
                        Y.one('#course-panel').removeClass('active');
                        Y.one('#header-heading').removeClass('smallScreen');
                });
        }

        if (m_links_logo) {
                Y.one('#m-links').on('click', function(e) {
                    if (winWidth() < 769){
                        if (Y.one('#header-heading').hasClass('smallScreen') && Y.one('#m-links-panel').hasClass('active')) {
                            Y.one('#header-heading').removeClass('smallScreen');
                        }
                        else {
                            Y.one('#header-heading').addClass('smallScreen');
                        }
                    }
                        Y.one('#m-links-panel').toggleClass('active');
                        if(Y.one('#help-panel')){Y.one('#help-panel').removeClass('active');}
                        if(Y.one('#course-panel')){Y.one('#course-panel').removeClass('active');}

                        e.stopPropagation();
                });
                Y.one('body').on('click', function(e) {
                        Y.one('#m-links-panel').removeClass('active');
                        Y.one('#header-heading').removeClass('smallScreen');
                });
        }


        /* ============ MAXIMIZE-CONTENT BUTTON =======*/
        var region_main   = Y.one('#region-main');

        if (region_main) {
            var page_navbar   = Y.one('#page-navbar');
            var bread_crumb   = Y.one('#page-navbar .breadcrumb-button');
            var left_column   = Y.one('#block-region-side-pre');
            var right_column  = Y.one('#block-region-side-post');
            var header        = Y.one('header');
            var span9         = Y.one('#page-content .span9');
            var span8         = Y.one('#page-content .span8');
            var page          = Y.one('div#page');

            var is_content_max = false;

            // only add the max button if there are more than one columns
            if (left_column || right_column) {
                var max_button = Y.Node.create('<div id="umn-clean-max-content-btn"></div>');

                // M.str is not available until the end of page load
                Y.on('domready', function() {
                    if (max_button.hasClass('max')) {
                        max_button.setAttribute('title', M.util.get_string('maxcontent_restore', 'theme_umn_clean'));
                    }
                    else {
                        max_button.setAttribute('title', M.util.get_string('maxcontent_max', 'theme_umn_clean'));
                    }
                });

                if (page_navbar && bread_crumb && page_navbar.getStyle('display') != 'none') {
                    bread_crumb.prepend(max_button);
                }
                else {
                    region_main.append(max_button);
                }

                /**
                 * helper function to maximize content
                 */
                function maximize_content() {
                    // hide
                    Y.Array.each([left_column, right_column, header], function(el) {
                        if (el != null) {
                            el.addClass('hidden');
                        }
                    });

                    // max width
                    Y.Array.each([region_main, span8, span9], function(el) {
                        if (el != null) {
                            el.addClass('max');
                        }
                    });

                    page.addClass('maximized');

                    max_button.addClass('max').setAttribute('title', M.util.get_string('maxcontent_restore', 'theme_umn_clean'));
                    is_content_max = true;

                };

                // check for sticky mode
                if (Y.Cookie.get('content-maximized') == '1') {
                    maximize_content();
                }
                // attach event handler for the max button
                max_button.on('click', function(e) {
                    if (!is_content_max) {

                        maximize_content();

                        // store sticky mode into cookie, if hot-key is pressed
                        if (e.ctrlKey || e.shiftKey) {
                            Y.Cookie.set('content-maximized', '1', {path: '/'});
                        }
                    }
                    else {
                        // unhide
                        Y.Array.each([left_column, right_column, header], function(el) {
                            if (el != null) {
                                el.removeClass('hidden');
                            }
                        });

                        // un-max width
                        Y.Array.each([region_main, span8, span9], function(el) {
                            if (el != null) {
                                el.removeClass('max');
                            }
                        });

                        page.removeClass('maximized');

                        max_button.removeClass('max').setAttribute('title', M.util.get_string('maxcontent_max', 'theme_umn_clean'));
                        is_content_max = false;

                        // unset sticky mode
                        Y.Cookie.remove('content-maximized', {path: '/'});
                    }
                });
            }
        }
    });
});
