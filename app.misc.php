<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 10/17/2019
 * Time: 1:20 PM
 */


/**
 * Paystack endpoints
 */
const PAYSTACK_API_KEY = 'sk_test_8d5f40d2aaca8d452582e98771f19cbd508d6483';
const PAYSTACK_INIT_TRANS_URL = 'https://api.paystack.co/transaction/initialize';
const PAYSTACK_VERIFY_INIT_TRANS_URL = 'https://api.paystack.co/transaction/verify';
const PAYSTACK_CHARGE_CARD_URL = 'https://api.paystack.co/transaction/charge_authorization';
const PAYSTACK_RESOLVE_BVN_URL = 'https://api.paystack.co/bank/resolve_bvn';
const PAYSTACK_MATCH_BVN_URL = 'https://api.paystack.co/bvn/match';
const PAYSTACK_RESOLVE_ACCOUNT_URL = 'https://api.paystack.co/bank/resolve';
const PAYSTACK_RESOLVE_CARD_URL = 'https://api.paystack.co/decision/bin';
const PAYSTACK_TRANSFER_RECIPIENT_URL = 'https://api.paystack.co/transferrecipient';
const PAYSTACK_TRANSFER_URL = 'https://api.paystack.co/transfer';

/**
 * Mono endpoints
 */
const MONO_API_KEY = 'test_sk_6e4S17jvqGIzJhoSKr7q';
const MONO_ACCOUNT_AUTH_URL = 'https://api.withmono.com/account/auth';
const MONO_ACCOUNT_DETAILS_URL = 'https://api.withmono.com/accounts';

const FIREBASE_API_KEY = 'AAAAkp1mBRM:APA91bHbiFw512qrXqjnQ-tKzX_snEL9aK2sGycg6NWQEbJrNza73_lGkfVFnqmou6Uy-I6jV_TKnwtZ2wm8xIdmunl8aLxKNdIVOMydS6ZhLGjLwzdTSPI1UfWZM3eoJh5EuXfqsQia';
const PAGINATION_PER_PAGE = 30;