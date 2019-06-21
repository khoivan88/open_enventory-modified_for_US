<?php
/*
Copyright 2006-2018 Felix Rudolphi and Lukas Goossen
open enventory is distributed under the terms of the GNU Affero General Public License, see COPYING for details. You can also find the license under http://www.gnu.org/licenses/agpl.txt

open enventory is a registered trademark of Felix Rudolphi and Lukas Goossen. Usage of the name "open enventory" or the logo requires prior written permission of the trademark holders. 

This file is part of open enventory.

open enventory is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

open enventory is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with open enventory.  If not, see <http://www.gnu.org/licenses/>.
*/
require_once 'lib_global_funcs.php';

pageHeader();

/*
 * get the data and options from the database and use dygraph with some extras
 */

// get data for main picture
if($_REQUEST['image_no'] == 0) {
	list($result)=mysql_select_array(array(
		"table" => "analytical_data_csv", 
		"filter" => "analytical_data_id=".fixNull($_REQUEST["analytical_data_id"]), 
		"dbs" => $db_id, 
		"limit" => 1, 
	));
}

// get data for ms pictures
else {
	list($result)=mysql_select_array(array(
		"table" => "analytical_data_image_gif", 
		"filter" => "analytical_data_id=".fixNull($_REQUEST["analytical_data_id"])." AND image_no=".fixNull($_REQUEST["image_no"]), 
		"dbs" => $db_id, 
		"limit" => 1, 
	));
}
?>

<html>
<head>
<script type="text/javascript"
  src="lib/dygraph-combined-dev.js"></script>
</head>
<body>
<div id="graphdiv"
  style="width:800px; height:600px;"></div>
<script type="text/javascript">
function darkenColor(colorStr) {
    // Defined in dygraph-utils.js
    var color = Dygraph.toRGB_(colorStr);
    color.r = Math.floor((255 + color.r) / 2);
    color.g = Math.floor((255 + color.g) / 2);
    color.b = Math.floor((255 + color.b) / 2);
    return 'rgb(' + color.r + ',' + color.g + ',' + color.b + ')';
  }
function barChartPlotter(e) {
    var ctx = e.drawingContext;
    var points = e.points;
    var y_bottom = e.dygraph.toDomYCoord(0);

    ctx.fillStyle = darkenColor(e.color);

    // Find the minimum separation between x-values.
    // This determines the bar width.
    var min_sep = Infinity;
    for (var i = 1; i < points.length; i++) {
      var sep = points[i].canvasx - points[i - 1].canvasx;
      if (sep < min_sep) min_sep = sep;
    }
    var bar_width = Math.floor(2.0 / 3 * min_sep);

    // Do the actual plotting.
    for (var i = 0; i < points.length; i++) {
      var p = points[i];
      var center_x = p.canvasx;

      ctx.fillRect(center_x - bar_width / 2, p.canvasy,
          bar_width, y_bottom - p.canvasy);

      ctx.strokeRect(center_x - bar_width / 2, p.canvasy,
          bar_width, y_bottom - p.canvasy);
    }
  }
  g = new Dygraph(
    document.getElementById("graphdiv"),
    <?php echo $result["analytical_data_csv"]; ?>
  );
</script>
</body>
</html>