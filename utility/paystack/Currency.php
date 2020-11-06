<?php


namespace utility\paystack;


class Currency
{
    /**
     * @var Currency
     */
    private static $instance;

    /**
     * @var int
     */
    private $amount;

    /**
     * @var string
     */
    private $from;

    /**
     * @var string
     */
    private $to;

    protected function __construct()
    {
    }

    private function __wakeup()
    {
        // TODO: Implement __wakeup() method.
    }

    private function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public static function getInstance() {
        if (!isset(self::$instance))
            self::$instance = new self;
        return self::$instance;
    }

    public function convert(int $amount, string $from, $to) {

    }

}