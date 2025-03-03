<?php
/**
 * Plugin Name: FrmCustomGraph
 * Plugin URI: dergoldbroiler.de
 * Description: Benutzeredefinierte Auswertung für Formidable Forms
 * Version: 1.0.0
 * Author: Björn Zschernack
 * Author URI: dergoldbroiler.de
 * textdomain: frmcustomgraph
 */
if (!defined('ABSPATH')) {
    exit; // Sicherheitscheck, um direkten Zugriff zu verhindern
}

/*
Styles und Scripte
*/
function frmcustom_styles() {
    wp_enqueue_style('frmcustom-style', plugin_dir_url(__FILE__) . 'styles/graph.css');
}
function frmcustom_scripts() {
    wp_enqueue_script(
        'frmcustom-script', 
        plugin_dir_url(__FILE__) . 'js/graph.js', 
        [], 
        '1.0.0', 
        true 
    );
}
add_action('wp_enqueue_scripts', 'frmcustom_scripts');
add_action('wp_enqueue_scripts', 'frmcustom_styles');


function frmcustom_get_template($template_name, $args = []) {
    $template_path = plugin_dir_path(__FILE__) . 'templates/' . $template_name;

    if (!file_exists($template_path)) {
        return ''; 
    }

    extract($args);

    ob_start();
    include $template_path;
    return ob_get_clean(); 
}





/*
* Shortcode Filter
*/

//Filterproperties erhalten, die generisch durch den User erstellt werden 
//(z.B.[750="inhalt_der_gefiltert_werden_soll_aus_feld_750"])
function get_generic_shortcode_atts($atts) {
    $generic_atts = [
        'form_id' => 0,
        'ids' => '',
        'labels' => false,
        'legende' => false,
        'filternames' => '',
        'filterfields' => ''
    ];

  $atts = shortcode_atts($atts, 'frmcustomgraph');
  $all_atts = func_get_args()[0];
  $filter_atts = array_diff_key($all_atts, $generic_atts);
  return $filter_atts;
}


function handleFilter($filter_atts, $entry){
    $entry = FrmEntry::getOne($entry->id);
    $positiveCount = 0;
    $matchCount = count($filter_atts); //need match for every field (&& Condition)
    

    foreach($filter_atts as $key => $value ):

       
       
        $savedValue = FrmEntryMeta::get_entry_meta_by_field($entry->id, $key);

        if(strtolower($value) == strtolower($savedValue)):
            $positiveCount++;
        endif;

    endforeach;


    if($positiveCount == $matchCount):
        return true;
    endif;


    return false;
}


function filterEntries($entries, $filter_atts){

    $filtered_entries = array();

    foreach($entries as $entry): 
        if(handleFilter($filter_atts, $entry)):
            array_push($filtered_entries, $entry);
        endif;
    endforeach;
   return $filtered_entries;
}
   
   
/*
* Entry Daten verarbeiten
*/
function getURLFilterAtts($names, $field_ids){
    $names = explode(',', $names);
    $field_ids = explode(',', $field_ids);
    $filter_atts = array();
    for($i = 0; $i < count($names); $i++):
        if(isset($_GET[$names[$i]]) && $_GET[$names[$i]] != ''):
            $filter_atts[$field_ids[$i]] = sanitize_text_field($_GET[$names[$i]]);
        endif;
      // $filter_atts[$field_ids[$i]] = sanitize_text_field($_GET[$names[$i]]);
    endfor;
    return $filter_atts;
}

function hasParams(){
    $url = $_SERVER['REQUEST_URI'];
    $url_components = parse_url($url);
    parse_str($url_components['query'], $params);
    if(count($params) > 0):
        return true;
    endif;
    return false;
}


//Verteilung der Antworten im field_values array zuordnen ud nach jedem Durchlauf updaten
function handleFieldDistribution($field_values,$field_id, $value){
    if($value == 0){
        $field_values[$field_id][0]++;
    }elseif($value == 25){
        $field_values[$field_id][1]++;
    }elseif($value == 50){
        $field_values[$field_id][2]++;
    }elseif($value == 75){
        $field_values[$field_id][3]++;
    }elseif($value == 100){
        $field_values[$field_id][4]++;
    } else {

    }
    return $field_values;
}



        
     
//Main Function
function setCustomGraph($atts) {

    if(is_admin()):
        return;
    endif;

    if(!isset($atts['ids'])):
        return;
    endif;

    $field_ids = explode(',', $atts['ids']);

    $entries = FrmEntry::getAll([
        'form_id' => $atts['form_id']
    ]);
  
    if(hasParams() && isset($atts['form_id']) && isset($atts['filternames']) && isset($atts['filterfields'])):
        $filter_atts = getURLFilterAtts($atts['filternames'], $atts['filterfields']);
        $additional_atts = get_generic_shortcode_atts($atts);
        $filter_atts = $filter_atts + $additional_atts;
     
    else:
        //Nachschauen, ob entries noch gefiltert werden müssen
        $filter_atts = get_generic_shortcode_atts($atts);
    endif;


    if(count($filter_atts) > 0):
        $entries = filterEntries($entries, $filter_atts);
    endif;

 
    if (!$entries) {
        return 'Keine Einträge gefunden.';
    }

    //Für den bau der Bricks im Template benötigt
    $field_values = array();
    foreach($field_ids as $field_id):
        $field_values[$field_id] = array(
            0 => 0,
            1 => 0,
            2 => 0,
            3 => 0,
            4 => 0,
            5 =>  FrmField::getOne($field_id)->name
        );
    endforeach;

    $content = ''; //ausgabepuffer hier rein

    $total = count($entries); //Basis für die Prozentberechnung

    foreach($entries as $entry):      

        //Lopp through the field ids
        for($i = 0; $i < count($field_ids); $i++):   
            
              $metadaten = FrmEntryMeta::getAll([
                'item_id' => $entry->id,
                'field_id' => $field_ids[$i]
              ]);
            
              $field_values = handleFieldDistribution($field_values, $field_ids[$i], $metadaten[0]->meta_value);
              $field = FrmField::getOne($field_ids[$i]); // Ersetze 123 mit deiner Field ID
    
           
    
        
            $label = "keine Frage mitgesendet";
    
            if ($field) {
                $label = esc_html($field->name);
            }
               
        endfor;
       
        
    endforeach;

    $template_data = [
        'entry_values' => $field_values,
        'total' => $total,
        'label' => $label,
        'options' => $field->options,
        'labels' => false,
        'entry_id' => $entry->id
    ];

    if(isset($atts['labels'])):
        $template_data['labels'] = true;
    endif;    

    $content .= frmcustom_get_template('graph.php', $template_data);

    if(isset($atts['legende'])):
        $content .= frmcustom_get_template('legende.php');
    endif;

    return $content;

}
add_shortcode('customgraph', 'setCustomGraph');