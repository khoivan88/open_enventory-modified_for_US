<!doctype html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        ChemDraw JS
    </title>
    <script>
        // Khoi: not required for Chemdraw JS, just to time loading
        // Ref: https://stackoverflow.com/a/61511955/6596203
        function waitForElm(selector) {
            return new Promise(resolve => {
                if (document.querySelector(selector)) {
                    return resolve(document.querySelector(selector));
                }
                const observer = new MutationObserver(mutations => {
                    if (document.querySelector(selector)) {
                        resolve(document.querySelector(selector));
                        observer.disconnect();
                    }
                });
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        }

        var cdjsLoadStart = window.performance.now();

        // https://chemdrawdirect.perkinelmer.cloud/js/sample/index.html#
        function hideIntializingIndicator() {
            document.querySelector("#workingIndicator").style.display = 'none';
            var loadTime = window.performance.now() - cdjsLoadStart;
            console.log("CDJS load time is " + loadTime + "ms");
        };

        window.addEventListener('DOMContentLoaded', function () {
            waitForElm('.cdd-container').then(hideIntializingIndicator);
        });
    </script>
    <script src="https://chemdrawdirect.perkinelmer.cloud/js/chemdrawweb/chemdrawweb.js">
    </script>
    <style>
        body {
            width: 100%;
            height: 100%;
            margin: 0;
        }

        #workingIndicator {
            margin: 0 auto;
            /* font-size: 3vw; */
            /* Ref: https://css-tricks.com/snippets/css/fluid-typography/ */
            font-size: calc(14px + (26 - 14) * ((100vw - 300px) / (1600 - 300)));
            text-align: center;
        }

        /* Ref: https://css-tricks.com/quick-css-trick-how-to-center-an-object-exactly-in-the-center/ */
        .centered {
            position: fixed; /* or absolute */
            top: 50%;
            left: 50%;
            /* bring your own prefixes */
            transform: translate(-50%, -50%);
        }

        #chemdrawjs-container {
            position: absolute;
            left: 0;
            top: 0;

            width: 99vw;
            /* max-width: 92vw; */
            height: 99vh;
            /* max-height: 92vh; */

            overflow: auto;
            resize: auto;

            border: solid black 1px;
        }

        /* Khoi: for waiting indicator. Ref: https://loading.io/css/ */
        .lds-ellipsis {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }
        .lds-ellipsis div {
            position: absolute;
            top: 33px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #0fac04;
            animation-timing-function: cubic-bezier(0, 1, 1, 0);
        }
        .lds-ellipsis div:nth-child(1) {
            left: 8px;
            animation: lds-ellipsis1 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(2) {
            left: 8px;
            animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(3) {
            left: 32px;
            animation: lds-ellipsis2 0.6s infinite;
        }
        .lds-ellipsis div:nth-child(4) {
            left: 56px;
            animation: lds-ellipsis3 0.6s infinite;
        }
        @keyframes lds-ellipsis1 {
            0% {
                transform: scale(0);
            }

            100% {
                transform: scale(1);
            }
        }
        @keyframes lds-ellipsis3 {
            0% {
                transform: scale(1);
            }

            100% {
                transform: scale(0);
            }
        }
        @keyframes lds-ellipsis2 {
            0% {
                transform: translate(0, 0);
            }

            100% {
                transform: translate(24px, 0);
            }
        }
    </style>
</head>

<body>

    <!-- Show an indicator for slow loading process-->
    <div id="workingIndicator" class="centered">
        <img src="images/chemdraw.png" alt="ChemDrawLogo" srcset="">
        <div id="workingIndicatorText">Initializing...</div>
        <div class="lds-ellipsis"><div></div><div></div><div></div><div></div></div>
    </div>

    <div id="chemdrawjs-container">
    </div>

    <script>
        var global_chemdraw, molFile, reactants, products;

        function removePipes(text) { // remove | and make proper molfile
            if (text == undefined || text == "") {
                return "";
            }
            text = String(text);
            var replaceText = "\n";
            text = text.replace(/:/g, "."); // Bugfix for Marvin on Safari
            text = text.replace(/\r\n/g, replaceText);
            text = text.replace(/\r/g, replaceText);
            text = text.replace(/\|/g, replaceText);
            return text;
        }

        // Ref: https://chemdrawdirect.perkinelmer.cloud/js/docs/API%20Reference/API%20Reference%20Guide.htm#API%20Reference.html#getmolcallback--void%3FTocPath%3DClasses%7CChemDrawDirect%7C_____18

        function chemdrawjsAttached(chemdrawweb) {
            // do something
            global_chemdraw = chemdrawweb;
            loadMolfile();
        }

        function convertNextMolfile(molfiles, reactants_length, idx) {
            if (idx < molfiles.length) {
                global_chemdraw.loadMOL(unescape(molfiles[idx]), function (cdxmlStr, error) {
                    if (idx < reactants_length) {
                        reactants.push(cdxmlStr);
                    } else {
                        products.push(cdxmlStr);
                    }
                    convertNextMolfile(molfiles, reactants_length, idx + 1);
                });
            } else { // all through
                global_chemdraw.clear();
                for (var i = reactants.length - 1; i >= 0; i--) { // back-to-start
                    global_chemdraw.addReactant(reactants[i]);
                }
                for (var i = 0, iMax = products.length; i < iMax; i++) { // left-to-right
                    global_chemdraw.addProduct(products[i]);
                }
            }
        }

        function loadMolfile() {
            if (global_chemdraw && molFile) {
                if (molFile.substr(0, 4) == "$RXN") {
                    // split
                    molFile = removePipes(molFile);
                    var molfiles = molFile.split("$MOL\n"),
                        header = molfiles.shift(),
                        header_lines = header.split("\n"),
                        countsLine = unescape(header_lines[header_lines.length - 2]),
                        reactants_length = parseInt(countsLine.substr(0, 3));
                    reactants = [];
                    products = [];
                    global_chemdraw.setCanvasSize({
                        width: 2000,
                        height: 2000
                    }, {
                        doNotCropDrawings: true
                    });
                    convertNextMolfile(molfiles, reactants_length, 0);
                } else {
                    global_chemdraw.loadMOL(molFile);
                }
            }
        }

        function chemdrawjsFailed(error) {
            alert(error);
        }

        function isReady() {
            return true; // will load molfile when ready (can be slow)
        }

        <?php
            // Ref: https://stackoverflow.com/a/5884896/6596203
            $mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_URL);
            $config_file = $mode == 'rxn' ? './config.json' : './config-small.json';
        ?>

        perkinelmer.ChemdrawWebManager.attach({
            id: 'chemdrawjs-container',
            callback: chemdrawjsAttached,
            errorCallback: chemdrawjsFailed,
            // licenseUrl: './ChemDraw-JS-License.xml',
            license: '<?php echo str_replace(array("\r\n", "\n", "\r"), '', file_get_contents('ChemDraw-JS-License.xml'));?>',
            preservePageInfo: true, // https://chemdrawdirect.perkinelmer.cloud/js/docs/Developers%20Guide_Service/Developers%20Guide.htm#Developers%20Guide/Understanding%20CDD%20View.html#Preserve2%3FTocPath%3DUnderstanding%2520the%2520ChemDraw%2520JS%2520View%7CChemDraw%2520JS%2520View%2520Mode%7C_____2
            // configUrl: './config-sample.json',
            <?php
                // Ref: https://stackoverflow.com/a/5884896/6596203
                $mode = filter_input(INPUT_GET, 'mode', FILTER_SANITIZE_URL);
                if ($mode != 'rxn') echo "configUrl: './config-small.json',"; ?>
        });

        // function triggerGetMolfile(targetId) {
        // 	global_chemdraw.getMOL(function (value) {
        // 		parent.syncValue("onReceiveMolfile", targetId, value);
        // 	});
        // }

        function getMolfile() {
            var retval;
            global_chemdraw.getMOL(function (mol, error) {
                if (!error) {
                    retval = mol;
                } else {
                    alert(error);
                }
            });
            return retval;
        }

        function getRxnfile() {
            var retval;
            global_chemdraw.getRXN(function (reaction, error) {
                if (!error) {
                    retval = reaction;
                } else {
                    alert(error);
                }
            });
            return retval;
        }

        function setMolRxnfile(data) {
            molFile = data;
            loadMolfile();
        }

        function triggerGetRxnfile(targetId) {
            global_chemdraw.getRXN(function (value) {
                parent.syncValue("onReceiveMolfile", targetId, value);
            });
        }

        function triggerCopyMolfile(targetId) {
            global_chemdraw.getMOL(function (value) {
                parent.syncValue("onCopy", targetId, value);
            });
        }

        function triggerCopyRxnfile(targetId) {
            global_chemdraw.getRXN(function (value) {
                parent.syncValue("onCopy", targetId, value);
            });
        }

        // global_chemdraw.fitToContainer();
    </script>
</body>

</html>