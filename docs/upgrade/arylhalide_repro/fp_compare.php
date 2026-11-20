<?php
if (PHP_SAPI!=="cli") { http_response_code(404); exit; } // command-line only; this folder is reachable over the web
// usage: php fp_compare.php stored.json query.json  -- applies getFingerprintFilter() logic (lib_db_filter.php:1200), mask -16 on fp1 for "similar"
$st=json_decode(file_get_contents($argv[1]),true); $qu=json_decode(file_get_contents($argv[2]),true);
printf("%-16s","query \\ stored"); foreach ($st as $n=>$x) printf("%-16s",$n); echo "\n";
foreach ($qu as $qn=>$q) {
	printf("%-16s",$qn);
	foreach ($st as $sn=>$s) {
		$ok=true; $miss=[];
		for ($a=0;$a<13;$a++) { $c=$q["query"][$a]; if ($a==0) $c&=-16; if ($c && (($s["stored"][$a]&$c)!=$c)) { $ok=false; $miss[]="fp".($a+1).":".dechex($c & ~$s["stored"][$a]); } }
		printf("%-16s",$ok?"pass":"DROP ".implode(",",$miss));
	}
	echo "\n";
}
