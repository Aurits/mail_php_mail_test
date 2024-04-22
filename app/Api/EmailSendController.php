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
            $fromEmail = $request->input('from_email');
            $fromName = $request->input('from_name');
            $smtpUsername = $request->input('smtp_username');
            $smtpPassword = $request->input('smtp_password');

            // Static SMTP configuration
            $smtpHost = 'webmail.mak.ac.ug';
            $smtpSecure = 'tls';
            $smtpPort = 587;

            // Initialize PHPMailer
            $mail = new PHPMailer(true);

            // SMTP configuration
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = $smtpSecure;
            $mail->Port = $smtpPort;

            // Sender and recipient
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            // Email content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;

            // Send the email
            $mail->send();

            // Return success response
            return response()->json(['message' => 'Email sent successfully!']);
        } catch (Exception $e) {
            // Return error response if an exception occurs
            return response()->json(['error' => 'Error sending email: ' . $e->getMessage()], 500);
        }
    }
}
