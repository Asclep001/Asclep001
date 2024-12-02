<?php
require ('../vendor/autoload.php'); // Include Composer's autoload file

use GeminiAPI\Client; 
use GeminiAPI\Resources\Parts\TextPart;

// Get the input from the request
$input = $_POST['keywords'] ?? null;

if ($input) {
    // Prepare the prompt for the GeminiAPI
    $prompt = "Suggest similar books based on the title: '$input'.";

    // Call the GeminiAPI to get suggestions
    $client = new Client('AIzaSyCV4k0VqPh52_bvdf1w9H78ERKiyYZtOBM'); // Replace with your actual API key
    $response = $client->generativeModel('gemini-pro')->generateContent(new TextPart($prompt));

    // Process the response to extract book details
    // This assumes the API returns a structured response. Adjust as needed.
    $suggestedBooks = []; // Initialize an array to hold similar books

    // Example processing (modify based on actual API response structure)
    // Assuming $response->text() returns a JSON string of similar books
    $suggestedBooks = json_decode($response->text(), true);

    // Return the similar books as JSON
    header('Content-Type: application/json');
    echo json_encode(['response' => $suggestedBooks]);
} else {
    // Handle the case where no input is found
    header('Content-Type: application/json', true, 400);
    echo json_encode(['error' => 'No input provided']);
}
?>