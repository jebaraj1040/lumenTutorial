<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ExportData extends Mailable
{
    use Queueable, SerializesModels;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $urls;
    public $name;
    public $module;
    public $subject;
    public $payLoad;
    public function __construct($payLoad, $urls, $name, $module, $subject)
    {
        $this->urls         = $urls;
        $this->name         = $name;
        $this->module       = $module;
        $this->subject       = $subject;
        $this->payLoad       = $payLoad;
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        return $this->view('emails.exportdata')->subject($this->subject);
    }
}
