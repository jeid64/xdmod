/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2013-1-1
*
*/

Ext.namespace('Ext.ux.plugins');
Ext.ux.plugins.ContainerBodyMask = function (opt) {
    var options = opt || {};

    return {
        init: function (c) {
            Ext.applyIf(c, {
                showMask: function (msg, msgClass, maskClass) {
                    var el;

                    if (this.rendered && (el = this[options.el] || Ext.get(options.el) || this.body ? this.body : null)) {
                        el.mask.call(el, msg || options.msg, msgClass || options.msgClass, maskClass || options.maskClass);
                    }
                },
                hideMask: function () {
                    var el;
                    if (this.rendered && (el = this[options.el] || Ext.get(options.el) || this.body ? this.body : null)) {
                        el.unmask.call(el);
                    }
                }
            });
            if (options.masked) {
                c.on('render', c.showMask.createDelegate(c, [null]), c, {
                    delay: 10,
                    single: true
                });
            }
        }
    };
};