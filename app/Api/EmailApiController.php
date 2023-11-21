<?php

namespace App\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPMailer\PHPMailer\Exception;

class EmailApiController extends Controller
{
    public function fetchEmails()
    {
        try {
            // Connect to the IMAP server
            $mailbox = imap_open("{webmail.mak.ac.ug:993/imap/ssl}INBOX", 'ambrose.alanda@students.mak.ac.ug', 'Gloria11111.@');

            if ($mailbox) {
                // Fetch emails
                $emails = imap_search($mailbox, 'ALL');
                $emailData = [];

                if ($emails) {
                    foreach ($emails as $emailId) {
                        // Fetch email details
                        $emailDetails = imap_fetchstructure($mailbox, $emailId);

                        // Add email details to the array
                        $emailData[] = [
                            'subject' => imap_headerinfo($mailbox, $emailId)->subject,
                            'message' => imap_body($mailbox, $emailId),
                            // Add other email details as needed
                        ];
                    }
                }

                // Close the connection to the IMAP server
                imap_close($mailbox);

                // Return the email data as JSON
                return response()->json($emailData);
            } else {
                // Handle connection error
                throw new Exception('Unable to connect to the IMAP server.');
            }
        } catch (Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'Error fetching emails: ' . $e->getMessage()], 500);
        }
    }
}
