<?php
require_once 'HTTP/Request2.php';

$request = new HTTP_Request2('https://www.sigmaaldrich.com/catalog/search?term=1634-04-4&interface=CAS%20No.&N=0&mode=partialmax&lang=en&region=US&focus=product', HTTP_Request2::METHOD_GET);
try {
    $response = $request->send();
    if (200 == $response->getStatus()) {
        echo $response->getBody();
    } else {
        echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
             $response->getReasonPhrase();
    }
} catch (HTTP_Request2_Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?> 
