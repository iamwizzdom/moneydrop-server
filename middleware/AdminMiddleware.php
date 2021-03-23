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
use que\http\request\Request;
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
use que\user\User;
use que\utility\hash\Hash;

class AdminMiddleware extends Middleware
{

    /**
     * @inheritDoc
     */
    public function handle(Input $input): MiddlewareResponse
    {
        // TODO: Implement handle() method.
        $hash = Hash::sha(sprintf("%s-%s-%s", 'K/r3L&Y6$dq52YFn', Request::getClientIp(), 'AzZY?9QWmn29QjDK'));
        $hasAccess = (headers('Auth-Secret') == $hash);

        $this->setAccess($hasAccess);

        if (!$hasAccess) {

            $this->setTitle("Auth Error");
            $this->setResponse(http()->output()->json([
                'status' => false,
                'message' => "Sorry, you are not authorized to access this route",
            ], HTTP::UNAUTHORIZED));

            return $this;
        }

        //login anonymous
        User::login((object)[]);

        return parent::handle($input);
    }
}
