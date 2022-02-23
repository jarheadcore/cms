<?php

namespace craft\base\conditions;

use craft\helpers\Cp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\base\InvalidConfigException;

/**
 * BaseTextConditionRule provides a base implementation for condition rules that are composed of an operator menu and text input.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
abstract class BaseTextConditionRule extends BaseConditionRule
{
    /**
     * @inheritdoc
     */
    public string $operator = self::OPERATOR_EQ;

    /**
     * @var string The input value.
     */
    public string $value = '';

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), [
            'value' => $this->value,
        ]);
    }

    /**
     * Returns the operators that should be allowed for this rule.
     *
     * @return array
     */
    protected function operators(): array
    {
        return [
            self::OPERATOR_EQ,
            self::OPERATOR_BEGINS_WITH,
            self::OPERATOR_ENDS_WITH,
            self::OPERATOR_CONTAINS,
        ];
    }

    /**
     * Returns the input type that should be used.
     *
     * @return string
     */
    protected function inputType(): string
    {
        return 'text';
    }

    /**
     * @inheritdoc
     */
    protected function inputHtml(): string
    {
        return
            Html::hiddenLabel($this->getLabel(), 'value') .
            Cp::textHtml([
                'type' => $this->inputType(),
                'id' => 'value',
                'name' => 'value',
                'value' => $this->value,
                'autocomplete' => false,
                'class' => 'fullwidth',
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['value'], 'safe'],
        ]);
    }

    /**
     * Returns the rule’s value, prepped for [[Db::parseParam()]] based on the selected operator.
     *
     * @return string|null
     */
    protected function paramValue(): ?string
    {
        if ($this->value === '') {
            return null;
        }

        $value = Db::escapeParam($this->value);

        return match ($this->operator) {
            self::OPERATOR_BEGINS_WITH => "$value*",
            self::OPERATOR_ENDS_WITH => "*$value",
            self::OPERATOR_CONTAINS => "*$value*",
            default => "$this->operator $value",
        };
    }

    /**
     * Returns whether the condition rule matches the given value.
     *
     * @param mixed $value
     * @return bool
     */
    protected function matchValue($value): bool
    {
        if ($this->value === '') {
            return true;
        }

        return match ($this->operator) {
            self::OPERATOR_EQ => $value == $this->value,
            self::OPERATOR_NE => $value != $this->value,
            self::OPERATOR_LT => $value < $this->value,
            self::OPERATOR_LTE => $value <= $this->value,
            self::OPERATOR_GT => $value > $this->value,
            self::OPERATOR_GTE => $value >= $this->value,
            self::OPERATOR_BEGINS_WITH => StringHelper::startsWith($value, $this->value),
            self::OPERATOR_ENDS_WITH => StringHelper::endsWith($value, $this->value),
            self::OPERATOR_CONTAINS => StringHelper::contains($value, $this->value),
            default => throw new InvalidConfigException("Invalid operator: $this->operator"),
        };
    }
}
