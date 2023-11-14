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
        $mail->Host = 'your-imap-server.com';
        $mail->Port = 993;
        $mail->SMTPSecure = 'tls'; // STARTTLS
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-email-password';

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
    }
}


    public function render()
    {
        return view('livewire.email-component', ['emails' => $this->emails]);
    }
}
