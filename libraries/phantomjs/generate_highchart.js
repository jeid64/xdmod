if (phantom.args.length != 5) {
   console.log('Usage: generate_highchart.js [png|svg] template filename width height');
   phantom.exit(1);
}

var output_format = phantom.args[0];
var address = phantom.args[1];
var output = phantom.args[2];
var width = phantom.args[3];
var height = phantom.args[4];

var page = new WebPage();

page.viewportSize = { width: width, height: height };
page.clipRect = { top: 0, left: 0, width: width, height: height };

page.open(address, function (status) {
   if (status !== 'success') {
      console.log('Unable to load the address, status: ' + status);
      phantom.exit(2);
      return;
   }

   if (output_format === 'png') {
      page.render(output);
      phantom.exit(0);
      return;
   }

   if (output_format === 'svg') {
      console.log(page.evaluate(function () {
         return chart.getSVG();
      }));

      phantom.exit(0);
      return;
   }

   console.log('Unknown format specified: ' + output_format);
   phantom.exit(3);
});//page.open(...

