<?php


namespace model;


use que\database\model\Model;

class Notification extends Model
{
    protected array $appends = ['user'];
    protected array $copy = ['created_at' => 'date_time'];
    protected array $casts = ['title,message,image' => 'string', 'date_time' => 'time_ago'];

    public function getUser() {
        return $this->belongTo('users', 'user_id', 'id', 'userModel');
    }

    public static function create(string $title, string $message, string $activity, int $userID,
                                  \que\database\interfaces\model\Model $payload, string $image = null) {

        $payload->refresh();

        db()->insert('notifications', [
            'title' => $title,
            'message' => $message,
            'image' => $image,
            'activity' => $activity,
            'user_id' => $userID,
            'payload' => $payload->getArray()
        ]);
    }
}