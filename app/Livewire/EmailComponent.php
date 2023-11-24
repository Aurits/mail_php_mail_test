<?php

namespace App\Livewire;

use Livewire\Component;
use PHPMailer\PHPMailer\Exception;

class EmailComponent extends Component
{
    public $emails = [];

    public function mount()
    {
        try {
            // Fetch emails from the server and populate $emails using imap functions
            $this->fetchEmails();
        } catch (Exception $e) {
            // Handle exceptions
            $this->addError('fetchEmails', 'Error fetching emails: ' . $e->getMessage());
        }
    }

    private function fetchEmails()
    {
        // Connect to the IMAP server
        $mailbox = imap_open("{webmail.mak.ac.ug:993/imap/ssl}", 'ambrose.alanda@students.mak.ac.ug', 'Gloria11111.@');

        if ($mailbox) {
            // Fetch emails
            $emails = imap_search($mailbox, 'ALL');

            if ($emails) {
                foreach ($emails as $emailId) {
                    // Fetch email details
                    $emailData = imap_fetchstructure($mailbox, $emailId);
                    // Add the email subject to the $this->emails array
                    $this->emails[] = imap_headerinfo($mailbox, $emailId)->subject;
                }
            }

            // Close the connection to the IMAP server
            imap_close($mailbox);
        } else {
            // Handle connection error
            throw new Exception('Unable to connect to the IMAP server.');
        }
    }

    public function render()
    {
        return view('livewire.email-component', ['emails' => $this->emails]);
    }
}
