<?php
namespace Models;

class AttributeItem
{
    protected string $displayValue;
    protected string $value;
    protected string $id;

    public function __construct(string $displayValue, string $value, string $id)
    {
        $this->displayValue = $displayValue;
        $this->value = $value;
        $this->id = $id;
    }

    public function getDetails(): array
    {
        return [
            'id' => $this->id,
            'displayValue' => $this->displayValue,
            'value' => $this->value,
        ];
    }
}
