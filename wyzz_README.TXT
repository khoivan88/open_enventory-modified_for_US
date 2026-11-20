Wyzz WYSIWYG Editor

(c) The Mouse Whisperer 2007


VERSION
=======
v0.62


LICENCE
=======
This software is licenced under the LGPL, a copy of which is in the download package.
Please leave all copyright notices and links intact.


INSTALLATION
============
1. Unzip everything to somewhere convenient, maintaining the directory structure.
2. Upload to your website so that wyzz.js is in the same directory as your form.
3. Place the following line in the <head> area of the page containing your form:

   <script language="JavaScript" type="text/javascript" src="wyzz.js"></script>
   
4. After the textarea you want to Wyzzify, place a small script like this:

  <textarea name="textfield" id="textfield" rows="10" cols="40"></textarea><br />
  <script language="javascript1.2">
    make_wyzz('textfield');
  </script>
  
  Make sure that the argument of the make_wyzz() function matches the ID of the textarea.
  

MODIFICATION
============
All of the following settings are near the top of wyzz.js, in an area marked by comments:

Modify the Width and Height parameters near the start of the wyzz.js file to suit your application.
Modify the entries in the buttonName array to amend the available toolar buttons and their order.
Modify the entries in the myFonts array to set which fonts are available to the editor.
By default, the editor tries to output xhtml-compliant code. This is somewhat experimental; if it causes problems, you can turn it off by changing the value of xhtml_out to 0.
Finally, modify the 'Edit Region Stylesheet' to point to the relevant stylesheet - default is wyzzstyles/editarea.css. You will probably want the rules in this stylesheet to match the style of the page whose content you are editing.


WARRANTY
========
None! This is free software, and it is up to you to verify its fitness for your purpose.
The author will not be held responsible for any loss or damage caused by the use of
any software in this package.
  
That's it! There's a forum on the site where you can ask any questions ...