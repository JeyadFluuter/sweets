<?php

namespace App\Support;

class LibyanPhone
{
    /**
     * يقبل الصيغ: 0912345678 | 912345678 | +218912345678 | 218912345678
     * الشبكات المعتمدة: 091 / 092 / 093 / 094 / 095
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '218')) {
            $digits = '0'.substr($digits, 3);
        }

        if (preg_match('/^9[1-5]\d{7}$/', $digits)) {
            $digits = '0'.$digits;
        }

        if (! preg_match('/^09[1-5]\d{7}$/', $digits)) {
            return null;
        }

        return $digits;
    }

    public static function isValid(?string $phone): bool
    {
        return self::normalize($phone) !== null;
    }

    public static function validationRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! self::isValid(is_string($value) ? $value : null)) {
                $fail('رقم الهاتف يجب أن يكون ليبياً صالحاً (مثال: 0912345678).');
            }
        };
    }
}
