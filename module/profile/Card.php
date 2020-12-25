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

            switch (Request::getUriParam('type')) {
                case 'add':

                    switch (Request::getUriParam('subtype')) {
//                        case 'init':
//
//                            try {
//                                $charge = $this->init_transaction(50);
//                            } catch (PaystackException $e) {
//                                throw $this->baseException($e->getMessage(), "Card Failed", HTTP::UNPROCESSABLE_ENTITY);
//                            }
//
//                            if (!$charge->isSuccessful()) {
//                                throw $this->baseException(
//                                    'Something unexpected happened while initiating the add card transaction',
//                                    "Card Failed", HTTP::EXPECTATION_FAILED
//                                );
//                            }
//
//                            $response = $charge->getResponseArray();
//
//                            if (!($response['status'] ?? false)) {
//                                throw $this->baseException($response['message'] ??
//                                    'Something unexpected happened while initiating the add card transaction',
//                                    "Card Failed", HTTP::EXPECTATION_FAILED);
//                            }
//
//                            return $this->http()->output()->json([
//                                'status' => true,
//                                'code' => HTTP::OK,
//                                'title' => 'Card Successful',
//                                'message' => "Card add transaction initiated successfully.",
//                                'response' => $response
//                            ], HTTP::OK);
//
                        case 'reference':
                            $validator->validate('reference')->isNotEmpty('Please enter a reference');

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification Failed", HTTP::UNPROCESSABLE_ENTITY);

                            $this->db()->insert('trans_ref_logs', [
                                'reference' => $input['reference'],
                                'user_id' => $this->user('id')
                            ]);

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Log Successful',
                                'message' => "Transaction reference logged successfully",
                                'response' => []
                            ], HTTP::OK);

                        case 'verify':

                            $validator->validate('reference')->isNotEmpty('Please enter a reference');
                            $validator->validate('card_name', true)->isNotEmpty('Please enter a valid name for this card.');

                            if ($validator->hasError()) throw $this->baseException(
                                "The inputted data is invalid", "Verification Failed", HTTP::UNPROCESSABLE_ENTITY);

                            try {
                                $verify = $this->verify_transaction($validator->getValue('reference'));
                            } catch (PaystackException $e) {
                                throw $this->baseException($e->getMessage(), "Verification Failed", HTTP::UNPROCESSABLE_ENTITY);
                            }

                            if (!$verify->isSuccessful()) {
                                throw $this->baseException("Sorry, we couldn't verify that card at this time.",
                                    "Verification Failed", HTTP::EXPECTATION_FAILED);
                            }

                            $response = $verify->getResponseArray();

                            $data = $response['data'] ?? [];

                            if (!($response['status'] ?? false) || ($data['status'] ?? 'failed') != 'success'
                                || empty($authorization = ($data['authorization'] ?? []))) {

                                throw $this->baseException("Sorry, we couldn't verify that transaction at this time.",
                                    "Verification Failed", HTTP::EXPECTATION_FAILED);
                            }

                            $trans = [
                                'uuid' => Str::uuidv4(),
                                'user_id' => user('id'),
                                'transaction_state' => TRANSACTION_TOP_UP,
                                'transaction_type' => TRANSACTION_CREDIT,
                                'gateway_reference' => $data['reference'],
                                'amount' => $data['amount'] / 100,
                                'currency' => $data['currency'],
                                'status' => APPROVAL_SUCCESSFUL
                            ];

                            db()->insert('transactions', $trans);

                            if (!$authorization['reusable']) return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::OK,
                                'title' => 'Verification Successful',
                                'message' => "Sorry, this card is not reusable, you may want to try another card instead. " .
                                    "However, we have topped up your wallet with {$data['amount']} {$data['currency']} which was debited from the card.",
                                'response' => []
                            ], HTTP::OK);

                            $card = db()->insert('cards', [
                                'uuid' => Str::uuidv4(),
                                'auth' => $authorization,
                                'name' => !empty($input['card_name']) && $input['card_name'] != '0' ? $input['card_name'] : '',
                                'user_id' => $this->user('id'),
                                'status' => STATE_ACTIVE,
                                'is_active' => true
                            ]);

                            if (!$card->isSuccessful()) {
                                return $this->http()->output()->json([
                                    'status' => true,
                                    'code' => HTTP::OK,
                                    'title' => 'Verification Successful',
                                    'message' => "Sorry, we couldn't add this card at this time, please let's try this again later. " .
                                        "However, we have topped up your wallet with {$data['amount']} {$data['currency']} which was debited from the card.",
                                    'response' => []
                                ], HTTP::OK);
                            }

                            $card = $card->getFirstWithModel();
                            $cardDetails = Arr::extract_by_keys($card->getValue('auth'),
                                ['card_type', 'last4', 'brand', 'exp_month', 'exp_year']);
                            $cardDetails['name'] = $card->getValue('name');
                            $cardDetails['uuid'] = $card->getValue('uuid');

                            return $this->http()->output()->json([
                                'status' => true,
                                'code' => HTTP::CREATED,
                                'title' => 'Verification Successful',
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

                        $cards = $this->getAllMyCards()?->getArray() ?: [];

                        Arr::callback($cards, function ($card) {
                            $card = array_merge($card, Arr::extract_by_keys((array) $card['auth'],
                                ['card_type', 'last4', 'brand', 'exp_month', 'exp_year']));
                            unset($card['auth']);
                            return $card;
                        });

                        return $this->http()->output()->json([
                            'status' => true,
                            'code' => HTTP::OK,
                            'title' => 'Card Successful',
                            'message' => !empty($cards) ? "Cards retrieved successfully." : "No Card found.",
                            'response' => $cards
                        ], HTTP::OK);
                    }

                    $card = $this->db()->find('cards', $this->user('id'), 'user_id',
                        function (Builder $builder) use ($cardID) {
                            $builder->where('uuid', $cardID);
                            $builder->where('is_active', true);
                        });

                    if (!$card->isSuccessful()) return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::NOT_FOUND,
                        'title' => 'Card Not Found',
                        'message' => "That card either does not exist or has been deactivated.",
                        'response' => []
                    ], HTTP::NOT_FOUND);

                    $card = $card->getFirstWithModel();
                    $cardDetails = Arr::extract_by_keys((array) $card->getValue('auth'),
                        ['card_type', 'last4', 'brand', 'exp_month', 'exp_year']);
                    $cardDetails['name'] = $card->getValue('name');
                    $cardDetails['uuid'] = $card->getValue('uuid');

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Card Successful',
                        'message' => "Card retrieved successfully.",
                        'response' => $cardDetails
                    ], HTTP::OK);

                case 'remove':

                    $remove = $this->removeCard(Request::getUriParam('subtype'));

                    return $this->http()->output()->json([
                        'status' => $remove,
                        'code' => HTTP::OK,
                        'title' => $remove ? 'Remove Successful' : 'Remove Failed',
                        'message' => $remove ? "Card removed successfully." : "Card removal failed",
                        'response' => []
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