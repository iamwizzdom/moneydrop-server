<?php


namespace model;


use que\database\model\Model;

class Review extends Model
{
    protected array $copy = ['created_at' => 'date,date_formatted'];
    protected array $casts = ['date' => 'date:d/m/y', 'date_formatted' => "date:jS M 'y"];

    public function getApplication() {
        return $this->belongTo('loan_applications', 'application_id', 'uuid', 'loanApplicationModel');
    }

    public function getUser() {
        return $this->belongTo('users', 'user_id', 'uuid', 'userModel');
    }

    public function getReviewer() {
        return $this->belongTo('users', 'reviewed_by', 'id', 'userModel');
    }
}