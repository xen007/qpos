<?php
namespace App\Mail;
use App\Models\User;
use App\Support\NotificationCatalog;
use Illuminate\Mail\Mailable;
final class OperationalAlertMail extends Mailable {
    public function __construct(public object $event,public User $recipient,string $language){
        $this->locale(in_array($language,['fr','en'],true)?$language:'fr');
    }
    public function build(): static{
        return $this->subject(__('notifications.types.'.$this->event->kind).' · QPOS')
            ->view('backend.notifications.email',['title'=>__('notifications.types.'.$this->event->kind),'shop'=>$this->event->point_of_sale_id,
                'reference'=>$this->event->source_id,'link'=>NotificationCatalog::link($this->event,$this->recipient)])
            ->withSymfonyMessage(function($message){
                $message->getHeaders()->addIdHeader('Message-ID','qpos-alert-'.$this->event->id.'-'.hash('sha256',json_encode($this->to)).'@qpos.local');
            });
    }
}
