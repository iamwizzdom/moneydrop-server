<?php


namespace history;


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
                    ->where('is_active', true);
            })
            ->notExists(function (Builder $builder) {
                $builder->table('loan_applications as _la')
                    ->where('loan_id', '{{la.loan_id}}')
                    ->exists(function (Builder $builder) {
                        $builder->table('loans')
                            ->where('uuid', '{{_la.loan_id}}')
                            ->where('is_fund_raiser', false)
                            ->where('is_active', true);
                    })
                    ->where('is_granted', true)
                    ->where('is_active', true);
            })
            ->where('is_active', true)
            ->endWhereGroup()
            ->startWhereGroup()
            ->orWhereIn('loan_id', function (Builder $builder) {
                $builder->select('uuid')->table('loans')
                    ->where('user_id', $this->user('id'))
                    ->where('is_active', true);
            })
            ->where('is_granted', true)
            ->where('is_active', true)
            ->endWhereGroup()
            ->paginate(30);

        $applications->setModelKey('loanApplicationModel');

        $pagination = Pagination::getInstance();

        return [
//            $applications->getQueryString(),
            'pagination' => [
                'page' => $pagination->getPaginator("default")->getPage(),
                'totalRecords' => $pagination->getTotalRecords("default"),
                'totalPages' => $pagination->getTotalPages("default"),
                'nextPage' => $pagination->getNextPage("default", true),
                'previousPage' => $pagination->getPreviousPage("default", true)
            ],
            'applications' => $applications->getAllWithModel()?->load('loan') ?: []
        ];
    }
}