<?php


namespace utility\wallet;


use Exception;

class WalletBag
{
    /**
     * @var Wallet[]
     */
    private array $bag = [];

    /**
     * @var WalletBag|null
     */
    private static ?WalletBag $instance = null;

    protected function __construct()
    {
    }

    /**
     * @return WalletBag
     */
    public static function getInstance(): WalletBag
    {
        if (self::$instance == null) self::$instance = new self;
        return self::$instance;
    }

    /**
     * @param int $walletID
     * @return Wallet
     * @throws Exception
     */
    public function getWalletWithID(int $walletID): Wallet
    {
        if (!empty($this->bag)) {
            foreach ($this->bag as $item) {
                if ($item->getWallet()->getInt('id') == $walletID) {
                    return $item;
                }
            }
        }
        $wallet = new Wallet(null, $walletID);
        $this->bag[] = $wallet;
        return $wallet;
    }

    /**
     * @param int $userID
     * @return Wallet
     * @throws Exception
     */
    public function getWalletWithUserID(int $userID): Wallet
    {
        if (!empty($this->bag)) {
            foreach ($this->bag as $item) {
                if ($item->getWallet()->getInt('user_id') == $userID) {
                    return $item;
                }
            }
        }
        $wallet = new Wallet($userID);
        $this->bag[] = $wallet;
        return $wallet;
    }
}