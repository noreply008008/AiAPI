<?php




if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $raw = file_get_contents('php://input');

// for raw conversion

        // disabled | on json request
        //      $data = json_decode($raw);

        // on url encoded request

        //      $data =  urldecode($raw);
                parse_str(urldecode($raw), $data);
                $info = json_decode($data["dimensionsOriginal"], true);



// disabled | for no conversion

        //      $data = $raw

// for extracting alibaba alert to itop template format

//  disabled authentication due to no custom parameter on alibaba cloud monitor webhook
//        $authkey = $data->authentication;
//                if ($authkey != 'Alibaba Cloud') {
//                        echo "Invalid authentication";
//                        return;
//                }


        $alert = $data["alertName"];
        $state = $data["alertState"];
        $prevstat = $data["preTriggerLevel"];
        $details = $data["instanceInfo"];
        $instanceID = $info["instanceId"];
        $instancename = $data["instanceName"];
        $status = $data["triggerLevel"];

// for not creating ticket if the alert is cleared

        if ($state == 'OK') {
                echo "Alert is cleared, no need to create ticket";
                return;
        }

// for not creating ticket if the alert only changed state

        if ($prevstat != 'null') {
                echo "Alert already created";
                return;
        }


// preparing template based on itop API  request format and configuration

        define("caller_id", [
                'name'=>'OG-Alibaba Cloud Monitor',
                'first_name'=>'OG-Alibaba'
               ]
              );
        $fields = array(
                'origin' => 'monitoring',
                'service_id' => '180',
                'servicesubcategory_id' => '418',
                'impact' => '2',
                'urgency' => '4',
                'org_id'=>'SELECT Organization WHERE name = "Site24x7_Cloud-Monitoring_Service"',
                'caller_id'=> caller_id,
                'title'=> $instancename.' | '.$alert.' Alert | '.$status,
                'description'=> $instanceID.' | '.$status.' | '.$details,
                );
        $payload = array(
                'operation'=>'core/create',
                'comment'=>'Created using AiAPI',
                'class'=>'Incident',
                'output_fields'=>'id, friendlyname',
                'fields'=>$fields
                );

// for testing purposes
define("testload", [
        'operation' => 'list_operations'
        ]
       );

//encoding payload to json structure
$convert = json_encode($payload);


// Specify the URL and data based on itop request format
define("url", "http://8.212.130.178/itop/web/webservices/rest.php");
$itopdata = [
        'auth_token' => 'QXh5eWVhTyt6NnlDWnRpS216OHdNclVLR1dIK0UwMys3ZnZJVGRqU0FlSW9iT283dkVvRkJNbjUwYTlsUkFBbkJ5T1hibEhKNTBwUVBJZC9yVVE9',
        'version' => '1.4',
        'json_data' => $convert
        ];

//Start sending data to itop API
// Initialize cURL session
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($itopdata));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Execute cURL session
$response = curl_exec($ch);

// Check for cURL errors
if ($response === false) {
    die('Error occurred while fetching the data: '
        . curl_error($ch));
}

// Close cURL session
curl_close($ch);

// Display the response
echo $response;

unset($raw);
unset($data);
unset($fields);
unset($payload);
unset($convert);
unset($itopdata);
unset($response);

        }
 else {

        echo "You should POST me!";

}

?>
