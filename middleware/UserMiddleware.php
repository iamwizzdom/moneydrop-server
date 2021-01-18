<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 6/5/2020
 * Time: 12:21 PM
 */

namespace app\middleware;


use Exception;
use que\common\exception\QueException;
use que\common\exception\QueRuntimeException;
use que\http\HTTP;
use que\http\input\Input;
use que\security\JWT\Exceptions\EmptyTokenException;
use que\security\JWT\Exceptions\InsecureTokenException;
use que\security\JWT\Exceptions\IntegrityViolationException;
use que\security\JWT\Exceptions\InvalidClaimTypeException;
use que\security\JWT\Exceptions\InvalidStructureException;
use que\security\JWT\Exceptions\MissingClaimException;
use que\security\JWT\Exceptions\TokenExpiredException;
use que\security\JWT\Exceptions\TokenInactiveException;
use que\security\JWT\Exceptions\UndefinedAlgorithmException;
use que\security\JWT\Exceptions\UnsupportedAlgorithmException;
use que\security\JWT\Exceptions\UnsupportedTokenTypeException;
use que\security\JWT\JWT;
use que\security\Middleware;
use que\security\MiddlewareResponse;

class UserMiddleware extends Middleware
{

    /**
     * @inheritDoc
     */
    public function handle(Input $input): MiddlewareResponse
    {
//        sleep(2);
        // TODO: Implement handle() method.
        $hasAccess = true;
        $message = "";
        $code = HTTP::EXPIRED_AUTHENTICATION;

        try {
            JWT::toUser(get_bearer_token() ?: '', "userModel", false);
        } catch (QueRuntimeException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
            $code = $e->getHttpCode();
        } catch (Exception $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        }

        $this->setAccess($hasAccess);

        if (!$hasAccess) {

            $this->setTitle("Auth Error");
            $this->setResponse(http()->output()->json([
                'status' => false,
                'message' => $message,
            ], $code));

            return $this;
        }

        return parent::handle($input);
    }
}
