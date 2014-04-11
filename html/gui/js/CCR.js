// JavaScript Document
Ext.namespace('CCR', 'CCR.xdmod', 'CCR.xdmod.ui', 'CCR.xdmod.ui.dd', 'XDMoD', 'XDMoD.Module', 'CCR.xdmod.reporting');

// ==============================================================

XDMoD.Tracking = {
   sequence_index: 0,
   timestamp: new Date().getTime(),
   suppress_close_handler: false
};

XDMoD.TrackEvent = function(category, action, details, suppress_close_handler) {
}//XDMoD.TrackEvent

// ==============================================================

XDMoD.GeneralOperations = {

    disableButton: function (button_id) {

        Ext.getCmp(button_id).setDisabled(true);

    }, //disableButton

    contactSuccessHandler: function (window_id) {

        var w = Ext.getCmp(window_id);
        w.hide();

        CCR.xdmod.ui.generalMessage('Message Sent', 'Thank you for contacting us.<br>We will get back to you as soon as possible.', true);

    } //contactSuccessHandler

} //XDMoD.GeneralOperations

// ==============================================================

XDMoD.GlobalToolbar = {};

XDMoD.GlobalToolbar.Logo = {

    xtype: 'tbtext',
    cls: 'logo93',
    width: 93,
    height: 32,
    border: false,

}; //XDMoD.GlobalToolbar.Logo

// -------------------------------------------------

XDMoD.GlobalToolbar.CustomCenterLogo = {

   xtype: 'tbtext',
   cls: 'custom_center_logo',
   height: 32,
   border: false

}; //XDMoD.GlobalToolbar.CustomCenterLogo

// -------------------------------------------------

XDMoD.GlobalToolbar.Profile = {

    text: 'My Profile',
    scale: 'small',
    iconCls: 'user_profile_16',
    tooltip: 'Profile Editor',

    handler: function () {

        XDMoD.TrackEvent("Portal", "My Profile Button Clicked");

        var profileEditor = new XDMoD.ProfileEditor();
        profileEditor.init();

    }

}; //XDMoD.GlobalToolbar.Profile

// -------------------------------------------------

XDMoD.GlobalToolbar.Dashboard = {

    text: 'Dashboard',
    scale: 'small',
    iconCls: 'btn_dashboard',
    tooltip: 'Internal Dashboard',
    handler: function () {

        XDMoD.TrackEvent("Portal", "Dashboard Button Clicked");
        
        var dsConfig = CCR.xdmod.initDashboard();

        if (dsConfig.success == true)
            window.open('internal_dashboard');
        else {

            if (dsConfig.message.toLowerCase() === 'session expired')
                CCR.xdmod.ui.login_reminder.show();
            else
                CCR.xdmod.ui.generalMessage('XDMoD', dsConfig.message, false);

        }

    } //handler

}; //XDMoD.GlobalToolbar.Dashboard

// -------------------------------------------------

XDMoD.GlobalToolbar.Assistant = function (tabPanel) {

    return {

        text: 'Assistant',
        iconCls: 'assist_16',
        handler: function () {

            var active_tab_id = tabPanel.getActiveTab().id;

            var zone = '';

            if (active_tab_id == 'usage_explorer') zone = 'usage_explorer';
            if (active_tab_id == 'report_tab_panel') zone = 'report_generator';

            if (CCR.xdmod.catalog[zone] == undefined) return;

            XDMoD.InitAssistant(CCR.xdmod.catalog[zone]);

        } //handler

    };

}; //XDMoD.GlobalToolbar.Assistant

// -------------------------------------------------

XDMoD.GlobalToolbar.SignUp = {

    text: 'Sign Up',
    tooltip: 'New User? Sign Up Today',
    scale: 'small',
    iconCls: 'signup_16',

    handler: function () {

        XDMoD.TrackEvent("Portal", "Sign Up Button Clicked");

        CCR.xdmod.ui.actionSignUp();

    }

}; //XDMoD.GlobalToolbar.SignUp

// -------------------------------------------------

XDMoD.GlobalToolbar.About = function () {

    var wndAbout = new CCR.BrowserWindow({

        width: 867,
        height: 450,
        iconCls: 'about_16',
        title: 'XDMoD ' + CCR.xdmod.short_version,
        src: "gui/general/about.php",
        versionStamp: true,
        
        listeners: {
        
           hide: function() {
           
               XDMoD.TrackEvent('About Window', 'Closed Window');
           
           }//hide
        
        }//listeners

    }); //wndAbout

    return {

        text: 'About',
        tooltip: 'About',
        scale: 'small',
        iconCls: 'about_16',

        handler: function () {
        
            XDMoD.TrackEvent("Portal", "About Button Clicked");
            
            wndAbout.show();
            
        }

    };

}; //XDMoD.GlobalToolbar.About

// -------------------------------------------------

XDMoD.GlobalToolbar.Contact = function (splashMode) {

    if (splashMode == undefined) splashMode = false;

    var dialogHeight = (splashMode == true) ? 420 : 280;

    return {

        text: 'Contact',
        tooltip: 'Contact',
        scale: 'small',
        iconCls: 'contact_16',

        handler: function () {

            XDMoD.TrackEvent("Portal", "Contact Button Clicked");
            
            var wndContact = new XDMoD.ContactDialog();
            wndContact.show();
   
        } //handler

    };

}; //XDMoD.GlobalToolbar.Contact

// -------------------------------------------------

XDMoD.GlobalToolbar.Help = function (tabPanel) {

    return {

        text: 'Help',
        tooltip: 'Help',
        scale: 'small',
        id: 'help_button',
        iconCls: 'help_16',
        menu: new Ext.menu.Menu({

            items: [

                {
                    text: 'User Manual',
                    iconCls: 'user_manual_16',
                    handler: function () {

                        XDMoD.TrackEvent("Portal", "Help -> User Manual Button Clicked");
    
                        if (tabPanel == undefined) {

                            window.open("user_manual.php");
                            return;

                        }

                        // Context-sensitive help based on 'active tab'

                        var active_tab_id = tabPanel.getActiveTab().id;

                        var searchTerms = '';

                        if (active_tab_id == 'tg_summary') searchTerms = 'Summary Tab';
                        if (active_tab_id == 'tg_usage') searchTerms = 'Usage Tab';
                        if (active_tab_id == 'usage_explorer') searchTerms = 'Usage Explorer';

                        if (active_tab_id == 'allocations') searchTerms = 'Allocations';

                        if (active_tab_id == 'data_miner') searchTerms = 'App Kernel Explorer';
                        if (active_tab_id == 'app_kernels') searchTerms = 'App Kernels';
                        if (active_tab_id == 'report_tab_panel') searchTerms = 'Report Generator';
                        if (active_tab_id == 'search_usage') searchTerms = 'Search Usage';

                        if (active_tab_id == 'compliance_tab') searchTerms = 'Compliance Tab';

                        window.open('user_manual.php?t=' + searchTerms.replace(' ', '+'));

                    }
                },

                {
                    text: 'FAQ',
                    iconCls: 'help_16',
                    handler: function () {

                        XDMoD.TrackEvent("Portal", "Help -> FAQ Button Clicked");

                        window.open('faq');

                    }
                },

                {
                    text: 'YouTube Channel',
                    iconCls: 'youtube_16',
                    handler: function () {
                    
                        XDMoD.TrackEvent("Portal", "Help -> YouTube Channel Button Clicked");
                    
                        window.open('http://www.youtube.com/user/ccrbuffalo?feature=watch');
                    
                    }
                }

            ]

        }) //menu

    };

}; //XDMoD.GlobalToolbar.Help

// =====================================================================

Ext.Ajax.timeout = 86400000;

CCR.xdmod.ui.tokenDelimiter = ':';

CCR.xdmod.ui.minChartScale = 0.5;
CCR.xdmod.ui.maxChartScale = 5;

CCR.xdmod.ui.deltaChartScale = 0.2;

CCR.xdmod.ui.thumbChartScale = 0.76;
CCR.xdmod.ui.thumbAspect = 3.0 / 5.0;
CCR.xdmod.ui.thumbPadding = 15.0;
CCR.xdmod.ui.scrollBarWidth = 15;

CCR.xdmod.ui.deltaThumbChartScale = 0.3;
CCR.xdmod.ui.highResScale = 2.594594594594595;

CCR.xdmod.ui.hd1280Scale = 1.72972972972973;
CCR.xdmod.ui.hd1920cale = 2.594594594594595;
CCR.xdmod.ui.print300dpiScale = 4.662162162162162;
CCR.xdmod.ui.smallChartScale = 0.61;

CCR.xdmod.ui.thumbWidth = 400;
CCR.xdmod.ui.thumbHeight = CCR.xdmod.ui.thumbWidth*CCR.xdmod.ui.thumbAspect;

CCR.xdmod.XSEDE_USER_TYPE = 700;

CCR.xdmod.UserTypes = {
    ProgramOfficer: 'po',
    CenterDirector: 'cd',
    CenterStaff: 'cs',
    CampusChampion: 'cc',
    PrincipalInvestigator: 'pi',
    User: 'usr'
}

CCR.xdmod.reporting.dirtyState = false;

CCR.xdmod.catalog = {
    usage_explorer: {},
    report_generator: {}
};

CCR.xdmod.ui.invertColor = function (hexTripletColor) {
    var color = hexTripletColor;
    color = parseInt(color, 16); // convert to integer
    color = 0xFFFFFF ^ color; // invert three bytes
    color = color.toString(16); // convert to hex
    color = ("000000" + color).slice(-6); // pad with leading zeros
    return color;
};
// ------------------------------------

// Global reference to login prompt

CCR.xdmod.ui.login_prompt = null;

// ------------------------------------

CCR.xdmod.ui.login_reminder = new Ext.Window({

    width: 200,
    height: 100,

    title: 'Not logged in',
    html: '<br><center>Please log in to continue</center>',
    closable: false,
    resizable: false,
    modal: true,

    present: function (cfg) {

        var item = this.getBottomToolbar().get(0);

        if (cfg.code != undefined)
            item.setText('Code: ' + cfg.code);
        else
            item.setText('');

        this.show();

    },

    bbar: {
        items: [

            {
                xtype: 'tbtext',
                style: {
                    color: '#888'
                }
            },

            '->',

            new Ext.Button({
                text: 'Log In',
                handler: function () {
                    location.href = location.href.split('#')[0];
                }
            })

        ]
    }

}); //CCR.xdmod.ui.login_reminder

CCR.xdmod.ui.createUserManualLink = function (tags) {

    return '<div style="background-image: url(\'gui/images/user_manual.png\'); background-repeat: no-repeat; height: 36px; padding-left: 40px; padding-top: 10px">' +
        'For more information, please refer to the <a href="javascript:void(0)" onClick="CCR.xdmod.ui.userManualNav(\'' + tags + '\')">User Manual</a>' +
        '</div>';

}; //CCR.xdmod.ui.createUserManualLink


CCR.xdmod.ui.userManualNav = function (tags) {

    window.open('user_manual.php?t=' + tags);

};

CCR.xdmod.ui.shortTitle = function (name) {
    if (name.length > 50) {
        return name.substr(0, 47) + '...';
    }
    return name;
};

CCR.xdmod.ui.randomBuffer = function () {
    return (300 * Math.random());
}

CCR.ucfirst = function (str) {
    return str.toLowerCase().replace(/\b([a-z])/gi, function (c) {
        return c.toUpperCase()
    });
}

CCR.xdmod.ui.userAssumedCenterRole = function () {

    var role_id = CCR.xdmod.ui.activeRole.split(';')[0];

    return (role_id == CCR.xdmod.UserTypes.CenterDirector || role_id == CCR.xdmod.UserTypes.CenterStaff);

} //CCR.xdmod.ui.userAssumedCenterRole

CCR.xdmod.enumAssignedResourceProviders = function () {

    var assignedResourceProviders = {};

    for (var x = 0; x < CCR.xdmod.ui.allRoles.length; x++) {

        var role_data = CCR.xdmod.ui.allRoles[x].param_value.split(':');
        var role_id = role_data[0];

        if (role_id == CCR.xdmod.UserTypes.CenterDirector || role_id == CCR.xdmod.UserTypes.CenterStaff) {

            assignedResourceProviders[role_data[1]] = CCR.xdmod.ui.allRoles[x].description.split(' - ')[1];

        }

    } //for

    return assignedResourceProviders;

} //enumAssignedResourceProviders

CCR.xdmod.ui.createMenuCategory = function (text) {

    return new Ext.menu.TextItem({
        html: '<div style="height: 20px; vertical-align: middle; background-color: #ddd; font-weight: bold"><span style="color: #00f; position: relative; top: 4px; left: 3px">' + text + '</span></div>'
    });

} //CCR.xdmod.ui.createMenuCategory

CCR.xdmod.ui.createRoleCategoryOptions = function (suffix) {
    var buf = [];
    var first = true;
    for (x in CCR.xdmod.ui.roleCategories) {
        buf.push(
            '<option value="', x, '" ', (first ? ' selected="true">' : '>'),
            CCR.xdmod.ui.roleCategories[x], ' ', suffix,
            '</option>'
        );
        first = false;
    }
    return buf.join('');
}

CCR.xdmod.ui.createRoleCategorySelector = function (suffix) {

    return new Ext.Toolbar.Item({
        autoEl: {
            tag: 'select',
            cls: 'x-role-category-select',
            html: CCR.xdmod.ui.createRoleCategoryOptions(suffix)
        }
    });

}

// -----------------------------------

CCR.invokePost = function (URL, PARAMS) {

    var temp = document.createElement("form");

    temp.action = URL;
    temp.method = "POST";
    temp.style.display = "none";

    for (var x in PARAMS) {
        var opt = document.createElement("textarea");
        opt.name = x;
        opt.value = PARAMS[x];
        temp.appendChild(opt);
    }

    document.body.appendChild(temp);
    temp.submit();
    return temp;

} //CCR.invokePost

// -----------------------------------

CCR.xdmod.ui.AssistPanel = Ext.extend(Ext.Panel, {

    layout: 'fit',
    margins: '2 2 2 0',

    bodyStyle: {
        overflow: 'auto'
    },

    initComponent: function () {

        var self = this;

        self.html = '<div class="x-grid-empty">';

        if (self.headerText) self.html += '<b>' + self.headerText + '</b><br/><br/>';
        if (self.subHeaderText) self.html += self.subHeaderText + '<br/><br/>';
        if (self.graphic) self.html += '<img src="' + self.graphic + '"><br/><br/>';
        if (self.userManualRef) self.html += CCR.xdmod.ui.createUserManualLink(self.userManualRef);

        self.html += '</div>';

        CCR.xdmod.ui.AssistPanel.superclass.initComponent.call(this);

    } //initComponent

}); //CCR.xdmod.ui.AssistPanel

// -----------------------------------

CCR.WebPanel = Ext.extend(Ext.Window, {

    onRender: function () {

        this.bodyCfg = {

            tag: 'iframe',
            src: this.src,
            cls: this.bodyCls,

            style: {
                border: '0px none'
            }
        };

        if (this.frameid)
            this.bodyCfg['id'] = this.frameid;

        CCR.WebPanel.superclass.onRender.apply(this, arguments);

    }, //onRender

    // -----------------------------

    initComponent: function () {

        CCR.WebPanel.superclass.initComponent.call(this);

    } //initComponent

}); //CCR.WebPanel

// -----------------------------------

CCR.xdmod.sponsor_message = 'This work was sponsored by NSF under grant number OCI 1025159';

var toggle_about_footer = function (o) {

    o.innerHTML = (o.innerHTML == CCR.xdmod.version) ? CCR.xdmod.sponsor_message : CCR.xdmod.version;

} //toggle_about_footer

CCR.BrowserWindow = Ext.extend(Ext.Window, {

    modal: true,
    resizable: false,
    closeAction: 'hide',
    versionStamp: false, // Set to 'true' during instantiation to display version stamp
    // in lower-left region of window (left of bbar)

    onRender: function () {

        this.bodyCfg = {

            tag: 'iframe',
            src: this.src,
            cls: this.bodyCls,

            style: {
                border: '0px none'
            }
        };

        if (this.frameid)
            this.bodyCfg['id'] = this.frameid;

        CCR.BrowserWindow.superclass.onRender.apply(this, arguments);

    }, //onRender

    // -----------------------------

    initComponent: function () {

        var self = this;

        var window_items = new Array();

        if (self.nbutton) {
            window_items.push(self.nbutton);
        }

        //+ CCR.xdmod.version

        if (self.versionStamp)
            window_items.push({
                xtype: 'tbtext',
                html: '<span style="color: #000; cursor: default" onClick="toggle_about_footer(this)">' + CCR.xdmod.sponsor_message + '</span>'
            });

        window_items.push('->');

        window_items.push(
            new Ext.Button({
                text: 'Close',
                iconCls: 'general_btn_close',
                handler: function () {

                    if (self.closeAction == 'close')
                        self.close();
                    else
                        self.hide();

                }
            })
        );

        Ext.apply(this, {

            bbar: {
                items: window_items
            }

        });

        CCR.BrowserWindow.superclass.initComponent.call(this);

    } //initComponent

}); //CCR.BrowserWindow

// -----------------------------------

/*
CCR.xdmod.ui.loginCheck = function() {
	return CCR.xdmod.InvokeController('user_auth', 'operation=login_check');
}
*/

var logoutCallback = function () {
    location.href = 'index.php';
}

CCR.xdmod.ui.actionLogout = function () {

    XDMoD.TrackEvent("Portal", "logout link clicked");

    XDMoD.REST.Call({
        action: 'authentication/utilities/logout',
        callback: logoutCallback
    });

} //actionLogout

// -----------------------------------

var presentLoginPromptOverlay = function (message, status, cb, custom_delay) {

    var cStatus = '#f00';

    var delay = (custom_delay != undefined) ? custom_delay : 2000;

    var cStatus = (status == true) ? '#080' : '#f00';

    Ext.getCmp('wnd_login').getEl().mask('<div class="overlay_message" style="color:' + cStatus + '">' + message + '</div>');

    (function () {

        Ext.getCmp('wnd_login').getEl().unmask();

        if (cb) cb();

    }).defer(delay);

} //presentLoginPromptOverlay

// -----------------------------------

var presentSignUpViaLoginPrompt = function () {

	 XDMoD.TrackEvent('Login Window', 'Clicked on Sign Up button');

    CCR.xdmod.ui.login_prompt.close();

    CCR.xdmod.ui.actionSignUp();

} //presentSignUpViaLoginPrompt

// -----------------------------------

CCR.xdmod.ui.actionSignUp = function () {

   var wndSignup = new XDMoD.SignUpDialog();
   wndSignup.show();

} //CCR.xdmod.ui.actionSignUp

// -----------------------------------

CCR.xdmod.ui.FadeInWindow = Ext.extend(Ext.Window, { //experimental
    animateTarget: true,
    setAnimateTarget: Ext.emptyFn,
    animShow: function () {
        this.el.fadeIn({
            duration: .55,
            callback: this.afterShow.createDelegate(this, [true], false),
            scope: this
        });
    },
    animHide: function () {
        if (this.el.shadow) {
            this.el.shadow.hide();
        }
        this.el.fadeOut({
            duration: .55,
            callback: this.afterHide,
            scope: this
        });
    }
});


CCR.xdmod.ui.actionLogin = function (config, animateTarget) {

    XDMoD.TrackEvent("Portal", "Sign In link clicked");

    CCR.xdmod.ui.login_prompt = new Ext.Window({

        title: "Welcome To XDMoD",

        width: 280,
        height: 300,
        modal: true,
        animate: true,
        resizable: false,
        tbar: {

            items: [{
                xtype: 'tbtext',
                html: '<span style="color: #000">Close this window to view public information</span>'
            }]

        },

        items: [

            new Ext.Panel({
                id: 'wnd_login',
                layout: 'fit',
                html: '<iframe src="gui/general/login.php" frameborder=0 width=100% height=270></iframe>'
            })

        ],
        
        listeners: {
        
          close: function() {
          
             XDMoD.TrackEvent('Login Window', 'Closed Window');

          }//close
          
        }//listeners

    });//CCR.xdmod.ui.login_prompt

    CCR.xdmod.ui.login_prompt.show(animateTarget);
    CCR.xdmod.ui.login_prompt.center();

} //actionLogin

// -----------------------------------

CCR.xdmod.ControllerBase = "controllers/";

// -----------------------------------

// For handling the cases where a UI element is bound to a datastore, and usage of that UI element alone determines when
// that data store reloads

CCR.xdmod.ControllerUIDataStoreHandler = function (activeStore) {

    CCR.xdmod.ControllerResponseHandler('{"status" : "' + activeStore.reader.jsonData.status + '"}', null);

}

// -----------------------------------

CCR.xdmod.ControllerResponseHandler = function (responseText, targetStore) {

    var responseData = Ext.decode(responseText);

    if (responseData.status == 'not_logged_in') {

        var newPanel = new XDMoD.LoginPrompt();
        newPanel.show();

        return false;

    }

    if (targetStore == null) {
        return true;
    }

    if (targetStore != null)
        targetStore.loadData(responseData);

} //CCR.xdmod.ControllerResponseHandler

// -----------------------------------

CCR.xdmod.ControllerProxy = function (targetStore, parameters) {

    if (parameters.operation == null) {
        Ext.MessageBox.alert('Controller Proxy', 'An operation must be specified');
        return;
    }

    Ext.Ajax.request({

        url: targetStore.url,
        method: 'POST',
        params: parameters,
        timeout: 60000, // 1 Minute,
        async: false,

        success: function (response) {

            CCR.xdmod.ControllerResponseHandler(response.responseText, targetStore);

        },

        failure: function () {
            alert('request_error');
        }

    });

} //CCR.xdmod.ControllerProxy

// -----------------------------------

CCR.xdmod.initDashboard = function () {

    if (window.XMLHttpRequest) {
        AJAX = new XMLHttpRequest();
    } else {
        AJAX = new ActiveXObject("Microsoft.XMLHTTP");
    }

    if (AJAX) {

        AJAX.open("POST", 'controllers/dashboard_launch.php', false);
        AJAX.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        AJAX.send();

        var json = Ext.util.JSON.decode(AJAX.responseText);

        return json;

    } //if (AJAX)

    alert('Session Manager:\nThere was a problem initializing the dashboard');
    return false;

} //CCR.xdmod.initDashboard

// -----------------------------------

CCR.xdmod.ui.generalMessage = function (msg_title, msg, success, show_delay) {

    if (!show_delay) show_delay = 2000;

    if (!success) success = false;

    var styleColor = (success) ? '#080' : '#f00';

    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 300) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({

        title: msg_title

        ,
        width: 300,
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available'

        ,
        origin: {
            offY: y_offset,
            offX: x_offset
        }

        ,
        iconCls: ''
        //,iconCls: 'popup_message_general'

        ,
        autoHeight: true,
        draggable: false,
        help: false

        ,
        hideFx: {
            delay: show_delay,
            mode: 'standard'
        }

    }).show(Ext.getDoc());

} //CCR.xdmod.ui.generalMessage

// -----------------------------------

CCR.xdmod.ui.userManagementMessage = function (msg, success) {

    if (!success) success = false;

    var styleColor = (success) ? '#080' : '#f00';

    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 300) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({

        title: 'User Management'

        ,
        width: 300,
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available'

        ,
        origin: {
            offY: y_offset,
            offX: x_offset
        }

        ,
        iconCls: 'user_management_message_prompt'

        ,
        autoHeight: true,
        draggable: false,
        help: false

        ,
        hideFx: {
            delay: 2000,
            mode: 'standard'
        }

    }).show(Ext.getDoc());

} //CCR.xdmod.ui.userManagementMessage

// -----------------------------------

CCR.xdmod.ui.reportGeneratorMessage = function (title, msg, success, callback) {

    if (!success) success = false;

    var styleColor = (success) ? '#080' : '#f00';

    var x_offset = -1 * ((Ext.getBody().getViewSize().width - 200) / 2);
    var y_offset = -1 * ((Ext.getBody().getViewSize().height - 40) / 2);

    new Ext.ux.window.MessageWindow({

        title: title || 'You have clicked:'

        ,
        html: '<b style="color: ' + styleColor + '">' + msg + '</b>' || 'No information available'

        ,
        origin: {
            offY: y_offset,
            offX: x_offset
        }

        ,
        iconCls: 'report_generator_message_prompt'

        ,
        autoHeight: true,
        draggable: false,
        help: false

        ,
        hideFx: {
            delay: 3000,
            mode: 'standard'
        }

        ,
        listeners: {
            hide: function () {
                if (callback) callback();
            }
        }

    }).show(Ext.getDoc());

} //CCR.xdmod.ui.reportGeneratorMessage

// -----------------------------------

CCR.xdmod.ui.toastMessage = function (title, msg) {
    if (CCR.xdmod.ui.isDeveloper == true) {
        new Ext.ux.window.MessageWindow({
            title: title || 'You have clicked:',
            html: msg || 'No information available',
            origin: {
                offY: -5,
                offX: -5
            },
            autoHeight: true,
            iconCls: 'load_time_message_prompt',
            help: false,
            hideFx: {
                delay: 1000,
                mode: 'standard'
            },
            listeners: {
                render: function () {
                    //alert(1);
                }
            }
        }).show(Ext.getDoc());
    }
}


CCR.xdmod.ui.intersect = function (a, b) {
    var result = new Array();
    for (var i = 0; i < a.length; i++) {
        for (var j = 0; j < b.length; j++) {
            if (a[i] == b[j]) result.push(a[i]);
        }
    }
    return result;
}

CCR.xdmod.ui.getComboBox = function (data, fields, valueField, displayField, editable, emptyText) {
    return new Ext.form.ComboBox({
        typeAhead: true,
        triggerAction: 'all',
        lazyRender: true,
        mode: 'local',
        emptyText: emptyText,
        editable: editable,
        store: new Ext.data.ArrayStore({
            id: 0,
            fields: fields,
            data: data
        }),
        valueField: valueField,
        displayField: displayField
    });
}
CCR.xdmod.ui.gridComboRenderer = function (combo) {
    return function (value) {

        var idx = combo.store.find(combo.valueField, value);
        var rec = combo.store.getAt(idx);
        if (!rec) return combo.emptyText;
        return rec.get(combo.displayField);
    };
}

// ExtJS Updates ---------------------------

Ext.checkUserAgent = function (r) {

    ua = navigator.userAgent.toLowerCase();

    return r.test(ua);

} //Ext.checkUserAgent

Ext.isIE9 = Ext.checkUserAgent(/msie 9/);

CCR.isBlank = function (value) {
    var result = false;

    if (value == 'undefined' || value == undefined || value == null || value == '' || value == ' ') {
        result = true;
    }

    return result;
};
// override 3.4.0 to be able to restore column state
Ext.override(Ext.grid.ColumnModel, {
    setState: function (col, state) {
        state = Ext.applyIf(state, this.defaults);
        if (this.columns && this.columns[col]) {
            Ext.apply(this.columns[col], state);
        } else if (this.config && this.config[col]) {
            Ext.apply(this.config[col], state);
        };
    }
});

// override 3.4.0 to fix layout bug with composite fields (field width too narrow)
Ext.override(Ext.form.TriggerField, {
    onResize: function (w, h) {
        Ext.form.TriggerField.superclass.onResize.call(this, w, h);
        var tw = this.getTriggerWidth();
        if (Ext.isNumber(w)) {
            this.el.setWidth(w - tw);
        }
        if (this.rendered && !this.readOnly && this.editable && !this.el.getWidth()) this.wrap.setWidth(w);
        else this.wrap.setWidth(this.el.getWidth() + tw);
    }
});

// override 3.4.0 to fix issue where drag to select didn't work in ext scheduler
Ext.override(Ext.dd.DragTracker, {
    onMouseMove: function (e, target) {
        // !Ext.isIE9 check added
        if (this.active && Ext.isIE && !Ext.isIE9 && !e.browserEvent.button) {
            e.preventDefault();
            this.onMouseUp(e);
            return;
        }

        e.preventDefault();
        var xy = e.getXY(),
            s = this.startXY;
        this.lastXY = xy;
        if (!this.active) {
            if (Math.abs(s[0] - xy[0]) > this.tolerance || Math.abs(s[1] - xy[1]) > this.tolerance) {
                this.triggerStart(e);
            } else {
                return;
            }
        }
        this.fireEvent('mousemove', this, e);
        this.onDrag(e);
        this.fireEvent('drag', this, e);
    }
});

// override 3.4.0 to fix issue with tooltip text wrapping in IE9 (tooltip 1 pixel too narrow)
// JS: I suspect this issue is caused by subpixel rendering in IE9 causing bad measurements
Ext.override(Ext.Tip, {
    doAutoWidth: function (adjust) {
        // next line added to allow beforeshow to cancel tooltip (see below)
        if (!this.body) return;
        adjust = adjust || 0;
        var bw = this.body.getTextWidth();
        if (this.title) {
            bw = Math.max(bw, this.header.child('span').getTextWidth(this.title));
        }
        bw += this.getFrameWidth() + (this.closable ? 20 : 0) + this.body.getPadding("lr") + adjust;
        // added this line:
        if (Ext.isIE9) bw += 1;
        this.setWidth(bw.constrain(this.minWidth, this.maxWidth));


        if (Ext.isIE7 && !this.repainted) {
            this.el.repaint();
            this.repainted = true;
        }
    }
});

// override 3.4.0 to allow beforeshow to cancel the tooltip
Ext.override(Ext.ToolTip, {
    show: function () {
        if (this.anchor) {
            this.showAt([-1000, -1000]);
            this.origConstrainPosition = this.constrainPosition;
            this.constrainPosition = false;
            this.anchor = this.origAnchor;
        }
        this.showAt(this.getTargetXY());

        if (this.anchor) {
            this.anchorEl.show();
            this.syncAnchor();
            this.constrainPosition = this.origConstrainPosition;
            // added "if (this.anchorEl)"
        } else if (this.anchorEl) {
            this.anchorEl.hide();
        }
    },
    showAt: function (xy) {
        this.lastActive = new Date();
        this.clearTimers();
        Ext.ToolTip.superclass.showAt.call(this, xy);
        if (this.dismissDelay && this.autoHide !== false) {
            this.dismissTimer = this.hide.defer(this.dismissDelay, this);
        }
        if (this.anchor && !this.anchorEl.isVisible()) {
            this.syncAnchor();
            this.anchorEl.show();
            // added "if (this.anchorEl)"
        } else if (this.anchorEl) {
            this.anchorEl.hide();
        }
    }
});

// override 3.4.0 to fix issue where enableDragDrop + checkbox selection has issues
// clicking on a selected checkbox does not unselect it + impossible to select multiple
// rows via checkbox
Ext.override(Ext.grid.GridDragZone, {
    getDragData: function (e) {
        var t = Ext.lib.Event.getTarget(e);
        var rowIndex = this.view.findRowIndex(t);
        if (rowIndex !== false) {
            var sm = this.grid.selModel;

            if (sm instanceof(Ext.grid.CheckboxSelectionModel)) {
                sm.onMouseDown(e, t);
            }

            if (t.className != 'x-grid3-row-checker' && (!sm.isSelected(rowIndex) || e.hasModifier())) {
                sm.handleMouseDown(this.grid, rowIndex, e);
            }
            return {
                grid: this.grid,
                ddel: this.ddel,
                rowIndex: rowIndex,
                selections: sm.getSelections()
            };
        }
        return false;
    }
});

// override 3.4.0 to fix false security warning in IE on component destroy
Ext.apply(Ext, {
    removeNode: Ext.isIE && !Ext.isIE9 ? function () {
        return function (n) {
            if (n && n.tagName != 'BODY') {
                (Ext.enableNestedListenerRemoval) ? Ext.EventManager.purgeElement(n, true) : Ext.EventManager.removeAll(n);
                if (n.parentNode && n.tagName == 'TD') {
                    n.parentNode.deleteCell(n);
                } else if (n.parentNode && n.tagName == 'TR') {
                    n.parentNode.deleteRow(n);
                } else {
                    n.outerHTML = ' ';
                }
                delete Ext.elCache[n.id];
            }
        };
    }() : function (n) {
        if (n && n.parentNode && n.tagName != 'BODY') {
            (Ext.enableNestedListenerRemoval) ? Ext.EventManager.purgeElement(n, true) : Ext.EventManager.removeAll(n);
            n.parentNode.removeChild(n);
            delete Ext.elCache[n.id];
        }
    }
});

// override 3.4.0 to ensure that the grid stops editing if the view is refreshed
// actual bug: removing grid lines with active lookup editor didn't hide editor
Ext.grid.GridView.prototype.processRows =
    Ext.grid.GridView.prototype.processRows.createInterceptor(function () {
        if (this.grid) this.grid.stopEditing(true);
    });

// override 3.4.0 to fix issue with chart labels losing their labelRenderer after hide/show
Ext.override(Ext.chart.CartesianChart, {
    createAxis: function (axis, value) {
        var o = Ext.apply({}, value),
            ref,
            old;

        if (this[axis]) {
            old = this[axis].labelFunction;
            this.removeFnProxy(old);
            this.labelFn.remove(old);
        }
        if (o.labelRenderer) {
            ref = this.getFunctionRef(o.labelRenderer);
            o.labelFunction = this.createFnProxy(function (v) {
                return ref.fn.call(ref.scope, v);
            });
            // delete o.labelRenderer; // <-- commented out this line
            this.labelFn.push(o.labelFunction);
        }
        if (axis.indexOf('xAxis') > -1 && o.position == 'left') {
            o.position = 'bottom';
        }
        return o;
    }
});

// override 3.4.0 to allow tabbing between editable grid cells to work correctly
Ext.override(Ext.grid.RowSelectionModel, {
    acceptsNav: function (row, col, cm) {
        if (!cm.isHidden(col) && cm.isCellEditable(col, row)) {
            // check that there is actually an editor
            if (cm.getCellEditor) return !!cm.getCellEditor(col, row);
            return true;
        };
        return false;
    }
});
