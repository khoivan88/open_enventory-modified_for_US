# OPEN ENVENTORY

## Table of contents

- [OPEN ENVENTORY](#OPEN-ENVENTORY)
  - [Table of contents](#Table-of-contents)
  - [General info](#General-info)
  - [Overview](#Overview)
  - [User guides](#User-guides)
  - [Technology](#Technology)
  - [Setup](#Setup)
  - [Version](#Version)
  - [Screenshots](#Screenshots)
  - [Docker image for CentOS 7 LAMP stack ready for OE](#Docker-build)
  - [Related Softwares](#Related-Softwares)

## General info

This github is a fork of the original [Open Enventory program](https://sourceforge.net/projects/enventory/)

  - Open Enventory is free software. You may redistribute copies of it under the terms of the GNU Affero General Public License V3 http://www.gnu.org/licenses/agpl.html. There is no warranty, to the extent permitted by law.

    - Development: Prof. Dr. Lukas Gooßen and Dr. Felix Rudolphi.  
    - Open Enventory is a registered trademark of Felix Rudolphi and Lukas Gooßen.  
    - Programming: Felix Rudolphi and Thorsten Bonck.  

This repository is made by Dr. Khoi Van for easy creation of forked modification. I don't own any of the code except for the modified one.


## Overview
- Open Enventory is a free, open-source (AGPL v3) programs for chemical inventory and electronic lab notebook. It combines:
  - a lab notebook,
  - a database for all spectroscopic data,
  - a chemical inventory and
  - a literature database.
- It is designed for the requirements of university groups and small companies, focuses primarily in Chemistry research.
- It is operated in the web browser (Edge, Firefox, Opera, Apple Safari, Google Chrome) and is therefore platform-independent. The integration means that physical data and safety information from the inventory can be used automatically in the laboratory journal. The automatic query of freely accessible substance data from online chemical catalogs saves manual data entry and catalog searches. Access to the catalog data also enables a price overview to be created with a click of the mouse.
- The laboratory journal uses the data from inventory management and makes the results accessible to all members of the working group. Both inventory and laboratory journal allow the data to be shared with other working groups for the purpose of collaboration.
- The goals of Open Enventory are:
  - to make unnecessary and boring tasks in the laboratory obsolete: searching for chemicals, price surveys.
  - to make knowledge accessible inside and outside (if access is granted) the workgroup, for longer periods.
  - to improve working safety be easily accessible safety instructions.
  - to reduce waste amounts and unnecessary costs by an easily searchable inventory database.
- Currently, Open Enventory can be used in German, English, French, Spanish, Italian, and Portuguese. Additional language files and modifications can be created with little effort.
- Sources:
  - http://www.open-enventory.de/index_en.html
  - http://sciformation.com/open_enventory.html?lang=en
  
For more detail about Open Enventory and why you should use it, you can look at this [presentation](https://www.dropbox.com/s/a1a44trp7imqfkx/Khoi%20Van%20-%20OE%20introduction%20-%2020191015%20-%20compressed.pptx?dl=0). <br/>
**Note**: This presentation includes videos as demonstration so you might have to download it to be able to play it correctly.


## User guides

- [Please see the Document site here.](https://open-enventory.gitbook.io/user-guides/)


## Technology

- PHP 5+
- MySQL
- HTML5, Javascript
- Bootstrap 4

## Setup

To run this program:
- Consult this [general installation guideline for this project](http://enventory.chemie.uni-kl.de/inventar/INSTALL/INSTALL.html)

If you are a beginner, you can follow step-by-step guideline for:
- Windows: using XAMPPS [link](https://open-enventory.gitbook.io/user-guides/installation/windows)
- Mac: using XAMPPS [link](https://open-enventory.gitbook.io/user-guides/installation/mac-osx)
- Centos 7: [link](https://open-enventory.gitbook.io/user-guides/installation/centos-7)
- Raspberry Pi:[link](https://open-enventory.gitbook.io/user-guides/installation/raspbian-on-raspberry-pi-3b)

## Version

[Versions detail](VERSION.md)

## Screenshots

- Login page on laptop (Win10/Chrome): 20190717
<img src="docs/new_login_laptop_20190718.png" alt="Login page on laptop (Win10/Chrome): 20190717" height="400"/>

- Login page on mobile (Android/Chrome): 20190717
<img src="docs/new_login_mobile_20190718.png" alt="Login page on mobile (Android/Chrome): 20190717" height="400"/>

- New user interface: 20190717
<img src="docs/interface_laptop_20190718.png" alt="New user interface: 20190717" height="400"/>
<br><br>

## Docker build
- You can find some instruction here: https://open-enventory.gitbook.io/user-guides/advanced-settings/docker-build

## Related Softwares
- [oe_find_structure](https://github.com/khoivan88/update_sql_mol): Find missing structures for chemicals in Open Enventory
- [oe_find_sds-public](https://github.com/khoivan88/Oe_find_sds-public): Find missing SDS for chemicals in Open Enventory
