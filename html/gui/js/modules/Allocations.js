/* 
 * JavaScript Document
 * @author Amin Ghadersohi
 * @author Ryan Gentner
 * @date 2013-June-03
 *
 * This class contains functionality for the Allocations tab.
 */

ListSorter = {

   doSort: function(sort_icon_id, alt_sort_icon_id, sort_by, container_id) {
    
      var breakdown_content = document.getElementById(container_id + '-user_breakdown');
      var table = breakdown_content.childNodes[0];

      var content = [];
      var pi_entry = undefined;
      
      for (var i = 1; i < table.rows.length; i++) {
      
         if (table.rows[i].cells[0].innerHTML.indexOf('(PI)') != -1 || table.rows[i].cells[1].innerHTML.indexOf('(PI)') != -1) {
            pi_entry = {name: table.rows[i].cells[0].innerHTML, usage: table.rows[i].cells[1].innerHTML};
         }
         else
            content.push({name: table.rows[i].cells[0].innerHTML, usage: table.rows[i].cells[1].innerHTML});
      
      }//for
      
      // --------------------------------------
      
      var sort_icon = document.getElementById(sort_icon_id);
      
      var oldsortref = sort_icon.getAttribute('sortref');
      var newsortref = (oldsortref == 'asc') ? 'desc' : 'asc';
      
      content.sort(function(a, b){     

         if (sort_by == 'name') {

            if (newsortref == 'asc') 
               return a[sort_by].localeCompare(b[sort_by]);
            if (newsortref == 'desc') 
               return b[sort_by].localeCompare(a[sort_by]);

         }//if (sort_by == 'name')

         if (sort_by == 'usage') {

            a = parseInt(a.usage.replace(/,/g, ''));
            b = parseInt(b.usage.replace(/,/g, ''));

            if (newsortref == 'asc') 
               return a - b;
            if (newsortref == 'desc') 
               return b - a;

         }//if (sort_by == 'usage')

      });//content.sort

      if (pi_entry != undefined) {
         content.unshift(pi_entry);
      }
      
      sort_icon.setAttribute('sortref', newsortref);

      for (var i = 1; i < table.rows.length; i++) {
         table.rows[i].cells[0].innerHTML = content[i - 1].name;
         table.rows[i].cells[1].innerHTML = content[i - 1].usage;
      }

      sort_icon.src = sort_icon.src.replace('sort_' + oldsortref, 'sort_' + newsortref);

      // Hide sort arrow of other column
      document.getElementById(alt_sort_icon_id).style.visibility = 'hidden';

      // Ensure that the sorted column has its sort arrow visible
      sort_icon.style.visibility = 'visible';

   }//doSort

};//ListSorter

// ==================================================================================

AllocationModule = {

   getTrackingConfig: function(module_id, record_index) {
   
      var corresponding_record = Ext.getCmp(module_id).fetchRecord(record_index);
   
      return {
      
         project_start_date: corresponding_record.data.start,
         project_end_date: corresponding_record.data.end,
         project_title: truncateText(corresponding_record.data.project_title, 50),
         project_charge_number: corresponding_record.data.charge_number,
         project_status: corresponding_record.data.status     

      };
   
   },//getTrackingConfig

   // ----------------------------------------------------------------------
      
   showResourceBreakdown: function(cfg, content_target, user, module_id, record_index) {
 
      var trackingConfig = AllocationModule.getTrackingConfig(module_id, record_index);
          trackingConfig.user_clicked = user;
      
      XDMoD.TrackEvent('Allocations', 'Clicked on user to acquire resource breakdown', Ext.encode(trackingConfig));
      
      title_target = document.getElementById(content_target + '-res_title');
      alt_title_target = document.getElementById(content_target + '-alt_res_title');

      summary_target = document.getElementById(content_target + '-res_summary');
      breakdown_target = document.getElementById(content_target + '-res_breakdown');

      arrow_target = document.getElementById(content_target + '-arrow');

      var markup = '<table border=0 cellspacing=0>' + 
                   '<tr class="allocation_breakdown_header">' + 
                   '<td class="allocation_breakdown_first_column" width=210><b>Resource</b></td>' + 
                   '<td width=100 align=right><b>Usage (SU)</b></td>' + 
                   '<td width=250></td>' + 
                   '</tr>';

      var resource_set = {};

      for (i = 0; i < cfg.length; i++) {

         var entry_class = 'allocation_resource_listing_entry_spacing';
         if (i == 0) entry_class += ' allocation_header_first_entry_gap';

         var qtip = '<b><div style=&quot;width: 220px&quot;>' + cfg[i].name + '</div></b>' + 
                    cfg[i].type + ' (<span style=&quot;color: #888&quot;>' + cfg[i].timeframe  + '</span>)<br />';

         markup += '<tr>' + 
                   '<td class="' + entry_class + '">' + cfg[i].name + '<br />' + 

                       '<div style="margin-top: 3px"><div ext:qtip="' + qtip + '" style="float: left; width: 12px; height: 12px; background-position: -2px -2px; background-image: url(\'gui/images/info.png\')"></div><span style="margin-left: 4px; margin-top: 4px; color: #888">' + cfg[i].timeframe + '</span></div></td>' +        

                        '<td align=right class="' + entry_class + '">' + cfg[i].used + '</td>' + 
                        '<td class="' + entry_class + '"></td>' + 
                   '</tr>';

         resource_set[cfg[i].name] = 1;

      }

      var pluralizer_allocations = (cfg.length == 1) ? '' : 's';
      var pluralizer_resources = (Object.keys(resource_set).length == 1) ? '' : 's';

      alt_title_target.innerHTML = 'Resource usage for <b>' + user + '</b><br />' + 
                                   '<span style="color: #888">' + cfg.length + ' allocation' + pluralizer_allocations + ' across ' + Object.keys(resource_set).length + ' resource' + pluralizer_resources + '</b></span>';

      markup += "<table>";

      breakdown_target.innerHTML = markup;
      breakdown_target.style.display = '';

      summary_target.style.display = 'none';

      alt_title_target.style.display = '';
      title_target.style.display = 'none';

      document.getElementById(content_target + '-return_link').innerHTML = '<a href="javascript:void(0)" onClick="AllocationModule.showResourceSummary(\'' + [content_target, module_id, record_index].join('\', \'') + '\')">Return to<br />resource summary</a>';

      arrow_target.src = 'gui/images/alloc_breakdown_arrow_r.png';
      arrow_target.style.visibility = 'visible';

      document.getElementById(content_target + '-res_container').className = 'allocation_resource_container allocation_breakdown_view';

   },//showResourceBreakdown

   // ----------------------------------------------------------------------

   showResourceSummary: function(content_target, module_id, record_index) {

      var trackingConfig = AllocationModule.getTrackingConfig(module_id, record_index);      
      XDMoD.TrackEvent('Allocations', 'Clicked on Return to resource summary link', Ext.encode(trackingConfig));
      
      document.getElementById(content_target + '-res_summary').style.display = '';
      document.getElementById(content_target + '-res_breakdown').style.display = 'none';

      document.getElementById(content_target + '-res_title').style.display = '';
      document.getElementById(content_target + '-alt_res_title').style.display = 'none';

      document.getElementById(content_target + '-arrow').style.visibility = 'hidden';
      document.getElementById(content_target + '-return_link').innerHTML = '';

      document.getElementById(content_target + '-res_container').className = 'allocation_resource_container allocation_summary_view';

   },//showResourceSummary

   // ----------------------------------------------------------------------

   showUserBreakdown: function(cfg, content_target, resource, timeframe, transaction_type, module_id, record_index) {

      var trackingConfig = AllocationModule.getTrackingConfig(module_id, record_index);
          trackingConfig.resource_clicked = resource;
          trackingConfig.resource_timeframe = timeframe;
          trackingConfig.resource_transaction_type = transaction_type;       

      XDMoD.TrackEvent('Allocations', 'Clicked on resource to acquire user breakdown', Ext.encode(trackingConfig));
      
      title_target = document.getElementById(content_target + '-user_title');
      alt_title_target = document.getElementById(content_target + '-alt_user_title');

      summary_target = document.getElementById(content_target + '-user_summary');
      breakdown_target = document.getElementById(content_target + '-user_breakdown');

      arrow_target = document.getElementById(content_target + '-arrow');

      alt_title_target.innerHTML = 'Users of <b>' + resource + ' (' + cfg.length + ')</b><br /><span style="color: #888">From <b>' + timeframe + '</b> (' + transaction_type + ')</span>';

      var refs = {
         name_sort_icon: Ext.id(),
         usage_sort_icon: Ext.id()
      };

      var name_sort_call = "ListSorter.doSort('" + [refs.name_sort_icon, refs.usage_sort_icon, 'name', content_target].join("','") + "')";
      var usage_sort_call = "ListSorter.doSort('" + [refs.usage_sort_icon, refs.name_sort_icon, 'usage', content_target].join("','") + "')";

      var markup = '<table border=0 width=200 cellspacing=0>' +
                   '<tr class="allocation_breakdown_header">' + 
                     '<td width=33 class="allocation_breakdown_first_column" ><span onClick="' + name_sort_call + '">Name</span></td>' + 
                     '<td width=107 style="padding-top: 4px"><img style="visibility: hidden" id="' + refs.name_sort_icon + '" sortref="desc" src="gui/images/sort_desc.gif" onClick="' + name_sort_call + '"></td>' + 
                     '<td width=78 align=right><span onClick="' + usage_sort_call + '">Usage (SU)</span></td>' + 
                     '<td width=30 style="padding-top: 4px"><img id="' + refs.usage_sort_icon + '" sortref="desc" src="gui/images/sort_desc.gif" onClick="' + usage_sort_call + '"></td>' + 
                   '</tr>';
      
      var tableContent = [];
      var entry_class = 'allocation_user_listing_entry_spacing';
      
      for (i = 0; i < cfg.length; i++) {

         var wrap = (cfg[i].is_pi === true) ? 
                    {start: '<b style="color: #000">', end: ' (PI)</b>'} : {start: '', end: ''};

         var entry = '<tr>' + 
                     '<td colspan=2 class="' + entry_class + '">' + wrap.start + cfg[i].name + wrap.end + '</td>' + 
                     '<td align=right class="' + entry_class + '">' + cfg[i].used + '</td>' +
                     '<td></td>' + 
                     '</tr>';
                  
         // Ensure that the PI is always at the top of the list (regardless of how he/she is falls within the sorting)
         
         if (cfg[i].is_pi === true)
            tableContent.unshift(entry);
         else
            tableContent.push(entry);

      }//for (i = 0; i < cfg.length; i++

      tableContent[0] = tableContent[0].replace(RegExp(entry_class, 'g'), entry_class + ' allocation_header_first_entry_gap');
      
      markup += tableContent.join('') + "<table>";

      breakdown_target.innerHTML = markup;
      breakdown_target.style.display = '';

      summary_target.style.display = 'none';

      alt_title_target.style.display = '';
      title_target.style.display = 'none';

      document.getElementById(content_target + '-return_link').innerHTML = '<a href="javascript:void(0)" onClick="AllocationModule.showUserSummary(\'' + [content_target, module_id, record_index].join('\', \'') + '\')">Return to<br />user summary</a>';

      arrow_target.src = 'gui/images/alloc_breakdown_arrow_l.png';
      arrow_target.style.visibility = 'visible';

      document.getElementById(content_target + '-user_container').className = 'allocation_user_container allocation_breakdown_view';

   },//showUserBreakdown

   // ----------------------------------------------------------------------

   showUserSummary: function(content_target, module_id, record_index) {

      var trackingConfig = AllocationModule.getTrackingConfig(module_id, record_index);
      XDMoD.TrackEvent('Allocations', 'Clicked on Return to user summary link', Ext.encode(trackingConfig));
      
      document.getElementById(content_target + '-user_summary').style.display = '';
      document.getElementById(content_target + '-user_breakdown').style.display = 'none';

      document.getElementById(content_target + '-user_title').style.display = '';
      document.getElementById(content_target + '-alt_user_title').style.display = 'none';

      document.getElementById(content_target + '-arrow').style.visibility = 'hidden';
      document.getElementById(content_target + '-return_link').innerHTML = '';

      document.getElementById(content_target + '-user_container').className = 'allocation_user_container allocation_summary_view';

   }//showUserSummary

};//AllocationModule

// ==================================================================================

XDMoD.Module.Allocations = function(config) {

   XDMoD.Module.Allocations.superclass.constructor.call(this, config);

}//XDMoD.Module.Allocations

// ==================================================================================

Ext.extend(XDMoD.Module.Allocations, XDMoD.PortalModule, {

   module_id: 'allocations',

   chartDataURL: 'controllers/ui_data/allocations.php',
   chartDataRoot: 'allocations',

   chartDataFields: [

      'description',
      'resource_name',
      'charge_number',
      'project_title',
      'base',
      'base_formatted',
      'remaining',
      'remaining_formatted',
      'status',
      'start',
      'end',
      'users',
      'resources'

   ],

   initComponent: function(){

      var self = this;
      
      var active_person_id = CCR.xdmod.ui.mappedPID;

      var span_allocation_type_id = Ext.id();
      var span_user_id = Ext.id();
      var span_empty_message_suffix = Ext.id();

      var cached_response = {};

      var checkboxConfig = {
         sPI: false,
         isNotPI: false
      };

      var assistPanel = new Ext.Panel({

         layout: 'fit',
         flex: 1,
         margins: '2 0 2 2',
         containerScroll: true,
         border: false,
         region: 'center',
         
         bodyStyle: {
            'overflow-y': 'auto'
         },

         html: '<div class="x-grid-empty">' + 

                '<div class="assist_panel_section">' + 
                '1. Select a <b>User / PI</b> from the drop-down menu above.  This can be done by either expanding the menu and browsing the list, ' + 
                'or by typing the name of a user into the field itself (matches will be presented to you for selecting as you type).' + 
                '</div><br />' + 

                '<div class="assist_panel_section">' + 
                '2. Check the respective boxes below the drop-down menu to view allocations based on the user\'s membership type (<b>PI</b> or <b>not a PI</b>).' +
                '</div><br />' + 

                '<i>Note that as you interact with the above section, the<br />right-hand section will update automatically.</i><br /><br />' + 

                '<div class="assist_panel_section">' + 
                '3. At the top of the right-hand section, you can choose to view only active or expired allocations (or both) by checking the respective boxes. ' +
                '<img src="gui/images/allocation_assist_status.png"> The number of allocations available per status are referenced in parentheses.' + 
                '</div><br />' +

                '<div class="assist_panel_section">' + 
                '4. For each allocation listed in the grid, you can view more detail by either double-clicking on the entry or clicking on the <b>+</b> icon to the left. ' + 
                '<img src="gui/images/allocation_assist_expand.png"><br /> As a result, you will be able to view users and resources associated with the allocation.' +
                '</div><br />' + 

                '<div class="assist_panel_section">' + 
                '5. In the <b>Associated Users</b> list, the first user listed (in boldface) is the PI of the allocation.  Each user\'s respective usage will be ' + 
                'listed to the right of his/her name.  With the exception of the PI, the users are sorted based on their SU consumption. Clicking on a <b>Usage (SU)</b> value (greater than 0) will present a breakdown of all resources utilized by that user.' + 
                '</div><br />' + 

                '<div class="assist_panel_section">' + 
                '6. In the <b>Associated Resources</b> list, clicking on the name of a resource (more specifically, an allocation) will present a breakdown of all ' + 
                'users who consumed SUs on that resource within the given timeframe.  By default, this list is sorted based on SU consumption.' + 
                '</div><br />' + 

                '</div>'

      });//assistPanel

      // =====================================================================================

      var chartStore = new Ext.data.JsonStore({

         autoDestroy: false,
         root: this.chartDataRoot,
         totalProperty: 'totalCount',
         fields: this.chartDataFields,
         proxy: new Ext.data.HttpProxy({
            method: 'GET',
            url: this.chartDataURL
         })

      });//chartStore

      chartStore.on('load', function (chartStore) {

         if (chartStore.reader.jsonData.totalCount == 0) {

            document.getElementById(span_user_id).innerHTML = cached_response.user;
            document.getElementById(span_allocation_type_id).innerHTML = chartStore.reader.jsonData.filter;

            var suffix = '';

            if (checkboxConfig.isPI !== checkboxConfig.isNotPI) {
               if (checkboxConfig.isPI === true) suffix = 'as a PI';
               if (checkboxConfig.isNotPI === true) suffix = 'as a member (non-PI)';
            }

            document.getElementById(span_empty_message_suffix).innerHTML = suffix;

         }

         var viewer = CCR.xdmod.ui.Viewer.getViewer();
         if(viewer.el) viewer.el.unmask();

      });//chartStore.on('load',...

      self.fetchRecord = function(record_id){
      
         return chartStore.getById(record_id);
      
      };//self.fetchRecord

      // =====================================================================================
            
      var generateProgbar = function(cfg) {

         var max_value = Math.max(cfg.remaining, cfg.base);
         var normalized_value = (cfg.remaining < 0) ? 0 : cfg.remaining;

         var progbar_width = 140;
         var progbar_height = 10;

         var prog = progbar_width * (normalized_value / max_value);

         if (max_value == 0) prog = 0;

         var colors = {
            active: {border: '#119911', fill: '#99ff99'},
            expired: {border: '#991111', fill: '#ffaaaa'}
         };

         var cBorder = colors[cfg.status].border;
         var cFill = colors[cfg.status].fill;

         return '<div style="background-color: #fff; border: 1px solid ' + cBorder + '; width: ' + progbar_width + 'px; height: ' + progbar_height + 'px">' + 
                '<div style="background-color: ' + cFill + '; width: ' + prog + 'px; height: ' + progbar_height + 'px">&nbsp;</div>' +
                '</div>';

      }//generateProgbar

      // =====================================================================================

      var title_renderer = function(val, metaData, record, rowIndex, colIndex, store){

         metaData.attr = 'ext:qtip="' + val + '"';

         var entryData = store.getAt(rowIndex).data;

         var titleColor = '#000';

         if (entryData.status == 'active') titleColor = '#070';
         if (entryData.status == 'expired') titleColor = '#d74a61';

         return '<span style="color: ' + titleColor + '">' + val + '</span><br /><span style="color: #888">' + entryData.description + '</span>';

      }//title_renderer

      // =====================================================================================

      var progbar_renderer = function(val, metaData, record, rowIndex, colIndex, store){

         var entryData = store.getAt(rowIndex).data;

         return generateProgbar(entryData);

      }//progbar_renderer

      // =====================================================================================

      var expander = new Ext.ux.grid.RowExpander({

         tpl : new Ext.Template(''),

         getBodyContent: function(record, index) {
            
            var data = record.data;
            
            var resourceZonePrefix = Ext.id();

            var users = [];    

            for (var i = 0; i < data.users.length; i++) {

               var wrap = (data.users[i].is_pi === true) ? 
                  {start: '<b style="color: #000">', end: ' (PI)</b>'} : {start: '', end: ''};

               var usageWrap = (data.users[i].total_formatted !== "0.00") ?
                           {
                              start: '<a href="javascript:void(0)" onClick=\'AllocationModule.showResourceBreakdown(' + 
                                     Ext.encode(data.users[i].resources) + ', "' + [resourceZonePrefix, data.users[i].name, self.id, record.id].join('", "') + '")\'>', 
                              end: '</a>'
                           } : 
                           {
                              start: '', 
                              end: ''
                           };

               var top_padding = (i == 0) ? 5 : 0;

               var entry_class = 'allocation_user_listing_entry_spacing';
               if (i == 0) entry_class += ' allocation_header_first_entry_gap';

               users.push(
                  '<td class="' + entry_class + '">' + wrap.start + data.users[i].name + wrap.end + '</td>' +
                  '<td align=right class="' + entry_class + '">' + usageWrap.start + data.users[i].total_formatted + usageWrap.end + '</td>'
               );  

            }//for (var i = 0; i < data.users.length; i++)

            var resources = [];

            for (var i = 0; i < data.resources.length; i++) {
               
               var usageWrap = (data.resources[i].base_formatted !== data.resources[i].remaining_formatted) ?
                           {
                              start: '<a href="javascript:void(0)" onClick=\'AllocationModule.showUserBreakdown(' + 
                                     Ext.encode(data.resources[i].users) + ', "' + [resourceZonePrefix, data.resources[i].resource_name, data.resources[i].timeframe, data.resources[i].type, self.id, record.id].join('", "') + '")\'>', 
                              end: '</a>'
                           } : 
                           {
                              start: '', 
                              end: ''
                           };

               var entry_class = 'allocation_resource_listing_entry_spacing';

               if (i == 0) entry_class += ' allocation_header_first_entry_gap';

                     var qtip = '<b><div style=&quot;width: 220px&quot;>' + data.resources[i].resource_name + '</div></b>' + 
                                data.resources[i].type + ' (<span style=&quot;color: #888&quot;>' + data.resources[i].timeframe + '</span>)<br />';

                     resources.push(

                        '<td class="' + entry_class + '" style="margin-bottom: 4px">' + usageWrap.start + data.resources[i].resource_name + usageWrap.end + '<br />' + 

                        '<div style="margin-top: 3px"><div ext:qtip="' + qtip + '" style="float: left; width: 12px; height: 12px; background-position: -2px -2px; background-image: url(\'gui/images/info.png\')"></div><span style="margin-left: 4px; margin-top: 4px; color: #888">' + data.resources[i].timeframe + '</span></div></td>' +        	              

                        '<td class="' + entry_class + '" align=right>' + data.resources[i].base_formatted + '</td>' + 

                        '<td class="' + entry_class + '" align=right>' + data.resources[i].remaining_formatted + '</td>' + 

                        '<td class="' + entry_class + '" style="padding-left: 10px !important">' + generateProgbar({
                           base: data.resources[i].base, 
                           remaining: data.resources[i].remaining,
                           status: data.status   
                        }) + '</td>'

                     );   

            }//for (var i = 0; i < data.resources.length; i++)

            var content = '<div style="overflow-x: auto; border: 0px solid #000"><table border=0>' +

            '<tr>' +

               '<td><span id="' + resourceZonePrefix + '-user_title"><b>Associated Users (' + data.users.length + ')</b><br />' +
               '<i><span style="color: #888">Click on a usage (in blue) for resource breakdown</span></i></span><span style="display: none" id="' + resourceZonePrefix + '-alt_user_title"></span></td>' +

               '<td width=100></td>' +

               '<td><span id="' + resourceZonePrefix + '-res_title"><b>Associated Resources (' + data.resources.length + ')</b><br />' + 
               '<i><span style="color: #888">Click on a resource name (in blue) to view user breakdown</span></i></span><span style="display: none" id="' + resourceZonePrefix + '-alt_res_title"></span></td>' +

            '</tr>' +

            '<tr>' +

               '<td><div id="' + resourceZonePrefix + '-user_container" class="allocation_user_container allocation_summary_view">' + 

                  '<div id="' + resourceZonePrefix + '-user_summary">' +
                  '<table border=0 width=200 cellspacing=0>' +
                  '<tr class="allocation_breakdown_header">' + 
                     '<td width=140 class="allocation_breakdown_first_column">Name</td>' +
                     '<td width=80 align=right>Usage (SU)</td><td width=30></td>' + 
                  '</tr>' +
                  '<tr>' + users.join('</tr><tr>') + '</tr>' + 
                  '</table>' +
                  '</div>' +

                  '<div style="display: none" id="' + resourceZonePrefix + '-user_breakdown">' +
                  '</div>' + 

               '</div></td>' +

               '<td style="vertical-align: middle !important" align=center><span id="' + resourceZonePrefix + '-return_link"></span></br >' + 
               '<img id="' + resourceZonePrefix +  '-arrow" style="visibility: hidden" src="gui/images/alloc_breakdown_arrow_r.png"></td>' + 

               '<td width=550>' + 
               '<div id="' + resourceZonePrefix + '-res_container" class="allocation_resource_container allocation_summary_view">' + 

                  '<div id="' + resourceZonePrefix + '-res_summary">' +
                  '<table border=0 width=490 cellspacing=0>' +
                  '<tr class="allocation_breakdown_header">' + 
                     '<td style="width: 167px" class="allocation_breakdown_first_column">Resource</td>' + 
                     '<td style="width: 95px" align=right>Base (SU)</td>' + 
                     '<td style="width: 106px" align=right>Remaining (SU)</td><td width=190></td>' + 
                  '</tr>' +
                  '<tr>' + resources.join('</tr><tr>') + '</tr>' + 
                  '</table>' +
                  '</div>' + 

                  '<div style="display: none" id="' + resourceZonePrefix + '-res_breakdown">' +
                  '</div>' + 

               '</div>' + 
               '</td>' +

            '</tr>' +
            '</table></div>';

            return content;

         }//getBodyContent

      });//expander         
      
      expander.on('expand', function(obj, record){
      
         var trackingConfig = {
            charge_number: record.data.charge_number,
            project_title: truncateText(record.data.project_title, 50),
            status: record.data.status
         };
         
         XDMoD.TrackEvent('Allocations', 'Expanded project entry', Ext.encode(trackingConfig));
      
      });//expander.on('expand', ...

      expander.on('collapse', function(obj, record){
      
         var trackingConfig = {
            charge_number: record.data.charge_number,
            project_title: truncateText(record.data.project_title, 50),
            status: record.data.status
         };
         
         XDMoD.TrackEvent('Allocations', 'Collapsed project entry', Ext.encode(trackingConfig));
      
      });//expander.on('collapse', ...

      // =====================================================================================

      var userColumns =  [

         expander,
         {header: "Project Title / Description", width: 298, sortable: true, dataIndex: 'project_title', renderer: title_renderer},
         {header: "Charge Number", width: 100, sortable: true, dataIndex: 'charge_number'},
         {header: "Start Date", width: 70, sortable: true, dataIndex: 'start'},
         {header: "End Date", width: 70, sortable: true, dataIndex: 'end'},
         {header: "Base (SU)", width: 100, sortable: true, dataIndex: 'base_formatted', align: 'right'},
         {header: "Remaining (SU)", width: 110, sortable: true, dataIndex: 'remaining_formatted', align: 'right'},
         {header: "Remaining Ratio", id: 'ratio', sortable: false, renderer:progbar_renderer}

      ];//userColumns

      // =====================================================================================

      var allocationGrid = new Ext.grid.GridPanel({

         autoScroll: true,
         store: chartStore,
         columns: userColumns, 
         autoExpandColumn: 'ratio',
         border: false,
         plugins: expander,
         enableColumnResize:false,
         viewConfig: {

            emptyText: 'No <b><span id="' + span_allocation_type_id + 
                       '"></span></b> allocations are associated with user <b><span id="' + span_user_id + '"></span></b> ' +
                       '<span id="' + span_empty_message_suffix + '"></span>'

         }

      });//allocationGrid

      // =====================================================================================	

      var viewPanel = new Ext.Panel({

         layout: 'fit',
         region: 'center',
         items: allocationGrid,
         border: false

      });//viewPanel

      // =====================================================================================	

      var cbLabelActiveCount = Ext.id();

      var cbActive = new Ext.form.Checkbox({

         boxLabel: 'Show Active (<span id="' + cbLabelActiveCount + '">0</span>)',
         ctCls: 'active_allocation_v',
         checked: true,
         handler: function() { 
         
            XDMoD.TrackEvent('Allocations', 'Toggled Active Checkbox', 'Show Active (' + (cbActive.checked ? 'checked' : 'unchecked') + ')');  
            filterAllocations(cbActive, cbExpired); 
         
         }

      });//cbActive

      // =====================================================================================	

      var cbLabelExpiredCount = Ext.id();

      var cbExpired = new Ext.form.Checkbox({

         boxLabel: 'Show Expired (<span id="' + cbLabelExpiredCount + '">0</span>)',
         ctCls: 'expired_allocation_v',
         checked: true,
         handler: function() { 
         
            XDMoD.TrackEvent('Allocations', 'Toggled Inactive Checkbox', 'Show Expired (' + (cbExpired.checked ? 'checked' : 'unchecked') + ')');   
            filterAllocations(cbExpired, cbActive); 
         
         }

      });//cbExpired

      // =====================================================================================	

      var myCheckboxGroup = new Ext.form.CheckboxGroup({

         itemCls: 'x-check-group-alt',
         columns: 2,

         style: {
            width: '300px'
         },

         items: [
            cbActive,
            cbExpired
         ]

      });//myCheckboxGroup

      // =====================================================================================	

      var imagesTb = new Ext.Toolbar({

         items: [
            myCheckboxGroup
         ]

      });//imagesTb

      // =====================================================================================	

      var images = new Ext.Panel({

         title: 'Viewer',
         region: 'center',
         margins: '2 2 2 0',
         layout: 'border',
         split: true,
         tbar: imagesTb,
         items: [
            viewPanel
         ]

      });//images

      // =====================================================================================	

      var cmbUserMappingViewer = new CCR.xdmod.ui.TGUserDropDown({
      
         id: 'user_list_'+this.id,
         fieldLabel: 'User / PI',
         emptyText: 'Specify an XSEDE user'
         
      });//cmbUserMappingViewer
 
      cmbUserMappingViewer.on('afterrender', function() {
         cmbUserMappingViewer.initializeWithValue(CCR.xdmod.ui.mappedPID, CCR.xdmod.ui.mappedPName);
      });

      cmbUserMappingViewer.on('select', function() { checkSelections(); });

      // =====================================================================================	

      var checkSelections = function(cb1, cb2) {

         if (cb1 !== undefined && cb2 !== undefined && cb1.getValue() === false && cb2.getValue() === false)
            cb2.setValue(true);

         var loaderParams = {operation: 'summary'};

         if (
         
            (checkboxConfig.isPI !== cbShowAllocationsAsPI.getValue()) ||
            (checkboxConfig.isNotPI !== cbShowAllocationsAsMember.getValue()) ||
            (cmbUserMappingViewer.getValue() !== active_person_id)

            ){

               images.el.mask('Updating allocation listing.  Please wait.');

               checkboxConfig.isPI = cbShowAllocationsAsPI.getValue();
               checkboxConfig.isNotPI = cbShowAllocationsAsMember.getValue();

               active_person_id = cmbUserMappingViewer.getValue();

               loaderParams.user_ref = active_person_id;

               if (cbShowAllocationsAsPI.getValue() !== cbShowAllocationsAsMember.getValue()) {

                  if (cbShowAllocationsAsPI.getValue() == true)
                     loaderParams.pi_mode = true;

                  if (cbShowAllocationsAsMember.getValue() == true)
                     loaderParams.pi_mode = false;

               }

               var trackingData = {
               
                  pi_mode: loaderParams.pi_mode,
                  user: cmbUserMappingViewer.getRawValue()
                  
               };
               
               XDMoD.TrackEvent('Allocations', 'Allocations module inputs changed', Ext.encode(trackingData));

               var conn = new Ext.data.Connection;
               
               conn.request({

                  url: 'controllers/ui_data/allocations.php', 
                  params: loaderParams,
                  method: 'POST',

                  callback: function(options, success, response) { 

                     images.el.unmask();

                     cached_response = Ext.decode(response.responseText);

                     images.setTitle('Allocations for ' + cached_response.user);	

                     document.getElementById(cbLabelActiveCount).innerHTML = cached_response.active_count;
                     document.getElementById(cbLabelExpiredCount).innerHTML = cached_response.expired_count;

                     filterAllocations();

                  }//callback

               });//conn.request

         }//if (pi checkbox changed || non-pi checkbox changed || user changed)

      }//checkSelections

      // =====================================================================================      

      var filterAllocations = function(cb1, cb2) {

         if (cb1 !== undefined && cb2 !== undefined && cb1.getValue() === false && cb2.getValue() === false)
            cb2.setValue(true);

         var presentable_allocations = cached_response.active_allocations.concat(cached_response.expired_allocations);
         var allocation_type = '';

         if (cbActive.getValue() !== cbExpired.getValue()) {

            if (cbActive.getValue() === true)  { presentable_allocations = cached_response.active_allocations; allocation_type = 'active'; }
            if (cbExpired.getValue() === true) { presentable_allocations = cached_response.expired_allocations; allocation_type = 'expired'; }

         }

         chartStore.loadData({
         
            totalCount: presentable_allocations.length,
            filter: allocation_type,
            allocations: presentable_allocations
            
         });

      }//filterAllocations

      // ===================================================================================== 

      var cbShowAllocationsAsPI = new Ext.form.Checkbox({

         hideLabel: true,
         boxLabel: 'This user <b>is</b> a PI on',
         height: 8,
         checked: true,
         listeners: {
            'check': function(){ checkSelections(cbShowAllocationsAsPI, cbShowAllocationsAsMember); }
         }

      });//cbShowAllocationsAsPI

      // =====================================================================================     
      
      var cbShowAllocationsAsMember = new Ext.form.Checkbox({

         hideLabel: true,
         boxLabel: 'This user is a member of, yet is <b>not a PI</b> on',
         height: 10,
         checked: true,
         listeners: {
            'check': function(){ checkSelections(cbShowAllocationsAsMember, cbShowAllocationsAsPI); }
         }

      });//cbShowAllocationsAsMember

      // =====================================================================================

      var leftPanel = new Ext.Panel({
         
         title: 'Allocations',
         width: 310,
         collapsible: true,
         region: 'west',
         //split: true,
         margins: '2 0 2 2',
         resizable: false,
         border: true,
         layout: 'border',
         plugins: new Ext.ux.collapsedPanelTitlePlugin(),

         items: [

            //North-west panel (User/PI selector, PI checkboxes)
            new Ext.FormPanel({  

               region: 'north',
               labelAlign: 'top',
               layout: 'form',
               height: 130,

               items: [{

                  xtype: 'fieldset',
                  header: false,
                  layout: 'form',
                  hideLabels: false,
                  border: false,
                  
                  defaults: {
                     anchor: '0'
                  },
                  
                  items: [

                     cmbUserMappingViewer,

                     {
                        xtype: 'label', 
                        cls: 'x-form-item',
                        text: 'Show allocations:',
                        style: { 
                           marginBottom: '0px', 
                           paddingBottom: '0px'
                        }
                     },

                     cbShowAllocationsAsPI,
                     cbShowAllocationsAsMember

                  ] 

               }]

            }),

            assistPanel

         ]//items

      });//leftPanel

      // =====================================================================================

      Ext.apply(this, {

         layout: 'border',
         items: [leftPanel, images]

      });

      this.on('afterrender', function() {

         new Ext.util.DelayedTask(function(){
            checkSelections();
         }).delay(300);

      });

      XDMoD.Module.Allocations.superclass.initComponent.apply(this, arguments);

   }//initComponent

});//XDMoD.Module.Allocations