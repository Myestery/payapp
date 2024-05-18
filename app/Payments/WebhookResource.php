<?php

namespace App\Payments;

use App\Jobs\WebhookProcess;

class WebhookResource
{
    public function __construct(public $event, public $data)
    {
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'data' => $this->data
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public static function fromArray(array $data): self
    {
        return new static($data['event'], $data['data']);
    }

    public function process(string $gatewayId){
        WebhookProcess::dispatch($this, $gatewayId);
    }
}
