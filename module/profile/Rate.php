<?php


namespace module\profile;


use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;

class Rate extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.

        try {

            if (!$input->validate('rate')->isFloatingNumber())
                throw $this->baseException("Rate must be numeric", "Rating Failed", HTTP::EXPECTATION_FAILED);

            if (!$input->validate('rate')->isNumberBetween(.1, 5))
                throw $this->baseException("Rate must be between 1 and 5", "Rating Failed", HTTP::EXPECTATION_FAILED);

            if (!$input->validate('id')->isUUID())
                throw $this->baseException("User ID must be a UUID", "Rating Failed", HTTP::EXPECTATION_FAILED);

            if ($input->validate('id')->isEqual($this->user('uuid')))
                throw $this->baseException("Sorry, you can't rate yourself", "Rating Failed", HTTP::EXPECTATION_FAILED);

            $user = $this->db()->find('users', $input['id'], 'uuid');

            if (!$user->isSuccessful()) throw $this->baseException("Invalid user ID", "Rating Failed", HTTP::NOT_FOUND);

            $user->setModelKey('userModel');
            $user = $user->getFirstWithModel();

            $rating = $this->db()->find('ratings', $user->getInt('id'), 'user_id', function (Builder $builder) {
                $builder->where('rated_by', $this->user('id'));
                $builder->where('is_active', true);
            });

            if ($rating->isSuccessful()) {
                $rating = $rating->getFirstWithModel();
                $status = $rating->update(['rating' => $input['rate']])->isSuccessful();
            } else {
                $rating = $this->db()->insert('ratings', [
                    'rating' => $input['rate'],
                    'user_id' => $user->getInt('id'),
                    'rated_by' => $this->user('id'),
                    'is_active' => true
                ]);
                $status = $rating->isSuccessful();
            }

            $input['rate'] = (fmod($input['rate'], 1) == 0)  ? floor($input['rate']) : $input['rate'];

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Rating ' . ($status ? 'Successful' : 'Failed'),
                'message' => $status ? "Thank you for giving {$user->getValue('firstname')} {$input['rate']} star." : "Failed to give {$input['rate']} star",
                'response' => []
            ]);

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object) []
            ], $e->getCode());
        }
    }
}