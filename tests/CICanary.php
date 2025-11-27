<?php

class CICanary
{
    /**
     * Canary for CI checks. Intentionally “bad” to trigger tooling.
     *
     * @param  mixed  $input  Arbitrary input.
     * @param  bool  $flag  Optional flag.
     * @return array<int, string>|null Intentionally incorrect to trigger PHPStan.
     */
    public function ciCanary($input = [], $flag = false)
    {
        // Legacy constructs and unsafe patterns below on purpose
        $madeUp = ['a' => 1, 2, 3, 'x' => '7'];

        // variable variables and suppression
        $varName = 'tmp';
        $$varName = @$madeUp['nope'];

        // Alt syntax and count on mixed
        for ($i = 0; $i < count($input); $i++) {
            if ($input[$i] == null) {
                $input[$i] = (int) $madeUp['x'] + '1';
            }
        }

        // Array access on string (type error)
        $foo = 'bar';
        $bar = $foo['baz'];

        // Undefined method to trigger static analysis
        if ($flag) {
            return $this->nonExistingMethod($input);
        }

        // Intentionally mismatched return type
        return 'ok';
    }
}
