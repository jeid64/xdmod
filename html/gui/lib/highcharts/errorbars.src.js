/* errorbars.src.js */
/**
* 3/12/2012 Amin Ghadersohi
*
* Error Bar module for Highcharts, beta
*
* License: http://www.highcharts.com/license
*/


(function (Highcharts, UNDEFINED) {

    // create shortcuts
    var HC = Highcharts,
    addEvent = HC.addEvent,
    defaultOptions = HC.getOptions(),
    defaultPlotOptions = defaultOptions.plotOptions,
    seriesTypes = HC.seriesTypes,
    extend = HC.extend,
    each = HC.each,
    map = HC.map,
    merge = HC.merge, 
    math = Math,
    mathRound = math.round,
	isString = HC.isString;


    /*****************************************************************************
    * Start ErrorBar series code *
    *****************************************************************************/

    // 1 - Set default options
    defaultPlotOptions.ErrorBar = merge(defaultPlotOptions.column, {
        animation: false,
        lineWidth: 2,
        states: {
            hover: {
                //brightness: 0.1,
                lineWidth: 3
            }
        }
    });

    // 2- Create the ErrorBarPoint object
    var ErrorBarPoint = Highcharts.extendClass(Highcharts.Point, {
        /**
        * Apply the options containing the x and ErrorBar data and possible some extra properties.
        * This is called on point init or from point.update. Extends base Point by adding
        * multiple y-like values.
        *
        * @param {Object} options
        */
        applyOptions: function (options) {
            var point = this,
            series = point.series,
            n,
            i = 0;


            // object input for example:
            // { x: Date(2010, 0, 1), bottom: 0.1, top: 0.2  }
             if (typeof options == 'object' && typeof options.length != 'number') {

                // copy options directly to point
                extend(point, options);

                point.options = options;
            }

            // array
            else if (options.length) {
                // with leading x value
                if (options.length == 3) {
                    if (typeof options[0] == 'string') {
                        point.name = options[0];
                    } else if (typeof options[0] == 'number') {
                        point.x = options[0];
                    }
                    i++;
                }
                point.bottom = options[i++];
                point.top = options[i++];
            }

            /*
            * If no x is set by now, get auto incremented value. All points must have an
            * x value, however the y value can be null to create a gap in the series
            */
			
		 //  point.high = max(point.open, point.close);
		 //  point.low = max(point.open, point.close);
           point.y = point.bottom + ((point.top - point.bottom)/2);
            if (point.x === undefined) {
                point.x = series.autoIncrement();
            }

        },

        /**
        * A specific ErrorBar tooltip formatter
        */
		
        tooltipFormatter: function (pointFormat)
	    {
            var point = this,
            series = point.series;
			if(point.bottom != null && point.top != null )	
			{
           		return ['<span style="color:' + series.color + ';font-weight:bold">', (point.name || series.name), '</span> ','<b>+/-: ', HC.numberFormat((point.top-point.bottom)/2,series.tooltipOptions), '<b\/>', '<br\/>' ].join('') ;  
			}
			else
			{
				return null;
			}
	     }

    });

    // 3 - Create the ErrorBarSeries object
    var ErrorBarSeries = Highcharts.extendClass(seriesTypes.column, {
        type: 'ErrorBar',
        pointClass: ErrorBarPoint,

        pointAttrToOptions: { // mapping between SVG attributes and the corresponding options
            stroke: 'color',
            'stroke-width': 'lineWidth'
        },


        /**
        * Translate data points from raw values x and y to plotX and plotY
        */
        translate: function () {
            var chart = this.chart,
                series = this,
                categories = series.xAxis.categories,
                yAxis = series.yAxis,
                stack = yAxis.stacks[series.type];

            seriesTypes.column.prototype.translate.apply(series);

            // do the translation
            each(this.data, function (point) {
                // the graphics
                 point.plotOpen = yAxis.translate(point.bottom, 0, 1);
                 point.plotClose = yAxis.translate(point.top, 0, 1);

            });
        },

        /**
        * Draw the data points
        */
        drawPoints: function () {
		
            var series = this, //state = series.state,
            //layer = series.stateLayers[state],
                seriesOptions = series.options,
                seriesStateAttr = series.stateAttr,
                data = series.data,
                chart = series.chart,
                pointAttr,
                pointOpen,
                pointClose,
                crispCorr,
                halfWidth,
                path,
                graphic,
                crispX;


            each(data, function (point) {
                graphic = point.graphic;
                if (point.plotY !== undefined &&
                    point.plotX >= 0 && point.plotX <= chart.plotSizeX &&
                    point.plotY >= 0 && point.plotY <= chart.plotSizeY) {

                    pointAttr = point.pointAttr[point.selected ? 'selected' : ''];

                    // crisp vector coordinates
                    crispCorr = (pointAttr['stroke-width'] % 2)  / 2.0 ;
                    crispX = point.plotX + crispCorr;
                    plotOpen = point.plotOpen+ pointAttr['stroke-width'] /2   ;
                    plotClose = point.plotClose;
                    halfWidth = Math.min(chart.plotSizeX/90.0, mathRound( point.pointWidth / 5));

					if(point.bottom != null && point.top != null )
					{
						path = [
						'M',
						crispX, plotOpen,
						'L',
						crispX, plotClose,
						'M',
						crispX + halfWidth, plotOpen,
						'L',
						crispX - halfWidth, plotOpen,
						'M',
						crispX + halfWidth, plotClose,
						'L',
						crispX - halfWidth , plotClose,
						'M',
						0,0,
						'L',
						0,0,
						'Z'
						];
						
					}
					else
					{
						path = [];
					}

                    if (graphic) {
                        graphic.animate({
                            d: path
                        });
                    } else {
                        point.graphic = chart.renderer.path(path)
                                .attr(pointAttr)
                                .add(series.group);
                    }

                } else if (graphic) {
                    point.graphic = graphic.destroy();
                }

            });

        }


    });
    seriesTypes.ErrorBar = ErrorBarSeries;
    /*****************************************************************************
    * End ErrorBar series code *
    *****************************************************************************/
}(Highcharts));