<?php


namespace observers;


use que\database\interfaces\model\Model;
use que\database\interfaces\observer\Observer;
use que\database\model\ModelCollection;
use que\database\observer\ObserverSignal;
use que\support\Str;
use utility\Wallet;
use utility\wallet\WalletBag;

class TransactionObserver implements Observer
{
    use Wallet;
    private ObserverSignal $signal;

    /**
     * @inheritDoc
     */
    public function __construct(ObserverSignal $signal)
    {
        $this->signal = $signal;
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
        } catch (\Exception $e) {
            $this->signal->undoOperation($e->getMessage());
            return;
        }

        if ($model->getInt('status') == APPROVAL_SUCCESSFUL) {

            switch ($model->getInt('transaction_state')) {
                case TRANSACTION_TOP_UP:
                    try {

                        if ($wallet->creditWallet($model->getFloat('amount')) === false) {
                            throw new \Exception("Unable to credit wallet at this time.");
                        }

                    } catch (\Exception $e) {
                        $this->signal->discontinueOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_CHARGE:
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new \Exception("Unable to debit wallet at this time.");
                        }

                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_WITHDRAWAL:
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new \Exception("Unable to withdraw from wallet at this time.");
                        }

                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
                    }
                    break;
                case TRANSACTION_TRANSFER:
                    db()->transStart();
                    try {

                        if ($wallet->debitWallet($model->getFloat('amount')) === false) {
                            throw new \Exception("Unable to debit wallet at this time.");
                        }

                        $wallet = $walletBag->getWalletWithID($model->getInt('wallet_id'));

                        $transfer = db()->insert('transactions', [
                            'uuid' => Str::uuidv4(),
                            'user_id' => $wallet->getWallet()->getInt('user_id'),
                            'transaction_state' => TRANSACTION_TOP_UP,
                            'transaction_type' => TRANSACTION_CREDIT,
                            'gateway_reference' => $model->getValue('uuid'),
                            'amount' => $model->getFloat('amount'),
                            'status' => APPROVAL_SUCCESSFUL
                        ]);

                        if (!$transfer->isSuccessful()) throw new \Exception($transfer->getQueryError());

                        db()->transComplete();

                    } catch (\Exception $e) {
                        db()->transRollBack();
                        $this->signal->undoOperation($e->getMessage());
                    }
                    break;
                default:
                    break;
            }

        } elseif ($model->getInt('status') == APPROVAL_PROCESSING) {

            if ($model->getInt('transaction_state') == TRANSACTION_TRANSFER) {

                try {

                    if ($wallet->lockFund($model->getFloat('amount')) === false) {
                        throw new \Exception("Unable to lock wallet fund at this time.");
                    }

                } catch (\Exception $e) {
                    $this->signal->undoOperation($e->getMessage());
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

            if ($oldModel->getInt('status') == APPROVAL_SUCCESSFUL &&
                ($oldModel->getInt('transaction_state') != TRANSACTION_REVERSED &&
                    $newModel->getInt('transaction_state') == TRANSACTION_REVERSED)) {

                //Reverse Transaction

                try {
                    $wallet = $walletBag->getWalletWithUserID($oldModel->getInt('user_id'));
                } catch (\Exception $e) {
                    $this->signal->undoOperation($e->getMessage());
                    return;
                }

                switch ($oldModel->getInt('transaction_state')) {
                    case TRANSACTION_TOP_UP:
                        try {
                            if ($wallet->debitWallet($oldModel->getFloat('amount')) === false) {
                                throw new \Exception("Unable to debit wallet at this time.");
                            }
                        } catch (\Exception $e) {
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    case TRANSACTION_WITHDRAWAL:
                    case TRANSACTION_CHARGE:
                        try {
                            if ($wallet->creditWallet($oldModel->getFloat('amount')) === false) {
                                throw new \Exception("Unable to credit wallet at this time.");
                            }
                        } catch (\Exception $e) {
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    case TRANSACTION_TRANSFER:
                        db()->transStart();
                        try {

                            $reverse = db()->update()->table('transactions')
                                ->columns(['transaction_state' => TRANSACTION_REVERSED])
                                ->where('transaction_state', TRANSACTION_TOP_UP)
                                ->where('gateway_reference', $oldModel->getValue('uuid'))
                                ->exec();

                            if (!$reverse->isSuccessful()) throw new \Exception($reverse->getQueryError());

                            if ($wallet->creditWallet($oldModel->getFloat('amount')) === false) {
                                throw new \Exception("Unable to credit wallet at this time.");
                            }

                            db()->transComplete();

                        } catch (\Exception $e) {
                            db()->transRollBack();
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    default:
                        break;
                }

            } elseif ($oldModel->getInt('status') == APPROVAL_SUCCESSFUL &&
                ($oldModel->getInt('transaction_state') == TRANSACTION_REVERSED &&
                    $newModel->getInt('transaction_state') != TRANSACTION_REVERSED)) {

                //Undo Transaction Reversal

                try {
                    $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                } catch (\Exception $e) {
                    $this->signal->undoOperation($e->getMessage());
                    return;
                }

                switch ($newModel->getInt('transaction_state')) {
                    case TRANSACTION_TOP_UP:
                        try {
                            if ($wallet->creditWallet($oldModel->getFloat('amount')) === false)
                                throw new \Exception("Unable to credit wallet at this time.");
                        } catch (\Exception $e) {
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    case TRANSACTION_CHARGE:
                        try {
                            if ($wallet->debitWallet($oldModel->getFloat('amount')) === false)
                                throw new \Exception("Unable to debit wallet at this time.");
                        } catch (\Exception $e) {
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    case TRANSACTION_WITHDRAWAL:
                        try {
                            if ($wallet->debitWallet($oldModel->getFloat('amount')) === false)
                                throw new \Exception("Unable to withdraw from wallet at this time.");
                        } catch (\Exception $e) {
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    case TRANSACTION_TRANSFER:
                        db()->transStart();
                        try {

                            $reverse = db()->update()->table('transactions')
                                ->columns(['transaction_state' => TRANSACTION_TOP_UP])
                                ->where('transaction_state', TRANSACTION_REVERSED)
                                ->where('gateway_reference', $oldModel->getValue('uuid'))
                                ->exec();

                            if (!$reverse->isSuccessful()) throw new \Exception($reverse->getQueryError());

                            if ($wallet->debitWallet($oldModel->getFloat('amount')) === false) {
                                throw new \Exception("Unable to debit wallet at this time.");
                            }

                            db()->transComplete();

                        } catch (\Exception $e) {
                            db()->transRollBack();
                            $this->signal->undoOperation($e->getMessage());
                        }
                        break;
                    default:
                        break;
                }

            } elseif ($oldModel->getInt('status') == APPROVAL_PROCESSING &&
                $newModel->getInt('status') == APPROVAL_SUCCESSFUL) {

                if ($newModel->getInt('transaction_state') == TRANSACTION_TRANSFER) {


                    try {
                        $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
                        return;
                    }

                    try {

                        if ($wallet->debitLockedFund($newModel->getFloat('amount')) === false) {
                            throw new \Exception("Unable to debit wallet at this time.");
                        }

                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
                    }

                }
            } elseif ($oldModel->getInt('status') == APPROVAL_PENDING &&
                $newModel->getInt('status') == APPROVAL_PROCESSING) {

                if ($newModel->getInt('transaction_state') == TRANSACTION_TRANSFER) {


                    try {
                        $wallet = $walletBag->getWalletWithUserID($newModel->getInt('user_id'));
                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
                        return;
                    }

                    try {

                        if ($wallet->lockFund($newModel->getFloat('amount')) === false) {
                            throw new \Exception("Unable to lock wallet fund at this time.");
                        }

                    } catch (\Exception $e) {
                        $this->signal->undoOperation($e->getMessage());
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

    /**
     * @inheritDoc
     */
    public function getSignal(): ObserverSignal
    {
        // TODO: Implement getSignal() method.
        return $this->signal;
    }
}