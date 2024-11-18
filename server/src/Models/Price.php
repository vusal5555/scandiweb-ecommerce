<?php
namespace Models;

use Config\Database;

class Price
{
    protected $amount;
    protected $currencyLabel;
    protected $currencySymbol;

    public function __construct($amount, $currencyLabel, $currencySymbol)
    {
        $this->amount = $amount;
        $this->currencyLabel = $currencyLabel;
        $this->currencySymbol = $currencySymbol;
    }

    public static function getByProductId($productId)
    {
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT amount, currency_label, currency_symbol FROM prices WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $productId]);

        $prices = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $prices[] = [
                'amount' => $row['amount'],
                'currency' => [
                    'label' => $row['currency_label'],
                    'symbol' => $row['currency_symbol'],
                ],
            ];
        }
        return $prices;
    }

    public function getDetails()
    {
        return [
            'amount' => $this->amount,
            'currencyLabel' => $this->currencyLabel,
            'currencySymbol' => $this->currencySymbol,
        ];
    }
}
