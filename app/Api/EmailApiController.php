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
            // Set the default time zone
            date_default_timezone_set('Africa/Nairobi');

            // Connect to the IMAP server
            $mailbox = imap_open("{webmail.mak.ac.ug:993/imap/ssl}INBOX", 'ambrose.alanda@students.mak.ac.ug', 'Gloria11111.@');


            if ($mailbox) {
                // Fetch emails and sort by date
                $emails = imap_search($mailbox, 'ALL');
                rsort($emails);

                $emailData = [];

                if ($emails) {
                    foreach ($emails as $emailId) {
                        // Fetch email details
                        $emailDetails = imap_fetchstructure($mailbox, $emailId);

                        // Fetch additional headers, including Message-ID
                        $headers = imap_headerinfo($mailbox, $emailId);

                        // Extract Message-ID
                        $messageId = $headers->message_id;

                        // Add email details to the array using Message-ID as a key
                        $emailData[$messageId] = [
                            'from' => $this->convertToUTF8($headers->fromaddress),
                            'to' => $this->convertToUTF8($headers->toaddress),
                            'reply_to' => $this->convertToUTF8($headers->reply_toaddress),
                            'date' => date('Y-m-d H:i:s', strtotime($headers->date)),
                            'subject' => $this->convertToUTF8($headers->subject),
                            'message' => $this->getBody($mailbox, $emailId, $emailDetails),
                            'attachments' => $this->getAttachments($mailbox, $emailId, $emailDetails),
                            // Add other email details as needed
                        ];
                    }
                }

                // Close the connection to the IMAP server
                imap_close($mailbox);

                // Convert all strings in $emailData to UTF-8
                // $emailData = array_map([$this, 'convertToUTF8Recursive'], $emailData);

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

    private function getBody($mailbox, $emailId, $emailDetails)
    {
        // Initialize the body variable
        $body = '';

        // Check if the email has multiple parts (MIME)
        if ($emailDetails->type === 1) {
            // Fetch the HTML and plain text parts if available
            $htmlPart = $this->getBodyAlternative($mailbox, $emailId, $emailDetails, 'TEXT/HTML');
            $plainPart = $this->getBodyAlternative($mailbox, $emailId, $emailDetails, 'TEXT/PLAIN');

            // Prioritize HTML over plain text
            $body = !empty($htmlPart) ? $htmlPart : $plainPart;
        } else {
            // Fetch the body for non-MIME emails
            $body = imap_body($mailbox, $emailId);
        }

        // Remove unwanted characters or formatting if needed

        return $body;
    }
    // Add this method to convert a string or array to UTF-8
    private function convertToUTF8Recursive($item)
    {
        if (is_array($item)) {
            return array_map([$this, 'convertToUTF8Recursive'], $item);
        } else {
            return mb_convert_encoding($item, 'UTF-8', mb_detect_encoding($item, 'UTF-8, ISO-8859-1', true));
        }
    }

    private function convertToUTF8($string)
    {
        return mb_convert_encoding($string, 'UTF-8', mb_detect_encoding($string));
    }

    private function getBodyAlternative($mailbox, $emailId, $emailDetails, $mimetype)
    {
        // Initialize the body variable
        $body = '';

        // Fetch the body using the alternative method
        $body = $this->get_part($mailbox, $emailId, $mimetype, $emailDetails);

        return $body;
    }

    private function get_part($mailbox, $uid, $mimetype, $structure = false, $partNumber = false)
    {
        if (!$structure) {
            $structure = imap_fetchstructure($mailbox, $uid, FT_UID);
        }
        if ($structure) {
            if ($mimetype == $this->get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($mailbox, $uid, $partNumber, FT_UID);
                switch ($structure->encoding) {
                    case 3:
                        return imap_base64($text);
                    case 4:
                        return imap_qprint($text);
                    default:
                        return $text;
                }
            }

            // multipart
            if ($structure->type == 1) {
                foreach ($structure->parts as $index => $subStruct) {
                    $prefix = "";
                    if ($partNumber) {
                        $prefix = $partNumber . ".";
                    }
                    $data = $this->get_part($mailbox, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
                    if ($data) {
                        return $data;
                    }
                }
            }
        }
        return false;
    }

    private function get_mime_type($structure)
    {
        $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

        if ($structure->subtype) {
            return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
        }
        return "TEXT/PLAIN";
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
