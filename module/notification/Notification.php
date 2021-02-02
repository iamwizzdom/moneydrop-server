<?php


namespace notification;


use que\http\input\Input;
use que\http\output\response\Html;
use que\http\output\response\Json;
use que\http\output\response\Jsonp;
use que\http\output\response\Plain;
use que\template\Pagination;

class Notification extends \que\common\manager\Manager implements \que\common\structure\Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $notifications = $this->db()->select('*')->table('notifications')
            ->where('user_id', $this->user('id'))->orderBy('desc', 'id')
            ->paginate(PAGINATION_PER_PAGE);

        $notifications->setModelKey('notificationModel');

        $pagination = Pagination::getInstance();

        return [
            'pagination' => [
                'page' => $pagination->getPaginator("default")->getPage(),
                'totalRecords' => $pagination->getTotalRecords("default"),
                'totalPages' => $pagination->getTotalPages("default"),
                'nextPage' => $pagination->getNextPage("default", true),
                'previousPage' => $pagination->getPreviousPage("default", true)
            ],
            'notifications' => $notifications->getAllWithModel() ?: []
        ];
    }
}
