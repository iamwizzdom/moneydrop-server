<?php
/**
 * Created by PhpStorm.
 * User: Wisdom Emenike
 * Date: 10/17/2019
 * Time: 1:20 PM
 */


const PAYSTACK_INIT_TRANS_URL = 'https://api.paystack.co/transaction/initialize';
const PAYSTACK_VERIFY_INIT_TRANS_URL = 'https://api.paystack.co/transaction/verify';
const PAYSTACK_CHARGE_CARD_URL = 'https://api.paystack.co/transaction/charge_authorization';
const PAYSTACK_RESOLVE_BVN_URL = 'https://api.paystack.co/bank/resolve_bvn';
const PAYSTACK_MATCH_BVN_URL = 'https://api.paystack.co/bvn/match';
const PAYSTACK_RESOLVE_ACCOUNT_URL = 'https://api.paystack.co/bank/resolve';
const PAYSTACK_RESOLVE_CARD_URL = 'https://api.paystack.co/decision/bin';

const PAYSTACK_KEY = 'sk_test_8d5f40d2aaca8d452582e98771f19cbd508d6483';