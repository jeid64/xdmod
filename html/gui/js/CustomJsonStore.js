/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @date 2012-Feb-1
 *
 * This class is an extension of json store
 *
 */
CCR.xdmod.CustomJsonStore = Ext.extend(Ext.data.JsonStore, {
    constructor: function (config) {

        CCR.xdmod.CustomJsonStore.superclass.constructor.call(this, config);
    },
    listeners: {
        'exception': function (dp, type, action, opt, response, arg) {
            if (response.success == false) {
                //todo: show a re-login box instead of logout
                Ext.MessageBox.alert("Error", response.message || 'Unknown Error');

                if (response.message == 'Session Expired') {
                    CCR.xdmod.ui.login_reminder.present({
                        code: this.reader.jsonData.code
                    });
                    //CCR.xdmod.ui.actionLogout.defer(1000);
                }

            }
        }
    }

});

Ext.reg('xdmodstore', CCR.xdmod.CustomJsonStore);