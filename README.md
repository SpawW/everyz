# everyz
Hack for plugin support on Zabbix web interface

Requirements
------------

Before you install everyz in your frontend, you'll need the following packages:

 * unzip
 * dialog (if you plan to use the wizard installer);
 * wget

Check your Operating System documentation about the specific name of the packages and how to install it.

How To Install - Wizard
-----------------------

Download the latest installer to the tmp folder, as follows:

```
    wget https://raw.githubusercontent.com/SpawW/everyz/master/local/app/everyz/installEveryz.sh -O /tmp/installEveryz.sh
    bash /tmp/installEveryz.sh
```

The Wizard will start and will ask for the following informations (one for each step):

 * Language: pt-BR or en-US;
 * Location of the frontend: folder where the Zabbix frontend is installed, usually /var/www or /var/www/html;
 * Latest Version: Yes to download the latest version or No if you are in a environment without internet access (you still need to download the file and place it in the /tmp. The file should be called 'EveryZ.zip');
 * Apache Configuration: Select if Yes if you leave the configuration of the apache/httpd to the installer (Recommended). If you select no, you will need to define the following configurations in your httpd environment:

```
<Directory "<FRONTEND_DIR>/local/app/everyz/js">
 Options FollowSymLinks
 AllowOverride All
 <IfModule mod_authz_core.c>
  Require all granted
 </IfModule>
 Order allow,deny
 Allow from all
</Directory>
<Directory "/var/www/html/local/app/everyz/images">
 Options FollowSymLinks
 AllowOverride All
 <IfModule mod_authz_core.c>
  Require all granted
 </IfModule>
 Order allow,deny
 Allow from all
</Directory>
<Directory "/var/www/html/local/app/everyz/css">
 Options FollowSymLinks
 AllowOverride All
 <IfModule mod_authz_core.c>
  Require all granted
 </IfModule>
 Order allow,deny
 Allow from all
</Directory>
```

PS: Change the "<FRONTEND_DIR>" to your zabbix frontend directory.

If all options were supplied correctly, you can now point your browser into the your zabbix frontend address and a new menu called "Extras" should be visible.

How to Install - Parameters
---------------------------

If you don't have dialog installed or want to create a script to install everyz, you can call the script with the following parameters:

 * -a=S|N: try to reconfigure apache/httpd;
 * -f=<FRONTEND_DIR>: zabbix frontend files location;
 * -l=en|pt: language as 'pt' or 'en';
 * -d:S|N

A working example using all options can be viewed bellow:

```
    sh installEveryz.sh -a=S -f=/var/www/html -l=pt -d=S
```

PS: Unlike the Wizard method, the installer **will not install dependencies** using parameters as information. Check at the Topic 'Requirements' to verify what packages are needed.


Changes
---------------------------
* 1.1.0 - 20170908
   - Add support for Zabbix 3.4
* 1.1.2 - 20170917
   - Add support for Zabbix 4.0 alfa
   - Fix (github.issue #95)
* 1.1.3 - 20180112
   - Fix issues related to Zabbix 3.4 upgrades
* 1.1.4 - 20180210
   - Small issues related to install process
* 1.1.5 - 20180321
   - Fix (github.issue #98)
   - Improve data validation on host import 
   - Add example about host import (https://github.com/SpawW/everyz/wiki/Modules---Host-Import---Example) 
* 1.1.6 - 20180403
   - Fix issues related to low retention of events / triggers 
   - Add support to resolve macros on popups and titles of lines and polylines (github.issue #30)
   - Add support to arrows in lines and polylines (github.issue #27 #24)
   - Add IP/DNS address of default host interface (github.issue #21)
   - Add host grouping when 2 hosts are very close in zoom level or at same position (github.issue #13)
   - Add support to extra buttons on host popup (github.issue #14)
