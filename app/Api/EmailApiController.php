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
            date_default_timezone_set('Africa/Nairobi');

            $request = Request::createFromGlobals();

            // Retrieve username and password from the request
            $username = $request->input('username');
            $password = $request->input('password');

            // Connect to the IMAP server
            $mailbox = imap_open("{webmail.mak.ac.ug:993/imap/ssl}INBOX", $username, $password, OP_READONLY);

            if ($mailbox) {
                // Fetch both seen and unseen emails
                $seenEmails = imap_search($mailbox, 'SEEN');
                $unseenEmails = imap_search($mailbox, 'UNSEEN');

                // Check if both arrays are not empty before merging
                if (!empty($seenEmails) && !empty($unseenEmails)) {
                    // Combine seen and unseen emails into a single array
                    $emails = array_merge($seenEmails, $unseenEmails);
                } elseif (!empty($seenEmails && empty($unsennEmails))) {
                    // If only seen emails exist, set $emails to $seenEmails
                    $emails = $seenEmails;
                } elseif (!empty($unseenEmails && empty($seenEmails))) {
                    // If only unseen emails exist, set $emails to $unseenEmails
                    $emails = $unseenEmails;
                } else {
                    // If both arrays are empty, set $emails to an empty array
                    $emails = [];
                }

                rsort($emails);

                // Limit the emails to 500
                $emails = array_slice($emails, 0, 250);



                $emailData = [];

                if ($emails) {
                    foreach ($emails as $emailId) {
                        // Fetch email details
                        $emailDetails = imap_fetchstructure($mailbox, $emailId);

                        if (is_array($unseenEmails)) {
                            // Determine the status of the email
                            $status = in_array($emailId, $unseenEmails) ? 'unseen' : 'seen';
                        } else {
                            // If $unseenEmails is not an array (likely false), set status to 'seen'
                            $status = 'seen';
                        }
                        // Set the 'seen' flag for each fetched email
                        if ($status === 'unseen') {

                            imap_setflag_full($mailbox, $emailId, "\\Seen");
                        }

                        // Add email details to the array
                        $emailData[] = $this->getEmailDetails($mailbox, $emailId, $emailDetails, $status);
                    }
                }

                // Close the connection to the IMAP server
                imap_close($mailbox);

                // Convert all strings in $emailData to UTF-8
                $emailData = array_map([$this, 'convertToUTF8Recursive'], $emailData);

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


    private function getEmailDetails($mailbox, $emailId, $emailDetails, $status)
    {
        // Fetch email headers
        $headers = imap_headerinfo($mailbox, $emailId);

        // Get email body
        $body = $this->getBody($mailbox, $emailId, $emailDetails);

        // Get attachments
        $attachments = $this->getAttachments($mailbox, $emailId, $emailDetails);

        // Assemble email details
        $emailDetails = [
            'id' => $emailId, // Add the ID of the message
            'from' => $headers->fromaddress,
            'to' => $headers->toaddress,
            'reply_to' => $headers->reply_toaddress,
            'date' => date('Y-m-d H:i:s', strtotime($headers->date)),
            'subject' => $headers->subject,
            'message' => $body,
            'attachments' => $attachments,
            'status' => $status, // Add the status of the email (seen or unseen)

        ];

        return $emailDetails;
    }
    //

    // Add this method to convert a string or array to UTF-8
    private function convertToUTF8Recursive($item)
    {
        if (is_array($item)) {
            return array_map([$this, 'convertToUTF8Recursive'], $item);
        } else {
            return mb_convert_encoding($item, 'UTF-8', mb_detect_encoding($item, 'UTF-8, ISO-8859-1', true));
        }
    }


    // Add this method to strip HTML tags from a string or array
    private function stripHtmlTagsRecursive($item)
    {
        if (is_array($item)) {
            return array_map([$this, 'stripHtmlTagsRecursive'], $item);
        } else {
            return strip_tags($item);
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

            // Decode HTML entities
            $body = html_entity_decode($body);

            // Ensure proper HTML structure
            //$body = '<html><head><meta charset="UTF-8"></head><body>' . $body . '</body></html>';
        } else {
            // Fetch the body for non-MIME emails
            $body = imap_body($mailbox, $emailId);
        }

        // Remove unwanted characters or formatting if needed
        $body = str_replace(["\r", "\n", "\""], '', $body);

        return $body;
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
            $structure = imap_fetchstructure($mailbox, $uid);
        }
        if ($structure) {
            if ($mimetype == $this->get_mime_type($structure)) {
                if (!$partNumber) {
                    $partNumber = 1;
                }
                $text = imap_fetchbody($mailbox, $uid, $partNumber);
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
        $attachments = null;

        if (isset($emailDetails->parts) && count($emailDetails->parts)) {
            foreach ($emailDetails->parts as $index => $part) {
                $attachment = $this->processPart($mailbox, $emailId, $part, $index + 1);

                if ($attachment) {
                    // Concatenate filename and URL and add to the attachments array
                    $attachments = $attachment['filename'] . ': ' . $attachment['url'];
                }
            }
        }

        return $attachments;
    }


    private function processPart($mailbox, $emailId, $part, $partNumber)
    {
        $attachment = [];

        if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
            $attachment['filename'] = isset($part->dparameters[0]->value) ? $part->dparameters[0]->value : 'Unknown';
            //a link at webmail to download the attachment
            $attachment['url'] = $this->getAttachmentUrl($mailbox, $emailId, $partNumber);
        }

        return $attachment;
    }

    private function getAttachmentUrl($mailbox, $emailId, $partNumber)
    {
        // Replace 'webmail.mak.ac.ug' with the actual base URL of your webmail
        $baseUrl = 'https://webmail.mak.ac.ug/';

        // Adjusting email ID by adding 2
        $adjustedEmailId = $emailId + 2;

        // Generate the link to download the attachment
        $attachmentLink = $baseUrl . '?_task=mail&_frame=1&_mbox=INBOX&_uid=' . $adjustedEmailId . '&_part=' . $partNumber . '$_action=get';

        // If you want to open the link in a new window, you can append '_extwin=1' to the URL
        $attachmentLink .= '&_extwin=1&_mimewarning=1&_embed=1';

        return $attachmentLink;
    }
}
