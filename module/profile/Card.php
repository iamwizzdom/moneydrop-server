<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/18/2020
 * Time: 1:21 PM
 */

namespace profile;


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
use que\http\request\Request;
use que\support\Arr;
use que\support\Str;
use que\utility\hash\Hash;
use utility\paystack\exception\PaystackException;
use utility\paystack\Paystack;

class Card extends Manager implements Api
{
    use Paystack;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            if (empty($this->user('bvn'))) throw $this->baseException(
                "Sorry, you must add your BVN to perform any card operation.", "Card Failed", HTTP::EXPECTATION_FAILED);

            switch (Request::getUriParam('type')) {
                case 'add':

                    switch (Request::getUriParam('subtype')) {
                        case 'init':

                            try {
                                $charge = $this->init_transaction($this->user('email'), 50);
                            } catch (PaystackException $e) {
                                throw $this->baseException($e->getMessage(), "Card Failed", HTTP::UNPROCESSABLE_ENTITY);
                            }

                            if (!$charge->isSuccessful()) {
                                throw $this->baseException(
                                    'Something unexpected happened while initiating the add card transaction',
                                    "Card Failed", HTTP::EXPECTATION_FAILED
                                );
                            }

                            $response = $charge->getResponseArray();

                            if (!($response['status'] ?? false)) {
                                throw $this->baseException($response['message'] ??
                                    'Something unexpected happened while initiating the add card transaction',
                                    "Card Failed", HTTP::EXPECTATION_FAILED);
                            }

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Card Successful',
                                'message' => "Card add transaction initiated successfully.",
                                'response' => $response
                            ], HTTP::OK);

                        case 'verify':

                            $validator->validate('reference')->isNotEmpty('Please enter a reference');

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Card Failed", HTTP::UNPROCESSABLE_ENTITY);

                            try {
                                $verify = $this->verify_init_transaction($validator->getValue('reference'));
                            } catch (PaystackException $e) {
                                throw $this->baseException($e->getMessage(), "Card Failed", HTTP::UNPROCESSABLE_ENTITY);
                            }

                            if (!$verify->isSuccessful()) {
                                throw $this->baseException("Sorry, we couldn't verify that card at this time.",
                                    "Card Failed", HTTP::EXPECTATION_FAILED);
                            }

                            $response = $verify->getResponseArray();

                            if (!($response['status'] ?? false) || ($response['data']['status'] ?? 'failed') != 'success'
                                || empty($authorization = ($response['data']['authorization'] ?? []))) {

                                throw $this->baseException("Sorry, we couldn't verify that card at this time.",
                                    "Card Failed", HTTP::EXPECTATION_FAILED);
                            }

                            $card = db()->insert('cards', [
                                'uuid' => Str::uuidv4(),
                                'auth' => $authorization,
                                'user_id' => $this->user('id'),
                                'status' => STATE_ACTIVE,
                                'is_active' => true
                            ]);

                            if (!$card->isSuccessful()) {
                                throw $this->baseException("Sorry, we couldn't add that card at this time.",
                                    "Card Failed", HTTP::EXPECTATION_FAILED);
                            }

                            $card = $card->getFirstWithModel();
                            $cardDetails = Arr::extract_by_keys($card->getValue('auth'), ['card_type', 'last4']);
                            $cardDetails['uuid'] = $card->getValue('uuid');

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::CREATED,
                                'title' => 'Card Successful',
                                'message' => "Card added successfully.",
                                'response' => [
                                    'card' => $cardDetails
                                ]
                            ], HTTP::CREATED);

                        default:
                            throw $this->baseException(
                                "Sorry, we're not sure what you're trying to do there.", "Card Failed", HTTP::BAD_REQUEST);
                    }

                case 'retrieve':

                    $cardID = Request::getUriParam('subtype');

                    if ($cardID == 'all') {

                        $cards = $this->db()->findAll('cards', $this->user('id'), 'user_id',
                            function (Builder $builder) {
                                $builder->where('is_active', true);
                            });

                        return $this->http()->output()->json([
                            'status' => true,
                            'code' => HTTP::OK,
                            'title' => 'Card Successful',
                            'message' => "Cards retrieved successfully.",
                            'response' => (object)$cards->isSuccessful() ? $cards->getAllArray() : []
                        ], HTTP::OK);
                    }

                    $card = $this->db()->find('cards', $this->user('id'), 'user_id',
                        function (Builder $builder) use ($cardID) {
                            $builder->where('uuid', $cardID);
                            $builder->where('is_active', true);
                        });

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Card Successful',
                        'message' => "Card retrieved successfully.",
                        'response' => (object)$card->isSuccessful() ? $card->getAllArray() : []
                    ], HTTP::OK);

                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Card Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {
            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'error' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }
}