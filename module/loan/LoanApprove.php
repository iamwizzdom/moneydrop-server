<?php


namespace loan;


use que\common\exception\BaseException;
use que\http\HTTP;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;

class LoanApprove extends \que\common\manager\Manager implements \que\common\structure\Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        try {

            $input['loan_id'] = Request::getUriParam('id');

            $validator = $input->validate('loan_id');

            if (!$validator->isUUID()) throw $this->baseException(
                "Please enter a valid loan ID", "Approve Failed", HTTP::UNPROCESSABLE_ENTITY);

            if (!$validator->isFoundInDB('loans', 'uuid')) throw $this->baseException(
                "Sorry, that loan ID does not exist", "Approve Failed", HTTP::EXPECTATION_FAILED);

            $loan = $this->db()->find('loans', $input['loan_id'], 'uuid');
            $loan->setModelKey('loanModel');
            $loan = $loan->getFirstWithModel();

            if ($loan?->getInt('status') != \model\Loan::STATUS_PENDING)
                throw $this->baseException("You can only approve a pending loan.", "Approve Failed", HTTP::NOT_ACCEPTABLE);

            $update = $loan->update(['status' => \model\Loan::STATUS_AWAITING]);
            if (!$update->isSuccessful()) throw $this->baseException($update->getQueryError() ?: "Loan approval failed at this time.", "Approve Failed", HTTP::EXPECTATION_FAILED);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Approve Successful',
                'message' => "Loan approved successfully.",
                'response' => [
                    'loan' => $loan
                ]
            ]);

        } catch (BaseException $e) {
            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object)[]
            ], $e->getCode());
        }
    }
}