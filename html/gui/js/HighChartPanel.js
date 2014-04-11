
/*  
* JavaScript Document
* @author Amin Ghadersohi
* @date 2012-Mar-07 (version 1)
*
* @author Ryan Gentner 
* @date 2013-Jun-23 (version 2)
*
* 
* Component for integrating with high charts api
*/

CCR.xdmod.ui.HighChartPanel = function (config)
{
    CCR.xdmod.ui.HighChartPanel.superclass.constructor.call(this, config);
}; // CCR.xdmod.ui.HighChartPanel


Ext.extend(CCR.xdmod.ui.HighChartPanel, Ext.Panel, 
{
	credits: true,
	isEmpty: true,
	initComponent: function ()
    {	
		
		Ext.apply(this,
		{
			layout: 'fit'
		});
		CCR.xdmod.ui.HighChartPanel.superclass.initComponent.apply(this, arguments);
		var THIS = this;
		this.on('render',function()
		{
			var baseChartOptions = 
			{
				chart: 
				{
					renderTo: this.id,
					width: this.width,
					height: this.height,
					animation: true,
					events: {
						
						selection: function(event)
						{
							//alert(event.xAxis[0].min+', '+event.xAxis[0].max);
							 THIS.fireEvent('timeseries_zoom', event);
						}
					}
				},
        		title: {
					text: ''
				},
				loading: {
					labelStyle: {
						top: '45%'
					}
				},
				exporting: {
					enabled: false
				},
				credits: {
					enabled: true
				}
			};

			var chartOptions = this.chartOptions || {};
			
			baseChartOptions.title = chartOptions.title;
			Ext.apply(chartOptions, baseChartOptions);
			
			var chart = new Highcharts.Chart(chartOptions);
			
			this.on('resize', function(t,adjWidth,adjHeight,rawWidth,rawHeight)
			{
				 if ( chart ) chart.setSize(adjWidth, adjHeight);
				 baseChartOptions.chart.width = adjWidth;
				 baseChartOptions.chart.height = adjHeight;
			});
			
						
			if(this.store)
			{
				this.store.on('load',function(t,records,options)
				{
					if(t.getCount() <= 0)
					{
						return;
					}
					if ( chart ) 
					{
						chart.destroy();
						delete chart;
						chart = null;
					}
					chartOptions = t.getAt(0).data;
					chartOptions = this.processOptions(baseChartOptions,chartOptions)
					this.isEmpty = chartOptions.series && chartOptions.series.length === 0;
					
					chart = new Highcharts.Chart(chartOptions);
					if(this.isEmpty)
					{
						var ch_width = chartOptions.chart.width * 0.8;
						var ch_height = chartOptions.chart.height * 0.8;
						
						chart.renderer.image('gui/images/report_thumbnail_no_data.png', 53, 30, ch_width, ch_height).add();  
					}
					
				},this);
			}
			
		},this,{single: true});
		
		this.addEvents("timeseries_zoom");
	},
	processOptions: function(baseChartOptions,chartOptions)
	{
		jQuery.extend(true,chartOptions, baseChartOptions);
		chartOptions.exporting.enabled = this.exporting === true || false;
		chartOptions.credits.enabled = this.credits === true || false;
		function evalFormatters(o)
		{
			for(var name in o)
			{	
				var otype = typeof(o[name]);
				if(otype === 'object')
				{
					evalFormatters(o[name]);
				}
				if(name === 'formatter' || name === 'labelFormatter' || name === 'click') 
				{
					o[name] = new Function(o[name]);
				}
				
			}
		}
		evalFormatters(chartOptions);
		
		
		return chartOptions;
		
	}
});