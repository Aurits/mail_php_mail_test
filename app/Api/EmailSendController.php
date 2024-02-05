<?php

namespace App\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailSendController extends Controller
{
    public function sendEmail(Request $request)
    {
        try {
            // Retrieve email details from the request
            $to = $request->input('to');
            $subject = $request->input('subject');
            $message = $request->input('message');

            // Initialize PHPMailer
            $mail = new PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'smtp.webmail.mak.ac.ug'; // Your SMTP server
            $mail->SMTPAuth = true;
            $mail->Username = 'ambrose.alanda@students.mak.ac.ug'; // Your SMTP username
            $mail->Password = 'Gloria11111.@'; // Your SMTP password
            $mail->SMTPSecure = 'tls'; // TLS encryption
            $mail->Port = 587; // SMTP port (usually 587 for TLS)

            // Sender and recipient
            $mail->setFrom('your_email@example.com', 'Your Name'); // Sender email and name
            $mail->addAddress($to); // Recipient email

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            // Send the email
            $mail->send();

            // Return success response
            return response()->json(['message' => 'Email sent successfully']);
        } catch (Exception $e) {
            // Return error response if an exception occurs
            return response()->json(['error' => 'Error sending email: ' . $e->getMessage()], 500);
        }
    }
}
