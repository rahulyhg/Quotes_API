<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require_once '../include/Responce.php';

require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();
	

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password

$app->post('/register', function() use ($app) {
            // check for required params
			
			
            verifyRequiredParams(array('name', 'email', 'password', 'gcm_regid'));

            $response = array();

            // reading post params
            $name = $app->request->post('name');
            $email = $app->request->post('email');
            $password = $app->request->post('password');
			$gcm_regid = $app->request->post('gcm_regid');


            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($name, $email, $password, $gcm_regid);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
            }
            // echo json response
            echoRespnse(201, $response);
        });
 */
 
$app->post('/register', function() use ($app) {
            // check for required params
			
			
			
            verifyRequiredParams(array('fname', 'lname', 'city_id', 'phn1', 'password', 'gcm_regid'));

			$Responce = new Responce();



            // reading post params
            $fname = $app->request->post('fname');
            $lname = $app->request->post('lname');
            $city_id = $app->request->post('city_id');
			$user_type_id = $app->request->post('user_type_id');

			$lat = $app->request->post('lat');
			$lng = $app->request->post('lng');
            $phno1 = $app->request->post('phn1');
            $phno2 = $app->request->post('phn2');
			$blood_group_id = $app->request->post('blood_group_id');
			$password = $app->request->post('password');
			$gcm_regid = $app->request->post('gcm_regid');
			$email = $app->request->post('email');


            // validating email address
         //   validateEmail($email);

            $db = new DbHandler();
			
			
            $res = $db->createUser($fname, $lname, $user_type_id, $city_id, $lat, $lng, $phno1, $phno2, $blood_group_id, $email, $password, $gcm_regid);

			
		if ($res == USER_CREATE_FAILED) 
		{
                $Responce -> setError(true);
				$Responce -> setMessage("Oops! An error occurred while registereing");	
        } 
        else if ($res == USER_ALREADY_EXISTED) 
        {
				$Responce -> setError(true);
				$Responce -> setMessage("Sorry, this phone number already existed, try logging in with this phone number");	
        }
        else 
         {
                $Responce -> setError(false);
				$Responce -> setMessage("You are successfully registered, Enter OTP for activation");	
				
				$otp = rand(1000, 9999);
		
				$db->sendSms($phno1, $otp);
				
				$OTPResult = $db->createOtp($res, $otp);
				
				$user = $db->getUserByPhn($phno1);
                
//                die(print_r($user));
//
//                $userinfo = array();
//                    
//                $userinfo = array ("id" => $user['user_id'],
//                                    "fname" => $user['fname'],
//                        );

                $Responce ->setData('user', $user );
					
				
            }
			
            // echo json response
            echoRespnse(201, $Responce->setArray());
        });
		
/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phn', 'password', 'gcm_regid'));

            // reading post params
            $phn = $app->request()->post('phn');
            $password = $app->request()->post('password');
			$gcm_regid = $app->request()->post('gcm_regid');

			$Responce =  new Responce();
            $db = new DbHandler();
			$msg;
			
            // check for correct email and password
            if ($db->checkLogin($phn, $password)) {
                // get the user by email
				
				$update = $db->updateGCMID($phn,$gcm_regid) ;
				if($update)
				{
					$msg = 'GCM Updated';
					$user = $db->getUserByPhn($phn);
				}
				else
				{
					$msg = 'GCM Update Fail';
				}
				
                

                if ($user != NULL) {
					
					
				$Responce ->setMessage("Login Successful ".$msg);
                $Responce ->setError(false);
                $Responce ->setData('user', $user );
                } else {
                    // unknown error occurred
					$Responce ->setError(true);
					$Responce ->setMessage("An error occurred. Please try again");
					$Responce ->setData('user', $user );
       
                }
            } else {
                // user credentials are wrong
					$Responce ->setError(true);
					$Responce ->setMessage("Login failed. Incorrect credentials");
					

            }

            echoRespnse(200, $Responce->setArray());
        });
		
$app->post('/update_password/', 'authenticate', function() use ($app) {
	
	
		
			verifyRequiredParams(array('current_password','password'));

			$Responce = new Responce();
			

            // reading post params
            $password = $app->request->post('password');
            $current_password = $app->request->post('current_password');



            global $user_id;
			$Responce = new Responce();
            $db = new DbHandler();
			
			if($db->checkLoginById($user_id, $current_password)){
				$result = $db->updatePassword($user_id,$password);
				$user = $db->getUserByID($user_id);
				
				$Responce -> setError(false);
				$Responce -> setMessage("Your password is updated");
				$Responce -> setData("user",$user);
				
				
			}else{
			    $Responce -> setError(true);
				$Responce -> setMessage("Old password doesn't match");
			}
			

		
					
            echoRespnse(200, $Responce->setArray());


        });
		
		
$app->post('/update_password_by_phn/', 'authenticate', function() use ($app) {
	
		
			verifyRequiredParams(array('phn','password'));

			$Responce = new Responce();
			

            // reading post params
            $phn = $app->request->post('phn');
            $password = $app->request->post('password');


            global $user_id;
			$Responce = new Responce();
            $db = new DbHandler();
			
			if($db->isUserExists($phn)){
				

				$user = $db->getUserByPhn($phn);

				$user_id = $user[0]['user_id'];


				$result = $db->updatePassword($user_id,$password);
				$user = $db->getUserByID($user_id);
				
				$Responce -> setError(false);
				$Responce -> setMessage("Your password is updated");
				$Responce -> setData("user",$user);
				
				
			}else{
			    $Responce -> setError(true);
				$Responce -> setMessage("This User doesn't exist");
			}
			

		
					
            echoRespnse(200, $Responce->setArray());


        });
		
		
$app->post('/get_cities/:state_id', 'authenticate', function($state_id) {
	
	

            global $user_id;
			$Responce = new Responce();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getCities($state_id);

			$Responce -> setError(false);
			$Responce -> setMessage("Cities Are");
			$Responce -> setData("cities",$result);




            echoRespnse(200, $Responce->setArray());
        });		
		
$app->post('/update_user/', 'authenticate', function() use ($app) {
	
	
		
			verifyRequiredParams(array('fname', 'lname', 'city_id'));

			$Responce = new Responce();



            // reading post params
            $fname = $app->request->post('fname');
            $lname = $app->request->post('lname');
            $city_id = $app->request->post('city_id');
			$blood_group_id = $app->request->post('blood_group_id');
	        $phn = $app->request->post('phn');


            global $user_id;
			$Responce = new Responce();
            $db = new DbHandler();

			
			
			$result = $db->updateUser($user_id,$fname, $lname, $city_id, $blood_group_id,$phn);
			$user = $db->getUserByID($user_id);

			
		if ($result == USER_UPDATE_SUCCESSFUL ) 
		{
			    $Responce -> setError(false);
				$Responce -> setMessage("Your details are updated");
				$Responce -> setData("user",$user);
        } 
		else if ($result == USER_UPDATE_FAILED )
		{
				$Responce -> setError(true);
				$Responce -> setMessage("failed to update your details");                  
		
		}else if ($result == USER_ALREADY_EXISTED)
		{
				$Responce -> setError(true);
				$Responce -> setMessage("This number already exists");  				
		}
		

		
					
            echoRespnse(200, $Responce->setArray());


        });
		

		
		
		
$app->post('/request_blood/', 'authenticate', function() use ($app) {
	
	
		
			verifyRequiredParams(array('name','sex','age','blood_group_id','city_id','address','contact'));
           
			$name = $app->request()->post('name');
            $sex = $app->request()->post('sex');
            $age = $app->request()->post('age');
            $blood_group_id = $app->request()->post('blood_group_id');
            $city_id = $app->request()->post('city_id');
            $address = $app->request()->post('address');
            $contact = $app->request()->post('contact');
            $alt_contact = $app->request()->post('alt_contact');

            global $user_id;
			$Responce = new Responce();
            $db = new DbHandler();

			
			$task_id = $db->createBloodRequest($name, $sex, $age, $blood_group_id, $city_id, $address, $contact, $alt_contact,$user_id);

			if($task_id){
				
			$message = $name . ' requires blood' ;
			$title = 'LifeLine';
			$to_vibrate = true;
			$to_play_sound = true;
			$vibrate_intensity = '';
			$ids = $db->getGCMID('all');
			//print_r($ids);
            $result = $db->sendNotification($ids,$message, $title, $to_vibrate, $to_play_sound, $vibrate_intensity);
				
			$Responce -> setError(false);
			$Responce -> setMessage("Blood Request Successful. Donors will contact you on provided phone number");
			}else{
				$Responce -> setError(true);
			$Responce -> setMessage("Blood Requested Failed");
			}		







            echoRespnse(200, $Responce->setArray());
        });		


$app->post('/request_OTP/', 'authenticate', function() use ($app) {
	
	
		
			verifyRequiredParams(array('phn'));
           
			$phn = $app->request()->post('phn');


			$Responce = new Responce();
            $db = new DbHandler();

			$otp = rand(1000, 9999);
		
			$db->sendSms($phn, $otp);
			
			$user = $db->getUserByPhn($phn);
			
			global $user_id;

			//$user = $db->getUserByPhn($phn);
			//print_r($user);
			
			
			if($user_id == 1){
				
				
				$user_id = $user[0]['user_id'];
			}
			
			$OTPResult = $db->createOtp($user_id, $otp);
				
				
			$user = $db->getUserByPhn($phn);



			if($OTPResult){
				
				$Responce -> setData("user",$user);

				$Responce -> setError(false);
				$Responce -> setMessage("OTP requested");
			}else{
				$Responce -> setError(true);
				$Responce -> setMessage("Failed to request OTP");
			}		







            echoRespnse(200, $Responce->setArray());
        });			
		

$app->post('/request_OTP_to_update_phn/', 'authenticate', function() use ($app) {
	
	
		
			verifyRequiredParams(array('phn'));
           
			$phn = $app->request()->post('phn');

			$Responce = new Responce();
            $db = new DbHandler();
            global $user_id;

			$otp = rand(1000, 9999);
		
			$db->sendSms($phn, $otp);

			

			$OTPResult = $db->createOtp($user_id, $otp);
				
			

			if($OTPResult){
				
				$Responce -> setData("otp",$otp);

				$Responce -> setError(false);
				$Responce -> setMessage("OTP requested");
			}else{
				$Responce -> setError(true);
				$Responce -> setMessage("Failed to request OTP");
			}		







            echoRespnse(200, $Responce->setArray());
        });
		
		
$app->get('/get_blood_requests/:choice', 'authenticate', function($choice){
	
	
		
			
			global $user_id;
	
            $Responce = new Responce();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getBloodRequests($choice,$user_id) ;


            if($result){
                $Responce ->setError(false);
                $Responce ->setMessage("Blood Requests Are");
                $Responce->setData("blood_requests", $result);
            }

            
           

            echoRespnse(200, $Responce->setArray());
        });
		
		
$app->get('/close_request/:request_id/:isFulfil', 'authenticate', function($request_id,$isFulfil){
	
	
		
			
			global $user_id;
	
            $Responce = new Responce();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->closeRequest($isFulfil,$request_id);


            if($result){
                $Responce ->setError(false);
                $Responce ->setMessage("Requests Closed");
            }else{
				$Responce ->setError(false);
                $Responce ->setMessage("Failed to close request");
			}

            
           

            echoRespnse(200, $Responce->setArray());
        });
		
		
$app->get('/activate_user_status', 'authenticate', function(){
	
	
		
			
			global $user_id;
	
            $Responce = new Responce();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->activateUserStatus($user_id) ;


            if($result){
                $Responce ->setError(false);
                $Responce ->setMessage("Your account is activated");
            }else{
				$Responce ->setError(true);
                $Responce ->setMessage("Unable to update Status");
				
			}

            
           

            echoRespnse(200, $Responce->setArray());
        });
		

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all tasks of particual user
 * method GET
 * url /tasks          
 */
$app->get('/tasks', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id);

            $response["error"] = false;
            $response["tasks"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id"];
                $tmp["task"] = $task["task"];
                $tmp["status"] = $task["status"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["tasks"], $tmp);
            }

            echoRespnse(200, $response);
        });
        
        
$app->get('/get_news', 'authenticate', function() {
            global $user_id;
	
            $Responce = new Responce();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllNews();


            if($result){
                $Responce ->setError(false);
                $Responce ->setMessage("News Are");
                $Responce->setData("news", $result);
            }

            
           

            echoRespnse(200, $Responce->setArray());
        });

/**
 * Listing single task of particual user
 * method GET
 * url /tasks/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/tasks/:id', 'authenticate', function($task_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getTask($task_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["task"] = $result["task"];
                $response["status"] = $result["status"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });
		

$app->post('/send_notification/:choice', 'authenticate', function($choice) use ($app){
            // check for required params
            verifyRequiredParams(array('id','message','title'));

            $response = array();
            $id = $app->request->post('id');
            $message = $app->request->post('message');
			$title = $app->request->post('title');

            $to_vibrate = $app->request->post('to_vibrate');
			$vibrate_intensity = $app->request->post('vibrate_intensity');
            $to_play_sound = $app->request->post('to_play_sound');

            global $user_id;
			 $Responce = new Responce();
	
            $db = new DbHandler();

            // creating new task
			$ids = $db->getGCMID($choice);
			//print_r($ids);
            $result = $db->sendNotification($ids,$message, $title, $to_vibrate, $to_play_sound, $vibrate_intensity);

            if ($result != NULL) {
                $Responce ->setError(false);
                $Responce ->setMessage("");
                $Responce->setData("result", $result);
            } else {
                $Responce ->setError(false);
                $Responce ->setMessage("");
                $Responce->setData("result", $result);
            }    
            echoRespnse(200, $Responce->setArray());
			
        });

/**
 * Creating new task in db
 * method POST
 * params - name
 * url - /tasks/
 */
$app->post('/tasks', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('task'));

            $response = array();
            $task = $app->request->post('task');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $task_id = $db->createTask($user_id, $task);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Task created successfully";
                $response["task_id"] = $task_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Failed to create task. Please try again";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
  //  $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>