<html>
<head>
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<link rel="stylesheet" href="../install/ChemDoodleWeb.css" type="text/css">
<link rel="stylesheet" href="../install/uis/jquery-ui-1.11.4.css" type="text/css">

<script type="text/javascript" src="../../lib/safe_dom.js"></script>
<script type="text/javascript" src="../install/ChemDoodleWeb.js"></script>
<script type="text/javascript" src="../install/uis/ChemDoodleWeb-uis.js"></script>
</head>
<body>
<script type="text/javascript">
var sketcher=new ChemDoodle.SketcherCanvas("sketcher", getInnerWidth()-32, getInnerHeight()-32,{useServices:false,oneMolecule:<?php echo ($_REQUEST["mode"]!="rxn"?"true":"false"); // , "sketcher/smallIcons/", false, false ?>});
//~ ChemDoodle.sketcher.gui.desktop
self.ChemDoodle=ChemDoodle;
sketcher.repaint();
</script>
</body>
</html>