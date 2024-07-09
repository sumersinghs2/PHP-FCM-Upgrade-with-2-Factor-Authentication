<?php
/*
* $user_token : A Array of the user tokens
* $notification_title : Title text for the push notification
* $notification_message : Message for the push notification
* $$slug : Slug for the push notification
*/
function sendPushNotification($user_token, $notification_title, $notification_message,$slug)
{
	
	$token = $user_token;
	$title = $notification_title;
	$message = $notification_message;

	foreach($token as $tok){
		$fields = array(
			'message' => array(
				'token' => $tok,
				'notification' => array(
					'title' => $title,
					'body' => $message,					
				),
				'data'=>array(
					'title' =>$title, 
					'body'  =>$message,
					'slug' => $slug
				),
				'android' => array(
					'priority' => 'high', // Priority for Android notifications
				),
				'apns' => array(
					'payload' => array(
						'aps' => array(
							'alert' => array(
								'title' => $title,
								'body' => $message,
							),
							'badge' => 3, // Badge count for iOS notifications							
							'vibrate' => true,
							'sound' => 'default',
							'largeIcon' => 'large_icon',
							'smallIcon' => 'small_icon',
							'tickerText' => 'Ticker text here...Ticker text here...Ticker text here',
							'content_available'=>true,
						),
					),
				),
			),
		);

		$url = "https://fcm.googleapis.com/v1/projects/project-code/messages:send";

		$headers = array(
			'Authorization: Bearer ' . $this->getFirebaseAccessToken(), // Use a function to get OAuth 2.0 token
			"Content-Type: application/json"

		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
		$result = curl_exec($ch);
		if ($result === FALSE) {
			die("Curl failed: ".curl_error($ch));
		}	
							
		return $result;
	}
}

/**
* Function to get the OAuth 2.0 Bearer Token for Firebase
*/
function getFirebaseAccessToken() {
	// Path to your service account JSON file
	$serviceAccountFile = "/json/project-firebase.json";		

	// Authenticate with Google APIs using the service account
	$credentials = json_decode(file_get_contents($serviceAccountFile), true);		

	// Construct the JWT
	$now = time();
	$payload = array(
		"iss" => $credentials['client_email'],
		"scope" => "https://www.googleapis.com/auth/cloud-platform",
		"aud" => "https://oauth2.googleapis.com/token",
		"iat" => $now,
		"exp" => $now + 3600 // Token valid for 1 hour
	);

	// Encode the JWT with the private key
	$jwt = $this->encodeJWT($payload, $credentials['private_key']);

	// Get the access token using the JWT
	$tokenUrl = "https://oauth2.googleapis.com/token";
	$tokenResponse = $this->makePostRequest($tokenUrl, array(
		"grant_type" => "urn:ietf:params:oauth:grant-type:jwt-bearer",
		"assertion" => $jwt
	));

	return $tokenResponse['access_token'];
}


/**
 * Function to encode a JWT
 */
function encodeJWT($payload, $privateKey) {
	// Create the JWT header
	$header = array("alg" => "RS256", "typ" => "JWT");

	// Encode header and payload to base64url
	$base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
	$base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

	// Create the signature
	$signature = '';
	openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
	$base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

	// Return the complete JWT
	return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

/**
 * Function to make a POST request and return the response
 */
function makePostRequest($url, $data) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	$result = curl_exec($ch);
	if ($result === FALSE) {
		die("Curl failed: " . curl_error($ch));
	}
	curl_close($ch);
	return json_decode($result, true);
}

