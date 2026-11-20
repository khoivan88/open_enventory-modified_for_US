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
        // Khoi: in the sidebar the sketcher is as wide as the sidebar, and ChemDoodle zooms the drawing on every wheel turn,
        // so the sidebar could not be scrolled down to the search buttons. There, the wheel scrolls the sidebar; Ctrl/Cmd + wheel zooms.
        var inSidebar = false;
        try {
            inSidebar = (parent!==self && parent.name=="sidenav");
        } catch (e) {
            // parent from another origin: leave ChemDoodle's behaviour
        }
        if (inSidebar) {
            window.addEventListener("wheel", function(e) {
                if (!e.ctrlKey && !e.metaKey) {
                    e.stopPropagation(); // before ChemDoodle's handler on the canvas, which would zoom and cancel the scrolling
                }
            }, true);
        }

        // Khoi: at least 200x200, e.g. when created while the sidebar is hidden (0 px wide); a negative size broke the canvas
        var sketcher = new ChemDoodle.SketcherCanvas("sketcher", Math.max(getInnerWidth() - 32, 200), Math.max(getInnerHeight() - 32, 200), {
                            useServices: false,
                            oneMolecule: <?php echo($_REQUEST["mode"] != "rxn" ? "true" : "false"); ?>,
                            includeQuery:false,
                            resizable:!inSidebar, // Khoi: in the sidebar the canvas is fitted to the frame (fitSketcher), no resize handle
                            requireStartingAtom: false,    // Felix 2022-02-20 / Khoi: "false" so user can draw anywhere on canvas. Read more here:https://web.chemdoodle.com/tutorial/2d-structure-canvases/sketcher-canvas#options
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

        // Khoi: in the sidebar, fit the canvas into this frame: toolbar and canvas taller or wider than the frame gave it scrollbars of its own,
        // a second scroll area under the pointer. Again when the frame changes size (wider sidebar for structure search, sidebar shown again).
        function fitSketcher() {
            var canvas = document.getElementById("sketcher");
            if (!canvas || getInnerWidth() < 100) { // hidden sidebar
                return;
            }
            var border = canvas.offsetWidth - canvas.clientWidth;
            var w = Math.max(getInnerWidth() - 16 - border, 150); // body margin 8px
            if (w != sketcher.width) {
                sketcher.resize(w, sketcher.height); // the toolbar wraps with the frame width, so its height is known after this
            }
            var h = Math.max(getInnerHeight() - canvas.getBoundingClientRect().top - 8 - border, 120);
            if (h != sketcher.height) {
                sketcher.resize(sketcher.width, h);
            }
        }
        if (inSidebar) {
            fitSketcher();
            window.addEventListener("resize", fitSketcher);
        }
    </script>
</body>

</html>