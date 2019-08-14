^FX Reference ZPL language guide: https://www.servopack.de/support/zebra/ZPLII-Prog.pdf

^XA
^CFa,15
^FO10,10
^FB290,3,,C ^FX auto resize block field, see ZPL manual p.137
^FD$paramHash["storage_name"] (compartment: $paramHash["compartment"])^FS
^FO60,70,c^BY2
^BCN,60,Y,N,N,A
^FD$paramHash["chemical_storage_barcode"]^FS
^XZ