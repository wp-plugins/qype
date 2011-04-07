<?php
/*
Plugin Name: Qype for Wordpress
Version: 0.2
Plugin URI: http://wordpress.org/extend/plugins/qype/
Description: Shows qype reviews in a sidebar widget
Author: Qype
Author URI: http://qype.com/
*/
/* 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once('template.php');

define('MAGPIE_CACHE_ON', 1); //2.7 Cache Bug
define('MAGPIE_CACHE_AGE', 180);
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

$wp_qype_options['widget_fields']['title'] = array('label'=>'Titel:', 'type'=>'text', 'default'=>'','size'=>'20');
$wp_qype_options['widget_fields']['place'] = array('label'=>'ID des Platzes:', 'type'=>'text', 'default'=>'','size'=>'5');
$wp_qype_options['widget_fields']['num'] = array('label'=>'Anzahl der Reviews:', 'type'=>'text', 'default'=>'5','size'=>'3');
$wp_qype_options['widget_fields']['minrating'] = array('label'=>'Zeige nur Reviews mit mind. sovielen Sternen:', 'type'=>'text', 'default'=>'0','size'=>'3');
$wp_qype_options['widget_fields']['encode_utf8'] = array('label'=>'utf-8 encodiert', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['summary'] = array('label'=>'zeige verkürzte Zusammenfassung', 'type'=>'checkbox', 'default'=>false);
//$wp_qype_options['widget_fields']['leere'] = array('label'=>'Zeige auch leere Beschreibungen', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['mitdatum'] = array('label'=>'mit Datum', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['mitwertung'] = array('label'=>'zeige Bewertung an', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['mitlink'] = array('label'=>'mit Verlinkung auf Qype', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['target_blank'] = array('label'=>'Link öffnet in einem neuen Fenster', 'type'=>'checkbox', 'default'=>false);
$wp_qype_options['widget_fields']['mituser'] = array('label'=>'zeige User an', 'type'=>'checkbox', 'default'=>false);

$wp_qype_options['prefix'] = 'wp_qype';

// Display Qype messages
function wp_qype_messages($num = 5, $place, $min_rating, $encode_utf8 = false, $target_blank=false, $showsummary=false, $mitlink=false, $mitdatum=false, $mitwertung=false, $mituser=false){
  global $wp_qype_options;
  include_once(ABSPATH . WPINC . '/rss.php');

  $APIKey='VgRbGHDkv79bSbPss5FKQ';

  $url="http://api.qype.com/v1/places/{$place}/reviews?consumer_key={$APIKey}&order=date_updated";

  $daten=@simplexml_load_file($url);

  if($target_blank) $target=' target="_blank" ';  
  else $target='';  

  if($showsummary) $showhere="summary";
  else $showhere="content";

  echo '<div class="wp_qype">';

  if (empty($daten)) {

    echo '<li>The Qype-API is currently not available. Sorry.</li>';

  } else {

    $i=0;
    foreach($daten->review AS $review){

      if((int) $review->rating >= $min_rating){

        $inhalt=trim(preg_replace("/\n/","",utf8_decode(strip_tags($review->$showhere))));

        if($encode_utf8) $inhalt=utf8_encode($inhalt);      

        foreach($review->link AS $link){

          $attr=$link->attributes();

          if($attr['rel']=='http://schemas.qype.com/user'){
            $userlink=$attr['href'];
            $username=$attr['title'];
            $user="<a href=\"http://www.qype.com/people/$username\" $target>$username</a>";

            // Get ProfilePic
            $url="http://api.qype.com/v1/users/{$username}?consumer_key={$APIKey}";
            $userdata=@simplexml_load_file($url);
            if (!empty($userdata) && $userdata->image){
              $images=$userdata->image->attributes();
              $avatar=$images['tiny'];
            }
          }
        }
        if(!empty($inhalt))
          template($review,$username,$inhalt,$target,$place,$avatar,$mitdatum,$mituser,$mitwertung,$mitlink);

        $i++;
        if ($i>=$num ) break;
      }
    }
  }

  echo '</div>';
}

// Qype widget stuff
function widget_wp_qype_init(){
  if ( !function_exists('register_sidebar_widget') ) return;
  
  $check_options = get_option('widget_wp_qype');
  if ($check_options['number']=='') {
    $check_options['number'] = 5;
    update_option('widget_wp_qype', $check_options);
  }
  
  function widget_wp_qype($args, $number = 5) {
    global $wp_qype_options;
    // $args is an array of strings that help widgets to conform to
    // the active theme: before_widget, before_title, after_widget,
    // and after_title are the array keys. Default tags: li and h2.
    extract($args);

    // Each widget can store its own options. We keep strings here.
    include_once(ABSPATH . WPINC . '/rss.php');
    $options = get_option('widget_wp_qype');
    
    // fill options with default values if value is not set
    $item=$options[$number];
    foreach($wp_qype_options['widget_fields'] as $key => $field) {
      if (! isset($item[$key])) {
        $item[$key] = $field['default'];
      }
    }

    // These lines generate our output.
    echo $before_widget . $before_title . '<a href="http://www.qype.com/" class="wp_qype_title_link">'.$item['title'].'</a>'.$after_title;
    if(strlen($item['subtitle'])>0) echo '<div id="wp_qype-subtitle">'.$item['subtitle'].'</div>';
      wp_qype_messages($item['num'],$item['place'],$item['minrating'],$item['encode_utf8'],$item['target_blank'],$item['summary'],$item['mitlink'],$item['mitdatum'],$item['mitwertung'],$item['mituser']);
    echo $after_widget;
        
  }

  // This is the function that outputs the form to let the users edit
  // the widget's title. It's an optional feature that users cry for.
  function widget_wp_qype_control($number) {    global $wp_qype_options;
    // Get our options and see if we're handling a form submission.  
    $options = get_option('widget_wp_qype');
    if ( isset($_POST['wp_qype-submit'])){      
      foreach($wp_qype_options['widget_fields'] as $key => $field) {        
        $options[$number][$key] = $field['default'];
        $field_name = sprintf('%s_%s_%s', $wp_qype_options['prefix'], $key, $number);
        if ($field['type'] == 'text') {
          $options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
        } elseif ($field['type'] == 'checkbox') {
          $options[$number][$key] = isset($_POST[$field_name]);
        }
      }
      update_option('widget_wp_qype', $options);
    }    
    foreach($wp_qype_options['widget_fields'] as $key => $field) {    
      $field_name = sprintf('%s_%s_%s', $wp_qype_options['prefix'], $key, $number);
      $field_checked = '';
      if ($field['type'] == 'text') {
        $field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
      } elseif ($field['type'] == 'checkbox') {
        $field_value = 1;
        if (! empty($options[$number][$key])) {
          $field_checked = 'checked="checked"';
        }
      }
      
      switch($field['type']){
        case 'checkbox':
          printf('<p style="text-align:left;" class="twitter_field"><label for="%s"><input id="%s" name="%s" size="%s" type="%s" value="%s" class="%s" %s /> %s</label></p>',
            $field_name, $field_name, $field_name, $field['size'], $field['type'], $field_value, $field['type'], $field_checked, __($field['label']));
        break;
        default:
          printf('<p style="text-align:left;" class="twitter_field"><label for="%s">%s<br/><input id="%s" name="%s" size="%s" type="%s" value="%s" class="%s" %s /></label></p>',
            $field_name, __($field['label']), $field_name, $field_name, $field['size'], $field['type'], $field_value, $field['type'], $field_checked);
      }
    }
    echo '<input type="hidden" id="wp_qype-submit" name="wp_qype-submit" value="1" />';
  }
  
  function widget_wp_qype_setup() {
    $options = $newoptions = get_option('widget_wp_qype');
    
    if ( isset($_POST['wp_qype-number-submit']) ) {
      $number = (int) $_POST['wp_qype-number'];
      $newoptions['number'] = $number;
    }
    
    if ( $options != $newoptions ) {
      update_option('widget_wp_qype', $newoptions);
      widget_wp_qype_register();
    }
  }
  
  
  function widget_wp_qype_page() {
    $options = $newoptions = get_option('widget_wp_qype');
  ?>
    <div class="wrap">
      <form method="POST">
        <h2><?php _e('WP Qype Widgets'); ?></h2>
        <p style="line-height: 30px;"><?php _e('How many Qype widgets would you like?'); ?>
        <select id="wp_qype-number" name="wp_qype-number" value="<?php echo $options['number']; ?>">
  <?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
        </select>
        <span class="submit"><input type="submit" name="wp_qype-number-submit" id="wp_qype-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
      </form>
    </div>
  <?php
  }
  
  
  function widget_wp_qype_register(){
    $options = get_option('widget_wp_qype');
    $dims = array('width' => 300, 'height' => 300);
    $class = array('classname' => 'widget_wp_qype');
    //for ($i = 1; $i <= 2; $i++) {    
    $i=1;
      //$name = sprintf(__('WP Qype #%d'), $i);
      $name = __('WP Qype');
      $id = "wp_qype-$i"; // Never never never translate an id
      wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_wp_qype' : /* unregister */ '', $class, $i);
      wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_wp_qype_control' : /* unregister */ '', $dims, $i);
    //}
    add_action('sidebar_admin_setup', 'widget_wp_qype_setup');
    //add_action('sidebar_admin_page', 'widget_wp_qype_page'); // it's not working
  }
  widget_wp_qype_register();}
  
  function mystyle(){  
  $path = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));   
  echo '<link rel="stylesheet" href="' .$path. 'style.css" type="text/css" />';
  }
  
  
// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_wp_qype_init');add_action('wp_head', 'mystyle');

?>
