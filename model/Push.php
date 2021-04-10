<?php


namespace model;


use JetBrains\PhpStorm\Pure;

class Push
{
    // push message title
    private string $title = '';
    private string $message = '';
    private string $image = '';
    // push message payload
    private array $payload = [];

    // flag indicating whether to show the push
    // notification or not
    // this flag will be useful when perform some operation
    // in background when push is received
    private bool $is_background = false;

    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    public function setImage(string $imageUrl)
    {
        $this->image = $imageUrl;
    }

    public function setPayload(array $data)
    {
        $this->payload = $data;
    }

    public function setIsBackground(bool $is_background)
    {
        $this->is_background = $is_background;
    }

    #[Pure] public function getData(): array
    {
        $res = array();
        $res['title'] = $this->title;
        $res['is_background'] = $this->is_background;
        $res['message'] = $this->message;
        $res['image'] = $this->image;
        $res['payload'] = (object) $this->payload;
        $res['timestamp'] = date(DATE_FORMAT_MYSQL);
        return $res;
    }
}