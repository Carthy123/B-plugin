<?php
/**
 * Plugin Name: My Custom Payment Plugin
 * Description: A payment plugin integrating real API authentication and transaction handling.
 * Version: 1.2
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Placeholder credentials for API authentication
define('API_USERNAME', '@@@@@@@');
define('API_PASSWORD', '@@@@@@@@@');
define('API_AUTH_ENDPOINT', '@@@@@@@@@@@@@@@@@@@@@'); // Authentication endpoint
define('API_TRANSACTION_ENDPOINT', '@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@'); // Transaction endpoint
define('API_TSQ_ENDPOINT', '@@@@@@@@@@@@@@@@@@@@@@@@@@@@') ; 
// Define log file paths
define('REQUEST_LOG_FILE', '@@@@@@@@@@@@@@@@@@@@@@');
define('RESPONSE_LOG_FILE', '@@@@@@@@@@@@@@@@@@@@@@@@@@@@');

// Initialize the plugin
add_action('init', 'my_custom_payment_init');

function my_custom_payment_init() {
    // Code to initialize your plugin goes here
}

// Shortcode for the payment form
function my_custom_payment_form_shortcode() {
    ob_start();
    ?>
    <style>
        #my_custom_payment_form {
            max-width: 500px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }
        #my_custom_payment_form label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
            color: #333;
        }
        #my_custom_payment_form input[type="text"],
        #my_custom_payment_form select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        #my_custom_payment_form input[type="submit"] {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            border: none;
            background-color: #28a745;
            color: #fff;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        #my_custom_payment_form input[type="submit"]:hover {
            background-color: #218838;
        }
        .payment-channel {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .payment-channel img {
            width: 30px; 
            height: auto; 
            margin-right: 10px;
        }
        .brassica-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .brassica-logo img {
            width: 100px; /* Adjust the size as needed */
            height: auto;
        }
    </style>
    <div class="brassica-logo">
        <img src="https://cdn.prod.website-files.com/67056193a2e9d92d86e2eff2/67056193a2e9d92d86e2f071_Brassica-Icon.svg" alt="Brassica Logo">
    </div>
    <form id="my_custom_payment_form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="process_payment">
        <label>Payment Channel:</label>
        <div class="payment-channel">
            <input type="radio" id="channel_mtn" name="channel" value="MTN" required>
            <label for="channel_mtn">
                <img src="https://managingghana.com/wp-content/uploads/2024/06/MTN-Ghana-Shines-Bright-with-Nine-Glorious-Wins-at-the-2024-GITTA-Awards.webp" alt="MTN">
                MTN
            </label>
        </div>
        <div class="payment-channel">
            <input type="radio" id="channel_atmoney" name="channel" value="AT Money" required>
            <label for="channel_atmoney">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSIgmQ3mP6HHtmdKPvyS6szi952yykKNiogzQ&s" alt="AT Money">
                AT Money
            </label>
        </div>
        <div class="payment-channel">
            <input type="radio" id="channel_telecel" name="channel" value="Telecel Cash" required>
            <label for="channel_telecel">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQS-jgtHYaFeQd6EKkkQnbdwCl_Fn5KZSMsfg&s" alt="Telecel Cash">
                Telecel Cash
            </label>
        </div>
        <label for="accountNumber">Account Number:</label>
        <input type="text" name="accountNumber" required>
        <label for="accountName">Account Name:</label>
        <input type="text" name="accountName" required>
        <label for="amount">Amount:</label>
        <input type="text" name="amount" required>
        <label for="debitNaration">Narration:</label>
        <input type="text" name="debitNaration" required>
        <input type="submit" value="Pay Now">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('custom_payment_form', 'my_custom_payment_form_shortcode');

// Process the payment with real API handling
function my_custom_process_payment() {
    // Validate and sanitize form data
    if (!isset($_POST['accountNumber'], $_POST['accountName'], $_POST['amount'], $_POST['debitNaration'], $_POST['channel'])) {
        wp_die('Incomplete form submission');
    }

    $accountNumber = sanitize_text_field($_POST['accountNumber']);
    $accountName = sanitize_text_field($_POST['accountName']);
    $amount = sanitize_text_field($_POST['amount']);
    $debitNaration = sanitize_text_field($_POST['debitNaration']);
    $channel = sanitize_text_field($_POST['channel']);

    // Generate a random 8-digit transaction ID
    $transactionId = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT);

    // Institution codes based on payment channel
    $institutionCodes = array(
        'MTN' => '300591',
        'AT Money' => '300592',
        'Telecel Cash' => '300594'
    );
    $institutionCode = isset($institutionCodes[$channel]) ? $institutionCodes[$channel] : '0000';

    // Step 1: Authenticate and get bearer token
    $auth_request_body = json_encode(array('username' => API_USERNAME, 'password' => API_PASSWORD));
    
    // Log the authentication request
    file_put_contents(REQUEST_LOG_FILE, date('Y-m-d H:i:s') . " - Authentication Request: $auth_request_body\n", FILE_APPEND);

    $auth_response = wp_remote_post(API_AUTH_ENDPOINT, array(
        'method' => 'POST',
        'body' => $auth_request_body,
        'headers' => array('Content-Type' => 'application/json'),
    ));

    // Log only the authentication response body
    $auth_response_body = wp_remote_retrieve_body($auth_response);
    file_put_contents(RESPONSE_LOG_FILE, date('Y-m-d H:i:s') . " - Authentication Response Body: $auth_response_body\n", FILE_APPEND);

    if (is_wp_error($auth_response) || wp_remote_retrieve_response_code($auth_response) !== 200) {
        wp_die('Authentication failed.');
    }

    $auth_body = json_decode($auth_response_body, true);
    $token = isset($auth_body['data']['resp1']) ? $auth_body['data']['resp1'] : null;

    if (is_null($token)) {
        wp_die('Token not found in authentication response.');
    }

    // Step 2: Send payment request
    $payment_request_body = json_encode(array(
        'transactionId' => $transactionId,
        'institutionCode' => $institutionCode,
        'accountNumber' => $accountNumber,
        'accountName' => $accountName,
        'amount' => $amount,
        'debitNaration' => $debitNaration,
        'serviceType' => 'test',
        'channel' => 'mno'
    ));

    // Log the payment request
    file_put_contents(REQUEST_LOG_FILE, date('Y-m-d H:i:s') . " - Payment Request: $payment_request_body\n", FILE_APPEND);

    $payment_response = wp_remote_post(API_TRANSACTION_ENDPOINT, array(
        'method' => 'POST',
        'body' => $payment_request_body,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token
        ),
    ));

    // Log only the payment response body
    $payment_response_body = wp_remote_retrieve_body($payment_response);
    file_put_contents(RESPONSE_LOG_FILE, date('Y-m-d H:i:s') . " - Payment Response Body: $payment_response_body\n", FILE_APPEND);

    // Handle payment response as needed
     // Parse the response to handle the status code
    $payment_response_data = json_decode($payment_response_body, true);

    if (isset($payment_response_data['statusCode']) && $payment_response_data['statusCode'] === "202") {
        // Display message if status code is 202
        echo "<p>Payment is being processed.</p>";
    } else {
        // Display a generic error message or handle other status codes
        echo "<p>There was an issue processing your payment. Please try again later.</p>";
    }

  // After handling the payment response
 check_transaction_status($transactionId, $token);



	// Check transaction status
	function check_transaction_status($transaction_id, $token) {
		$max_attempts = 4;
		$attempts = 0;
	
		do {
			sleep(5);
	
			$status_request = [
				'transactionType' => 'debit',
				'transactionId' => $transaction_id
			];
	
			// Log only the status check request body
			file_put_contents(RESPONSE_LOG_FILE, date('Y-m-d H:i:s') . " - Status Check Request: " . json_encode($status_request) . "\n", FILE_APPEND);
	
			$response = wp_remote_post(API_TSQ_ENDPOINT, [
				'body' => json_encode($status_request),
				'headers' => [
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . $token
				],
			]);
	
			if (is_wp_error($response)) {


				// Log the error if status check fails
				file_put_contents(RESPONSE_LOG_FILE, date('Y-m-d H:i:s') . " - Status Check Error: " . $response->get_error_message() . "\n", FILE_APPEND);
				continue;
			}
	
			$body = json_decode(wp_remote_retrieve_body($response), true);
			
			  
                // Log the status check response body for both successful and unsuccessful attempts
                file_put_contents(RESPONSE_LOG_FILE, date('Y-m-d H:i:s') . " - Status Check Response Body: " . json_encode($body) . "\n", FILE_APPEND);
 

			// Handle the response and check for statusCode 200
			if (isset($body['statusCode']) && $body['statusCode'] === "200") {
				echo "<p>Payment successful!</p>";
				return;
			} else {
				echo "<p>Waiting for payment confirmation...</p>";
			}
	
			$attempts++;
	
		} while ($attempts < $max_attempts);
	
		echo "<p>Payment confirmation could not be obtained after multiple attempts. Please check your transaction status.</p>";
	}
		
}

// Register the process payment action
add_action('admin_post_process_payment', 'my_custom_process_payment');
add_action('admin_post_nopriv_process_payment', 'my_custom_process_payment');


