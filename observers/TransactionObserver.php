<?php


namespace observers;


use Exception;
use que\database\interfaces\model\Model;
use que\database\model\ModelCollection;
use que\database\observer\Observer;
use que\database\observer\ObserverSignal;
use que\support\Str;
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

        if ($model->getInt('status') == APPROVAL_SUCCESSFUL) {

            switch ($model->getInt('type')) {
                case TRANSACTION_TOP_UP:
                    try {

                        if ($wallet->creditWallet($model->getFloat('amount')) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }

                    } catch (Exception $e) {
                        $this->getSignal()->discontinueOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_CHARGE:
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_WITHDRAWAL:
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new Exception("Unable to withdraw from wallet at this time.");
                        }

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_TRANSFER:
                    db()->transStart();
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        $wallet = $walletBag->getWalletWithID($model->getInt('wallet_id'));

                        $transfer = db()->insert('transactions', [
                            'uuid' => Str::uuidv4(),
                            'user_id' => $wallet->getWallet()->getInt('user_id'),
                            'type' => TRANSACTION_TOP_UP,
                            'direction' => "w2w",
                            'gateway_reference' => $model->getValue('uuid'),
                            'amount' => $model->getFloat('amount'),
                            'status' => APPROVAL_SUCCESSFUL,
                            'comment' => "Money transfer top-up transaction"
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

        } elseif ($model->getInt('status') == APPROVAL_PROCESSING) {

            if ($model->getInt('type') == TRANSACTION_TRANSFER ||
                $model->getInt('type') == TRANSACTION_CHARGE) {

                try {

                    if ($wallet->lockFund($model->getFloat('amount')) === false) {
                        throw new Exception("Unable to lock wallet fund at this time.");
                    }

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

            if ($oldModel->getInt('status') != APPROVAL_SUCCESSFUL &&
                $newModel->getInt('status') == APPROVAL_REVERSED) {

                //Reverse Transaction

                try {
                    $wallet = $walletBag->getWalletWithUserID($oldModel->getInt('user_id'));
                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                    return;
                }

                $i = $oldModel->getInt('type');
                if ($i == TRANSACTION_TRANSFER || $i == TRANSACTION_CHARGE) {
                    try {
                        if ($wallet->unlockFund($oldModel->getFloat('amount')) === false) {
                            throw new Exception("Unable to credit wallet at this time.");
                        }
                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }
                }

            } elseif ($oldModel->getInt('status') == APPROVAL_REVERSED &&
                $newModel->getInt('status') != APPROVAL_REVERSED) {

                //Undo Transaction Reversal

                try {
                    $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                } catch (Exception $e) {
                    $this->getSignal()->undoOperation($e->getMessage());
                    return;
                }

                if ($newModel->getInt('type') == TRANSACTION_TRANSFER ||
                    $newModel->getInt('type') == TRANSACTION_CHARGE) {

                    switch ($newModel->getInt('status')) {
                        case APPROVAL_PROCESSING:
                            try {

                                if ($wallet->lockFund($oldModel->getFloat('amount')) === false) {
                                    throw new Exception("Unable to lock wallet fund at this time.");
                                }

                            } catch (Exception $e) {
                                $this->getSignal()->undoOperation($e->getMessage());
                            }
                            break;
                        case APPROVAL_SUCCESSFUL:

                            if ($newModel->getInt('type') == TRANSACTION_CHARGE) {
                                try {

                                    if ($wallet->debitWallet($oldModel->getFloat('amount')) === false) {
                                        throw new Exception("Unable to debit wallet at this time.");
                                    }

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

                }

            } elseif ($oldModel->getInt('status') == APPROVAL_PROCESSING &&
                $newModel->getInt('status') == APPROVAL_SUCCESSFUL) {

                if ($newModel->getInt('type') == TRANSACTION_TRANSFER ||
                    $newModel->getInt('type') == TRANSACTION_CHARGE) {


                    try {
                        $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                        return;
                    }

                    db()->transStart();
                    try {

                        if ($wallet->debitLockedFund($oldModel->getFloat('amount')) === false) {
                            throw new Exception("Unable to debit wallet at this time.");
                        }

                        if ($newModel->getInt('type') == TRANSACTION_TRANSFER) {

                            $wallet = $walletBag->getWalletWithID($oldModel->getInt('wallet_id'));

                            $transfer = db()->insert('transactions', [
                                'uuid' => Str::uuidv4(),
                                'user_id' => $wallet->getWallet()->getInt('user_id'),
                                'type' => TRANSACTION_TOP_UP,
                                'direction' => "w2w",
                                'gateway_reference' => $oldModel->getValue('uuid'),
                                'amount' => $oldModel->getFloat('amount'),
                                'status' => APPROVAL_SUCCESSFUL,
                                'comment' => "Money transfer top-up transaction"
                            ]);

                            if (!$transfer->isSuccessful()) throw new Exception($transfer->getQueryError());
                        }

                        db()->transComplete();

                    } catch (Exception $e) {
                        db()->transRollBack();
                        $this->getSignal()->undoOperation($e->getMessage());
                    }

                }
            } elseif ($oldModel->getInt('status') == APPROVAL_PENDING &&
                $newModel->getInt('status') == APPROVAL_PROCESSING) {

                if ($newModel->getInt('type') == TRANSACTION_TRANSFER ||
                    $newModel->getInt('type') == TRANSACTION_CHARGE) {


                    try {
                        $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                        return;
                    }

                    try {

                        if ($wallet->lockFund($newModel->getFloat('amount')) === false) {
                            throw new Exception("Unable to lock wallet fund at this time.");
                        }

                    } catch (Exception $e) {
                        $this->getSignal()->undoOperation($e->getMessage());
                    }

                }
            } elseif ($oldModel->getInt('status') != APPROVAL_SUCCESSFUL &&
                $newModel->getInt('status') == APPROVAL_SUCCESSFUL) {

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
