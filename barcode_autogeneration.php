<!DOCTYPE html>
<html lang="en">
<!-- <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>s("barcode_autogeneration")</title>
</head> -->
<body style="background-color:<?php require_once "lib_global_settings.php"; echo defBgColor;?>">
    <h1 style="text-align:center">Location/User Barcodes Autogeneration</h1>
    <ul>
        <li> 
            While turning ON <i>"Use barcodes without prefix (slower, no printed 
                barcodes on labels, no 99 as prefix, for existing barcode systems)"</i> 
                in <u>Settings/Global settings/Inventory</u>, leaving "barcode" as blank 
                (or not adding user-defined barcodes) for <b>Storages</b> and <b>Users</b>
                will not scan auto-generated barcode for users or storages in Terminal mode.
            </li>
    </ul>
    <h2>Solution:</h2>
    <ul>
        <li>
            If you have your own Storage and/or User barcodes and want to import that info, please use 
            "<a href="import.php">Import tab-separated text file</a>" function.
        </li>
        <br>
        <li>
            You can also add, modify, print barcodes for 
            <a href="list.php?table=storage&dbs=-1">Storages</a> or 
            <a href="list.php?table=person&dbs=-1">Users</a> by 
            editting them manually in the respective Menus.
        </li>
        <br>
        <li> 
            You can choose the options below to let Open Enventory autopopulate the barcodes for 
            ALL of Storages and/or Users. This will NOT change any barcode that has been manually 
            added or imported.
        </li>
    </ul>
    <br><br>
    <div>
        <form method="post" action="barcode_autogeneration.php" >
            <button type="submit"  formtarget="_self" name="storage_barcode_autogeneration">
                Autopopulate <b>Storage</b> barcodes</button>
        </form>
    </div>
    <br>
    <div>
        <form method="post" action="barcode_autogeneration.php" >
            <button type="submit"  formtarget="_self" name="user_barcode_autogeneration">
                Autopopulate <b>User</b> barcodes</button>
        </form>
    </div>

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
    // Khoi: this file is added to the source code to add automatically populate barcodes for storages and users when "Existing barcode" option is used
    require_once "lib_global_settings.php";
    require_once "lib_global_funcs.php";
    pageHeader();
    echo stylesheet.
    style.
    getFixedStyleBlock().
    _style."
    </head>";
    // echo "<title>".s("barcode_autogeneration")."</title>";
    
    if (isset($_POST["storage_barcode_autogeneration"])) {
        storage_barcode_autogeneration();
    } elseif (isset($_POST["user_barcode_autogeneration"])) {
        user_barcode_autogeneration();
    }
    
    function storage_barcode_autogeneration() {
        $sql_query = "UPDATE storage 
                        SET storage_barcode=cast(
                                concat(
                                    concat(92, lpad(storage_id, 5, 0)), 
                                    (10 - 
                                        ((9*3 + 2 + 
                                        substring(cast(concat(92, lpad(storage_id, 5, 0)) as char), 3, 1)*3 +
                                        substring(cast(concat(92, lpad(storage_id, 5, 0)) as char), 4, 1)*1 +
                                        substring(cast(concat(92, lpad(storage_id, 5, 0)) as char), 5, 1)*3 +
                                        substring(cast(concat(92, lpad(storage_id, 5, 0)) as char), 6, 1)*1 +
                                        substring(cast(concat(92, lpad(storage_id, 5, 0)) as char), 7, 1)*3
                                        ) % 10)
                                    ) % 10)
                            as binary)
                        WHERE storage_barcode is NULL or storage_barcode=''";

        $conn = mysqli_connect(db_server,$_SESSION["user"],$_SESSION["password"],$_SESSION["db_name"]);
        $result = mysqli_query($conn, $sql_query);
        if ($result) {
            echo "<br>Success! All storage barcodes have been created.";
        } else {
            echo "<br>Unsuccessful!";
        }
        mysqli_close($conn);
    }
    
    function user_barcode_autogeneration() {
        $sql_query = "  UPDATE person  
                        SET person_barcode=cast(   
                            concat(concat(91, lpad(person_id, 5, 0)),  
                            (10 -   ((9*3 + 1 +  substring(cast(concat(91, lpad(person_id, 5, 0)) as char), 3, 1)*3 + 
                            substring(cast(concat(91, lpad(person_id, 5, 0)) as char), 4, 1)*1 + 
                            substring(cast(concat(91, lpad(person_id, 5, 0)) as char), 5, 1)*3 + 
                            substring(cast(concat(91, lpad(person_id, 5, 0)) as char), 6, 1)*1 + 
                            substring(cast(concat(91, lpad(person_id, 5, 0)) as char), 7, 1)*3   ) % 10) ) % 10)
                            as binary) 
                        WHERE person_barcode is NULL or person_barcode=''";

        $conn = mysqli_connect(db_server,$_SESSION["user"],$_SESSION["password"],$_SESSION["db_name"]);
                    $result = mysqli_query($conn, $sql_query);
        if ($result) {
            echo "<br>Success! All user barcodes have been created.";
        } else {
            echo "<br>Unsuccessful!";
        }
        mysqli_close($conn);
    }
    ?>

</body>
</html>