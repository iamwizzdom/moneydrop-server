<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 6/5/2020
 * Time: 12:21 PM
 */

namespace app\middleware;


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
        // TODO: Implement handle() method.
        $hasAccess = true;
        $message = "";

        try {
            JWT::toUser(get_bearer_token() ?: '');
        } catch (EmptyTokenException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (InsecureTokenException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (IntegrityViolationException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (InvalidClaimTypeException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (InvalidStructureException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (MissingClaimException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (TokenExpiredException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (TokenInactiveException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (UndefinedAlgorithmException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (UnsupportedAlgorithmException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        } catch (UnsupportedTokenTypeException $e) {
            $hasAccess = false;
            $message = $e->getMessage();
        }

        if (!$hasAccess) {

            $this->setAccess($hasAccess);
            $this->setResponse(http()->output()->json([
                'status' => false,
                'message' => $message,
            ], HTTP::UNAUTHORIZED));

            return $this;
        }

        return parent::handle($input);
    }
}
