<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly JobApplication $application,
        private readonly string $previousStatus,
        private readonly string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'application_status_changed',
            'application_id' => $this->application->id,
            'job_id' => $this->application->job_post_id,
            'job_title' => $this->application->jobPost->title,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your application status changed')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your application for '.$this->application->jobPost->title.' moved from '.$this->previousStatus.' to '.$this->newStatus.'.')
            ->line('Sign in to your account to view the full application history.');
    }
}
