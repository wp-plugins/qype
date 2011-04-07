<?php


function template($review,$user,$inhalt,$target,$place,$avatar,$mitdatum,$mituser,$mitwertung,$mitlink){

  if($mitdatum) $datum=strftime("%e. %B %Y",strtotime($review->updated));
  else $datum='';

  if($mituser) $user=$user;
  else $user='';

  $userlink="http://www.qype.com/people/$user";

  if($mituser AND $avatar) $pic='<img src="'.$avatar.'"/>';
  else $pic='';

  if($mitwertung) $rating=$review->rating." Sterne";
  else $rating='';

  if($mitlink) $link=' <a href="http://www.qype.com/place/'.$place.'" '.$target.'>weiterlesen auf Qype</a>';
  else $link='';

?>

  <div class="wp_qype">
    <p>
      <a href="<?php print($userlink) ?>" <?php print($target) ?>>
        <?php print($pic) ?>
      </a>
      <span class="wp_qype_datum"><?php print($datum) ?></span>
      <?php print($user) ?> <?php if($rating) print('('.$rating.')') ?>
    </p>
    <p>
      <?php print($inhalt) ?>
      <?php print($link) ?>
    </p>
  </div>

<?php
}
?>
