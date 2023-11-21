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
                            'from' => imap_headerinfo($mailbox, $emailId)->fromaddress,
                            'to' => imap_headerinfo($mailbox, $emailId)->toaddress,
                            'reply_to' => imap_headerinfo($mailbox, $emailId)->reply_toaddress,
                            'date' => date('Y-m-d H:i:s', strtotime(imap_headerinfo($mailbox, $emailId)->date)),
                            'subject' => imap_headerinfo($mailbox, $emailId)->subject,
                            'message' => $this->getBody($mailbox, $emailId, $emailDetails),
                            //'message' => imap_body($mailbox, $emailId),
                            'attachments' => $this->getAttachments($mailbox, $emailId, $emailDetails),
                            // Add other email details as needed
                        ];
                    }
                }

                // Close the connection to the IMAP server
                imap_close($mailbox);

                // Return the email data as JSON
                return response()->json(['emails' => $emailData]);
            } else {
                // Handle connection error
                throw new Exception('Unable to connect to the IMAP server.');
            }
        } catch (Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'Error fetching emails: ' . $e->getMessage()], 500);
        }
    }

    // Modify the getBody function
    private function getBody($mailbox, $emailId, $emailDetails)
    {
        // Initialize the body variable
        $body = '';

        // Check if the email has multiple parts (MIME)
        if ($emailDetails->type === 1) {
            // Loop through each part of the email
            foreach ($emailDetails->parts as $partId => $part) {
                // Check if the part is plain text
                if ($part->subtype === 'PLAIN') {
                    // Fetch the plain text part
                    $body = imap_fetchbody($mailbox, $emailId, $partId + 1);

                    // Decode the content if needed (depends on the encoding)
                    $body = $this->decodeContent($body, $part->encoding);

                    // Break the loop after finding the plain text part
                    break;
                }
            }
        } else {
            // Fetch the body for non-MIME emails
            $body = imap_body($mailbox, $emailId);
        }

        // Remove unwanted characters or formatting if needed

        return $body;
    }

    // Add a function to decode content if needed
    private function decodeContent($content, $encoding)
    {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                // No decoding needed for 7BIT and 8BIT
                return $content;
            case 2: // BINARY
                // May need decoding depending on the content
                return $content;
            case 3: // BASE64
                // Decode BASE64-encoded content
                return base64_decode($content);
            case 4: // QUOTED-PRINTABLE
                // Decode Quoted-Printable content
                return quoted_printable_decode($content);
            case 5: // OTHER
                // You may need to handle other encodings based on your requirements
                return $content;
            default:
                return $content;
        }
    }


    private function getAttachments($mailbox, $emailId, $emailDetails)
    {
        // Initialize the attachments array
        $attachments = [];

        // Check if the email has multiple parts (MIME)
        if ($emailDetails->type === 1) {
            // Loop through each part of the email
            foreach ($emailDetails->parts as $partId => $part) {
                // Check if the part has a filename (indicating an attachment)
                if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
                    // Fetch the attachment
                    $attachment = [
                        'filename' => $part->dparameters[0]->value,
                        'content' => imap_fetchbody($mailbox, $emailId, $partId + 1),
                    ];

                    // Add the attachment to the attachments array
                    $attachments[] = $attachment;
                }
            }
        }

        return $attachments;
    }
}
