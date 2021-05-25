<?php


namespace loan;


use model\LoanRepayment;
use que\common\exception\BaseException;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\database\interfaces\model\Model;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use que\support\Num;
use que\support\Str;
use que\template\Pagination;
use que\user\User;
use que\utility\money\Item;
use utility\Wallet;

class Repayment extends Manager implements Api
{
    use Wallet;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);
        try {

            $input['application_id'] = Request::getUriParam('id');

            $validator->validate('application_id')->isUUID("Please enter a valid application ID")
                ->isFoundInDB("loan_applications", 'uuid',
                    "That application doesn't not seem to be eligible for repayment", function (Builder $builder) {
                    $builder->where('status', \model\LoanApplication::STATUS_GRANTED);
                    $builder->where('is_active', true);
                });

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Repayment Failed", HTTP::UNPROCESSABLE_ENTITY);

            $application = $this->db()->find('loan_applications', $input['application_id'], 'uuid');
            $application->setModelKey("loanApplicationModel");
            $application = $application->getFirstWithModel();
            $application?->load('loan');

            if ($application->is_repaid) throw $this->baseException(
                "Your repayment for this loan is already complete", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER && $application->loan->user_id == $this->user('id') ||
                $application->loan->loan_type == \model\Loan::LOAN_TYPE_REQUEST && $application->user_id == $this->user('id'))
                throw $this->baseException("Sorry, you can't pay yourself", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER && $application->user_id != $this->user('id') ||
                $application->loan->loan_type == \model\Loan::LOAN_TYPE_REQUEST && $application->loan->user_id != $this->user('id'))
                throw $this->baseException("Sorry, you can't pay for a loan you did not receive.", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            $amountUnpaid = Item::cents(($application->amount_payable - $application->repaid_amount))->getFactor();

            $validator->validate('amount')->isFloatingNumber("Please enter a valid amount")
                ->isFloatingNumberGreaterThan(.1, "Amount must be at least %s NGN")
                ->isFloatingNumberLessThanOrEqual($amountUnpaid, "You can't pay more than the %s NGN left unpaid");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Repayment Failed", HTTP::UNPROCESSABLE_ENTITY);

            $repay = $this->db()->insert('loan_repayments', [
                'uuid' => Str::uuidv4(),
                'application_id' => $input['application_id'],
                'amount' => Item::factor($input['amount'])->getCents(),
                'user_id' => $this->user('id')
            ]);

            if (!$repay->isSuccessful()) throw $this->baseException(
                $repay->getQueryError() ?: "Sorry we could not complete that loan repayment at this time.",
                "Repayment Failed", HTTP::EXPECTATION_FAILED);

            $this->refreshWallet();
            $amount = Num::to_word($input['amount']);

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER) {
                $recipient = "{$application->loan->user->firstname} {$application->loan->user->lastname}";
            } else {
                $recipient = "{$application->applicant->firstname} {$application->applicant->lastname}";
            }

            $application->refresh();

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Repayment Successful',
                'message' => "You have successfully paid the sum of {$amount} NGN to {$recipient}.",
                'response' => [
                    'application' => $application,
                    'balance' => $this->getBalance(),
                    'available_balance' => $this->getAvailableBalance(),
                ]
            ]);

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }

    public function makeFullRepayment(Input $input) {

        $validator = $this->validator($input);
        try {

            $input['application_id'] = Request::getUriParam('id');

            $validator->validate('application_id')->isUUID("Please enter a valid application ID")
                ->isFoundInDB("loan_applications", 'uuid',
                    "That application doesn't not seem to be eligible for repayment", function (Builder $builder) {
                        $builder->where('status', \model\LoanApplication::STATUS_GRANTED);
                        $builder->where('is_active', true);
                    });

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Repayment Failed", HTTP::UNPROCESSABLE_ENTITY);

            $application = $this->db()->find('loan_applications', $input['application_id'], 'uuid');
            $application->setModelKey("loanApplicationModel");
            $application = $application->getFirstWithModel();
            $application?->load('loan');

            if ($application->is_repaid) throw $this->baseException(
                "Your repayment for this loan is already complete", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            if (!$this->user('id')) {
                User::logout_silently();
                if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER) {
                    User::login($application->applicant);
                } else {
                    User::login($application->loan->user);
                }
            }

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER && $application->loan->user_id == $this->user('id') ||
                $application->loan->loan_type == \model\Loan::LOAN_TYPE_REQUEST && $application->user_id == $this->user('id'))
                throw $this->baseException("Sorry, you can't pay yourself", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER && $application->user_id != $this->user('id') ||
                $application->loan->loan_type == \model\Loan::LOAN_TYPE_REQUEST && $application->loan->user_id != $this->user('id'))
                throw $this->baseException("Sorry, you can't pay for a loan you did not receive.", "Repayment Failed", HTTP::NOT_ACCEPTABLE);

            $input['amount'] = Item::cents(($application->amount_payable - $application->repaid_amount))->getFactor();

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Repayment Failed", HTTP::UNPROCESSABLE_ENTITY);

            $channel = LoanRepayment::PAYMENT_CHANNEL_WALLET;

            RETRY:

            $repay = $this->db()->insert('loan_repayments', [
                'uuid' => Str::uuidv4(),
                'application_id' => $input['application_id'],
                'amount' => Item::factor($input['amount'])->getCents(),
                'user_id' => $this->user('id'),
                'payment_channel' => $channel
            ]);

            if (!$repay->isSuccessful()) {
                if ($channel == LoanRepayment::PAYMENT_CHANNEL_WALLET) {
                    $channel = LoanRepayment::PAYMENT_CHANNEL_BANK;
                    goto RETRY;
                }
                throw $this->baseException(
                    $repay->getQueryError() ?: "Sorry we could not complete that loan repayment at this time.",
                    "Repayment Failed", HTTP::EXPECTATION_FAILED);
            }

            $this->refreshWallet();
            $amount = Num::to_word($input['amount']);

            if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER) {
                $recipient = "{$application->loan->user->firstname} {$application->loan->user->lastname}";
            } else {
                $recipient = "{$application->applicant->firstname} {$application->applicant->lastname}";
            }

            $application->refresh();

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Repayment Successful',
                'message' => "You have successfully paid the sum of {$amount} NGN to {$recipient}.",
                'response' => [
                    'application' => $application,
                    'balance' => $this->getBalance(),
                    'available_balance' => $this->getAvailableBalance(),
                ]
            ]);

        } catch (BaseException $e) {

            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object)$validator->getErrors()
            ], $e->getCode());
        }
    }

    /**
     * @param Input $input
     * @return Json
     */
    public function history(Input $input)
    {

        try {

            $application_id = Request::getUriParam('id');

            $application = $this->db()->find('loan_applications', $application_id, 'uuid');

            if (!$application->isSuccessful()) throw $this->baseException(
                "That loan application does not exist", "Repayment History Failed", HTTP::NOT_FOUND);

            $application->setModelKey('loanApplicationModel');
            $application = $application->getFirstWithModel();
            $application->load('loan');
            $userId = $this->user('id');

            if ($application->loan->user_id != $userId && $application->applicant->id != $userId)
                throw $this->baseException("You don't have permission to view repayment history of this loan application",
                    "Repayment History Failed", HTTP::UNAUTHORIZED);

            $repayments = $this->db()->select('*')->table('loan_repayments')
                ->where('application_id', $application_id)
                ->orderBy('desc', 'id')
                ->paginate(headers('X-PerPage', PAGINATION_PER_PAGE));

            $repayments->setModelKey('loanRepaymentModel');
            $repayments = $repayments->getAllWithModel();
            $repayments?->load('payer');
            $repayments?->load('transaction');
            $pagination = Pagination::getInstance();

            try {
               return $this->http()->output()->json([
                    'status' => true,
                    'pagination' => [
                        'page' => $pagination->getPaginator("default")->getPage(),
                        'totalRecords' => $pagination->getTotalRecords("default"),
                        'totalPages' => $pagination->getTotalPages("default"),
                        'nextPage' => $pagination->getNextPage("default", true),
                        'previousPage' => $pagination->getPreviousPage("default", true)
                    ],
                    'repayments' => $repayments ?: []
                ]);
            } catch (\Exception $e) {
                throw $this->baseException($e->getMessage(), "Repayment History Failed", HTTP::INTERNAL_SERVER_ERROR);
            }

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