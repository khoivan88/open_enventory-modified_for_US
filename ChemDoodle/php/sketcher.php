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
        var sketcher = new ChemDoodle.SketcherCanvas("sketcher", getInnerWidth() - 32, getInnerHeight() - 32, {
                            useServices: false,
                            oneMolecule: <?php echo($_REQUEST["mode"] != "rxn" ? "true" : "false"); ?>,
                            includeQuery:false,
                            resizable:true,
                            // requireStartingAtom: false,    // Khoi: "false" so user can draw anywhere on canvas. Read more here:https://web.chemdoodle.com/tutorial/2d-structure-canvases/sketcher-canvas#options
                        });

        // Allow parent frame to call commands inside `ChemDoodle` module
        self.ChemDoodle = ChemDoodle;


        // Ref: https://web.chemdoodle.com/tutorial/advanced/sketcher-query-sketcher#additional
        // enables overlap clear widths, so that some depth is introduced to overlapping bonds
        sketcher.styles.bonds_clearOverlaps_2D = true;
        // double the bond length to 40 so query labels are readable
        sketcher.styles.bondLength_2D = 40;

            // sets terminal carbon labels to display
        sketcher.styles.atoms_displayTerminalCarbonLabels_2D = true;
        // sets atom labels to be colored by JMol colors, which are easy to recognize
        sketcher.styles.atoms_useJMOLColors = true;
        // sets the shape color to improve contrast when drawing figures
        sketcher.styles.shapes_color = '#c10000';
        // because we do not load any content, we need to repaint the sketcher, otherwise we would just see an empty area with the toolbar
        // however, you can instead use one of the Canvas.load... functions to pre-populate the canvas with content, then you don't need to call repaint
        sketcher.repaint();
    </script>
</body>

</html>