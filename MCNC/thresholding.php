<?php

ini_set('memory_limit', '128M');

//Setting Email Function
$to = 'austin.lee1993@gmail.com, alee@mcnc.org';
$subject = 'Thresholding Reports';
$message = '';
$from = 'kis-team@mcnc.org';

$utilization_end = mktime(23, 59, 59, date('n'), date('j')-1, date('Y')); 
$utilization_start = mktime(0, 0, 0, date('n'), date('j')-30, date('Y'));

require_once('/srv/ncren_ws/classes/NCRENWS.php');
$ncren_ws = new NCRENWS(array('use_test' => false));

$wsr = $ncren_ws->get_services(array(
    'limit' => 100,
    'status' => 'active',
    'type_name_like' => 'Network Access ',
    'direction_id' => 2,
    'load' => array('endpoints', 'circuits'),
    'circuit_params' => array(
        'status' => 'active',
        'layer_not' => 1,
        'load' => 'endpoints'
    )
));

$email_template = '';
define("THRESHOLD", 0.6);

if (!empty($wsr->results)) {

  //array to store service utilities that are over the threshold
  $overThreshold_array = array();

	foreach ($wsr->results as $service) {

    if ($service->type->base == 'transport') {

      if(!empty($service->circuits)){

        //arrays to store utilizations and circuits
        $util_array = $circuits = array();

            foreach ($service->circuits as $circuit){

            $circuits[] = $circuit;

         	// Check for associated endpoints within circuit array
     	    if(!empty($circuit->endpoints)){

     	    	// Loop through endpoints array
     		    foreach ($circuit->endpoints as $circuitEndpoint){

     		    	// Check to see if interface is on endpoint
     			    if(!empty($circuitEndpoint->interface)){

                // Get interface component ID
                $component_ids = get_component_ids($circuitEndpoint->interface);

                //Store utilizations into one array
                if (!empty($component_ids)) {
                  $util_array[$component_ids[0]] = get_utilization($component_ids);
                }
              }
            }
  	      }
        }

        // Get the max utilization value and corresponding interface ID
        $max_utilization = 0;
        $max_component_id = array();
        if (!empty($util_array)) {

          // Sort array by utilization value in descending order, preserving keys (interface IDs)
          arsort($util_array);

          //Use the highest utilization
          foreach ($util_array as $component_id => $utilization) {
              $max_utilization = $utilization;
              $max_component_id = array($component_id);
              break;
          }
        }

        if (!empty($service->contracted_bandwidth)) {

          $utilization_percentage = $max_utilization / ($service->contracted_bandwidth);

          if ($utilization_percentage > THRESHOLD) {
            $overThreshold_array[] = new ServiceUtil($service->org->name, $service->id, $service->name, $service->type->name, $circuits, $max_utilization, $service->contracted_bandwidth, $utilization_percentage, $max_component_id);
          }
        } else {
            echo "Utilization %: N/A\n";
        }
      }
    }else {

      //Create a blank array
      $interfaces = array();

      //Check if endpoint array exists within service
			if(!empty($service->endpoints)) {

				//Loop through each object with endpoint array
				foreach ($service->endpoints as $endpoint) {

                  //Check if interface object exists
                  if (!empty($endpoint->interface)) {

                      //Store interfaces into one array
                      $interfaces[$endpoint->interface->id] = $endpoint->interface;

                  }
                }

                if(!empty($service->contracted_bandwidth)){

                  // Get interface SNAPP component IDs
                  $component_ids = get_component_ids($interfaces);

                  $utilization_3 = get_utilization($component_ids);
                  $utilization_percentage = $utilization_3/$service->contracted_bandwidth;

                  if ($utilization_percentage > THRESHOLD){
                    $_wsr = $ncren_ws->get_circuits(array('interface_id' => array_keys($interfaces), 'status' => 'active'));
                    $circuits = !empty($_wsr->results) ? $_wsr->results : array();
                    $overThreshold_array[] = new ServiceUtil($service->org->name, $service->id, $service->name, $service->type->name, $circuits, $utilization_3, $service->contracted_bandwidth, $utilization_percentage, $component_ids);
                  }
                }
            }
		}
  }

    //Sort Services that are over threshold in descending order
    usort($overThreshold_array, function($a, $b){
        if ($a->util_percentage == $b->util_percentage){
            return 0;
        }
        else{
            return ($a->util_percentage > $b->util_percentage)? -1 : 1;
        }
    });


  $email_template .= '<tbody><tr><th>Utilization</th><th>Mbps</th><th>Site</th><th>Carrier (Circuit Name)</th><th>Service</th><th>Ticket</th></tr>';

  foreach($overThreshold_array as $serviceUtil){
    $email_template .= '<tr><td><a href="https://snapp.ncren.net/ncren-websvc//show-graph.cgi?collection_ids='.implode(',', $serviceUtil->get_utilization_component_ids())."&start=$utilization_start&end=$utilization_end\">". percent_convert($serviceUtil->get_util_percentage()) . "</a></td>";
    $email_template .= '<td>'. $serviceUtil->get_contracted_bandwidth() . '</td>';
    $email_template .= '<td>'. $serviceUtil->get_org() . '</td><td>';

        foreach ($serviceUtil->get_circuits() as $circuit) {
           $email_template .= $circuit->carrier->name . ' (' . $circuit->name . ')' . '<br>';
        }

    $email_template .= '</td><a href="https://db.ncren.net/?method=service_details&service_id='.$serviceUtil->get_service_id().'">'  . $serviceUtil->get_service_name() . '</a>'. ' ('. $serviceUtil->get_type() .')' . '</td><td>';

    if (!empty($serviceUtil->tickets)) {
      foreach ($serviceUtil->get_tickets() as $ticket) {
          $email_template .= 'Ticket ' . '<a href="https://footprints.mcnc.org/MRcgi/MRlogin.pl?DL='. $ticket->id .'DA36">'. $ticket->id . '</a>';
            foreach($ticket->ncren_circuit_names as $ticketCircuit){
                $email_template .=  ' (' . $ticketCircuit. ')';
            }
          $email_template .= '-- ' . strtoupper($ticket->status) . ' - ' . $ticket->title;
      }
    }

    $email_template .= "</td></tr>\n";
  }
}

//convert utility percentage into a percentage for reporting
function percent_convert($string){
    $string = (float)$string * 100;
    $string = number_format($string, 1);
    return $string . '%';
}

//Get SNAPP component IDs for a set of interfaces
function get_component_ids ($interfaces) {
  global $ncren_ws;

  NCRENWS::arrayify($interfaces);

  $node_interfaces = $component_ids = array();
  if (!empty($interfaces)) {
    foreach ($interfaces as $interface) {
      if($interface->node->name){
            $node_interfaces[] = "{$interface->node->name}--{$interface->name}";
      }
    }
    $wsr = $ncren_ws->get_utilization_components(array('component_name' => $node_interfaces));
    if (!empty($wsr->results)) {
      foreach ($wsr->results as $component) {
        $component_ids[] = $component->id;
      }
    }
  }

  return $component_ids;
}

//Get utilization for interface component_ids
function get_utilization ($component_ids) {
  global $ncren_ws, $utilization_start, $utilization_end;

  if (!empty($component_ids)) {
    $wsr = $ncren_ws->get_utilization_data(array(
      'collection_id' => $component_ids,
      'start' => $utilization_start,
      'end' => $utilization_end,
    ));
    return isset($wsr->results[0]->ninetyfifth) ? $wsr->results[0]->ninetyfifth/1000000 : 0;
  }
  return 0;
}

//Create HTML Headers
$headers  = 'MIME-Version: 1.0' . "\r\n";
$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
$headers .= 'From: '.$from."\r\n".
    'Reply-To: '.$from."\r\n" .
    'X-Mailer: PHP/' . phpversion();

// HTML Email message template
$message = '<html><body>';
$message .= '<p> Utilization (95th percentiles) for the following services exceeded '. THRESHOLD * 100 . "% of their NCREN contracted bandwidth over the 30-day period from " . date('m/d/Y H:i:s', $utilization_start) . " through " . date('m/d/Y H:i:s', $utilization_end) . ".</p>" ;
$message .= '<table border = "1" cellpadding = "2" style="border-collapse: collapse">'. $email_template . '</table>';
$message .= '</body></html>';

// Sending email
if(mail($to, $subject, $message, $headers)){
    echo 'Your thresholding report has been sent successfully.';
} else{
    echo 'Unable to send report. Please try again.';
}


class ServiceUtil{

	private $org, $service_id, $service_name, $type, $circuits, $tickets, $utilization, $contracted_bandwidth, $utilization_component_ids;
    public $util_percentage;

	public function __construct($org, $service_id, $service_name, $type, $circuits, $utilization, $bandwidth, $util_percentage, $utilization_component_ids) {
    $this->org = $org;
    $this->service_id = $service_id;
    $this->service_name = $service_name;
    $this->type = $type;
    $this->circuits = $circuits;
    $this->utilization = $utilization;
    $this->contracted_bandwidth = $bandwidth;
    $this->util_percentage = $util_percentage;
    $this->utilization_component_ids = $utilization_component_ids;

    $this->set_circuit_tickets();
  }

  //get tickets for circuits
  private function set_circuit_tickets () {
    global $ncren_ws;

    if (!empty($this->circuits)) {
      $circuit_names = array();
      foreach ($this->circuits as $circuit) $circuit_names[] = $circuit->name;

      $_wsr = $ncren_ws->get_tickets(array('project_id' => 36, 'status_not' => 'Closed', 'circuit_name' => $circuit_names));
      $this->tickets = !empty($_wsr->results) ? $_wsr->results : array();
    } else $this->tickets = array();
  }

  public function get_org(){
    return $this->org;
  }

  public function get_service_id(){
    return $this->service_id;
  }

  public function get_service_name(){
    return $this->service_name;
  }

  public function get_type(){
    return $this->type;
  }

  public function get_circuits(){
    return $this->circuits;
  }

  public function get_utilization(){
    return $this->utilization;
  }

  public function get_util_percentage(){
    return $this->util_percentage;
  }

  public function get_utilization_component_ids(){
    return $this->utilization_component_ids;
  }

  public function get_contracted_bandwidth(){
    return $this->contracted_bandwidth;
  }

}
?>
