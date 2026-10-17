<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute meýdany kabul edilmeli.',
    'accepted_if' => ':other :value bolanda, :attribute meýdany kabul edilmeli.',
    'active_url' => ':attribute meýdany hakyky URL däl.',
    'after' => ':attribute meýdany :date senesinden soňky sene bolmaly.',
    'after_or_equal' => ':attribute meýdany :date senesinden ir bolmadyk sene bolmaly.',
    'alpha' => ':attribute meýdany diňe harplardan ybarat bolmaly.',
    'alpha_dash' => ':attribute meýdany diňe harplardan, sanlardan, defislerden we aşaky çyzyklardan ybarat bolmaly.',
    'alpha_num' => ':attribute meýdany diňe harplardan we sanlardan ybarat bolmaly.',
    'array' => ':attribute meýdany massiw bolmaly.',
    'ascii' => ':attribute meýdany diňe bir baýtly harp-san nyşanlaryndan we belgilerden ybarat bolmaly.',
    'before' => ':attribute meýdany :date senesinden öňki sene bolmaly.',
    'before_or_equal' => ':attribute meýdany :date senesinden giç bolmadyk sene bolmaly.',
    'between' => [
        'array' => ':attribute meýdanyndaky elementleriň sany :min bilen :max aralygynda bolmaly.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :min bilen :max kilobaýt aralygynda bolmaly.',
        'numeric' => ':attribute meýdany :min bilen :max aralygynda bolmaly.',
        'string' => ':attribute meýdanyndaky nyşanlaryň sany :min bilen :max aralygynda bolmaly.',
    ],
    'boolean' => ':attribute meýdany logiki baha bolmaly.',
    'can' => ':attribute meýdanynda rugsat berilmedik baha bar.',
    'confirmed' => ':attribute meýdany tassyklama bilen gabat gelmeýär.',
    'contains' => ':attribute meýdanynda hökmany baha ýok.',
    'current_password' => 'Parol nädogry.',
    'date' => ':attribute meýdany hakyky sene däl.',
    'date_equals' => ':attribute meýdany :date senesine deň bolmaly.',
    'date_format' => ':attribute meýdany :format formatyna laýyk gelmeýär.',
    'decimal' => ':attribute meýdanynyň droby bölegi :decimal sandan ybarat bolmaly.',
    'declined' => ':attribute meýdany ret edilmeli.',
    'declined_if' => ':other :value bolanda, :attribute meýdany ret edilmeli.',
    'different' => ':attribute we :other meýdanlary dürli bolmaly.',
    'digits' => ':attribute meýdany :digits sandan ybarat bolmaly.',
    'digits_between' => ':attribute meýdany :min bilen :max aralygyndaky sandan ybarat bolmaly.',
    'dimensions' => ':attribute meýdanyndaky suratyň ölçegleri ýaramsyz.',
    'distinct' => ':attribute meýdanynda gaýtalanýan baha bar.',
    'doesnt_end_with' => ':attribute meýdany şu bahalaryň biri bilen gutarmaly däl: :values.',
    'doesnt_start_with' => ':attribute meýdany şu bahalaryň biri bilen başlanmaly däl: :values.',
    'email' => ':attribute meýdany hakyky email salgysy bolmaly.',
    'ends_with' => ':attribute meýdany şu bahalaryň biri bilen gutarmaly: :values.',
    'enum' => ':attribute üçin saýlanan baha nädogry.',
    'exists' => ':attribute üçin saýlanan baha nädogry.',
    'extensions' => ':attribute meýdanynyň giňeltmesi şularyň biri bolmaly: :values.',
    'file' => ':attribute meýdany faýl bolmaly.',
    'filled' => ':attribute meýdany hökman doldurylmaly.',
    'gt' => [
        'array' => ':attribute meýdanynda :value elementden köp bolmaly.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :value kilobaýtdan uly bolmaly.',
        'numeric' => ':attribute meýdany :value bahadan uly bolmaly.',
        'string' => ':attribute meýdanynda :value nyşandan köp bolmaly.',
    ],
    'gte' => [
        'array' => ':attribute meýdanyndaky elementleriň sany :value ýa-da ondan köp bolmaly.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :value kilobaýt ýa-da ondan uly bolmaly.',
        'numeric' => ':attribute meýdany :value bahasyna deň ýa-da ondan uly bolmaly.',
        'string' => ':attribute meýdanyndaky nyşanlaryň sany :value ýa-da ondan köp bolmaly.',
    ],
    'hex_color' => ':attribute meýdany hakyky onaltylyk reňk bolmaly.',
    'image' => ':attribute meýdany surat bolmaly.',
    'in' => ':attribute üçin saýlanan baha nädogry.',
    'in_array' => ':attribute meýdany :other içinde ýok.',
    'integer' => ':attribute meýdany bitin san bolmaly.',
    'ip' => ':attribute meýdany hakyky IP salgysy bolmaly.',
    'ipv4' => ':attribute meýdany hakyky IPv4 salgysy bolmaly.',
    'ipv6' => ':attribute meýdany hakyky IPv6 salgysy bolmaly.',
    'json' => ':attribute meýdany JSON setiri bolmaly.',
    'list' => ':attribute meýdany sanaw bolmaly.',
    'lt' => [
        'array' => ':attribute meýdanynda :value elementden az bolmaly.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :value kilobaýtdan kiçi bolmaly.',
        'numeric' => ':attribute meýdany :value bahadan kiçi bolmaly.',
        'string' => ':attribute meýdanynda :value nyşandan az bolmaly.',
    ],
    'lte' => [
        'array' => ':attribute meýdanynda :value elementden köp bolmaly däl.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :value kilobaýtdan uly bolmaly däl.',
        'numeric' => ':attribute meýdany :value bahasyna deň ýa-da ondan kiçi bolmaly.',
        'string' => ':attribute meýdanynda :value nyşandan köp bolmaly däl.',
    ],
    'mac_address' => ':attribute meýdany hakyky MAC salgysy bolmaly.',
    'max' => [
        'array' => ':attribute meýdanynda :max elementden köp bolmaly däl.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :max kilobaýtdan uly bolmaly däl.',
        'numeric' => ':attribute meýdany :max bahadan uly bolmaly däl.',
        'string' => ':attribute meýdanynda :max nyşandan köp bolmaly däl.',
    ],
    'max_digits' => ':attribute meýdany :max sandan köp bolmaly däl.',
    'mimes' => ':attribute meýdany şu görnüşleriň biriniň faýly bolmaly: :values.',
    'mimetypes' => ':attribute meýdany şu görnüşleriň biriniň faýly bolmaly: :values.',
    'min' => [
        'array' => ':attribute meýdanynda :min elementden az bolmaly däl.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :min kilobaýtdan kiçi bolmaly däl.',
        'numeric' => ':attribute meýdany :min bahadan kiçi bolmaly däl.',
        'string' => ':attribute meýdanynda :min nyşandan az bolmaly däl.',
    ],
    'min_digits' => ':attribute meýdany :min sandan az bolmaly däl.',
    'missing' => ':attribute meýdany bolmaly däl.',
    'missing_if' => ':other :value bolanda, :attribute meýdany bolmaly däl.',
    'missing_unless' => ':other :value bolmasa, :attribute meýdany bolmaly däl.',
    'missing_with' => ':values bar bolanda, :attribute meýdany bolmaly däl.',
    'missing_with_all' => ':values bar bolanda, :attribute meýdany bolmaly däl.',
    'multiple_of' => ':attribute meýdany :value bahasyna kratny bolmaly.',
    'not_in' => ':attribute üçin saýlanan baha nädogry.',
    'not_regex' => ':attribute meýdanynyň formaty nädogry.',
    'numeric' => ':attribute meýdany san bolmaly.',
    'password' => [
        'letters' => ':attribute meýdanynda iň bolmanda bir harp bolmaly.',
        'mixed' => ':attribute meýdanynda iň bolmanda bir baş we bir setir harp bolmaly.',
        'numbers' => ':attribute meýdanynda iň bolmanda bir san bolmaly.',
        'symbols' => ':attribute meýdanynda iň bolmanda bir nyşan bolmaly.',
        'uncompromised' => 'Görkezilen :attribute maglumat syzmalarynda duş geldi. Başga baha saýlaň.',
    ],
    'present' => ':attribute meýdany bolmaly.',
    'present_if' => ':other :value bolanda, :attribute meýdany bolmaly.',
    'present_unless' => ':other :value bolmasa, :attribute meýdany bolmaly.',
    'present_with' => ':values bar bolanda, :attribute meýdany bolmaly.',
    'present_with_all' => ':values bar bolanda, :attribute meýdany bolmaly.',
    'prohibited' => ':attribute meýdany gadagan.',
    'prohibited_if' => ':other :value bolanda, :attribute meýdany gadagan.',
    'prohibited_unless' => ':other :value bolmasa, :attribute meýdany gadagan.',
    'prohibits' => ':attribute meýdany :other meýdanynyň bolmagyny gadagan edýär.',
    'regex' => ':attribute meýdanynyň formaty nädogry.',
    'required' => ':attribute meýdany hökman doldurylmaly.',
    'required_array_keys' => ':attribute meýdanynda şu açarlar üçin bahalar bolmaly: :values.',
    'required_if' => ':other :value bolanda, :attribute meýdany hökman doldurylmaly.',
    'required_if_accepted' => ':other kabul edilende, :attribute meýdany hökman doldurylmaly.',
    'required_if_declined' => ':other ret edilende, :attribute meýdany hökman doldurylmaly.',
    'required_unless' => ':other :value bolmasa, :attribute meýdany hökman doldurylmaly.',
    'required_with' => ':values bar bolanda, :attribute meýdany hökman doldurylmaly.',
    'required_with_all' => ':values bar bolanda, :attribute meýdany hökman doldurylmaly.',
    'required_without' => ':values ýok bolanda, :attribute meýdany hökman doldurylmaly.',
    'required_without_all' => ':values ýok bolanda, :attribute meýdany hökman doldurylmaly.',
    'same' => ':attribute we :other meýdanlary gabat gelmeli.',
    'size' => [
        'array' => ':attribute meýdanyndaky elementleriň sany :size bolmaly.',
        'file' => ':attribute meýdanyndaky faýlyň göwrümi :size kilobaýt bolmaly.',
        'numeric' => ':attribute meýdany :size bahasyna deň bolmaly.',
        'string' => ':attribute meýdanyndaky nyşanlaryň sany :size bolmaly.',
    ],
    'starts_with' => ':attribute meýdany şu bahalaryň biri bilen başlanmaly: :values.',
    'string' => ':attribute meýdany setir bolmaly.',
    'timezone' => ':attribute meýdany hakyky wagt guşagy bolmaly.',
    'unique' => 'Şeýle :attribute eýýäm bar.',
    'uploaded' => ':attribute meýdanyny ýüklemek başartmady.',
    'uppercase' => ':attribute meýdany baş harplar bilen ýazylmaly.',
    'url' => ':attribute meýdany hakyky URL salgysy bolmaly.',
    'ulid' => ':attribute meýdany hakyky ULID bolmaly.',
    'uuid' => ':attribute meýdany hakyky UUID bolmaly.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'name' => 'Ady',
        'phone' => 'Telefon',
        'address' => 'Salgy',
        'comment' => 'Teswir',
        'vendor_id' => 'Restoranyň ID-si',
        'items' => 'Harytlar',
        'total' => 'Jemi',
        'payment_method' => 'Töleg usuly',
    ],

];
