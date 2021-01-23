<?php


namespace loan;


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
use que\support\Arr;
use que\support\Config;
use que\support\Num;
use que\support\Str;
use que\template\Pagination;
use utility\Wallet;

class LoanApplication extends Manager implements Api
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

            switch ($type = Request::getUriParam('type')) {
                case "apply":

                    $input['loan_id'] = Request::getUriParam('id');

                    $loanValidator = $validator->validate('loan_id')->isUUID('Please enter a valid loan ID')
                        ->isFoundInDB('loans', 'uuid', "Sorry, either that loan does not exist or it's not eligible for applications yet.",
                            function (Builder $builder) {
                                $builder->where('status', STATE_AWAITING);
                                $builder->where('is_active', true);
                            })->isNotFoundInDB('loan_applications', 'loan_id', 'You already applied for this loan',
                            function (Builder $builder) {
                                $builder->where('user_id', $this->user('id'));
                                $builder->where('is_active', true);
                            });

                    $loan = $this->db()->find('loans', $input['loan_id'], 'uuid')->getFirstWithModel();

                    if ($loan->getBool('is_active') && !$loan->getBool('is_funder_raiser')) {
                        $loanValidator->isNotFoundInDB('loan_applications', 'loan_id', 'This loan is already granted to an applicant',
                            function (Builder $builder) {
                                $builder->where('is_granted', true);
                            });
                    }

                    $availableRaise = null;

                    if ($loan->getBool('is_active') && $loan->getBool('is_funder_raiser')) {

                        $applications = $this->db()->findAll('loan_applications', $input['loan_id'], 'uuid', function (Builder $builder) {
                            $builder->where('is_active', true);
                        })->getAllWithModel();

                        $raised = $applications->sum(function (Model $model) {
                            return $model->getFloat('amount');
                        });

                        if ($loan->validate('amount')->isFloatingNumberGreaterThanOrEqual($raised)) {
                            $loanValidator->getValidator()->addError('loan_id', "Sorry, this loan has already reached the maximum amount to be raised");
                        } else $availableRaise = $loan->getFloat('amount') - $raised;
                    }

                    $amountValidator = $validator->validate('amount')->isFloatingNumber('Please enter a valid amount')
                        ->isFloatingNumberGreaterThanOrEqual(\model\Loan::MIN_LOAN_AMOUNT, "Sorry, you must apply with at least %s NGN.")
                        ->isFloatingNumberLessThanOrEqual($loan->getFloat('amount'), "Sorry, you can't apply with an amount greater than the loan amount");

                    if ($availableRaise != null) {
                        $amountValidator->isFloatingNumberLessThanOrEqual($availableRaise, "Sorry, you're raising more than what's left to be raised for this loan");
                    }

                    $validator->validate('note', true)->isNotEmpty('Please enter a valid note')
                        ->hasMinWord(10, "Please write something meaningful of at least %s words.");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Loan Failed", HTTP::UNPROCESSABLE_ENTITY);

                    $application = $this->db()->insert('loan_applications', [
                        'uuid' => Str::uuidv4(),
                        'note' => $input['note'],
                        'amount' => (float)$input['amount'],
                        'loan_id' => $input['loan_id'],
                        'user_id' => $this->user('id')
                    ]);

                    if (!$application->isSuccessful()) throw $this->baseException(
                        "Sorry we could not record your application at this time, let's try that again later.",
                        "Loan Failed", HTTP::EXPECTATION_FAILED);

                    $application = $application->getFirstWithModel();
                    $application->load('loan');

                    $this->refreshWallet();

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::CREATED,
                        'title' => 'Loan Application Successful',
                        'message' => "You have successfully applied for this loan.",
                        'response' => [
                            'application' => $application,
                            'balance' => $this->getBalance(),
                            'available_balance' => $this->getAvailableBalance(),
                        ]
                    ], HTTP::CREATED);

                case "application":

                    $params = Request::getUriParams();

                    if (!isset($params['_id'])) throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Loan Failed", HTTP::BAD_REQUEST);

                    $input['loan_id'] = $params['id'];
                    $input['application_id'] = $params['_id'];

                    $validator->validate('loan_id')->isUUID('Please enter a valid loan ID')
                        ->isFoundInDB('loans', 'uuid', "Sorry, it seems that loan does not exist or is no longer eligible for granting.",
                            function (Builder $builder) {
                                $builder->where('status', STATE_AWAITING);
                                $builder->where('is_active', true);
                            })
                        ->isFoundInDB('loans', 'uuid', "Sorry, you can't grant a loan that does not belong to you.",
                            function (Builder $builder) {
                                $builder->where('user_id', $this->user('id'));
                                $builder->where('is_active', true);
                            });

                    $validator->validate('application_id')->isUUID('Please enter a valid application ID')
                        ->isFoundInDB('loan_applications', 'uuid', "Sorry, it seems that application does not exist or it has been cancelled by the applicant.",
                            function (Builder $builder) {
                                $builder->where('is_active', true);
                            })
                        ->isNotFoundInDB('loan_applications', 'uuid', 'You already granted this loan to an applicant.',
                            function (Builder $builder) {
                                $builder->where('loan_id', \input('loan_id'));
                                $builder->where('is_granted', true);
                                $builder->where('is_active', true);
                            });

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Loan Failed", HTTP::UNPROCESSABLE_ENTITY);

                    $application = $this->db()->find('loan_applications', $input['application_id'], 'uuid');

                    if (!$application->isSuccessful()) throw $this->baseException(
                        "Sorry we could not find that loan application.",
                        "Loan Failed", HTTP::EXPECTATION_FAILED);

                    $application->setModelKey('loanApplicationModel');
                    $application = $application->getFirstWithModel();

                    $application->load('loan');

                    $grant = $application->update([
                        'is_granted' => true,
                        'due_date' => \model\Loan::getLoanDueDate($application->loan->tenure)
                    ]);

                    if (!$grant?->isSuccessful()) throw $this->baseException(
                        $grant?->getQueryError() ?: "Sorry we couldn't grant that loan at this time, please let's try that again later",
                        "Loan Failed", HTTP::EXPECTATION_FAILED);

                    $this->refreshWallet();

                    $amount = Num::to_word($application->loan->amount);

                    if ($application->loan->loan_type == \model\Loan::LOAN_TYPE_OFFER) {
                        $message = "You have successfully given {$application->applicant->firstname} a loan of {$amount} NGN.";
                    } else {
                        $message = "You have successfully received a loan of {$amount} NGN from {$application->applicant->firstname}.";
                    }

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Loan Successful',
                        'message' => $message,
                        'response' => [
                            'application' => $application,
                            'balance' => $this->getBalance(),
                            'available_balance' => $this->getAvailableBalance(),
                        ]
                    ]);

                case "applicants":

                    $applications = $this->db()->select(
                        "*, la.id as id, la.uuid as uuid, la.user_id as user_id",
                        "la.created_at as created_at, la.updated_at as updated_at",
                    )->table('loan_applications as la')
                        ->join('loans as l', 'l.uuid', 'la.loan_id')
                        ->where('la.loan_id', Request::getUriParam('id'))
                        ->where('l.user_id', $this->user('id'))
                        ->where('la.is_active', true)
                        ->orderBy('desc', 'la.id')
                        ->paginate(30);

                    $applications->setModelKey("loanApplicationModel");

                    $pagination = Pagination::getInstance();

                    $applications = $applications->getAllWithModel();
                    $applications?->load('loan');

                    if ($applications) {
                        $applications->_set('has_granted', $applications->isTrueForAny(function (Model $model) {
                            return $model->getBool('is_granted') === true;
                        }));
                    }

                    return [
                        'pagination' => [
                            'page' => $pagination->getPaginator("default")->getPage(),
                            'totalRecords' => $pagination->getTotalRecords("default"),
                            'totalPages' => $pagination->getTotalPages("default"),
                            'nextPage' => $pagination->getNextPage("default", true),
                            'previousPage' => $pagination->getPreviousPage("default", true)
                        ],
                        'applications' => $applications ?: []
                    ];

                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to do there.", "Loan Failed", HTTP::BAD_REQUEST);
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
