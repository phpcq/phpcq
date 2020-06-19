<?php

declare(strict_types=1);

namespace Phpcq\Test\Config\Validation;

trait ConstraintProviderTrait
{
    public function boolConstraintProvider(): array
    {
        return [
            'accepts false' => [
                'value' => false,
                'error' => false
            ],
            'accepts true' => [
                'value' => true,
                'error' => false,
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept array' => [
                'value' => ['foo' => 'bar'],
                'error' => true,
            ],
            'does not accept list' => [
                'value' => ['foo', 'bar'],
                'error' => true,
            ],
            'does not accept string' => [
                'value' => 'foo',
                'error' => true,
            ],
            'does not accept float' => [
                'value' => 12.3,
                'error' => true,
            ],
            'does not accept int' => [
                'value' => 123,
                'error' => true,
            ]
        ];
    }

    public function floatConstraintProvider(): array
    {
        return [
            'accepts floats' => [
                'value' => 3.4,
                'error' => false
            ],
            'does not accept boolean' => [
                'value' => true,
                'error' => true,
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept array' => [
                'value' => ['foo' => 'bar'],
                'error' => true,
            ],
            'does not accept list' => [
                'value' => ['foo', 'bar'],
                'error' => true,
            ],
            'does not accept string' => [
                'value' => 'foo',
                'error' => true,
            ],
            'does not accept int' => [
                'value' => 123,
                'error' => true,
            ]
        ];
    }

    public function intConstraintProvider(): array
    {
        return [
            'accepts integers' => [
                'value' => 3,
                'error' => false
            ],
            'does not accept boolean' => [
                'value' => true,
                'error' => true,
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept array' => [
                'value' => ['foo' => 'bar'],
                'error' => true,
            ],
            'does not accept list' => [
                'value' => ['foo', 'bar'],
                'error' => true,
            ],
            'does not accept string' => [
                'value' => 'foo',
                'error' => true,
            ],
            'does not accept float' => [
                'value' => 12.3,
                'error' => true,
            ],
        ];
    }

    public function arrayConstraintProvider(): array
    {
        return [
            'does accept object like arrays' => [
                'value' => ['foo' => 'bar'],
                'error' => false,
            ],
            'does accept list arrays' => [
                'value' => ['foo', 'bar'],
                'error' => false,
            ],
            'does not accept floats' => [
                'value' => 3.3,
                'error' => true
            ],
            'does not accept boolean' => [
                'value' => true,
                'error' => true,
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept string' => [
                'value' => 'foo',
                'error' => true,
            ],
            'does not accept float' => [
                'value' => 12.3,
                'error' => true,
            ],
        ];
    }

    public function stringConstraintProvider(): array
    {
        return [
            'does accept string' => [
                'value' => 'foo bar',
                'error' => false,
            ],
            'does not accept boolean' => [
                'value' => true,
                'error' => true
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept array' => [
                'value' => ['foo' => 'bar'],
                'error' => true,
            ],
            'does not accept list' => [
                'value' => ['foo', 'bar'],
                'error' => true,
            ],
            'does not accept float' => [
                'value' => 12.3,
                'error' => true,
            ],
            'does not accept int' => [
                'value' => 123,
                'error' => true,
            ]
        ];
    }

    public function listConstraintProvider(): array
    {
        return [
            'accept list arrays' => [
                'value' => ['foo', 'bar'],
                'error' => false,
            ],
            'calls item validator' => [
                'value' => ['foo', 'bar'],
                'error' => false,
                'validator' => 2,
            ],
            'does not accept mixed arrays' => [
                'value' => ['foo', 'bar' => 'baz', 'example'],
                'error' => true,
            ],
            'does not accept string' => [
                'value' => 'foo bar',
                'error' => true,
            ],
            'does not accept boolean' => [
                'value' => true,
                'error' => true
            ],
            'does not accept null' => [
                'value' => null,
                'error' => true,
            ],
            'does not accept array' => [
                'value' => ['foo' => 'bar'],
                'error' => true,
            ],
            'does not accept float' => [
                'value' => 12.3,
                'error' => true,
            ],
            'does not accept int' => [
                'value' => 123,
                'error' => true,
            ]
        ];
    }

    public function enumConstraintProvider(): array
    {
        return [
            'accepts contained in allowed values' => [
                'value'   => 'foo',
                'allowed' => ['foo', 'bar'],
                'error'   => false,
            ],
            'accepts not values no in allowed values' => [
                'value'   => 'baz',
                'allowed' => ['foo', 'bar'],
                'error'   => true,
            ],
            'compares strict' => [
                'value'   => 0,
                'allowed' => ['0'],
                'error'   => true,
            ]
        ];
    }
}