<?php

namespace App\Notifications;

use App\Reply;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class YouWereMentioned extends Notification
{
    use Queueable;

    /**
     * @var string
     */
    protected $subjectTitle;

    /**
     * @var \App\User
     */
    protected $subjectOwner;

    /**
     * @var string
     */
    protected $subjectPath;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Reply|\App\Thread  $subject
     * @return void
     * @throws \Exception
     */
    public function __construct($subject)
    {
        if ($subject instanceof Reply) {
            $this->subjectTitle = $subject->thread->title;
            $this->subjectOwner = $subject->owner;
        } elseif ($subject instanceof Thread) {
            $this->subjectTitle = $subject->title;
            $this->subjectOwner = $subject->creator;
        } else {
            throw new \Exception('Unhandled model passed.');
        }

        $this->subjectPath = $subject->path();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database'];
    }

    // /**
    //  * Get the mail representation of the notification.
    //  *
    //  * @param  mixed  $notifiable
    //  * @return \Illuminate\Notifications\Messages\MailMessage
    //  */
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //                 ->line('The introduction to the notification.')
    //                 ->action('Notification Action', url('/'))
    //                 ->line('Thank you for using our application!');
    // }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => $this->message(),
            'notifier' => $this->subjectOwner,
            'link' => $this->subjectPath,
        ];
    }

    /**
     * Get a message string for the notification.
     */
    public function message()
    {
        return sprintf('%s t\'a mentionné dans "%s"', $this->subjectOwner->name, $this->subjectTitle);
    }
}
