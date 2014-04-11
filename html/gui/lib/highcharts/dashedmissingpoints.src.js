/* dashedmissingpoints.src.js */
/**
* 3/13/2012 Amin Ghadersohi
*
* Line series with dashed lines between missing points, if connectNulls is true, for Highcharts, beta
*
* License: http://www.highcharts.com/license
*/

//CHANGES WERE PUT INTO HIGHCHART.SRC.JS

(function () { // encapsulate

    // create shortcuts
    var HC = Highcharts,
    addEvent = HC.addEvent,
    defaultOptions = HC.getOptions(),
    defaultPlotOptions = defaultOptions.plotOptions,
    seriesTypes = HC.seriesTypes,
    extend = HC.extend,
    each = HC.each,
	M = HC.M,
	L = HC.L,
    map = HC.map,
    merge = HC.merge,
    math = Math,
    mathRound = math.round;

 
    var Line2Series = Highcharts.extendClass(seriesTypes.line, {
		getSegments: function () {
			var series = this,
				lastNull = -1, 
				lastNonNull = -1,
				segments = [],
				missingSegments = [],
				i,
				points = series.points,
				pointsLength = points.length;
	
			if (pointsLength) { // no action required for []
				
				/*
				// if connect nulls, just remove null points
				if (series.options.connectNulls) {
					i = pointsLength;
					while (i--) {
						if (points[i].y === null) {
							points.splice(i, 1);
						}
					}
					segments = [points];
					
				// else, split on null points
				} else {*/
					var missingSegment = [];
					each(points, function (point, i) {
						if (point.y === null) {
							if (i > lastNull + 1) {
								segments.push(points.slice(lastNull + 1, i));
							}else {
								
							}
							lastNull = i;
						} else if (i === pointsLength - 1) { // last value
							segments.push(points.slice(lastNull + 1, i + 1));
						} else
						{
							lastNonNull = i;
						}
						if (series.options.connectNulls) {
							if(lastNull === i && lastNonNull === i - 1 && lastNonNull > -1)
							{
								missingSegment.push(points[i-1]);
							}
							
							if(((i === lastNonNull && lastNull === i - 1)  || (i === pointsLength - 1 && lastNull !== i)) && missingSegment.length > 0)
							{
								missingSegment.push(points[i]);
								
								missingSegments.push(missingSegment);
								missingSegment = [];
							}
						}
					});
				//}
			}
		
			// register it
			series.segments = segments;
			series.missingSegments = missingSegments;
		},
		/**
		 * Get the graph path
		 */
		getGraphPath: function () {
			var series = this,
				graphPath = [],
				segmentPath,
				singlePoints = []; // used in drawTracker
	
			// Divide into segments and build graph and area paths
			each(series.segments, function (segment) {
				
				segmentPath = series.getSegmentPath(segment);
				
				// add the segment to the graph, or a single point for tracking
				if (segment.length > 1) {
					graphPath = graphPath.concat(segmentPath);
				} else {
					singlePoints.push(segment[0]);
				}
			});
		
			// Record it for use in drawGraph and drawTracker, and return graphPath
			series.singlePoints = singlePoints;
			series.graphPath = graphPath;
		
			return graphPath;
		},
		getMissingGraphPath: function () {
			var series = this,
				segmentPath,
				missingGraphPath = []; // used in drawTracker
	
			// Divide into segments and build graph and area paths
			each(series.missingSegments, function (segment) {
				
				segmentPath = series.getSegmentPath(segment);
			
				// add the segment to the graph, or a single point for tracking
				if (segment.length > 1) {
					missingGraphPath = missingGraphPath.concat(segmentPath);
				} else {
				//	singlePoints.push(segment[0]);
				}
			});
			// Record it for use in drawGraph and drawTracker, and return graphPath
			//series.missingGraphPath = missingGraphPath;
				
			return missingGraphPath;
			
		},		
		/**
		 * Draw the actual graph
		 */
		drawGraph: function () {		
			var options = this.options,
				graph = this.graph,
				missingGraph = this.missingGraph,
				group = this.group,
				color = options.lineColor || this.color,
				lineWidth = options.lineWidth,
				dashStyle =  options.dashStyle,
				attribs,
				graphPath = this.getGraphPath(),
				missingGraphPath = this.getMissingGraphPath();
			//DumperAlert(missingGraphPath);
	
			// draw the graph
			if (graph) {
				stop(graph); // cancel running animations, #459
				graph.animate({ d: graphPath });
	
			} else {
				if (lineWidth) {
					attribs = {
						stroke: color,
						'stroke-width': lineWidth,
						zIndex: 1 // #1069
					};
					if (dashStyle) {
						attribs.dashstyle = dashStyle;
					}
	
					this.graph = this.chart.renderer.path(graphPath)
						.attr(attribs).add(group).shadow(options.shadow);
				}
			}
			
			if (missingGraph) {
				stop(missingGraph); // cancel running animations, #459
				missingGraph.animate({ d: missingGraphPath });
	
			} else {
				if (lineWidth) {
					attribs = {
						stroke: color,
						'stroke-width': lineWidth,
						zIndex: 1 // #1069
					};
					attribs.dashstyle = 'Dot';
					
					this.missingGraph = this.chart.renderer.path(missingGraphPath)
						.attr(attribs).add(group).shadow(options.shadow);
				}
			}
		}

       


    });
    seriesTypes.line = Line2Series;
	
	


})();