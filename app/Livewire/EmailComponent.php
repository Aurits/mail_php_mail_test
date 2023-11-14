<?php

namespace App\Livewire;

use Livewire\Component;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailComponent extends Component
{
    public $emails = [];

    public function mount()
    {
        // Fetch emails from the server and populate $emails
        $this->fetchEmails();
    }

    private function fetchEmails()
    {
        try {
            $mail = new PHPMailer(true);

            // Configure PHPMailer settings for IMAP with STARTTLS
            $mail->isIMAP();
            $mail->Host = 'webmail.mak.ac.ug';
            $mail->Port = 993;
            $mail->SMTPSecure = 'starttls'; // STARTTLS
            $mail->SMTPAuth = false;
            $mail->Username = 'ambrose.alanda@students.mak.ac.ug';//config('mail.imap_username'); // Use env variables or config
            $mail->Password = 'Gloria11111.@'; //config('mail.imap_password'); // Use env variables or config

            // Connect to the server
            $mail->connect();

            // Select the mailbox
            $mail->select('INBOX');

            // Search for all emails
            $emails = $mail->searchMailbox('ALL');

            // Fetch email subjects (you can customize this based on your needs)
            $this->emails = array_map(function ($email) {
                return $email->subject;
            }, $emails);
        } catch (Exception $e) {
            // Handle exceptions
            $this->addError('fetchEmails', 'Error fetching emails: ' . $e->getMessage());
        } finally {
            // Close the connection
            if (isset($mail)) {
                $mail->close();
            }
        }
    }

    public function render()
    {
        return view('livewire.email-component', ['emails' => $this->emails]);
    }
}
