function extraButtons(host){
  <?php
  ?>
  let avButton = '<img class="everyzShortcutIMG" hspace=10 vspace=10  title="Availability report" src="local/app/everyz/images/availability.png" onclick=\''
  + 'javascript:hostAvailability('+host.id+');' + '\'/>&nbsp;';
  let svButton = '<img class="everyzShortcutIMG" hspace=10 vspace=10  title="Google Street View" src="local/app/everyz/images/street-view.png" onclick=\''
  + 'zbxePopUp("https://maps.google.com/maps?q=&layer=c&cbll='+host.location_lat+','+host.location_lon+'")' + '\'/>&nbsp;';
  extraCode = avButton + svButton;
  return extraCode;
}
<?php
