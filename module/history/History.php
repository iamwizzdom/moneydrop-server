<?php


namespace history;


use model\Loan;
use model\LoanApplication;
use que\common\manager\Manager;
use que\common\structure\Api;
use que\database\interfaces\Builder;
use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\http\request\Request;
use que\template\Pagination;

class History extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $applications = $this->db()->select("*")
            ->table('loan_applications as la')
            ->startWhereGroup()
            ->where('user_id', $this->user('id'))
            ->exists(function (Builder $builder) {
                $builder->table('loans')
                    ->where('uuid', '{{la.loan_id}}')
//                    ->where('loan_type', Loan::LOAN_TYPE_OFFER)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->endWhereGroup()
            ->startWhereGroup()
            ->orExists(function (Builder $builder) {
                $builder->table('loans')
                    ->where('uuid', '{{la.loan_id}}')
                    ->where('user_id', $this->user('id'))
//                    ->where('loan_type', Loan::LOAN_TYPE_REQUEST)
                    ->where('is_active', true);
            })
            ->where('status', [LoanApplication::STATUS_GRANTED, LoanApplication::STATUS_REPAID])
            ->where('is_active', true)
            ->endWhereGroup()
            ->orderBy('desc', 'id')
            ->paginate(PAGINATION_PER_PAGE);

        $applications->setModelKey('loanApplicationModel');

        return $applications->getAllWithModel()?->load('loan') ?: [];

//        $pagination = Pagination::getInstance();
//
//        return [
////            $applications->getQueryString(),
//            'pagination' => [
//                'page' => $pagination->getPaginator("default")->getPage(),
//                'totalRecords' => $pagination->getTotalRecords("default"),
//                'totalPages' => $pagination->getTotalPages("default"),
//                'nextPage' => $pagination->getNextPage("default", true),
//                'previousPage' => $pagination->getPreviousPage("default", true)
//            ],
//            'applications' => $applications->getAllWithModel()?->load('loan') ?: []
//        ];
    }
}