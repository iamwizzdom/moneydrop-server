<?php


namespace module\review;


use model\Loan;
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
use que\template\Pagination;

class Review extends Manager implements Api
{

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);
        try {

            $input['application_id'] = Request::getUriParam('id');

            $validator->validate('application_id')->isUUID("Please pass a valid application ID");
            $validator->validate('review')->isNotEmpty("Please a valid review")
                ->hasMinWord(10, "Please write a meaningful review of at least %s words");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Review Failed", HTTP::UNPROCESSABLE_ENTITY);

            $application = $this->db()->find('loan_applications', $input['application_id'], 'uuid',
                function (Builder $builder) {
                $builder->where('is_active', true);
            });

            if (!$application->isSuccessful()) throw $this->baseException(
                "Sorry, we couldn't find that application. It either does not exist or has been deactivated",
                "Review Failed", HTTP::UNPROCESSABLE_ENTITY);

            $application->setModelKey('loanApplicationModel');

            $application = $application->getFirstWithModel();

//            if (!$application->is_repaid) throw $this->baseException(
//                "Sorry, you can only review this loan recipient after the loan has been repaid completely",
//                "Review Failed", HTTP::UNAUTHORIZED);

            $application->load('loan');

            if (($application->loan->loan_type == Loan::LOAN_TYPE_REQUEST && $application->loan->is_mine) ||
                ($application->loan->loan_type == Loan::LOAN_TYPE_OFFER && $application->applicant->id == $this->user('id')))  {
                throw $this->baseException("Sorry, you can't review yourself", "Review Failed", HTTP::NOT_ACCEPTABLE);
            }

            $check = $this->db()->check('reviews', function (Builder $builder) {
                $builder->where('application_id', \input('application_id'));
                $builder->where('user_id', $this->user('id'));
            });

            if ($application->loan->loan_type == Loan::LOAN_TYPE_REQUEST) $applicant = $application->loan->user;
            else $applicant = $application->applicant;

            if ($check->isSuccessful()) throw $this->baseException(
                "Sorry, you already reviewed {$applicant->firstname} on this loan", "Review Failed", HTTP::CONFLICT);

            $review = $this->db()->insert('reviews', [
                'review' => $input['review'],
                'application_id' => $input['application_id'],
                'user_id' => $applicant->uuid,
                'reviewed_by' => $this->user('id')
            ]);

            if (!$review->isSuccessful()) throw $this->baseException(
                "Sorry, we couldn't submit that review at this time. Please let's try that again later",
                "Review Failed", HTTP::EXPECTATION_FAILED);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::CREATED,
                'title' => 'Review Successful',
                'message' => "Thank you for reviewing {$applicant->firstname} on this loan.",
                'response' => []
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

    public function viewReviews(Input $input) {

        $reviews = $this->db()->select('*')->table('reviews')
            ->where('user_id', Request::getUriParam('id'))
            ->where('is_active', true)
            ->orderBy('desc', 'id')
            ->paginate(PAGINATION_PER_PAGE);

        $reviews->setModelKey('reviewModel');
        $status = $reviews->isSuccessful();
        $reviews = $reviews->getAllWithModel();
        $reviews?->load('application.loan')->load('user')->load('reviewer');

        $pagination = Pagination::getInstance();

        return $this->http()->output()->json([
            'status' => $status,
            'code' => $status ? HTTP::OK : HTTP::NO_CONTENT,
            'title' => $status ? 'Review(s) Found' : "No Reviews Found",
            'message' => $status ? "Retrieved reviews successfully." : "No review were found",
            'pagination' => [
                'page' => $pagination->getPaginator("default")->getPage(),
                'totalRecords' => $pagination->getTotalRecords("default"),
                'totalPages' => $pagination->getTotalPages("default"),
                'nextPage' => $pagination->getNextPage("default", true),
                'previousPage' => $pagination->getPreviousPage("default", true)
            ],
            'reviews' => $reviews ?: []
        ]);
    }

    public function editReview(Input $input) {

        $validator = $this->validator($input);

        try {

            $input['review_id'] = Request::getUriParam('id');

            $validator->validate('review_id')->isUUID("Please pass a valid review ID");
            $validator->validate('review')->isNotEmpty("Please a valid review")
                ->hasMinWord(10, "Please write a meaningful review of at least %s words");

            if ($validator->hasError()) throw $this->baseException(
                "The inputted data is invalid", "Review Failed", HTTP::UNPROCESSABLE_ENTITY);

            $review = $this->db()->find('reviews', $input['review_id'], 'uuid', function (Builder $builder) {
                $builder->where('is_active', true);
            });

            $review->setModelKey('reviewModel');

            if (!$review->isSuccessful()) throw $this->baseException(
                "Sorry that review either has been deleted or does not exist", "Review Failed", HTTP::NOT_FOUND);

            $review = $review->getFirstWithModel();
            $review?->load('application.loan')->load('user')->load('reviewer');

            if ($review->getValue('reviewed_by') != $this->user('id')) throw $this->baseException(
                "Sorry, you can only modify your own review.", "Review Failed", HTTP::UNAUTHORIZED);

            if (!$review->update(['review' => $input['review']])->isSuccessful()) throw $this->baseException(
                "Sorry, we couldn't update this review at this time, please try again later.", "Review Failed", HTTP::EXPECTATION_FAILED);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Review Successful',
                'message' => "Your review has been updated successfully",
                'response' => [
                    'review' => $review
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

    public function deleteReview(Input $input) {

        $validator = $this->validator($input);

        try {

            $input['review_id'] = Request::getUriParam('id');

            if (!$input->validate('review_id')->isUUID()) throw $this->baseException(
                "Please pass a valid review ID", "Review Failed", HTTP::UNPROCESSABLE_ENTITY);

            $review = $this->db()->find('reviews', $input['review_id'], 'uuid', function (Builder $builder) {
                $builder->where('is_active', true);
            });

            if (!$review->isSuccessful()) throw $this->baseException(
                "Sorry that review either has been deleted or does not exist", "Review Failed", HTTP::NOT_FOUND);

            $review = $review->getFirstWithModel();

            if ($review->getValue('reviewed_by') != $this->user('id')) throw $this->baseException(
                "Sorry, you can only delete your own review.", "Review Failed", HTTP::UNAUTHORIZED);

            if (!$review->update(['is_active' => false])->isSuccessful()) throw $this->baseException(
                "Sorry, we couldn't delete this review at this time, please try again later.", "Review Failed", HTTP::EXPECTATION_FAILED);

            return $this->http()->output()->json([
                'status' => true,
                'code' => HTTP::OK,
                'title' => 'Review Successful',
                'message' => "Your review has been deleted successfully",
                'response' => []
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
}