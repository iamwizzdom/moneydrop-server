<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 11/12/2020
 * Time: 2:15 PM
 */

namespace profile;


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
use que\route\Route;
use que\utility\hash\Hash;
use utility\paystack\exception\PaystackException;
use utility\paystack\Paystack;

class Update extends Manager implements Api
{
    use Paystack;

    /**
     * @inheritDoc
     */
    public function process(Input $input)
    {
        // TODO: Implement process() method.
        $validator = $this->validator($input);

        try {

            switch (Request::getUriParam('type')) {
                case 'picture':

                    $file = $validator->validateBase64File();
                    $file->setMaxFileSize(convert_mega_bytes(1));
                    $file->setAllowedExtension(['png', 'jpg', 'jpeg']);
                    $file->setUploadDir('/profile/picture/');
                    $file->setFileName(Hash::sha($this->user('id')));

                    if (!$file->upload("picture", $input->getBody()))
                        $validator->addConditionErrors('picture', $file->getErrors('picture'), true);

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update(['picture' => "storage/{$file->getFileInfo('path')}"]))
                        throw $this->baseException("Failed to update picture at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Picture updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'name':

                    $validator->validate('firstname')->isNotEmpty('Please enter a valid first name')
                        ->hasMinLength(3, "Your first name must be at least %s characters long");

                    $validator->validate('middlename', true)->isNotEmpty('Please enter a valid middle name')
                        ->hasMinLength(3, "Your middle name must be at least %s characters long");

                    $validator->validate('lastname')->isNotEmpty('Please enter a valid last name')
                        ->hasMinLength(3, "Your last name must be at least %s characters long");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
                        "Failed to update name at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Name updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

//                case 'phone':
//
//                    $validator->validate('phone')->isPhoneNumber("Please enter a valid phone number")
//                        ->startsWithAny(['+234', '234'], "Sorry, we only support nigerian phone numbers for now.")
//                        ->hasMinLength(13, "Enter your phone number with your country code, and it must be at least %s digits long")
//                        ->isNotEqual($this->user('phone'), "That's already your phone number.")
//                        ->isUniqueInDB("users", "phone", "That phone number already exist", $this->user('id'));
//
//                    if ($validator->hasError()) throw $this->baseException(
//                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);
//
//                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
//                        "Failed to update phone number at this time, please try again later.",
//                        "Update Failed", HTTP::EXPECTATION_FAILED);
//
//                    return $this->http()->output()->json([
//                        'status' => true,
//                        'code' => HTTP::OK,
//                        'title' => 'Update Successful',
//                        'message' => "Phone number updated successfully.",
//                        'response' => [
//                            'user' => $this->user()->getUserArray()
//                        ]
//                    ]);

//                case 'email':
//
//                    $validator->validate('email')->isEmail("Please enter a valid email address")->toLower()
//                        ->isNotEqual($this->user('email'), "That's already your email.")
//                        ->isFoundInDB('verifications', 'data', "That email has not been verified.",
//                            function (Builder $builder) {
//                                $builder->where('is_verified', true);
//                                $builder->where('is_active', true);
//                            })->isUniqueInDB("users", "email", "That email address already exist", $this->user('id'));
//
//                    if ($validator->hasError()) throw $this->baseException(
//                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);
//
//                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
//                        "Failed to update email at this time, please try again later.",
//                        "Update Failed", HTTP::EXPECTATION_FAILED);
//
//                    return $this->http()->output()->json([
//                        'status' => true,
//                        'code' => HTTP::OK,
//                        'title' => 'Update Successful',
//                        'message' => "Email address updated successfully.",
//                        'response' => [
//                            'user' => $this->user()->getUserArray()
//                        ]
//                    ]);

                case 'gender':

                    $validator->validate('gender')->isNumber("Please select a valid gender")
                        ->isEqualToAny([GENDER_MALE, GENDER_FEMALE], "Sorry, you have not selected a valid gender")
                    ->isNotEqual($this->user()->getInt('gender'), "That's already your gender");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
                        "Failed to update email at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Gender updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'address':

                    $validator->validate('address')->isNotEmpty("Please enter a address")
                        ->hasMinWord(5, "Your address should have a minimum of %s words");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
                        "Failed to update email at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Address updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'dob':

                    $validator->validate('dob')->isDate("Please enter a valid date of birth", 'Y-m-d')
                        ->isDateNotEqual(
                            \DateTime::createFromFormat('Y-m-d', $this->user('dob')),
                            "That's already your date of birth."
                        )->isDateLessThanOrEqual(
                                \DateTime::createFromFormat('Y-m-d', date('Y-m-d')),
                            "Sorry, we don't accept people that were born in the future"
                        )->isDateLessThanOrEqual(
                            \DateTime::createFromFormat('Y-m-d', date('Y-m-d', strtotime('-15years'))),
                            "You must be at least 15 years old to use " . config('template.app.header.name')
                        );

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
                        "Failed to update date of birth at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Date of birth updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'bvn':

                    $validator->validate('bvn')->isNotEmpty('Please enter a valid BVN')
                        ->isNotEqual($this->user('bvn'), "That's already your BVN.")
                        ->hasMinLength(11, "A valid BVN must be at least %s characters long")
                        ->hasMaxLength(11, "A valid BVN must not exceed %s characters");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    try {
                        $charge = \utility\Wallet::charge(LIVE ? \model\Transaction::BVN_RESOLVE_PREMIUM_FEE : \model\Transaction::BVN_RESOLVE_STANDARD_FEE, 500,"Resolve BVN charge");
                        if ($charge->isSuccessful()) $bvnResolve = $this->resolve_bvn($validator->getValue('bvn'));
                        else throw new PaystackException($charge->getQueryError());
                    } catch (PaystackException $e) {
                        throw $this->baseException($e->getMessage(), "Update Failed", HTTP::UNPROCESSABLE_ENTITY);
                    }

                    if (!$bvnResolve->isSuccessful()) {
                        throw $this->baseException("Sorry, we couldn't resolve that BVN at this time, please try again later.",
                            "Update Failed", HTTP::UNPROCESSABLE_ENTITY);
                    }

                    $response = $bvnResolve->getResponseArray();

                    if (!($response['status'] ?? false)) {
                        throw $this->baseException(
                            $response['message'] ?? "Sorry, we couldn't resolve that BVN at this time, please try again later.",
                            "Update Failed", HTTP::UNPROCESSABLE_ENTITY);
                    }

                    $data = $response['data'] ?? [];

                    $userModel = $this->user()->getModel();

                    if ($userModel->validate('firstname')->toLower()->isNotEqual(strtolower($data['first_name'] ?? ''))
                    && $userModel->validate('middlename')->toLower()->isNotEqual(strtolower($data['first_name'] ?? ''))) {
                        $validator->addError('bvn', "The first name on that BVN do not match your first/middle name on this platform.");
                    }

                    if (!$validator->hasError() && $userModel->validate('lastname')->toLower()->isNotEqual(strtolower($data['last_name'] ?? ''))) {
                        $validator->addError('bvn', "The last name on that BVN do not match your last name on this platform.");
                    }

                    if (!$validator->hasError() && $userModel->validate('dob')->isDateNotEqual(
                        \DateTime::createFromFormat('Y-m-d', $data['formatted_dob'] ?? date('Y-m-d')), 'Y-m-d')) {
                        $validator->addError('bvn', "The date of birth on that BVN do not match your date of birth on this platform.");
                    }

                    if ($validator->hasError()) throw $this->baseException(
                        "Sorry, we couldn't resolve that BVN at this time, please try again later.",
                        "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update($validator->getValidated())) throw $this->baseException(
                        "Failed to update BVN at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "BVN updated successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'password':

                    $validator->validate('current_password')->isNotEmpty("Please enter a valid password")
                        ->hash('SHA512')->isEqual($this->user('password'), "Password do not match");

                    $validator->validate('password')->isNotEmpty("Please enter a valid password")->hasMinLength(
                        8, "Your password must be at least %s characters long")->isAlphaNumeric(
                        "Your password is not strong enough (make it alpha-numeric)")
                        ->isConfirmed("Password do not match")->hash('SHA512');

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update(['password' => $validator->getValue('password')]))
                        throw $this->baseException("Failed to update password at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Password changed successfully.",
                        'response' => [
                            'user' => $this->user()->getUserArray()
                        ]
                    ]);

                case 'pn_token':

                    $validator->validate('pn_token')->isNotEmpty("Please enter a valid token");

                    if ($validator->hasError()) throw $this->baseException(
                        "The inputted data is invalid", "Update Failed", HTTP::UNPROCESSABLE_ENTITY);

                    if (!$this->user()->update(['pn_token' => $validator->getValue('pn_token')]))
                        throw $this->baseException("Failed to update token at this time, please try again later.",
                        "Update Failed", HTTP::EXPECTATION_FAILED);

                    return $this->http()->output()->json([
                        'status' => true,
                        'code' => HTTP::OK,
                        'title' => 'Update Successful',
                        'message' => "Token saved successfully.",
                        'response' => []
                    ]);

                default:
                    throw $this->baseException(
                        "Sorry, we're not sure what you're trying to update there.", "Update Failed", HTTP::BAD_REQUEST);
            }

        } catch (BaseException $e) {
            return $this->http()->output()->json([
                'status' => $e->getStatus(),
                'code' => $e->getCode(),
                'title' => $e->getTitle(),
                'message' => $e->getMessage(),
                'errors' => (object) $validator->getErrors()
            ], $e->getCode());
        }
    }
}
