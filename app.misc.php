<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 10/17/2019
 * Time: 1:20 PM
 */

const PAYSTACK = 1;
const FLUTTERWAVE = 2;

const GATEWAY = FLUTTERWAVE;

/**
 * Paystack key
 */
const PAYSTACK_API_KEY = 'sk_test_8d5f40d2aaca8d452582e98771f19cbd508d6483';

/**
 * Paystack endpoints
 */
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
 * Flutterwave Keys
 */
const FLUTTERWAVE_SECRET_KEY = "FLWSECK_TEST-2e259f3ffdd346e188e9554b7dd574d4-X";
const FLUTTERWAVE_ENCRYPTION_KEY = "FLWSECK_TEST76766747e348";

/**
 * Flutterwave endpoints
 */
const FLUTTERWAVE_TRANS_VERIFY_URL = LIVE ?
    "https://api.ravepay.co/flwv3-pug/getpaidx/api/v2/verify" :
    "https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/v2/verify";
const FLUTTERWAVE_CHARGE_CARD_URL = LIVE ?
    'https://api.ravepay.co/flwv3-pug/getpaidx/api/tokenized/charge' :
    'https://ravesandboxapi.flutterwave.com/flwv3-pug/getpaidx/api/tokenized/charge';
const FLUTTERWAVE_TRANSFER_URL = LIVE ?
    'https://api.ravepay.co/v2/gpx/transfers/create' :
    'https://ravesandboxapi.flutterwave.com/v2/gpx/transfers/create';

/**
 * Mono endpoints
 */
const MONO_API_KEY = 'test_sk_6e4S17jvqGIzJhoSKr7q';
const MONO_ACCOUNT_AUTH_URL = 'https://api.withmono.com/account/auth';
const MONO_ACCOUNT_DETAILS_URL = 'https://api.withmono.com/accounts';

const FIREBASE_API_KEY = 'AAAAkp1mBRM:APA91bHbiFw512qrXqjnQ-tKzX_snEL9aK2sGycg6NWQEbJrNza73_lGkfVFnqmou6Uy-I6jV_TKnwtZ2wm8xIdmunl8aLxKNdIVOMydS6ZhLGjLwzdTSPI1UfWZM3eoJh5EuXfqsQia';
const PAGINATION_PER_PAGE = 30;

const GOOGLE_CLIENT_ID = "629705934099-185d1ijs1uum9qra9k7klsvglfjsv2sg.apps.googleusercontent.com";