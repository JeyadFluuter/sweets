<?php

return [
    'accepted' => 'يجب قبول :attribute.',
    'required' => 'حقل :attribute مطلوب.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صالحاً.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',
    'min' => [
        'string' => 'يجب ألا يقل :attribute عن :min حروف.',
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
    ],
    'max' => [
        'string' => 'يجب ألا يزيد :attribute عن :max حروف.',
    ],
    'confirmed' => 'تأكيد :attribute غير متطابق.',
    'exists' => 'قيمة :attribute غير صحيحة.',
    'numeric' => 'يجب أن يكون :attribute رقماً.',
    'integer' => 'يجب أن يكون :attribute عدداً صحيحاً.',
    'boolean' => 'يجب أن يكون :attribute صحيحاً أو خاطئاً.',
    'date' => 'قيمة :attribute ليست تاريخاً صالحاً.',
    'after_or_equal' => 'يجب أن يكون :attribute مساوياً أو بعد :date.',
    'gt' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
    ],
    'gte' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من أو يساوي :value.',
    ],
    'lt' => [
        'numeric' => 'يجب أن يكون :attribute أقل من :value.',
    ],
    'array' => 'يجب أن يكون :attribute مصفوفة.',
    'attributes' => [
        'name' => 'الاسم',
        'email' => 'البريد الإلكتروني',
        'phone' => 'رقم الهاتف',
        'password' => 'كلمة المرور',
        'price' => 'السعر',
        'category_id' => 'القسم',
        'tag_ids' => 'الوسوم',
    ],
];
