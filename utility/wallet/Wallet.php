<?php


namespace utility\wallet;


use Exception;
use que\database\interfaces\model\Model;

class Wallet
{
    use \utility\Wallet {
        __construct as startWallet;
    }

    public function __construct(?int $userID = null, int $walletID = null)
    {
        $this->startWallet();
        $this->loadWallet($userID, $walletID);
    }

    /**
     * @return Model|null
     * @throws Exception
     */
    public function getWallet(): ?Model
    {
        if (!$this->wallet) throw new Exception("No wallet found");
        return $this->wallet;
    }
}