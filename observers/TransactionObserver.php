<?php


namespace observers;


use model\Notification;
use model\Transaction;
use Exception;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\database\observer\ObserverSignal;
use que\support\Str;
use que\utility\money\Item;
use utility\Wallet;
use utility\wallet\WalletBag;

class TransactionObserver extends Observer
{
    use Wallet {
        __construct as walletConstruct;
    }

    public function __construct(ObserverSignal $signal)
    {
        parent::__construct($signal);
        $this->walletConstruct();
    }

    /**
     * @inheritDoc
     */
    public function onCreating(Model $model)
    {
        // TODO: Implement onCreating() method.
        if (!$model instanceof Transaction) $model = Transaction::cast($model);

        if ($model->getInt('type') == Transaction::TRANS_TYPE_TRANSFER) {
            $model->load('to_wallet')->to_wallet->load('user');
            if ($model->to_wallet) $model->set('narration', "Transfer to {$model->to_wallet->user->firstname} {$model->to_wallet->user->lastname}");
        }
    }

    /**
     * @inheritDoc
     */
    public function onCreated(Model $model)
    {
        // TODO: Implement onCreated() method.

        db()->delete()->table('trans_ref_logs')->where(
            'reference', $model->getValue('gateway_reference'))->exec();

        $walletBag = WalletBag::getInstance();

        try {
            $wallet = $walletBag->getWalletWithUserID($model->getInt('user_id'));
        } catch (Exception $e) {
            $this->getSignal()->undoOperation($e->getMessage());
            return;
        }

        if (!$model instanceof Transaction) $model = Transaction::cast($model);

        if ($model->getInt('status') == Transaction::TRANS_STATUS_SUCCESSFUL) {

            switch ($model->getInt('type')) {
                case Transaction::TRANS_TYPE_TOP_UP:
                    try {

                        if ($wallet->creditWallet($amount = ($model->getFloat('amount') - $model->getFloat('fee'))) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Top-up Transaction",
                            "Your wallet has been credited {$amount} NGN",
                            "transactionReceipt", $model->user_id, $model);

                    } catch (Exception $e) {
                        $this->getSignal()->discontinueOperation($e->getMessage());
                    }
                    break;
                case Transaction::TRANS_TYPE_CHARGE:
                    try {

                        if ($wallet->debitWallet($amount = ($model->getFloat('amount') + $model->getFloat('fee'))) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Charge Transaction",
                            "Your wallet has been charged {$amount} NGN",
                            "transactionReceipt", $model->user_id, $model);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                    break;
                case Transaction::TRANS_TYPE_WITHDRAWAL:
                    try {

                        if ($wallet->debitWallet(($model->getFloat('amount') + $model->getFloat('fee'))) === false) {
                            throw new Exception("Unable to withdraw from wallet at this time.");
                        }

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                    break;
                case Transaction::TRANS_TYPE_TRANSFER:
                    db()->transStart();
                    try {

                        if ($wallet->debitWallet($amount = ($model->getFloat('amount') + $model->getFloat('fee'))) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Transfer Transaction",
                            "Your wallet was debited {$amount} NGN",
                            "transactionReceipt", $model->user_id, $model);

                        if (!$model instanceof Transaction) $model = Transaction::cast($model);

                        $wallet = $walletBag->getWalletWithID($model->getInt('to_wallet_id'));

                        $model->load('from_wallet')->from_wallet->load('user');

                        $transfer = db()->insert('transactions', [
                            'uuid' => Str::uuidv4(),
                            'user_id' => $wallet->getWallet()->getInt('user_id'),
                            'type' => Transaction::TRANS_TYPE_TOP_UP,
                            'from_wallet_id' => $model->from_wallet_id,
                            'direction' => "w2w",
                            'gateway_reference' => $model->getValue('uuid'),
                            'amount' => $model->getFloat('amount'),
                            'fee' => $model->getFloat('creditor_fee'),
                            'status' => Transaction::TRANS_STATUS_SUCCESSFUL,
                            'narration' => "Money transfer from {$model->from_wallet->user->firstname} {$model->from_wallet->user->lastname}"
                        ]);

                        if (!$transfer->isSuccessful()) throw new Exception($transfer->getQueryError());

                        db()->transComplete();

                    } catch (Exception $e) {
                        db()->transRollBack();
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                    break;
                default:
                    break;
            }

        } elseif ($model->getInt('status') == Transaction::TRANS_STATUS_PROCESSING) {

            if ($model->getInt('type') == Transaction::TRANS_TYPE_TRANSFER ||
                $model->getInt('type') == Transaction::TRANS_TYPE_CHARGE) {

                try {

                    if ($wallet->lockFund($amount = ($model->getFloat('amount') + $model->getFloat('fee')), false) === false) {
                        throw new Exception("Unable to lock wallet fund at this time.");
                    }

                    $amount = Item::cents($amount)->getFactor(true);

                    Notification::create("Charge Transaction",
                        "Your wallet has been charged {$amount} NGN",
                        "transactionReceipt", $model->user_id, $model);

                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                }

            }
        }
    }

    /**
     * @inheritDoc
     */
    public function onCreateFailed(Model $model, array $errors, $errorCode)
    {
        // TODO: Implement onCreateFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onCreateRetryStarted(Model $model)
    {
        // TODO: Implement onCreateRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onCreateRetryComplete(Model $model, bool $status, int $attempts)
    {
        // TODO: Implement onCreateRetryComplete() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdating(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdating() method.
        $newModels->map(function (Model $newModel) use ($oldModels) {

            if (!$newModel instanceof Transaction) $newModel = Transaction::cast($newModel);
            if ($newModel->getInt('type') == Transaction::TRANS_TYPE_TRANSFER) {
                $newModel->load('to_wallet')->to_wallet->load('user');
                if ($newModel->to_wallet) $newModel->set('narration', "Transfer to {$newModel->to_wallet->user->firstname} {$newModel->to_wallet->user->lastname}");
            }

        });
    }

    /**
     * @inheritDoc
     */
    public function onUpdated(ModelCollection $newModels, ModelCollection $oldModels)
    {
        // TODO: Implement onUpdated() method.
        $newModels->map(function (Model $newModel) use ($oldModels) {

            $walletBag = WalletBag::getInstance();

            $oldModel = $oldModels->find(function (Model $m) use ($newModel) {
                return $newModel->validate('id')->isEqual($m->getValue('id'));
            });

            if (!$newModel instanceof Transaction) $newModel = Transaction::cast($newModel);
            if (!$oldModel instanceof Transaction) $oldModel = Transaction::cast($oldModel);

            if ($oldModel->getInt('status') != Transaction::TRANS_STATUS_SUCCESSFUL &&
                $newModel->getInt('status') == Transaction::TRANS_STATUS_REVERSED) {

                //Reverse Transaction

                try {
                    $wallet = $walletBag->getWalletWithUserID($oldModel->getInt('user_id'));
                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                    return;
                }

                $i = $oldModel->getInt('type');
                if ($i == Transaction::TRANS_TYPE_TRANSFER || $i == Transaction::TRANS_TYPE_CHARGE) {
                    try {

                        if ($wallet->unlockFund($amount = ($oldModel->getFloat('amount') + $oldModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Transaction Reversal",
                            "The charge of {$amount} NGN on your wallet has been reversed",
                            "transactionReceipt", $newModel->user_id, $newModel);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                } elseif ($i == Transaction::TRANS_TYPE_TOP_UP) {
                    try {

                        if ($wallet->debitWallet($amount = ($oldModel->getFloat('amount') + $oldModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Top-up Reversal",
                            "The credit of {$amount} NGN on your wallet has been reversed",
                            "transactionReceipt", $newModel->user_id, $newModel);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                }

            } elseif ($oldModel->getInt('status') == Transaction::TRANS_STATUS_REVERSED &&
                $newModel->getInt('status') != Transaction::TRANS_STATUS_REVERSED) {

                //Undo Transaction Reversal

                try {
                    $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                    return;
                }

                if ($newModel->getInt('type') == Transaction::TRANS_TYPE_TRANSFER ||
                    $newModel->getInt('type') == Transaction::TRANS_TYPE_CHARGE) {

                    switch ($newModel->getInt('status')) {
                        case Transaction::TRANS_STATUS_PROCESSING:
                            try {

                                if ($wallet->lockFund($amount = ($oldModel->getFloat('amount') + $oldModel->getFloat('fee'))) === false) {
                                    throw new Exception("Unable to lock wallet fund at this time.");
                                }

                                $amount = Item::cents($amount)->getFactor(true);

                                Notification::create("Charge Transaction",
                                    "Your wallet was charged {$amount} NGN",
                                    "transactionReceipt", $newModel->user_id, $newModel);

                            } catch (Exception $e) {
                                $this->getSignal()->undoOperation($e->getMessage());
                            }
                            break;
                        case Transaction::TRANS_STATUS_SUCCESSFUL:

                            if ($newModel->getInt('type') == Transaction::TRANS_TYPE_CHARGE) {
                                try {

                                    if ($wallet->debitWallet($amount = ($oldModel->getFloat('amount') + $oldModel->getFloat('fee'))) === false) {
                                        throw new Exception("Unable to debit wallet at this time.");
                                    }

                                    $amount = Item::cents($amount)->getFactor(true);

                                    Notification::create("Charge Transaction",
                                        "Your wallet was debited {$amount} NGN",
                                        "transactionReceipt", $newModel->user_id, $newModel);

                                } catch (Exception $e) {
                                    $this->getSignal()->undoOperation($e->getMessage());
                                }

                            } else {
                                $this->onCreated($newModel);
                            }
                            break;
                        default:
                            break;
                    }

                } elseif ($newModel->getInt('type') == Transaction::TRANS_TYPE_TOP_UP) {
                    try {

                        if ($wallet->creditWallet($amount = ($oldModel->getFloat('amount') - $oldModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Top-up Transaction",
                            "Your wallet was credited {$amount} NGN",
                            "transactionReceipt", $newModel->user_id, $newModel);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                }

            } elseif ($oldModel->getInt('status') == Transaction::TRANS_STATUS_PROCESSING &&
                $newModel->getInt('status') == Transaction::TRANS_STATUS_SUCCESSFUL) {


                try {
                    $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                    return;
                }

                if ($newModel->getInt('type') == Transaction::TRANS_TYPE_TRANSFER ||
                    $newModel->getInt('type') == Transaction::TRANS_TYPE_CHARGE) {

                    db()->transStart();
                    try {

                        if ($wallet->debitLockedFund($amount = ($newModel->getFloat('amount') + $newModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Transfer Transaction",
                            "Your wallet was debited {$amount} NGN",
                            "transactionReceipt", $newModel->user_id, $newModel);

                        if ($newModel->getInt('type') == Transaction::TRANS_TYPE_TRANSFER) {

                            $wallet = $walletBag->getWalletWithID($newModel->getInt('to_wallet_id'));

                            $newModel->load('from_wallet')->from_wallet->load('user');

                            $transfer = db()->insert('transactions', [
                                'uuid' => Str::uuidv4(),
                                'user_id' => $wallet->getWallet()->getInt('user_id'),
                                'type' => Transaction::TRANS_TYPE_TOP_UP,
                                'from_wallet_id' => $newModel->from_wallet_id,
                                'direction' => "w2w",
                                'gateway_reference' => $newModel->getValue('uuid'),
                                'amount' => $newModel->getFloat('amount'),
                                'fee' => $newModel->getFloat('creditor_fee'),
                                'status' => Transaction::TRANS_STATUS_SUCCESSFUL,
                                'narration' => "Money transfer from {$newModel->from_wallet->user->firstname} {$newModel->from_wallet->user->lastname}"
                            ]);

                            if (!$transfer->isSuccessful()) throw new Exception($transfer->getQueryError());
                        }

                        db()->transComplete();

                    } catch (Exception $e) {
                        db()->transRollBack();
                        $this->getSignal()->undoOperation($e->getMessage());
                    }

                } elseif ($newModel->getInt('type') == Transaction::TRANS_TYPE_TOP_UP) {
                    try {

                        if ($wallet->creditWallet($amount = ($newModel->getFloat('amount') - $newModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Top-up Transaction",
                            "Your wallet was credited {$amount} NGN",
                            "transactionReceipt", $newModel->user_id, $newModel);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                }

            } elseif ($oldModel->getInt('status') == Transaction::TRANS_STATUS_PENDING &&
                $newModel->getInt('status') == Transaction::TRANS_STATUS_PROCESSING) {

                if ($newModel->getInt('type') == Transaction::TRANS_TYPE_TRANSFER ||
                    $newModel->getInt('type') == Transaction::TRANS_TYPE_CHARGE) {

                    try {
                        $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                        return;
                    }

                    try {

                        if ($wallet->lockFund($amount = ($newModel->getFloat('amount') + $newModel->getFloat('fee'))) === false) {
                            throw new Exception("Unable to lock wallet fund at this time.");
                        }

                        $amount = Item::cents($amount)->getFactor(true);

                        Notification::create("Charge Transaction",
                            "Your wallet was charged {$amount} NGN",
                            "transactionReceipt", $newModel->user_id, $newModel);

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }

                }
            } elseif ($oldModel->getInt('status') != Transaction::TRANS_STATUS_SUCCESSFUL &&
                $newModel->getInt('status') == Transaction::TRANS_STATUS_SUCCESSFUL) {

                $this->onCreated($newModel);

            }
        });
    }

    /**
     * @inheritDoc
     */
    public function onUpdateFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onUpdateFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdateRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onUpdateRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onUpdateRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onUpdateRetryComplete() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleting(ModelCollection $models)
    {
        // TODO: Implement onDeleting() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleted(ModelCollection $models)
    {
        // TODO: Implement onDeleted() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteFailed(ModelCollection $models, array $errors, $errorCode)
    {
        // TODO: Implement onDeleteFailed() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteRetryStarted(ModelCollection $models)
    {
        // TODO: Implement onDeleteRetryStarted() method.
    }

    /**
     * @inheritDoc
     */
    public function onDeleteRetryComplete(ModelCollection $models, bool $status, int $attempts)
    {
        // TODO: Implement onDeleteRetryComplete() method.
    }
}
