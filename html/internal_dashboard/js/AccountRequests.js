Ext.ns('XDMoD');

XDMoD.AccountRequests = Ext.extend(Ext.Panel,  {

   initComponent: function(){

      var self = this;
      var cachedMD5 = '';

      var adminPanel = new XDMoD.AdminPanel();

      // ---------------------------------

      self.storeProvider = new DashboardStore({

         url: 'controllers/controller.php',
         root: 'response',
         baseParams: {'operation' : 'enum_account_requests' },

         fields: [
            'id',
            'first_name',
            'last_name',
            'organization',
            'title',
            'email_address',
            'field_of_science',
            'additional_information',
            'time_submitted',
            'status',
            'comments'
         ]

      });

      self.storeProvider.on('load', function(s, r) {

         var suffix = (r.length != 1) ? 's' : '';

         tbNumRequests.setText('<b style="color: #00f">' + r.length + ' account request' + suffix + '</b>');

      });

      var presentUser = function (data, parent) {

         var w = new XDMoD.CommentEditor();

         w.setParent(parent);
         w.initWithData(data);

         w.show();

      }//presentUser

      // ---------------------------------

      var rowRenderer = function(val, metaData, record, rowIndex, colIndex, store){

         var entryData = store.getAt(rowIndex).data;

         var activeColor = (entryData.status == 'new') ? '#000' : '#080';

         return '<span style="color: ' + activeColor + '">' + val + '</span>';

      }//rowRenderer

      // ---------------------------------

      var staleCheck = function () {

         ServerRequest({

            url: 'controllers/controller.php',
            params: {
               'operation' : 'enum_account_requests',
               'md5only': true
            },
            method: 'POST',
            callback: function(options, success, response) {

               if (success) {

                  var json = Ext.util.JSON.decode(response.responseText);

                  if (json.success == true) {

                     var updateNeeded = (cachedMD5 != json.md5);

                     if(updateNeeded)
                        document.getElementById('btn_refresh_toolicon').className = 'x-btn x-btn-text-icon update_highlight';
                     else
                        document.getElementById('btn_refresh_toolicon').className = 'x-btn x-btn-text-icon';

                  }//if (json.success == true)

                  (function(){ staleCheck(); }).defer(10000);

               }//if

            }//callback

         });//ServerRequest

      }//staleCheck()

      // ---------------------------------

      var generateLegend = function(c) {

         var markup = '<div><table border=0><tr>';

         for (var i = 0; i < c.length; i++)
            markup += '<td style="background-color: ' + c[i].color + '" width=30>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td><td style="padding: 0 10px 0 0">&nbsp;' + c[i].label + '</td>';

         markup += '</tr></table></div>';

         return markup;

      }//generateLegend

      // ---------------------------------

      self.storeProvider.on('load', function(store, records, options) {

         cachedMD5 = store.reader.jsonData.md5;

      });

      // ---------------------------------

      self.on('afterrender', function(){

         reloadAccountRequests();
         (function(){ staleCheck(); }).defer(1000);

      });

      self.userGrid = new Ext.grid.GridPanel({

         store: self.storeProvider,

         viewConfig: {
            emptyText: 'No account requests are present',
            forceFit: true
         },

         autoScroll: true,
         enableHdMenu: false,
         loadMask: true,

         sm: new Ext.grid.RowSelectionModel({

            singleSelect: true,

            listeners: {

               rowselect: function(smObj, rowIndex, record) {

                  Ext.getCmp('btn_toolbar_edit').setDisabled(false);
                  Ext.getCmp('btn_toolbar_delete').setDisabled(false);
                  Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(false);

               },

               rowdeselect: function() {

                  Ext.getCmp('btn_toolbar_edit').setDisabled(true);
                  Ext.getCmp('btn_toolbar_delete').setDisabled(true);
                  Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);

               }

            }//listeners

         }),

         columns: [
            //checkBoxSelMod,
            {header: 'ID', width: 10, dataIndex: 'id', sortable: false, hidden: true},
            {header: 'First Name', width: 80, dataIndex: 'first_name', sortable: true, renderer: rowRenderer},
            {header: 'Last Name', width: 80, dataIndex: 'last_name', sortable: true, renderer: rowRenderer},
            {header: 'Organization', width: 80, dataIndex: 'organization', sortable: true, renderer: rowRenderer},
            {header: 'Title', width: 80, dataIndex: 'title', sortable: true, renderer: rowRenderer},
            {header: 'E-Mail Address', width: 60, dataIndex: 'email_address', sortable: true, renderer: rowRenderer},
            //{header: 'Field Of Science', width: 50, dataIndex: 'field_of_science', sortable: true, renderer: rowRenderer},
            {header: 'Additional Information', width: 50, dataIndex: 'additional_information', sortable: true, renderer: rowRenderer},
            {header: 'Time Submitted', width: 60, dataIndex: 'time_submitted', sortable: true, renderer: rowRenderer},
            {header: 'Status', width: 80, dataIndex: 'status', sortable: true, renderer: rowRenderer},
            {header: 'Comments', width: 50, dataIndex: 'comments', sortable: false, renderer: rowRenderer}
         ]

      });//self.userGrid

      self.userGrid.on('rowdblclick', function(grid, ri, e) {

         presentUser(grid.getSelectionModel().getSelected().data, self);

      });//self.userGrid.on('rowdblclick', ...

      // ---------------------------------

      var tbNumRequests = new Ext.Toolbar.TextItem({
         html: '...'
      });

      // ---------------------------------

      var reloadAccountRequests = function() {

         document.getElementById('btn_refresh_toolicon').className = 'x-btn x-btn-text-icon';
         self.userGrid.getSelectionModel().clearSelections(true);

         Ext.getCmp('btn_toolbar_edit').setDisabled(true);
         Ext.getCmp('btn_toolbar_delete').setDisabled(true);
         Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);

         self.storeProvider.reload();

      }//reloadAccountRequests

      // ---------------------------------

      Ext.apply(this, {

         title: 'XDMoD Account Requests',
         region: 'center',
         layout: 'fit',

         tbar: {

            items: [

               {

                  xtype: 'button',
                  id: 'btn_refresh_toolicon',
                  iconCls: 'btn_refresh',
                  text: 'Refresh',
                  handler: function(){

                     reloadAccountRequests();

                  }

               },

               {
                  xtype: 'button',
                  id: 'btn_toolbar_new_user_dialog',
                  iconCls: 'btn_init_dialog',
                  text: 'Initialize New User Dialog',
                  disabled: true,
                  handler: function(){

                     adminPanel.initNewUser({
                                             user_data: self.userGrid.getSelectionModel().getSelected().data,
                                             callback: reloadAccountRequests
                                           });

                  }

               },

               {
                  xtype: 'button',
                  id: 'btn_toolbar_edit',
                  iconCls: 'btn_edit',
                  text: 'Edit Comment',
                  disabled: true,
                  handler: function(){

                     presentUser(self.userGrid.getSelectionModel().getSelected().data, self);

                  }

               },

               {

                  xtype: 'button',
                  id: 'btn_toolbar_delete',
                  iconCls: 'btn_delete',
                  text: 'Delete Entry',
                  disabled: true,
                  handler: function(){

                     Ext.Msg.show({

                        maxWidth: 800,
                        minWidth: 400,
                        title: 'Delete Selected Request',
                        msg: 'Are you sure you want to delete this request?<br><b>This action cannot be undone.</b>',
                        buttons: Ext.Msg.YESNO,

                        fn: function(resp) {

                           if (resp == 'yes'){

                              ServerRequest({

                                 url: 'controllers/controller.php',
                                 params: {
                                    'operation' : 'delete_request',
                                    'id': self.userGrid.getSelectionModel().getSelected().data.id
                                 },
                                 method: 'POST',
                                 callback: function(options, success, response) {

                                    if (success) {

                                       var json = Ext.util.JSON.decode(response.responseText);

                                       if(json.success == true) {

                                          self.userGrid.getSelectionModel().clearSelections(true);
                                          Ext.getCmp('btn_toolbar_edit').setDisabled(true);
                                          Ext.getCmp('btn_toolbar_delete').setDisabled(true);
                                          Ext.getCmp('btn_toolbar_new_user_dialog').setDisabled(true);
                                          self.storeProvider.reload();

                                       }
                                       else
                                          alert(json.message);

                                    }//if

                                 }//callback

                              });//ServerRequest

                           }//if (resp == 'yes')

                        },//fn

                        icon: Ext.MessageBox.QUESTION

                     });//Ext.Msg.show

                  }

               },

               {
                  xtype: 'buttongroup',
                  items: [
                     {
                        text: 'Create & Manage Users',
                        scale: 'small',
                        iconCls: 'btn_group',
                        id: 'about_button',
                        handler: function () {
                           adminPanel.showPanel({
                              doListReload: false,
                              callback: function () {
                                 current_users.reloadUserList();
                              }
                           });
                        },
                        scope: this
                     }
                  ]
               },

               '->',

               new Ext.Toolbar.TextItem({

                  html: generateLegend([
                     {
                        color: '#000',
                        label: 'Pending'
                     },
                     {
                        color: '#080',
                        label: 'Already Created'
                     }
                  ])

               })

            ]

         },

         bbar: {

            items: [
               tbNumRequests
            ]

         },

         items: [
            self.userGrid
         ]

      });//Ext.apply

      XDMoD.AccountRequests.superclass.initComponent.call(this);

   }//initComponent

})//XDMoD.AccountRequests
